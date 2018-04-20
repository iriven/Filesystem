# Filesystem
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XDCFPNTKUC4TU)

[Iriven Php Filesystem:](https://github.com/iriven/PhpLogger) A helper class for filesystem operations. This class uses PHP Iterators in order to dynamically and stably provide filesystem operation access

## Goals

* Have a generic API for handling common tasks across files and folders.
* Have consistent output which you can rely on.
* Integrate well with all PHP packages/frameworks.
* Make it easy to test your filesystem interactions.
* Support streams for big file handling.

## Overview

The following methods are provided:
```php
public static function appendToFile($file, $content, $context = null)
public static function basename($path)
public static function chgrp($file, $group, $recursive = false)
public static function chmod( $file,$mode,$recursive=false,$umask=null)
public static function chown($file, $user, $group = null, $recursive = false)
public static function clean($directory)
public static function convertBytes($bytes, $unit='', $precision=2)
public static function copy($source, $destination, $permissions = 0775)
public static function cwd()
public static function dirname($path)
public static function diskUsage($path)
public static function extension($path)
public static function exists( $path )
public static function filename($path)
public static function getPerms($path)
public static function getSize($path, $humanReadable = true)
public static function group($file)
public static function hardlink($file, $destination)
public static function insertIntoFile($file, $marker, $data, $after = true)
public static function isDir($path)
public static function isExecutable($path)
public static function isFile($path)
public static function isHidden($path)
public static function isLink($path)
public static function isReadable($path)
public static function isWritable($path)
public static function mimeType($path)
public static function mkdir($directory, $mode=0775)
public static function move($source, $destination, $overwrite = false)
public static function owner($file)
public static function pathname($path, $relativeTo = null)
public static function prependToFile($path, $content)
public static function readFile($path, $lock = false)
public static function readlink($path, $canonicalize = false)
public static function remove($path)
public static function rename($source, $destination, $overwrite = false)
public static function replaceInFile($file, $search, $replace)
public static function rmdir($directory)
public static function scandir($directory, $fileExtension = null, $excludeHidden=false, $childFirst=true)
public static function separator()
public static function stat($path)
public static function symlink($directory, $link, $copyOnWindows = false)
public static function touch($file, $time = null, $atime = null)
public static function type($path)
public static function writeFile($file,$content, $flags = 0, $context = null)

```
## Installation

The class can be used standalone, however it's recommended to install via Composer:
```php
composer require iriven/Filesystem
```
If using Composer, just do something like:
```php
use \Iriven\Plugins\Filesystem\FileSystem;
include 'path/to/vendor/autoload.php';
```
Otherwise, simply include the class:
```php
use \Iriven\Plugins\Filesystem\FileSystem;
include 'path/to/FileSystem.php';
```
## Authors

* **Alfred TCHONDJO** - *Project Initiator* - [iriven France](https://www.facebook.com/Tchalf)

## License

This project is licensed under the GNU General Public License V3 - see the [LICENSE](LICENSE) file for details

## Donation

If this project help you reduce time to develop, you can give me a cup of coffee :)

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XDCFPNTKUC4TU)

## Disclaimer

If you use this library in your project please add a backlink to this page by this code.

```html

<a href="https://github.com/iriven/Filesystem" target="_blank">This Project Uses Alfred's TCHONDJO  Filesystem Library.</a>
```
## Issues Repport
Repport issues [Here](https://github.com/iriven/Filesystem/issues)

## Security
If you discover any security related issues, please email iriven@yahoo.fr instead of using the issue tracker.

## Enjoy
Oh and if you've come down this far, you might as well follow me on [twitter](https://twitter.com/IrivenFrance).
