<?php
/**
 * @created      09.05.2021
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2021 smiley
 * @license      MIT
 */

namespace chillerlan\ID3TagExamples;

use chillerlan\ID3Tag\ID3;
use FilesystemIterator, RecursiveDirectoryIterator, RecursiveIteratorIterator;
use function file_exists, mkdir, var_dump;

require_once __DIR__.'/../vendor/autoload.php';

// replace $librarydir with the path to your mp3 library
$librarydir = __DIR__.'/../tests/files/id3v1';
$imagedir   = __DIR__.'/img/';

if(!file_exists($imagedir)){
	mkdir($imagedir);
}

$iterator = new RecursiveDirectoryIterator($librarydir, FilesystemIterator::CURRENT_AS_SELF | FilesystemIterator::SKIP_DOTS);
$id3      = new ID3;

foreach(new RecursiveIteratorIterator($iterator) as $path => $file){

	if(!$file->isReadable() || $file->getExtension() !== 'mp3'){
		continue;
	}

	$data = $id3->read($path);

	if($data->id3v2 === null){
		// no v2 tag, log event if necessary...
		continue;
	}

	// prepare some values for a database insert
	$values = [
		'filepath' => $data->filename,
		'artist'   => '',
		'album'    => '',
		'title'    => '',
		'image'    => '',
	];

	foreach($data->id3v2 as $frame){
		$name = $frame['name'];

		// get some basic info
		if($name === 'TP1' || $name === 'TPE1'){
			$values['artist'] = $frame['content'];
		}

		if($name === 'TAL' || $name === 'TALB'){
			$values['album'] = $frame['content'];
		}

		if($name === 'TT2' || $name === 'TIT2'){
			$values['title'] = $frame['content'];
		}

		// ...

		if($name === 'PIC' || $name === 'APIC'){

			// no image data, skip
			if(empty($frame['content'])){
				continue;
			}

			// determine filename
			$imagefile = $imagedir.$frame['hash'].'.'.$frame['format'];

			$values['image'] = $frame['hash'];

			if(file_exists($imagefile)){
				continue;
			}

			$size = getimagesizefromstring($frame['content']);

			if(!$size){
				// log getimagesize error...
			}

			$metadata = [
				'hash'   => $frame['hash'],
				'format' => $frame['format'],
				'size_h' => $size[0] ?? 0,
				'size_w' => $size[1] ?? 0,
				// ...
			];

			// save the image
			file_put_contents($imagefile, $frame['content']);

			// insert image metadata into image_table
			var_dump($metadata);
		}

	}

	// insert id3 tag data into id3_table
	var_dump($values);
}
