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
	 * 4. Declared ID3v2 frames
	 *
	 * @lnk http://id3.org/id3v2.3.0#Declared_ID3v2_frames
	 *
	 * @var array
	 */
	public const TAG_INFO = [
		// 2.2
		'BUF' => 'Recommended buffer size',
		'CNT' => 'Play counter',
		'COM' => 'Comments',
		'CRA' => 'Audio encryption',
		'CRM' => 'Encrypted meta frame',
		'ETC' => 'Event timing codes',
		'EQU' => 'Equalization',
		'GEO' => 'General encapsulated object',
		'IPL' => 'Involved people list',
		'LNK' => 'Linked information',
		'MCI' => 'Music CD Identifier',
		'MLL' => 'MPEG location lookup table',
		'PIC' => 'Attached picture',
		'POP' => 'Popularimeter',
		'REV' => 'Reverb',
		'RVA' => 'Relative volume adjustment',
		'SLT' => 'Synchronized lyric/text',
		'STC' => 'Synced tempo codes',
		'TAL' => 'Album/Movie/Show title',
		'TBP' => 'BPM (Beats Per Minute)',
		'TCM' => 'Composer',
		'TCO' => 'Content type',
		'TCR' => 'Copyright message',
		'TDA' => 'Date',
		'TDY' => 'Playlist delay',
		'TEN' => 'Encoded by',
		'TFT' => 'File type',
		'TIM' => 'Time',
		'TKE' => 'Initial key',
		'TLA' => 'Language(s)',
		'TLE' => 'Length',
		'TMT' => 'Media type',
		'TOA' => 'Original artist(s)/performer(s)',
		'TOF' => 'Original filename',
		'TOL' => 'Original Lyricist(s)/text writer(s)',
		'TOR' => 'Original release year',
		'TOT' => 'Original album/Movie/Show title',
		'TP1' => 'Lead artist(s)/Lead performer(s)/Soloist(s)/Performing group',
		'TP2' => 'Band/Orchestra/Accompaniment',
		'TP3' => 'Conductor/Performer refinement',
		'TP4' => 'Interpreted, remixed, or otherwise modified by',
		'TPA' => 'Part of a set',
		'TPB' => 'Publisher',
		'TRC' => 'ISRC (International Standard Recording Code)',
		'TRD' => 'Recording dates',
		'TRK' => 'Track number/Position in set',
		'TSI' => 'Size',
		'TSS' => 'Software/hardware and settings used for encoding',
		'TT1' => 'Content group description',
		'TT2' => 'Title/Songname/Content description',
		'TT3' => 'Subtitle/Description refinement',
		'TXT' => 'Lyricist/text writer',
		'TXX' => 'User defined text information frame',
		'TYE' => 'Year',
		'UFI' => 'Unique file identifier',
		'ULT' => 'Unsychronized lyric/text transcription',
		'WAF' => 'Official audio file webpage',
		'WAR' => 'Official artist/performer webpage',
		'WAS' => 'Official audio source webpage',
		'WCM' => 'Commercial information',
		'WCP' => 'Copyright/Legal information',
		'WPB' => 'Publishers official webpage',
		'WXX' => 'User defined URL link frame',

		'ITU' => 'iTunes?',
		'PCS' => 'Podcast?',
		'TDR' => 'Release date',
		'TDS' => '?',
		'TID' => '?',
		'WFD' => '?',
		'CM1' => '?',

		// 2.3 & 2.4
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
			'tagInfo'  => $this::TAG_INFO[$tagName] ?? '',
			'encoding' => $this->encoding
		] + $parsedFrame;
	}

}
