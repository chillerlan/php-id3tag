<?php
/**
 * Class ID3v23
 *
 * @created      22.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 *
 * @noinspection PhpUnusedParameterInspection
 */

namespace chillerlan\ID3Tag;

use function count, explode, implode, ord, sha1, strlen, strpos, strtolower, substr, trim, unpack;

/**
 * @link http://id3.org/id3v2.3.0
 */
class ID3v23 extends ID3v22{

	/**
	 * 4. Declared ID3v2 frames
	 *
	 * @link http://id3.org/id3v2.3.0#Declared_ID3v2_frames
	 */
	protected array $declaredFrames = [
		'AENC' => 'Audio encryption',
		'APIC' => 'Attached picture',
		'COMM' => 'Comments',
		'COMR' => 'Commercial frame',
		'ENCR' => 'Encryption method registration',
		'EQUA' => 'Equalization',
		'ETCO' => 'Event timing codes',
		'GEOB' => 'General encapsulated object',
		'GRID' => 'Group identification registration',
		'IPLS' => 'Involved people list',
		'LINK' => 'Linked information',
		'MCDI' => 'Music CD identifier',
		'MLLT' => 'MPEG location lookup table',
		'OWNE' => 'Ownership frame',
		'PRIV' => 'Private frame',
		'PCNT' => 'Play counter',
		'POPM' => 'Popularimeter',
		'POSS' => 'Position synchronisation frame',
		'RBUF' => 'Recommended buffer size',
		'RVAD' => 'Relative volume adjustment',
		'RVRB' => 'Reverb',
		'SYLT' => 'Synchronized lyric/text',
		'SYTC' => 'Synchronized tempo codes',
		'TALB' => 'Album/Movie/Show title',
		'TBPM' => 'BPM (beats per minute)',
		'TCOM' => 'Composer',
		'TCON' => 'Content type',
		'TCOP' => 'Copyright message',
		'TDAT' => 'Date',
		'TDLY' => 'Playlist delay',
		'TENC' => 'Encoded by',
		'TEXT' => 'Lyricist/Text writer',
		'TFLT' => 'File type',
		'TIME' => 'Time',
		'TIT1' => 'Content group description',
		'TIT2' => 'Title/songname/content description',
		'TIT3' => 'Subtitle/Description refinement',
		'TKEY' => 'Initial key',
		'TLAN' => 'Language(s)',
		'TLEN' => 'Length',
		'TMED' => 'Media type',
		'TOAL' => 'Original album/movie/show title',
		'TOFN' => 'Original filename',
		'TOLY' => 'Original lyricist(s)/text writer(s)',
		'TOPE' => 'Original artist(s)/performer(s)',
		'TORY' => 'Original release year',
		'TOWN' => 'File owner/licensee',
		'TPE1' => 'Lead performer(s)/Soloist(s)',
		'TPE2' => 'Band/orchestra/accompaniment',
		'TPE3' => 'Conductor/performer refinement',
		'TPE4' => 'Interpreted, remixed, or otherwise modified by',
		'TPOS' => 'Part of a set',
		'TPUB' => 'Publisher',
		'TRCK' => 'Track number/Position in set',
		'TRDA' => 'Recording dates',
		'TRSN' => 'Internet radio station name',
		'TRSO' => 'Internet radio station owner',
		'TSIZ' => 'Size',
		'TSRC' => 'ISRC (international standard recording code)',
		'TSSE' => 'Software/Hardware and settings used for encoding',
		'TYER' => 'Year',
		'TXXX' => 'User defined text information frame',
		'UFID' => 'Unique file identifier',
		'USER' => 'Terms of use',
		'USLT' => 'Unsychronized lyric/text transcription',
		'WCOM' => 'Commercial information',
		'WCOP' => 'Copyright/Legal information',
		'WOAF' => 'Official audio file webpage',
		'WOAR' => 'Official artist/performer webpage',
		'WOAS' => 'Official audio source webpage',
		'WORS' => 'Official internet radio station homepage',
		'WPAY' => 'Payment',
		'WPUB' => 'Publishers official webpage',
		'WXXX' => 'User defined URL link frame',

		'GRP1' => 'ITunes Grouping',
		'TCMP' => 'ITunes compilation field',
		'TDEN' => 'Encoding time',
		'TSST' => 'Set subtitle',
		'TIPL' => 'Involved people list',
		'TMOO' => 'Mood',
		'TDOR' => 'Original release time',
		'TDRL' => 'Release time',
		'TDTG' => 'Tagging time',
		'TDRC' => 'Recording time',
		'TSOA' => 'Album sort order',
		'TSOP' => 'Performer sort order',
		'TSOT' => 'Title sort order',
		'TSO2' => 'Album-Artist sort order',
		'TSOC' => 'Composer sort order',
		'EQU2' => 'Equalisation',
		'RVA2' => 'Relative volume adjustment',
		'SIGN' => 'Signature',
		'ASPI' => 'Audio seek point index',
		'RGAD' => 'Replay Gain Adjustment',
		'CHAP' => 'Chapters',
		'CTOC' => 'Chapters Table Of Contents',

		'NCON' => '???',
		'TDES' => '???PODCASTDESC',
		'TCAT' => '???PODCASTCATEGORY',
		'TGID' => '???PODCASTID',
		'TKWD' => '???PODCASTKEYWORDS',
		'WFED' => '???PODCASTURL',
	];


