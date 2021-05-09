# php-id3tag

An [id3 tag](https://id3.org/) reader.

[![PHP Version Support][php-badge]][php]
[![version][packagist-badge]][packagist]
[![license][license-badge]][license]
[![Coverage][coverage-badge]][coverage]
[![Scrunitizer][scrutinizer-badge]][scrutinizer]
[![Packagist downloads][downloads-badge]][downloads]<br/>
[![Continuous Integration][gh-action-badge]][gh-action]

[php-badge]: https://img.shields.io/packagist/php-v/chillerlan/php-id3tag?logo=php&color=8892BF
[php]: https://www.php.net/supported-versions.php
[packagist-badge]: https://img.shields.io/packagist/v/chillerlan/php-id3tag.svg?logo=packagist
[packagist]: https://packagist.org/packages/chillerlan/php-id3tag
[license-badge]: https://img.shields.io/github/license/chillerlan/php-id3tag.svg
[license]: https://github.com/chillerlan/php-id3tag/blob/main/LICENSE
[coverage-badge]: https://img.shields.io/codecov/c/github/chillerlan/php-id3tag.svg?logo=codecov
[coverage]: https://codecov.io/github/chillerlan/php-id3tag
[scrutinizer-badge]: https://img.shields.io/scrutinizer/g/chillerlan/php-id3tag.svg?logo=scrutinizer
[scrutinizer]: https://scrutinizer-ci.com/g/chillerlan/php-id3tag
[downloads-badge]: https://img.shields.io/packagist/dt/chillerlan/php-id3tag.svg?logo=packagist
[downloads]: https://packagist.org/packages/chillerlan/php-id3tag/stats
[gh-action-badge]: https://github.com/chillerlan/php-id3tag/workflows/Continuous%20Integration/badge.svg
[gh-action]: https://github.com/chillerlan/php-id3tag/actions

# Documentation

## Requirements
- PHP 7.4+

## Installation
**requires [composer](https://getcomposer.org)**

### *composer.json*
(note: replace `dev-main` with a [version boundary](https://getcomposer.org/doc/articles/versions.md#summary))
```json
{
	"require": {
		"php": "^7.4",
		"chillerlan/php-id3tag": "dev-main"
	}
}
```

Profit!

## Usage

```php
use chillerlan\ID3Tag\ID3;

$id3 = new ID3;

// ID3::read() returns an ID3Data object
$data = $id3->read('/path/to/my.mp3');

if($data->id3v2 !== null){
	foreach($data->id3v2 as $tagdata){
		// ...
		var_dump($tagdata);
	}
}

```
