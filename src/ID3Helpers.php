<?php
/**
 * Class ID3Helpers
 *
 * @created      25.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\ID3Tag;

use DateInterval, DateTime, RuntimeException;

use function ord, pow, sprintf, str_replace, strlen;

use const PHP_INT_SIZE;

final class ID3Helpers{

	/**
	 *
	 */
	public static function syncSafeInteger(int $raw):int{
		return $raw & 0x0000007F | ($raw & 0x00007F00) >> 1 | ($raw & 0x007F0000) >> 2 | ($raw & 0x7F000000) >> 3;
	}

	/**
	 * @link https://github.com/JamesHeinrich/getID3/blob/ec929d85af7b6bc04c10175a37144cdec0f94c72/getid3/getid3.lib.php#L334
	 *
	 * @throws \Exception
	 */
	public static function bigEndian2Int(string $byteword, bool $syncsafe = null, bool $signed = null):?int{
		$intvalue    = 0;
		$bytewordlen = strlen($byteword);

		if($bytewordlen === 0){
			return null;
		}

		for($i = 0; $i < $bytewordlen; $i++){

			$intvalue += $syncsafe
				? (ord($byteword[$i]) & 0x7F) * pow(2, ($bytewordlen - 1 - $i) * 7) // disregard MSB, effectively 7-bit bytes
				: ord($byteword[$i]) * pow(256, ($bytewordlen - 1 - $i));

		}

		if($signed && !$syncsafe){

			// syncsafe ints are not allowed to be signed
			if($bytewordlen <= PHP_INT_SIZE){
				$signMaskBit = 0x80 << (8 * ($bytewordlen - 1));

				if($intvalue & $signMaskBit){
					$intvalue = 0 - ($intvalue & ($signMaskBit - 1));
				}
			}
			else{
				throw new RuntimeException(
					sprintf('ERROR: Cannot have signed integers larger than %s bits %s', (8 * PHP_INT_SIZE), strlen($byteword))
				);
			}
		}

		return $intvalue;
	}

	/**
	 *
	 */
	public static function unsyncString(string $string):string{
		return str_replace("\xFF\x00", "\xFF", $string);
	}

	/**
	 *
	 */
	public static function formatTime(int $duration):string{

		$dt = (new DateTime)
			->add(new DateInterval('PT'.$duration.'S'))
			->diff(new DateTime)
		;

		$format = '%i:%s';

		if($dt->h > 0){
			$format = '%H:'.$format;
		}

		if($dt->h > 24){
			$format = '%D:'.$format;
		}

		return $dt->format($format);
	}

}
