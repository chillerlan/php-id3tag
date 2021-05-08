<?php
/**
 * Class ID3v1Test
 *
 * @created      13.03.2020
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2020 smiley
 * @license      MIT
 */

namespace chillerlan\ID3TagTest;

use chillerlan\ID3Tag\{ID3, ID3v1};
use PHPUnit\Framework\TestCase;

use function array_combine, str_repeat;

class ID3v1Test extends TestCase{

	/**
	 * Files taken from the id3.org ID3v1 and ID3v1.1 testsuite
	 */
	public function ID3v1TagProvider():array{
		return [
			'An ordinary ID3v1 tag with all fields set to a plauseble value' =>
				['id3v1_001_basic', ['Title', 'Artist', 'Album', 2003, 'Comment', 7]],
			'An ordinary ID3v1.1 tag with all fields set to a plauseble value' =>
				['id3v1_002_basic', ['Title', 'Artist', 'Album', 2003, 'Comment', 12, 7]],
			'An ID3 tag with all fields set to shortest legal value' =>
				['id3v1_004_basic', ['', '', '', 2003, '', 0]],
			'An ID3v1 tag with all fields set to longest value' =>
				['id3v1_005_basic', ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaA', 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbB', 'cccccccccccccccccccccccccccccC', 2003, 'dddddddddddddddddddddddddddddD', 0]],
			'An ID3v1.1 tag with all fields set to longest value' =>
				['id3v1_006_basic', ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaA', 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbB', 'cccccccccccccccccccccccccccccC', 2003, 'dddddddddddddddddddddddddddD', 1, 0]],
			'Title with 8-bit iso-8859-1 characters' =>
				['id3v1_271_extra', ['räksmörgås', 'räksmörgås', 'räksmörgås', 2003, 'räksmörgås', 0]],
			'Title with utf-8-encoded 8-bit string' =>
				['id3v1_272_extra', ['räksmörgås', 'räksmörgås', 'räksmörgås', 2003, 'räksmörgås', 0]],
			'Comment field with http://-style URL' =>
				['id3v1_273_extra', ['', '', '', 2003, 'http://www.id3.org/', 0]],
		];
	}

	/**
	 * @dataProvider ID3v1TagProvider
	 */
	public function testID3v1(string $file, array $expected):void{
		$ID3Data  = (new ID3)->read(__DIR__.'/files/id3v1/'.$file.'.mp3');
		$id3v1    = array_combine($ID3Data->id3v1TagIndex, $ID3Data->id3v1);
		$expected = array_combine($ID3Data->id3v1TagIndex, $expected);

		foreach($id3v1 as $tag => $data){
			/** @phan-suppress-next-line PhanTypeArraySuspiciousNullable */
			$this::assertSame($expected[$tag], $data['content']);
		}

	}

	public function testID3v1ParseError():void{
		$parser = new ID3v1;

		// invalid tag size
		$this::assertNull($parser->parse('foo'));
		// invalid identifier
		$this::assertNull($parser->parse(str_repeat('0', 256)));
	}

}
