<?php
/**
 * Class ID3
 *
 * @created      22.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\ID3Tag;

use stdClass;

use function fclose, file_exists, floor, fopen, fread, fseek, ftell, in_array, is_file,
	is_readable, is_resource, ord, realpath, round, sprintf, strlen, strtolower, substr, unpack;

use const PHP_INT_MAX, SEEK_END, SEEK_SET;

/**
 * @link http://id3.org/id3guide
 * @link http://www.mp3-tech.org/
 * @link https://en.wikipedia.org/wiki/ID3
 * @link https://docs.microsoft.com/windows/desktop/wmformat/id3-tag-support
 * @link https://github.com/brokencube/id3
 * @link http://www.zedwood.com/article/php-calculate-duration-of-mp3
 * @link https://www.mp3tag.de/en/
 * @link https://github.com/taglib/taglib/tree/master/tests
 */
class ID3{

	protected const BITRATES = [
		0b0000 => [  0,   0,   0,   0,   0],
		0b0001 => [ 32,  32,  32,  32,   8],
		0b0010 => [ 64,  48,  40,  48,  16],
		0b0011 => [ 96,  56,  48,  56,  24],
		0b0100 => [128,  64,  56,  64,  32],
		0b0101 => [160,  80,  64,  80,  40],
		0b0110 => [192,  96,  80,  96,  48],
		0b0111 => [224, 112,  96, 112,  56],
		0b1000 => [256, 128, 112, 128,  64],
		0b1001 => [288, 160, 128, 144,  80],
		0b1010 => [320, 192, 160, 160,  96],
		0b1011 => [352, 224, 192, 176, 112],
		0b1100 => [384, 256, 224, 192, 128],
		0b1101 => [416, 320, 256, 224, 144],
		0b1110 => [448, 384, 320, 256, 160],
		0b1111 => [ -1,  -1,  -1,  -1,  -1],
	];

	protected const SAMPLE_RATES = [
		0b00 => [
			0b00 => 11025,
			0b01 => 12000,
			0b10 => 8000,
			0b11 => 0,
		],
		0b10 => [
			0b00 => 22050,
			0b01 => 24000,
			0b10 => 16000,
			0b11 => 0,
		],
		0b11 => [
			0b00 => 44100,
			0b01 => 48000,
			0b10 => 32000,
			0b11 => 0,
		],
	];

	/**
	 * "Xing"/"Info" identification string at 0x0D (13), 0x15 (21) or 0x24 (36)
	 */
	protected const VBR_ID_STRING_POS = [
		// v2.5
		0b00 => [
			0b00 => 21, // stereo
			0b01 => 21, // jntstereo
			0b10 => 21, // dual channel
			0b11 => 21, // mono
		],
		// v2
		0b10 => [
			0b00 => 21,
			0b01 => 21,
			0b10 => 21,
			0b11 => 13,
		],
		// v1
		0b11 => [
			0b00 => 36,
			0b01 => 36,
			0b10 => 36,
			0b11 => 21,
		],
	];

	/**
	 * @var resource
	 */
	protected $fh;

	/**
	 * @return void
	 */
	public function __destruct(){

		if(is_resource($this->fh)){
			fclose($this->fh); // @codeCoverageIgnore
		}

	}

	/**
	 * @throws \chillerlan\ID3Tag\ID3Exception
	 */
	public function read(string $filename):ID3Data{
		$file = realpath($filename);

		if(!$file || !file_exists($file) || !is_file($file) || !is_readable($file)){
			throw new ID3Exception(sprintf('invalid file: %s', $filename));
		}

		$data     = new ID3Data($file);
		$this->fh = fopen($file, 'rb');

		// invalid resource or 2GB limit on 32-bit
		if(!$this->fh || $data->filesize > PHP_INT_MAX){
			return $data; // @codeCoverageIgnore
		}

		// check for an ID3v1 tag
		fseek($this->fh, -128, SEEK_END);

		if(fread($this->fh, 3) === 'TAG'){
			fseek($this->fh, -256, SEEK_END);

			$data->id3v1     = (new ID3v1)->parse(fread($this->fh, 256));
			$data->v1tagsize = 256;
		}

		// check for an id3v2 tag
		fseek($this->fh, 0, SEEK_SET);

		if(fread($this->fh, 3) === 'ID3'){
			fseek($this->fh, 6, SEEK_SET);
			$tagsize = ID3Helpers::syncSafeInteger(unpack('N', fread($this->fh, 4))[1]);
			fseek($this->fh, 0, SEEK_SET);

			$data->id3v2     = $this->readID3v2Tag(fread($this->fh, $tagsize));
			$data->v2tagsize = $tagsize + 10;
		}

		$data->setProperties($this->getMP3Stats($data->filesize, $data->v1tagsize, $data->v2tagsize));

		fclose($this->fh);

		return $data;
	}

