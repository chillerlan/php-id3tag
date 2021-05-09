<?php
/**
 * Class ID3Data
 *
 * @created      07.03.2020
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2020 smiley
 * @license      MIT
 */

namespace chillerlan\ID3Tag;

use finfo;
use function array_column, filesize, in_array, is_array, property_exists;
use const FILEINFO_MIME_ENCODING, FILEINFO_MIME_TYPE;

/**
 * @property string $filename
 * @property int $filesize
 * @property int $v1tagsize
 * @property int $v2tagsize
 * @property string $mimeType
 * @property string $mimeEncoding
 * @property string $finfo
 * @property int|null $framecount
 * @property float $duration
 * @property int $bitrate
 * @property array|null $id3v1
 * @property array|null $id3v2
 * @property array|null $id3v1TagIndex
 * @property array|null $id3v2TagIndex
 */
final class ID3Data{

	private string $filename;
	private int $filesize;
	private int $v1tagsize = 0;
	private int $v2tagsize = 0;
	private string $mimeType;
	private string $mimeEncoding;
	private string $finfo;
	private ?int $framecount = null;
	private int $duration = 0;
	private int $bitrate;
	private ?array $id3v1 = null;
	private ?array $id3v2 = null;
	private ?array $id3v1TagIndex = null;
	private ?array $id3v2TagIndex = null;

	/**
	 * ID3Data constructor.
	 */
	public function __construct(string $file){
		$this->filename     = $file;
		$this->filesize     = filesize($file);
		$this->mimeType     = (new finfo(FILEINFO_MIME_TYPE))->file($file);
		$this->mimeEncoding = (new finfo(FILEINFO_MIME_ENCODING))->file($file);
		$this->finfo        = (new finfo)->file($file);
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get(string $property){

		if(property_exists($this, $property)){
			return $this->{$property};
		}

		return null;
	}

	/**
	 *
	 */
	public function setProperties(iterable $properties):ID3Data{

		foreach($properties as $property => $value){

			if(!property_exists($this, $property)){
				continue;
			}

			$this->{$property} = $value;

			if(in_array($property, ['id3v1', 'id3v2']) && is_array($value)){
				$this->{$property.'TagIndex'} = array_column($value, 'tag');
			}

		}

		return $this;
	}

}
