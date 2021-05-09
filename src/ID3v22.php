<?php
/**
 * Class ID3v22
 *
 * @created      22.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 *
 * @noinspection PhpUnusedParameterInspection
 */

namespace chillerlan\ID3Tag;

use function bin2hex, ord, sha1, strlen, strpos, strtolower, substr, trim, unpack;

/**
 * @link http://id3.org/id3v2-00
 */
class ID3v22 extends ID3v2Abstract{

	/**
	 * 3.2. ID3v2.2 frames overview (excerpt)
	 *
	 * @inheritDoc
	 */
	public function parse(string $rawdata):array{
		$frames    = [];
		$index     = 0;
		$rawlength = strlen($rawdata);

		while($index < $rawlength){

			// frame name
			$name   = substr($rawdata, $index, 3);
			$index += 3;

			// name is end tag or garbage
			if($name === "\x00\x00\x00" || strlen($name) !== 3){
				break;
			}

			// frame length bytes
			$length = substr($rawdata, $index, 3);

			// length data is garbage
			if(strlen($length) !== 3){
				break;
			}

			$length = unpack('N', "\x00".$length)[1] ?? 0;
			$index += 3;

			// frame length exceeds tag size
			if($length > $rawlength || $index >= $rawlength){
				break;
			}

			// frame is empty
			if($length < 1){
				continue;
			}

			// frame data
			$data = substr($rawdata, $index, $length);
			$index += $length;

			// frame is empty
			if(strlen($data) < 1){
				continue;
			}

			$this->setTermpos($data);

			$parsed = $this->parseFrame([
				'name'   => $name,
				'data'   => $data,
				'length' => $length,
			]);

			if(!empty($parsed)){
				$frames[] = $this->addTagInfo($parsed, $name);
			}
		}

		return $frames;
	}

	/**
	 * 4.2. Text information frames
	 *
	 * Text information identifier  "T00" - "TZZ" , excluding "TXX",
	 * described in 4.2.2.
	 * Frame size                   $xx xx xx
	 * Text encoding                $xx
	 * Information                  <textstring>
	 */
	protected function T(array $frame):array{
		$content = $this->decodeString(substr($frame['data'], 1));

		// lists with a slash-delimiter
#		if(in_array($frame['name'], ['TP1', 'TCM', 'TXT', 'TOA', 'TOL'], true)){
#			$content = explode('/', $content);
#		}

		return ['content' => $content];
	}

	/**
	 * 4.2.2. User defined text information frame
	 *
	 * User defined...   "TXX"
	 * Frame size        $xx xx xx
	 * Text encoding     $xx
	 * Description       <textstring> $00 (00)
	 * Value             <textstring>
	 */
	protected function TXX(array $frame):array{
		$content = $this->decodeString(substr($frame['data'], $this->termpos));

		// multi value delimited by a null byte
#		if(strpos($content, "\x00") !== false){
#			$content = explode("\x00", $content);
#		}

		return [
			'desc'    => $this->decodeString(substr($frame['data'], 1, $this->termpos)),
			'content' => $content,
		];
	}

	/**
	 * 4.3. URL link frames
	 *
	 * URL link frame   "W00" - "WZZ" , excluding "WXX"
	 * (described in 4.3.2.)
	 * Frame size       $xx xx xx
	 * URL              <textstring>
	 */
	protected function W(array $frame):array{
		return ['content' => trim($frame['data'])];
	}

	/**
	 * 4.3.2. User defined URL link frame
	 *
	 * User defined...   "WXX"
	 * Frame size        $xx xx xx
	 * Text encoding     $xx
	 * Description       <textstring> $00 (00)
	 * URL               <textstring>
	 */
	protected function WXX(array $frame):array{
		return [
			'desc'    => $this->decodeString(substr($frame['data'], 1, $this->termpos)),
			'content' => trim(substr($frame['data'], $this->termpos)),
		];
	}

	/**
	 * 4.4. Involved people list
	 *
	 * Involved people list   "IPL"
	 * Frame size             $xx xx xx
	 * Text encoding          $xx
	 * People list strings    <textstrings>
	 */
	protected function IPL(array $frame):array{
		return $this->T($frame);
	}