	/**
	 *
	 * @noinspection PhpUnusedLocalVariableInspection
	 */
	protected function readID3v2Tag(string $rawdata):?array{

		/**
		 * 3.1. ID3v2 header
		 *
		 * The ID3v2 tag header, which should be the first information in the
		 * file, is 10 bytes as follows:
		 *
		 * ID3v2/file identifier      "ID3"
		 * ID3v2 version              $03 00
		 * ID3v2 flags                %abc00000
		 * ID3v2 size             4 * %0xxxxxxx
		 *
		 * @link http://id3.org/id3v2.3.0#ID3v2_header
		 */
		$id3version = ord(substr($rawdata, 3, 1));

		if(!in_array($id3version, [2, 3, 4], true)){
#			throw new ID3Exception('invalid id3 version');
			return null;
		}

		$flags = ord(substr($rawdata, 5, 1));

		$compression  = false;
		$exthead      = false;
		$experimental = false;
		$hasfooter    = false;

		/**
		 * a - Unsynchronisation
		 *
		 * Bit 7 in the 'ID3v2 flags' indicates whether or not
		 * unsynchronisation is used (see section 5 for details); a set bit
		 * indicates usage.
		 */
		$unsync = (bool)($flags & 0b10000000);

		if($id3version === 2){

			/**
			 * b - Compression
			 *
			 * Bit 6 is indicating whether or not compression is used;
			 * a set bit indicates usage. Since no compression scheme has been
			 * decided yet, the ID3 decoder (for now) should just ignore the entire
			 * tag if the compression bit is set.
			 */
			$compression = (bool)($flags & 0b01000000);
		}

		if($id3version === 3 || $id3version === 4){

			/**
			 * b - Extended header
			 *
			 * Bit 6 indicates whether or not the header is followed by an
			 * extended header. The extended header is described in section 3.2.
			 */
			$exthead = (bool)($flags & 0b01000000);

			/**
			 * c - Experimental indicator
			 *
			 * Bit 5 should be used as an 'experimental indicator'.
			 * This flag should always be set when the tag is in an experimental stage.
			 */
			$experimental = (bool)($flags & 0b00100000);
		}

		if($id3version === 4){

			/**
			 * d - Footer present
			 *
			 * Bit 4 indicates that a footer (section 3.4) is present at the very
			 * end of the tag. A set bit indicates the presence of a footer.
			 */
			$hasfooter = (bool)($flags & 0b00010000);
		}

		$start = 10;

		/**
		 * 3.2. ID3v2.3 extended header
		 *
		 * @link http://id3.org/id3v2.3.0#ID3v2_extended_header
		 *
		 * Extended header size   $xx xx xx xx
		 * Extended Flags         $xx xx
		 * Size of padding        $xx xx xx xx
		 */
		if($exthead){
			$extHeaderSize = ID3Helpers::syncSafeInteger(unpack('N', substr($rawdata, $start, 4))[1]);
			$start         += 4;
			// @todo (skipping the extended header for now...)
			/** @noinspection PhpUnusedLocalVariableInspection */
			$extHeader = substr($rawdata, $start, $extHeaderSize);
			$start     += $extHeaderSize;
		}

		$tagdata = substr($rawdata, $start);

		if($unsync && $id3version <= 3){
			$tagdata = ID3Helpers::unsyncString($tagdata);
		}

		$parser = __NAMESPACE__.'\\ID3v2'.$id3version;

		return (new $parser)->parse($tagdata);
	}

	/**
	 *
	 */
	protected function getMP3Stats(int $filesize, int $v1Tagsize, int $v2Tagsize):array{
		$offset     = $this->getMP3FrameStart($filesize, $v1Tagsize, $v2Tagsize);
		$framecount = 0;
		$duration   = 0.0;
		$bitrate    = 0;

		if($offset === null){
			return [];
		}

		while($offset < $filesize){
			$frame = $this->parseMP3FrameHeader($offset);

			if($frame === null){
				$offset = $this->getMP3FrameStart($filesize, $v1Tagsize, $offset);

				if($offset !== null){
					continue;
				}

				break;
			}

			/**
			 * In the Info Tag, the "Xing" identification string at 0x0D (13),
			 * 0x15 (21) or 0x24 (36) (depending on MPEG layer and number of channels)
			 * of the header is replaced by "Info" in case of a CBR file.
			 *
			 * This was done to avoid CBR files to be recognized as traditional
			 * Xing VBR files by some decoders. Although the two identification
			 * strings "Xing" and "Info" are both valid, it is suggested that you
			 * keep the identification string "Xing" in case of VBR bistream
			 * in order to keep compatibility.
			 *
			 * @link http://gabriel.mp3-tech.org/mp3infotag.html
			 */
			if($framecount === 0){
				$pos = $this::VBR_ID_STRING_POS[$frame->version][$frame->channelmode] ?? 36;
				fseek($this->fh, $offset + $pos, SEEK_SET);

				if(strtolower(fread($this->fh, 4)) !== 'xing'){ // lower just in case someone thinks they're special

					return [
						'duration' => (int)round(($filesize - $v1Tagsize - $v2Tagsize) / (($frame->bitrate * 1000) / 8)),
						'bitrate'  => $frame->bitrate,
					];
				}

			}

			$duration += $frame->duration;
			$offset   += $frame->length;
			$bitrate  += $frame->bitrate;

			$framecount++;
		}

		return [
			'duration' => (int)round($duration),
			'bitrate'  => (int)round($bitrate / $framecount),
			'frames'   => $framecount,
		];
	}

