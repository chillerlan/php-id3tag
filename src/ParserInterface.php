<?php
/**
 * Interface ParserInterface
 *
 * @created      26.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\ID3Tag;

/**
 *
 */
interface ParserInterface{

	/**
	 *
	 */
	public function parse(string $rawdata):?array;
}
