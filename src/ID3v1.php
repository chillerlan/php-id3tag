<?php
/**
 * Class ID3v1
 *
 * @created      26.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\ID3Tag;

use function array_keys, array_map, array_values, call_user_func_array, date, intval, method_exists, strlen, substr, trim, unpack;

/**
 * @link http://id3.org/ID3v1
 * @link http://www.birdcagesoft.com/ID3v12.txt
 */
final class ID3v1 implements ParserInterface{

	/**
	 * Genre definitions 0-79 follow the ID3 tag specification of 1999
	 * and the first set of Winamp extensions (80-125)
	 */
	public const GENRES = [
		0   => 'Blues', // starting at 0 was the stupidest thing to do...
		1   => 'Classic Rock',
		2   => 'Country',
		3   => 'Dance',
		4   => 'Disco',
		5   => 'Funk',
		6   => 'Grunge',
		7   => 'Hip-Hop',
		8   => 'Jazz',
		9   => 'Metal',
		10  => 'New Age',
		11  => 'Oldies',
		12  => 'Other',
		13  => 'Pop',
		14  => 'R&B',
		15  => 'Rap',
		16  => 'Reggae',
		17  => 'Rock',
		18  => 'Techno',
		19  => 'Industrial',
		20  => 'Alternative',
		21  => 'Ska',
		22  => 'Death Metal',
		23  => 'Pranks',
		24  => 'Soundtrack',
		25  => 'Euro-Techno',
		26  => 'Ambient',
		27  => 'Trip-Hop',
		28  => 'Vocal',
		29  => 'Jazz+Funk',
		30  => 'Fusion',
		31  => 'Trance',
		32  => 'Classical',
		33  => 'Instrumental',
		34  => 'Acid',
		35  => 'House',
		36  => 'Game',
		37  => 'Sound Clip',
		38  => 'Gospel',
		39  => 'Noise',
		40  => 'Alternative Rock',
		41  => 'Bass',
		42  => 'Soul',
		43  => 'Punk',
		44  => 'Space',
		45  => 'Meditative',
		46  => 'Instrumental Pop',
		47  => 'Instrumental Rock',
		48  => 'Ethnic',
		49  => 'Gothic',
		50  => 'Darkwave',
		51  => 'Techno-Industrial',
		52  => 'Electronic',
		53  => 'Pop-Folk',
		54  => 'Eurodance',
		55  => 'Dream',
		56  => 'Southern Rock',
		57  => 'Comedy',
		58  => 'Cult',
		59  => 'Gangsta',
		60  => 'Top 40',
		61  => 'Christian Rap',
		62  => 'Pop/Funk',
		63  => 'Jungle',
		64  => 'Native US',
		65  => 'Cabaret',
		66  => 'New Wave',
		67  => 'Psychadelic',
		68  => 'Rave',
		69  => 'Showtunes',
		70  => 'Trailer',
		71  => 'Lo-Fi',
		72  => 'Tribal',
		73  => 'Acid Punk',
		74  => 'Acid Jazz',
		75  => 'Polka',
		76  => 'Retro',
		77  => 'Musical',
		78  => 'Rock & Roll',
		79  => 'Hard Rock',
		// Winamp extensions
		80  => 'Folk',
		81  => 'Folk-Rock',
		82  => 'National Folk',
		83  => 'Swing',
		84  => 'Fast Fusion',
		85  => 'Bebob',
		86  => 'Latin',
		87  => 'Revival',
		88  => 'Celtic',
		89  => 'Bluegrass',
		90  => 'Avantgarde',
		91  => 'Gothic Rock',
		92  => 'Progressive Rock',
		93  => 'Psychedelic Rock',
		94  => 'Symphonic Rock',
		95  => 'Slow Rock',
		96  => 'Big Band',
		97  => 'Chorus',
		98  => 'Easy Listening',
		99  => 'Acoustic',
		100 => 'Humour',
		101 => 'Speech',
		102 => 'Chanson',
		103 => 'Opera',
		104 => 'Chamber Music',
		105 => 'Sonata',
		106 => 'Symphony',
		107 => 'Booty Bass',
		108 => 'Primus',
		109 => 'Porn Groove',
		110 => 'Satire',
		111 => 'Slow Jam',
		112 => 'Club',
		113 => 'Tango',
		114 => 'Samba',
		115 => 'Folklore',
		116 => 'Ballad',
		117 => 'Power Ballad',
		118 => 'Rhythmic Soul',
		119 => 'Freestyle',
		120 => 'Duet',
		121 => 'Punk Rock',
		122 => 'Drum Solo',
		123 => 'Acapella',
		124 => 'Euro-House',
		125 => 'Dance Hall',
	];

	/**
	 * @inheritDoc
	 */
	public function parse(string $rawdata):?array{

		if(strlen($rawdata) !== 256){
#			throw new ID3Exception('invalid id3v1 tag size');
			return null;
		}

		$tagdata = substr($rawdata, 128);
#		$extdata = substr($rawdata, 0, 128); // @todo v1.2

		$format = $tagdata[125] === "\x00" && $tagdata[126] !== "\x00"
			? 'a28comment/a1null/c1track/c1genre' // v1.1
			: 'a30comment/c1genre'; // v1.0

		$data = unpack('a3identifier/a30title/a30artist/a30album/a4year/'.$format, $tagdata);

		if($data['identifier'] !== 'TAG'){
#			throw new ID3Exception('invalid id3v1 identifier');
			return null;
		}

		unset($data['identifier'], $data['null']);

		return array_map([$this, 'parseFrame'], array_keys($data), array_values($data));
	}

	/**
	 * @param string $tag
	 * @param mixed  $content
	 *
	 * @return array
	 */
	protected function parseFrame(string $tag, $content):array{

		if(method_exists($this, $tag)){
			return call_user_func_array([$this, $tag], [$tag, $content]);
		}

		// Detecting UTF-8 before ISO-8859-1 will cause ASCII strings being tagged as UTF-8, which is fine.
		// However, it will prevent UTF-8 encoded strings from being wrongly decoded twice.
		$encoding = mb_detect_encoding($content, ['Windows-1251', 'Windows-1252', 'KOI8-R', 'UTF-8', 'ISO-8859-1']);

		if($encoding !== 'UTF-8'){
			$content = mb_convert_encoding($content, 'UTF-8', $encoding);
		}

		return [
			'tag'      => $tag,
			'content'  => trim($content),
			'encoding' => $encoding,
		];
	}

	/**
	 * @param string $tag
	 * @param mixed  $content
	 *
	 * @return array
	 */
	protected function year(string $tag, $content):array{
		$content = intval(trim($content));

		return [
			'tag'     => $tag,
			'content' => $content <= date('Y') && $content > 0 ? $content : null,
		];
	}

	/**
	 *
	 */
	protected function genre(string $tag, int $content):array{
		return [
			'tag'     => $tag,
			'content' => $content, // there's no sane way to tell whether a genre was actually set
			'desc'    => $this::GENRES[$content] ?? null,
		];
	}

	/**
	 *
	 */
	protected function track(string $tag, int $content):array{
		return [
			'tag'     => $tag,
			'content' => $content,
		];
	}

}