	/**
	 *
	 */
	protected function getMP3FrameStart(int $filesize, int $v1Tagsize, int $offset):?int{
		fseek($this->fh, $offset, SEEK_SET);
		$size = $filesize - $v1Tagsize;

		while($offset < $size){
			$offset++;

			$byte = fread($this->fh, 1);

			if($byte === false){
				return null;
			}

			if($byte === "\xff"){
				$byte = fread($this->fh, 1);

				if($byte === "\xf3" ||$byte === "\xfa" || $byte === "\xfb"){//
					$offset = ftell($this->fh) - 2;
					fseek($this->fh, $offset, SEEK_SET);

					return $offset;
				}

			}

		}

		return null;
	}

	/**
	 *
	 */
	protected function parseMP3FrameHeader(int $offset):?stdClass{
		fseek($this->fh, $offset, SEEK_SET);

		$headerBytes = fread($this->fh, 4);

		if(strlen($headerBytes) !== 4 || !($headerBytes[0] === "\xff" && (ord($headerBytes[1]) & 0xe0))){
			return null;
		}

		$b1 = ord($headerBytes[1]);
		$b2 = ord($headerBytes[2]);
		$b3 = ord($headerBytes[3]);

		$info = new stdClass;

		/**
		 * http://www.mp3-tech.org/programmer/frame_header.html
		 * AAAA AAAA  AAAB BCCD  EEEE FFGH  IIJJ KLMM
		 * A - Frame sync (all bits set)
		 * B - MPEG Audio version ID
		 * C - Layer description
		 * D - Protection bit
		 * E - Bitrate index
		 * F - Sampling rate frequency index
		 * G - Padding bit
		 * H - Private bit
		 * I - Channel Mode
		 * J - Mode extension (Only if Joint stereo)
		 * K - Copyright
		 * L - Original
		 * M - Emphasis
		 */
#		$info->sync          = (bigEndian2Int(substr($headerBytes, 0, 2)) & 0xFFE0) >> 4;
		$info->version       = ($b1 & 0x18) >> 3;         //    BB
		$info->layer         = ($b1 & 0x06) >> 1;         //      CC
#		$info->protection    = (bool)($b1 & 0x01);        //        D
		$info->bitrateIndex  = ($b2 & 0xF0) >> 4;         // EEEE
		$info->samplerate    = ($b2 & 0x0C) >> 2;         //     FF
		$info->padding       = (bool)(($b2 & 0x02) >> 1); //       G
#		$info->private       = (bool)($b2 & 0x01);        //        H
		$info->channelmode   = ($b3 & 0xC0) >> 6;         // II
#		$info->modeextension = ($b3 & 0x30) >> 4;         //   JJ
#		$info->copyright     = (bool)(($b3 & 0x08) >> 3); //     K
#		$info->original      = (bool)(($b3 & 0x04) >> 2); //      L
#		$info->emphasis      = ($b3 & 0x03);              //       MM

		if(!in_array($info->version, [0b00, 0b10, 0b11]) || !in_array($info->layer, [0b01, 0b10, 0b11])){
			return null;
		}

		$sampleRate = $this::SAMPLE_RATES[$info->version][$info->samplerate];

		if($sampleRate <= 0){
			// Invalid sample rate value
			return null;
		}

		$bitRate = 0;

		if($info->version === 0b11){
			switch($info->layer){
				case 0b11:
					$bitRate = $this::BITRATES[$info->bitrateIndex][0];
					break;
				case 0b10:
					$bitRate = $this::BITRATES[$info->bitrateIndex][1];
					break;
				case 0b01:
					$bitRate = $this::BITRATES[$info->bitrateIndex][2];
					break;
			}
		}
		else{
			switch($info->layer){
				case 0b11:
					$bitRate = $this::BITRATES[$info->bitrateIndex][3];
					break;
				case 0b10:
				case 0b01:
					$bitRate = $this::BITRATES[$info->bitrateIndex][4];
					break;
			}
		}

		if($bitRate <= 0){
			return null; // bitrate "free"
		}

		$info->bitrate = $bitRate;
		$bitRate *= 1000;

		if($info->layer === 0b11){
			$info->length = (((12 * $bitRate) / $sampleRate) + (int)$info->padding) * 4;
		}
		elseif($info->layer === 0b10 || $info->layer === 0b01){
			$info->length = ((144 * $bitRate) / $sampleRate) + (int)$info->padding;
		}

		$info->length = floor($info->length);

		if($info->length <= 0){
			return null;
		}

		$info->duration = $info->length * 8 / $bitRate;

		return $info;
	}

	/**
	 * @todo
	 */
#	public function write(iterable $data):bool{
#		return false;
#	}

}