	/**
	 * 3.3. ID3v2.3 frame overview
	 *
	 * @link http://id3.org/id3v2.3.0#ID3v2_frame_overview
	 *
	 * Frame ID   $xx xx xx xx  (four characters)
	 * Size       $xx xx xx xx
	 * Flags      $xx xx
	 *
	 * @inheritDoc
	 */
	public function parse(string $rawdata):array{
		$frames    = [];
		$index     = 0;
		$rawlength = strlen($rawdata);

		while($index < $rawlength){

			// frame name
			$name   = substr($rawdata, $index, 4);
			$index += 4;

			// name is end tag or garbage
			if($name === "\x00\x00\x00\x00" || strlen($name) !== 4){
				break;
			}

			// frame length bytes
			$length = substr($rawdata, $index, 4);

			// length data is garbage
			if(strlen($length) !== 4){
				break;
			}

			$length = unpack('N', $length)[1] ?? 0;
			$index += 4;

			// frame length exceeds tag size
			if($length > $rawlength || $index >= $rawlength){
				break;
			}

			// frame is empty
			if($length < 1){
				continue;
			}

			// status & format bytes
			$status = ord(substr($rawdata, $index, 1));
			$format = ord(substr($rawdata, $index + 1, 1));
			$index += 2;

			// frame data
			$data = substr($rawdata, $index, $length);
			$index += $length;

			// frame is empty
			if(strlen($data) < 1){
				continue; // @codeCoverageIgnore
			}

			$format = $this->getFrameFormat($format);
			$status = $this->getFrameStatus($status);

			if($format['unsync']){
				$data = ID3Helpers::unsyncString($data);
			}

			$this->setTermpos($data);

			$parsed = $this->parseFrame([
				'name'   => $name,
				'data'   => $data,
				'length' => $length,
				'format' => $format,
				'status' => $status,
			]);

			if(!empty($parsed)){
				$frames[] = $this->addTagInfo($parsed, $name);
			}
		}

		return $frames;
	}

	/**
	 * @link http://id3.org/id3v2.3.0#Frame_header_flags
	 */
	protected function getFrameFormat(int $flags):array{
		return [
			'flags'       => $flags,
			'length'      => false,
			'unsync'      => false,
			'encryption'  => (bool)($flags & 0b01000000),
			'compression' => (bool)($flags & 0b10000000),
			'grouping'    => (bool)($flags & 0b00100000),
		];
	}

	/**
	 * @link http://id3.org/id3v2.3.0#Frame_header_flags
	 */
	protected function getFrameStatus(int $flags):array{
		return [
			'flags'     => $flags,
			'read-only' => (bool)($flags & 0b00100000),
			'file'      => (bool)($flags & 0b01000000),
			'tag'       => (bool)($flags & 0b10000000),
		];
	}

	/**
	 * 4.1. Unique file identifier
	 *
	 * <Header for 'Unique file identifier', ID: "UFID">
	 * Owner identifier        <text string> $00
	 * Identifier              <up to 64 bytes binary data>
	 */
	protected function UFID(array $frame):array{
		return []; // skip
	}

	/**
	 * 4.2.2. User defined text information frame
	 *
	 * @link http://id3.org/id3v2.3.0#User_defined_text_information_frame
	 *
	 * <Header for 'User defined text information frame', ID: "TXXX">
	 * Text encoding     $xx
	 * Description       <text string according to encoding> $00 (00)
	 * Value             <text string according to encoding>
	 */
	protected function TXXX(array $frame):array{
		return $this->TXX($frame);
	}

	/**
	 * 4.3.2. User defined URL link frame
	 *
	 * @link http://id3.org/id3v2.3.0#User_defined_URL_link_frame
	 *
	 * <Header for 'User defined URL link frame', ID: "WXXX">
	 * Text encoding     $xx
	 * Description       <text string according to encoding> $00 (00)
	 * URL               <text string>
	 */
	protected function WXXX(array $frame):array{
		return $this->WXX($frame);
	}

	/**
	 * 4.4. Involved people list
	 *
	 * @link http://id3.org/id3v2.3.0#Involved_people_list
	 *
	 * <Header for 'Involved people list', ID: "IPLS">
	 * Text encoding          $xx
	 * People list strings    <text strings according to encoding>
	 */
	protected function IPLS(array $frame):array{
		return $this->T($frame);
	}

	/**
	 * 4.5. Music CD identifier
	 *
	 * @link http://id3.org/id3v2.3.0#Music_CD_identifier
	 *
	 * <Header for 'Music CD identifier', ID: "MCDI">
	 * CD TOC                <binary data>
	 */
	protected function MCDI(array $frame):array{
		return $this->MCI($frame);
	}