	/**
	 * 4.5. Music CD Identifier
	 *
	 * Music CD identifier   "MCI"
	 * Frame size            $xx xx xx
	 * CD TOC                <binary data>
	 */
	protected function MCI(array $frame):array{
		return ['content' => $frame['data'],];
	}

	/**
	 * 4.9. Unsychronised lyrics/text transcription
	 *
	 * Unsynced lyrics/text "ULT"
	 * Frame size           $xx xx xx
	 * Text encoding        $xx
	 * Language             $xx xx xx
	 * Content descriptor   <textstring> $00 (00)
	 * Lyrics/text          <textstring>
	 */
	protected function ULT(array $frame):array{
		return $this->COM($frame);
	}

	/**
	 * 4.11. Comments
	 *
	 * Comment                   "COM"
	 * Frame size                $xx xx xx
	 * Text encoding             $xx
	 * Language                  $xx xx xx
	 * Short content description <textstring> $00 (00)
	 * The actual text           <textstring>
	 */
	protected function COM(array $frame):array{
		return [
			'desc'    => $this->decodeString(substr($frame['data'], 4, $this->termpos - 3)),
			'content' => $this->decodeString(substr($frame['data'], $this->termpos)),
			'lang'    => substr($frame['data'], 1, 3),
		];
	}

	/**
	 * 4.15.   Attached picture
	 *
	 * Attached picture   "PIC"
	 * Frame size         $xx xx xx
	 * Text encoding      $xx
	 * Image format       $xx xx xx
	 * Picture type       $xx
	 * Description        <textstring> $00 (00)
	 * Picture data       <binary data>
	 */
	protected function PIC(array $frame):array{
		$format = strtolower(substr($frame['data'], 1, 3));
		$type   = ord(substr($frame['data'], 4, 1));

		if($format === 'jpeg'){
			$format = 'jpg';
		}

		$magicbytes = $this::imageFormatMagicbytes[$format] ?? false;

		if(!$magicbytes){
			return ['rawdata' => bin2hex($frame['data'])];
		}

		$termpos = strpos($frame['data'], "\x00".$magicbytes);
		$image   = substr($frame['data'], $termpos + 1);

		return [
			'desc'     => $this->decodeString(substr($frame['data'], 5, $termpos - 5)),
			'content'  => $image, # 'data:image/'.$format.';base64,'.base64_encode($image),
			'format'   => $format,
			'mime'     => 'image/'.$format,
			'typeID'   => $type,
			'typeInfo' => $this::PICTURE_TYPE[$type] ?? '',
			'hash'     => sha1($image),
		];
	}

	/**
	 * 4.17. Play counter
	 *
	 * Play counter   "CNT"
	 * Frame size     $xx xx xx
	 * Counter        $xx xx xx xx (xx ...)
	 */
	protected function CNT(array $frame):array{
		return ['count' => ID3Helpers::bigEndian2Int($frame['data']) ?? 0];
	}

	/**
	 * 4.18. Popularimeter
	 *
	 * Popularimeter   "POP"
	 * Frame size      $xx xx xx
	 * Email to user   <textstring> $00
	 * Rating          $xx
	 * Counter         $xx xx xx xx (xx ...)
	 *
	 *
	 * The following list details how Windows Explorer reads and writes the POPM frame:
	 *
	 * 224-255 = 5 stars when READ with Windows Explorer, writes 255
	 * 160-223 = 4 stars, writes 196
	 * 096-159 = 3 stars, writes 128
	 * 032-095 = 2 stars, writes 64
	 * 001-031 = 1 star, writes 1
	 */
	protected function POP(array $frame):array{
		$t = strpos($frame['data'], "\x00", 1);

		return [
			'desc'   => substr($frame['data'], 0, $t),
			'rating' => ord(substr($frame['data'], $t + 1, 1)),
#			'count'  => ID3Helpers::bigEndian2Int(substr($frame['data'], $t + 2)) ?? 0,
		];
	}

}
