<?php
/**
 * Class ID3v2Abstract
 *
 * @created      22.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\ID3Tag;

use function bin2hex, call_user_func_array, method_exists, ord, preg_match, strpos, substr, trim;

/**
 * @link http://id3.org/id3guide
 * @link http://id3.org/id3v2-chapters-1.0
 * @link http://id3.org/id3v2-accessibility-1.0
 * @link https://sno.phy.queensu.ca/~phil/exiftool/TagNames/ID3.html
 */
abstract class ID3v2Abstract implements ParserInterface{

	/**
	 * 4.15. Attached picture
	 *
	 * Picture type
	 *
	 * @var array
	 */
	public const PICTURE_TYPE = [
		0x00 => 'Other',
		0x01 => '32x32 pixels \'file icon\' (PNG only)',
		0x02 => 'Other file icon',
		0x03 => 'Cover (front)',
		0x04 => 'Cover (back)',
		0x05 => 'Leaflet page',
		0x06 => 'Media (e.g. label side of CD)',
		0x07 => 'Lead artist/lead performer/soloist',
		0x08 => 'Artist/performer',
		0x09 => 'Conductor',
		0x0A => 'Band/Orchestra',
		0x0B => 'Composer',
		0x0C => 'Lyricist/text writer',
		0x0D => 'Recording Location',
		0x0E => 'During recording',
		0x0F => 'During performance',
		0x10 => 'Movie/video screen capture',
		0x11 => 'A bright coloured fish',
		0x12 => 'Illustration',
		0x13 => 'Band/artist logotype',
		0x14 => 'Publisher/Studio logotype',
	];

	/**
	 * @var array
	 */
	public const ENCODING_NAMES = [
		// $00  ISO-8859-1.
		// Terminated with $00.
		0x00   => 'ISO-8859-1',
		// $01  UTF-16 encoded Unicode with BOM. All strings in the same frame SHALL have the same byteorder.
		// Terminated with $00 00.
		0x01   => 'UTF-16',
		// $02  UTF-16 big endian, encoded Unicode without BOM.
		// Terminated with $00 00.
		0x02   => 'UTF-16BE',
		// $03  UTF-8 encoded Unicode.
		// Terminated with $00.
		0x03   => 'UTF-8',
	];

	/**
	 * @var array
	 */
	protected const imageFormatMagicbytes = [
		'png'  => "\x89\x50\x4e\x47",
		'jpg'  => "\xff\xd8",
		'gif'  => "\x47\x49\x46\x38",
		'bmp'  => "\x42\x4d",
	];

	protected array $declaredFrames;
	protected string $encoding;
	protected string $terminator = "\x00";
	protected int $termpos;

	/**
	 *
	 */
	protected function setTermpos(string $data):void{
		$encodingByte   = ord(substr($data, 0, 1));
		$this->encoding = $this::ENCODING_NAMES[$encodingByte] ?? 'ISO-8859-1';
		$this->termpos  = strpos($data, $this->terminator, 1);

		// UTF-16
		if($encodingByte === 1 || $encodingByte === 2){
			$this->terminator = "\x00\x00";

			// match terminator + BOM
			preg_match('/[\x00]{2}[\xfe\xff]{2}/', $data, $match);

			// no BOM / content following the terminator is not encoded
			if(empty($match) || $encodingByte === 2){
				preg_match('/[\x00]{2}[^\x00]+/', $data, $match);
			}

			$this->termpos = strpos($data, $match[0] ?? "\x00") + 2; // add 2 bytes for the terminator
		}

	}

	/**
	 *
	 */
	protected function decodeString(string $data):string{
		return trim(mb_convert_encoding($data, 'UTF-8', $this->encoding));
	}

	/**
	 *
	 */
	protected function parseFrame(array $frame):array{

		if(method_exists($this, $frame['name'])){
			return call_user_func_array([$this, $frame['name']], [$frame]);
		}

		$shortname = substr($frame['name'], 0, 1);

		if(method_exists($this, $shortname)){
			return call_user_func_array([$this, $shortname], [$frame]);
		}

		$frame['data'] = bin2hex($frame['data']);

		return ['rawdata' => $frame];
	}

	/**
	 *
	 */
	protected function addTagInfo(array $parsedFrame, string $tagName):array{
		return [
			'tag'      => $tagName,
			'tagInfo'  => $this->declaredFrames[$tagName] ?? '',
			'encoding' => $this->encoding
		] + $parsedFrame;
	}

}
