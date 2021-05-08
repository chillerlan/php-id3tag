<?php
/**
 * Class ID3v24
 *
 * @created      22.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\ID3Tag;

use function unpack;

/**
 * @link http://id3.org/id3v2.4.0-changes
 * @link http://id3.org/id3v2.4.0-structure
 * @link http://id3.org/id3v2.4.0-frames
 */
class ID3v24 extends ID3v23{

	/**
	 *
	 */
	protected function getFrameLength(string $raw):int{
		$raw = unpack('N', $raw)[1];

		return ID3Helpers::syncSafeInteger($raw);
	}

	/**
	 *
	 */
	protected function getFrameFormat(int $flags):array{
		return [
			'flags'       => $flags,
			'length'      => (bool)($flags & 0b00000001),
			'unsync'      => (bool)($flags & 0b00000010),
			'encryption'  => (bool)($flags & 0b00000100),
			'compression' => (bool)($flags & 0b00001000),
			'grouping'    => (bool)($flags & 0b01000000),
		];
	}

	/**
	 *
	 */
	protected function getFrameStatus(int $flags):array{
		return [
			'flags'     => $flags,
			'read-only' => (bool)($flags & 0b00010000),
			'file'      => (bool)($flags & 0b00100000),
			'tag'       => (bool)($flags & 0b01000000),
		];
	}

}