	/**
	 * 4.9. Unsychronised lyrics/text transcription
	 *
	 * @link http://id3.org/id3v2.3.0#Unsychronised_lyrics.2Ftext_transcription
	 *
	 * <Header for 'Unsynchronised lyrics/text transcription', ID: "USLT">
	 * Text encoding        $xx
	 * Language             $xx xx xx
	 * Content descriptor   <text string according to encoding> $00 (00)
	 * Lyrics/text          <full text string according to encoding>
	 */
	protected function USLT(array $frame):array{
		return $this->COM($frame);
	}

	/**
	 * 4.11. Comments
	 *
	 * @link http://id3.org/id3v2.3.0#Comments
	 *
	 * <Header for 'Comment', ID: "COMM">
	 * Text encoding          $xx
	 * Language               $xx xx xx
	 * Short content descrip. <text string according to encoding> $00 (00)
	 * The actual text        <full text string according to encoding>
	 */
	protected function COMM(array $frame):array{
		return $this->COM($frame);
	}

	/**
	 * 4.15. Attached picture
	 *
	 * @link http://id3.org/id3v2.3.0#Attached_picture
	 *
	 * <Header for 'Attached picture', ID: "APIC">
	 * Text encoding      $xx
	 * MIME type          <text string> $00
	 * Picture type       $xx
	 * Description        <text string according to encoding> $00 (00)
	 * Picture data       <binary data>
	 */
	protected function APIC(array $frame):array{
		$t    = strpos($frame['data'], "\x00", 1);
		$mime = strtolower(substr($frame['data'], 1, $t - 1));
		$type = ord(substr($frame['data'], $t + 1, 1));

		// wonky mime type @todo
		$m = explode('/', $mime);

		if(count($m) !== 2 || $m[0] !== 'image'){

			// is it a v2.2 style format?
			if($m[0] !== 'image' && isset($this::imageFormatMagicbytes[$m[0]])){
				$m    = ['image', $m[0]];
				$mime = implode('/', $m);
			}
			else{
				return [];
			}
		}

		if($m[1] === 'jpeg'){
			$m[1] = 'jpg';
		}

		$magicbytes = $this::imageFormatMagicbytes[$m[1]] ?? false;

		if(!$magicbytes){
			return [];
		}

		$termpos = strpos($frame['data'], "\x00".$magicbytes, $t + 1);
		$image   = substr($frame['data'], $termpos + 1);

		return [
			'desc'     => $this->decodeString(substr($frame['data'], $t + 2, $termpos - $t - 2)),
			'content'  => $image, # 'data:'.$mime.';base64,'.base64_encode($image),
			'format'   => $m[1],
			'mime'     => $mime,
			'typeID'   => $type,
			'typeInfo' => $this::PICTURE_TYPE[$type] ?? '',
			'hash'     => sha1($image),
		];
	}

	/**
	 * 4.16.   General encapsulated object
	 *
	 * <Header for 'General encapsulated object', ID: "GEOB">
	 * Text encoding          $xx
	 * MIME type              <text string> $00
	 * Filename               <text string according to encoding> $00 (00)
	 * Content description    <text string according to encoding> $00 (00)
	 * Encapsulated object    <binary data>
	 */
	protected function GEOB(array $frame):array{
		return []; // skip
	}

	/**
	 * 4.17. Play counter
	 *
	 * @link http://id3.org/id3v2.3.0#Play_counter
	 *
	 * <Header for 'Play counter', ID: "PCNT">
	 * Counter        $xx xx xx xx (xx ...)
	 */
	protected function PCNT(array $frame):array{
		return $this->CNT($frame);
	}

	/**
	 * 4.18. Popularimeter
	 *
	 * @link http://id3.org/id3v2.3.0#Popularimeter
	 *
	 * <Header for 'Popularimeter', ID: "POPM">
	 * Email to user   <text string> $00
	 * Rating          $xx
	 * Counter         $xx xx xx xx (xx ...)
	 */
	protected function POPM(array $frame):array{
		return $this->POP($frame);
	}

	/**
	 * 4.21. Linked information
	 *
	 * <Header for 'Linked information', ID: "LINK">
	 * Frame identifier        $xx xx xx
	 * URL                     <text string> $00
	 * ID and additional data  <text string(s)>
	 */
	protected function LINK(array $frame):array{
		return []; // skip
	}

	/**
	 * 4.28. Private frame
	 *
	 * @link http://id3.org/id3v2.3.0#Private_frame
	 *
	 * <Header for 'Private frame', ID: "PRIV">
	 * Owner identifier      <text string> $00
	 * The private data      <binary data>
	 */
	protected function PRIV(array $frame):array{
		return [
			'desc'    => trim(substr($frame['data'], 0, $this->termpos)),
			'content' => trim(substr($frame['data'], $this->termpos)),
		];
	}

}
