<?php
/**
 * Created by PhpStorm.
 * User: sjhc1170
 * Date: 12/04/2018
 * Time: 11:29
 */

namespace Iriven\Plugins\FileSystem;

/**
 * Class FileSystem
 * @package Iriven\Plugins\FileSystem
 */
class FileSystem
{
    /**
     * Appends content to an existing file.
     *
     * @param $file
     * @param $content
     * @param null $context
     * @return bool
     */
    public static function append($file, $content, $context = null)
    {
        return self::write($file,$content,FILE_APPEND | LOCK_EX, $context);
    }

    /**
     * Extract the trailing name component from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public static function basename($path)
    {
        $path = self::pathname($path);
        $output = null;
        try
        {
            if(!self::exists($path))
                throw new \RuntimeException(sprintf(
                    'File or directory "%s" does not exist.',
                    $path
                ));
            $output = pathinfo($path, PATHINFO_BASENAME);
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::basename : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Change the group of an array of files or directories.
     *
     * @param $file
     * @param $group
     * @param bool $recursive
     * @return bool|mixed
     */
    public static function chgrp($file, $group, $recursive = false)
    {
        $output  = false;
        $file = self::pathname($file);
        try{
            if(!self::exists($file))
                throw new \RuntimeException(sprintf(
                    'File or directory "%s" does not exist.',
                    $file
                ));
            if (is_link($file) && function_exists('lchgrp'))
            {
                if (true !== @lchgrp($file, $group))
                    throw new \RuntimeException(sprintf('Failed to chgrp file "%s" to %s.', $file, $group));
            }
            else {
                if (true !== @chgrp($file, $group))
                    throw new \RuntimeException(sprintf('Failed to chgrp file "%s".', $file, $group));
            }
            if ($recursive && self::isDir($file) && !is_link($file))
            {
                $scan = self::scandir($file, null, false, false);
                foreach ($scan as $path)
                    if (!$output = call_user_func_array([__CLASS__, __METHOD__], [$path, $group, false])) break;
            }
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::chgrp : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Change mode for files or directories.
     *
     * @param $file
     * @param $mode
     * @param int $umask
     * @param bool $recursive
     * @return bool|mixed
     */
    public static function chmod( $file,$mode,$recursive=false,$umask=null)
    {
        $output  = false;
        $file = self::pathname($file);
        try
        {
            $umask OR $umask = umask();
            if(!self::exists($file))
                throw new \RuntimeException(sprintf(
                    'File or directory "%s" does not exist.',
                    $file
                ));
            if (!@chmod($file, $mode & ~$umask))
                throw new \RuntimeException(sprintf('Failed to chmod file "%s".', $file));
            $output  = true;
            if ($recursive && self::isDir($file) && !is_link($file))
            {
                $scan = self::scandir($file, null, true, false);
                foreach ($scan as $path)
                    if (!$output = call_user_func_array([__CLASS__, __METHOD__], [$path, $mode, false, $umask])) break;
            }
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::chmod : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     *
     * @param $file
     * @param $user
     * @param null $group
     * @param bool $recursive
     * @return bool
     */
    public static function chown($file, $user, $group = null, $recursive = false)
    {
        $output  = false;
        $file = self::pathname($file);
        try{
            if(!self::exists($file))
                throw new \RuntimeException(sprintf(
                    'File or directory "%s" does not exist.',
                    $file
                ));
            if (is_link($file) && function_exists('lchown'))
            {
                if (true !== @lchown($file, $user))
                    throw new \RuntimeException(sprintf('Failed to chown file "%s" to %s.', $file, $user));
                if($group) self::chgrp($file, $group, $recursive);
            }
            else {
                if (true !== @chown($file, $user))
                    throw new \RuntimeException(sprintf('Failed to chown file "%s".', $file, $user));
                if ($recursive && is_dir($file) && !is_link($file))
                {
                    $scan = self::scandir($file, null, false, false);
                    foreach ($scan as $path)
                    {
                        if (!$output = call_user_func_array([__CLASS__, __METHOD__], [$path, $user, $group, false])) break;
                        if($group) self::chgrp($path, $group, false);
                    }
                }
                else
                    if($group) self::chgrp($file, $group, $recursive);
            }
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::chmod : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Delete all files and sub directories in a given directory
     *
     * @param $directory
     * @return bool
     */
    public static function clean($directory){
        $output  = false;
        $directory = self::pathname($directory);
        try{
            if( !is_dir( $directory ) OR !is_readable($directory))
                throw new \RuntimeException(sprintf(
                    'Unable to read "%s" directory, maybe it does not exist or we don\'t have "read" permission on it.',
                    $directory
                ));
            $mode = \RecursiveIteratorIterator::CHILD_FIRST;
            $fileSystem = new \RecursiveDirectoryIterator( $directory, \FilesystemIterator::SKIP_DOTS );
            if (!$iterator = new \RecursiveIteratorIterator($fileSystem,$mode,\RecursiveIteratorIterator::CATCH_GET_CHILD))
                throw new \RuntimeException(sprintf(
                    'Unable to list directory "%s".',
                    $directory
                ));
            foreach ($iterator as $item)
            {
                $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            $output  = true;
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::clean : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * @param $bytes
     * @param string $unit
     * @param int $precision
     * @return bool|float|int|mixed|string
     */
    public static function convertBytes($bytes, $unit='', $precision=2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $output = false;
        is_int($precision) or $precision = 2;
        $format = '%.'.$precision.'f';
        if(is_numeric($bytes))
        {
            if(!$unit)
            {
                $exp = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
                $unit = $units[$exp];
            }
            else {
                $unit = strtoupper(trim($unit));
                if(!in_array($unit,$units,false))
                    return call_user_func_array([__CLASS__,__METHOD__],[$bytes,null]);
                if($unit[0] == 'B')
                    return sprintf($format .' %s', (float) $bytes, 'Bytes' );
                $exp = array_search($unit,$units);
            }
            $output = (float) $bytes / pow(1024, floor($exp));
            if($unit[0] == 'B')  $unit = 'Bytes';
        }
        return $output ? sprintf($format .' %s', $output, $unit ): $output;
    }
    /**
     * Copy a file, or recursively copy a folder and its contents
     *
     * @param $source
     * @param $destination
     * @param int $permissions
     * @return bool
     */
    public static function copy($source, $destination, $permissions = 0775)
    {
        $output = false;
        try
        {
            $source = self::pathname($source);
            $destination = self::pathname($destination);
            if(!self::exists($source))
                throw new \RuntimeException(sprintf(
                    'Failed to copy "%s" because it does not exist.',
                    $source
                ));
            if (is_link($source))
                return symlink(readlink($source), $destination);
            if (self::isFile($source))
                return copy($source, $destination);
            if (!self::isDir($destination))
                self::mkdir($destination, $permissions);
            $directory = dir($source);
            while (false !== $item = $directory->read())
            {
                if(in_array(basename($item), ['.', '..'])) continue;
                call_user_func_array([__CLASS__, __METHOD__],
                    [ $source.self::separator().$item, $destination.self::separator().$item ]);
            }
            $directory->close();
            $output = true;
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::copy : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }

        return $output;
    }
    /**
     * Gets the current working directory
     *
     *
     * @return string|bool the current working directory on success, or false on failure.
     */
    public function cwd()
    {
        return @getcwd();
    }
    /**
     * Extract the parent directory from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public static function dirname($path)
    {
        $path = self::pathname($path);
        $output = null;
        try
        {
            if(!self::exists($path))
                throw new \RuntimeException(sprintf(
                    'File or directory "%s" does not exist.',
                    $path
                ));
            $info = new \SplFileInfo($path);
            $output = dirname($info->getPathname());
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::dirname : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Get statistics about system file folder
     *
     * @param string $path
     *
     * @return array disk_usage (disk_free_space, disk_space_used, disk_space_total, disk_used_percentage)
     *
     */
    public static function diskUsage($path)
    {
        $output = false;
        $path = self::pathname($path);
        if(self::isReadable($path))
        {
            $output = [];
            $df = disk_free_space($path);
            $dt = disk_total_space($path);
            $du = $dt - $df;
            $dp = sprintf('%.2f %s',($du / $dt) * 100, '%');
            $output['filesytem'] = $path;
            $output['free_space'] = self::convertBytes($df);
            $output['space_used'] = self::convertBytes($du);
            $output['total_size'] = self::convertBytes($dt);
            $output['percent_used'] = $dp;
        }
        return $output;
    }

    /**
     * Extract the file extension from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public static function extension($path)
    {
        $path = self::pathname($path);
        $output = null;
        try
        {
            if(!self::exists($path))
                throw new \RuntimeException(sprintf(
                    'File or directory "%s" does not exist.',
                    $path
                ));
            $info = new \SplFileInfo($path);
            $output = $info->getExtension();
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::extension : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Checks the existence of files or directories.
     *
     * @param $file
     * @return bool
     */
    public static function exists( $file )
    {
        $file = self::pathname($file);
        try{
            $maxPathLength = PHP_MAXPATHLEN - 2;
            if (strlen($file) > $maxPathLength)
                throw new \RuntimeException(sprintf('Could not check if file "%s"exist because path length exceeds %d characters.', $file, $maxPathLength));
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::exists : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return file_exists($file) || is_link($file);
    }



    /**
     * Gets file permissions
     *
     * @param string $path Path to the file.
     * @return string Mode of the file (last 4 digits).
     */
    public static function getPerms($path) {
        $path =  self::pathname($path);
        $output = false;
        try{
            if (!self::exists($path))
                throw new \RuntimeException(sprintf('Origin file "%s" does not exists.', $path));
            $info = new \SplFileInfo($path);
            $output = substr( decoct( $info->getPerms() ), -4 );
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::exists : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Returns a file or directory human readable size
     *
     * @param $path
     * @return bool|string
     */
    public static function getSize($path)
    {
        $path = self::pathname($path);
        $size = false;
        try
        {
            if (self::isReadable($path))
            {
                if(self::isDir($path))
                    foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file)
                        $size += $file->getSize();
                if(self::isFile($path))
                    $size = filesize($path);
            }
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::getSize : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $size? self::convertBytes($size) : $size;
    }
    /**
     *
     * @param string $file
     * @return string|false
     */
    public static function group($file)
    {
        $file = self::pathname($file);
        $group = false;
        try
        {
            if (!self::isReadable($file))
                throw new \RuntimeException(sprintf('Origin file "%s" does not exists.', $file));
            if ( $gid = @filegroup($file)){
                if ( ! function_exists('posix_getgrgid') )
                    $group = $gid;
                else
                $group = posix_getgrgid($gid)['name'];
            }
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::group : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $group;
    }
    /**
     * Creates a hard link to a file.
     *
     * @param string $file  The original file
     * @param string $destination The target file(s)
     */
    public static function hardlink($file, $destination)
    {
        $file = self::pathname($file);
        $destination = self::pathname($destination);
        try
        {
            if (!self::exists($file))
                throw new \RuntimeException(sprintf('Origin file "%s" does not exists.', $file));
            if (is_file($destination))
            {
                if (fileinode($file) !== fileinode($destination))
                    self::remove($destination);
            }
            if (true !== @link($file, $destination))
                throw new \RuntimeException(sprintf('Failed to create hard link from "%s" to "%s".', $file, $destination));
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::hardlink : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
    }
    /**
     * @param $directory
     * @return bool
     */
    public static function isDir( $directory )
    {
        $directory = self::pathname($directory);
        if(self::exists($directory))
        {
            return is_dir( $directory );
        }
        return false;
    }

    /**
     * @param $path
     * @return bool
     */
    public static function isExecutable($path){
        $path = self::pathname($path);
        if(self::exists($path))
        {
            return is_executable( $path );
        }
        return false;
    }

    /**
     * @param $file
     * @return bool
     */
    public static function isFile( $file )
    {
        $file = self::pathname($file);
        if(self::exists($file))
        {
            return is_file( $file );
        }
        return false;
    }
    /**
     * @param $file
     * @return bool
     */
    public static function isHidden($file)
    {
        $file = self::pathname($file);
        if(self::exists($file))
        {
            if(basename($file)[0] === '.') return true;
            if(function_exists('exec'))
            {
                $attr = trim(exec('FOR %A IN ("'.$file.'") DO @ECHO %~aA'));
                if($attr[3] === 'h') return true;
            }
        }
        return false;
    }

    /**
     * @param $file
     * @return bool
     */
    public static function isLink($file)
    {
        $file = self::pathname($file);
        if(!self::exists($file)) return false;
        return is_link($file);
    }

    /**
     * Tells whether a file exists and is readable.
     *
     * @param string $file Path to the file
     *
     * @return bool
     */
    public static function isReadable($file)
    {
        $file = self::pathname($file);
        if(!self::exists($file)) return false;
        return is_readable($file);
    }

    /**
     * Tells whether a file exists and is readable.
     *
     * @param string $file Path to the file
     *
     * @return bool
     */
    public static function isWritable($file)
    {
        $file = self::pathname($file);
        if(!self::exists($file)) return false;
        return is_writable($file);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @param  string  $path
     * @return string|false
     */
    public static function mimeType($path)
    {
        $path = self::pathname($path);
        $output = null;
        try
        {
            if(!self::exists($path))
                throw new \RuntimeException(sprintf(
                    'File or directory "%s" does not exist.',
                    $path
                ));
            $output = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::mimeType : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * @param string $directory
     * @param int $mode
     * @return bool
     */
    public static function mkdir($directory, $mode=0775)
    {
        $directory = self::pathname($directory);
        $output = true;
        if(!self::exists($directory))
        {

            if(true !== @mkdir($directory,$mode, true))
            {
                $error = error_get_last();
                $output = false;
                if (!self::isDir($directory)) {
                    // The directory was not created by a concurrent process. Let's throw an exception with a developer friendly error message if we have one
                    if ($error)
                        throw new \RuntimeException(sprintf('Failed to create "%s": %s.', $directory, $error['message']));
                    throw new \RuntimeException(sprintf('Failed to create "%s"', $directory));
                }
            }
        }
        return $output;
    }
    /**
     * Extract the file name from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public static function filename($path)
    {
        $path = self::pathname($path);
        $output = null;
        try
        {
            if(!self::exists($path))
                throw new \RuntimeException(sprintf(
                    'File or directory "%s" does not exist.',
                    $path
                ));
            $output = pathinfo($path, PATHINFO_FILENAME);
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::name : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Gets file owner
     *
     * @param string $file Path to the file.
     * @return string|bool Username of the user or false on error.
     */
    public static function owner($file)
    {
        $file = self::pathname($file);
        $owneruid = @fileowner($file);
        if ( ! $owneruid ) return false;
        if ( ! function_exists('posix_getpwuid') )
           return $owneruid;
        $ownerarray = posix_getpwuid($owneruid);
        return $ownerarray['name'];
    }
    /**
     * return a normalized form of a given path
     *
     * @param $path
     * @param null $relativeTo
     * @return string
     */
    public static function pathname($path, $relativeTo = null) {
        $path = preg_replace('#[/\\\\]+#', self::separator(), $path);
        $path = rtrim($path, self::separator());
        $isAbsolute = stripos(PHP_OS, 'win')===0 ?
            preg_match('/^[A-Za-z]+:/', $path):
            !strncmp($path, self::separator(), 1);
        if (!$isAbsolute)
        {
            if (!$relativeTo) $relativeTo = getcwd();
            $path = $relativeTo.self::separator().$path;
        }
        if (is_link($path) and ($parentPath = realpath(dirname($path))))
            return $parentPath.self::separator().$path;
        if ($realpath = realpath($path))  return $realpath;
        $parts = explode(self::separator(), trim($path, self::separator()));
        while (end($parts) !== false)
        {
            array_pop($parts);
            $attempt = stripos(PHP_OS, 'win')===0 ?
                implode(self::separator(), $parts):
                self::separator().implode(self::separator(), $parts);
            if ($realpaths = realpath($attempt))
            {
                $path = $realpaths.substr($path, strlen($attempt));
                break;
            }
        }
        return $path;
    }
    /**
     * Prepend to a file.
     *
     * @param  string  $path
     * @param  string  $content
     * @return int
     */
    public static function prepend($path, $content)
    {
        $path = self::pathname($path);
        if (self::exists($path))
            return self::write($path, $content.self::read($path));
        return self::write($path, $content);
    }
    /**
     * Get the contents of a file
     *
     * @param $path
     * @param bool $lock
     * @return bool|null|string
     */
    public static function read($path, $lock = false)
    {
        $path = self::pathname($path);
        $output = null;
        try
        {
            if (!self::isFile($path))
                throw new \RuntimeException(sprintf('File does not exist at path "%s".', $path));
            if (!$lock)
            {
                $output = file_get_contents($path);
            }
            else {
                if($handle = fopen($path, 'rb'))
                {
                if (flock($handle, LOCK_SH))
                    {
                        clearstatcache(true, $path);
                        $output = fread($handle, filesize($path) ?: 1);
                        flock($handle, LOCK_UN);
                    }
                    fclose($handle);
                }
            }
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::read : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;

    }
    /**
     * Resolves links in paths.
     *
     * @param string $path         A filesystem path
     * @param bool   $canonicalize Whether or not to return a canonicalized path
     *
     * @return string|null
     */
    public static function readlink($path, $canonicalize = false)
    {
        $path = self::pathname($path);
        if ($canonicalize)
        {
            if (!self::exists($path))
                return null;
            if ('\\' === self::separator())
                $path = readlink($path);
            return realpath($path);
        }
        if (!is_link($path))  return null;
        if ('\\' === self::separator())
            return realpath($path);
        return readlink($path);
    }

    /***
     * Delete file or directory from file system
     *
     * @param $path
     * @return bool
     */
    public static function remove($path)
    {
        $path = self::pathname($path);
        if (self::exists($path))
        {
            if (self::isDir($path) and !is_link($path))
                return self::rmdir($path);
            return @unlink($path);
        }
        return false;
    }

    /**
     * Renames a file or a directory.
     *
     * @param string $source    The origin filename or directory
     * @param string $destination    The new filename or directory
     * @param bool $overwrite Whether to overwrite the target if it already exists
     * @return bool
     */
    public static function rename($source, $destination, $overwrite = false)
    {
        $source = self::pathname($source);
        $destination = self::pathname($destination);
        $output = false;
        try
        {
            if(!self::exists($source))
                throw new \RuntimeException(sprintf('Unable to rename "%s" , file or directory does not exist.', $source));
            if (!$overwrite && self::isReadable($destination))
                throw new \RuntimeException(sprintf('Cannot rename because the target "%s" already exists.', $destination));
            if (true !== @rename($source, $destination))
            {
                if (self::isDir($source))
                {
                    self::copy($source, $destination);
                    self::remove($source);
                }
                throw new \RuntimeException(sprintf('Cannot rename "%s" to "%s".', $source, $destination));
            }
            $output = true;
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::rename : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Searches for a given text and replaces the text if found.
     *
     * @param $file
     * @param $search
     * @param $replace
     * @return bool
     */
    public static function replace($file, $search, $replace)
    {
        $file = self::pathname($file);
        $output = false;
        try{
            if(!self::exists($file))
                throw new \RuntimeException(sprintf('Unable to read "%s" file, maybe it does not exist or we don\'t have "read" permission on it.', $file));
            $output = self::write($file, str_replace($search, $replace, self::read($file)));
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::replace : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Recursively deletes files and directories from file system
     *
     * @param $directory
     * @return bool
     */
    public static function rmdir($directory)
    {
        $directory = self::pathname($directory);
        if(!self::clean($directory)) return false;
        return rmdir($directory);
    }

    /**
     * List all files in directory (and sub directories) optionnaly filtered by extension
     *
     * @param $directory
     * @param null $fileExtension
     * @param bool $excludeHidden
     * @param bool $childFirst
     * @return array
     */
    public static function scandir($directory, $fileExtension = null, $excludeHidden=false, $childFirst=true)
    {
        $output  = [];
        $directory = self::pathname($directory);
        try{
            if ( self::isFile( $directory ) )
                $directory = dirname( $directory );
            if( !self::isDir( $directory ) OR !is_readable($directory))
                throw new \RuntimeException(sprintf(
                    'Unable to read "%s" directory, maybe it does not exist or we don\'t have "read" permission on it.',
                    $directory
                ));
            $mode = \RecursiveIteratorIterator::CHILD_FIRST;
            if(!$childFirst)
                $mode = \RecursiveIteratorIterator::SELF_FIRST;
            $fileSystem = new \RecursiveDirectoryIterator( $directory, \RecursiveDirectoryIterator::SKIP_DOTS );
            if (!$iterator = new \RecursiveIteratorIterator($fileSystem,$mode,\RecursiveIteratorIterator::CATCH_GET_CHILD))
                throw new \RuntimeException(sprintf(
                    'Unable to list directory "%s".',
                    $directory
                ));
            foreach ($iterator as $item)
            {
                if (in_array(basename($item), ['.', '..'])) continue;
                if($fileExtension AND pathinfo($item->getFilename(), PATHINFO_EXTENSION) !== $fileExtension) continue;
                if($excludeHidden AND $item->getFilename()[0] === '.') continue;
                $output[] = $item->getPathname();
            }
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::scandir : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * return directory separator
     *
     * @return string
     */
    public static function separator()
    {
        return DIRECTORY_SEPARATOR;
    }

    /**
     * Returns information about a given file or directory
     *
     * @param $path
     * @return array
     */
    public static function stat($path)
    {
        $path =  self::pathname($path);
        $output = [];
        try{
            if( !self::exists( $path ) )
                throw new \RuntimeException(sprintf(
                    'Unable to read "%s" directory, maybe it does not exist or we don\'t have "read" permission on it.',
                    $path
                ));
            $info = new \SplFileInfo($path);
            $stat = new \stdClass();
            foreach ((self::isLink($path)? lstat($path):stat($path)) as $key => $val)
                $stat->$key = $val;
            $output = [
                'type'      => $info->getType(),
                'name'      => $info->getFilename(),
                'path'      => $info->getPathname(),
                'nlink'     => $stat->nlink,
                'size'      => self::getSize($info->getPathname()),
                'blksize'   => $stat->blksize,
                'blocks'    => $stat->blocks,
                'dev'       => $stat->dev,
                'rdev'      => $stat->rdev ,
                'mode'      => $info->getPerms(),
                'perms'     => self::getPerms($info->getPathname()),
                'uid'       => $info->getOwner() ,
                'gid'       => $info->getGroup() ,
                'owner'     => self::owner($info->getPathname()),
                'group'     => self::group($info->getPathname()),
                'inode'     => $info->getInode(),
                'atime'     => $info->getATime(),
                'mtime'     => $info->getMTime(),
                'ctime'     => $info->getCTime(),
                'readable'  => self::isReadable($info->getPathname())?'true':'false',
                'writable'  => self::isWritable($info->getPathname())?'true':'false',
                'executable'=> self::isExecutable($info->getPathname())?'true':'false'
            ];
            $output['accessed']   = date('M j, Y h:i:s',$output['atime']);
            $output['modified']   = date('M j, Y h:i:s',$output['mtime']);
            $output['created']   = date('M j, Y h:i:s',$output['ctime']);
            if($output['type'] == 'file')
                $output['extension'] = $info->getExtension();
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::stat : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }
    /**
     * Creates a symbolic link or copy a directory.
     *
     * @param string $directory     The origin directory path
     * @param string $link     The symbolic link name
     * @param bool   $copyOnWindows Whether to copy files if on Windows
     * @return bool
     */
    public static function symlink($directory, $link, $copyOnWindows = false)
    {
        $directory = self::pathname($directory);
        $link = self::pathname($link);
        $output = false;
        try
        {
            if ('\\' === self::separator())
            {
                if ($copyOnWindows)
                    return self::copy($directory, $link);
            }
            self::mkdir(dirname($link));
            if (is_link($link))
            {
                if (readlink($link) != $directory)
                    self::remove($link);
                else
                    $output = true;
            }
            if (!$output && true !== @symlink($directory, $link))
                throw new \RuntimeException(sprintf('Failed to create symbolic link from "%s" to "%s".', $directory, $link));
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::symlink : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }
    /**
     * Sets access and modification time of file.
     *
     * @param string $file A filename
     * @param int|null $time  The touch time as a Unix timestamp
     * @param int|null $atime The access time as a Unix timestamp
     * @return bool
     */
    public static function touch($file, $time = null, $atime = null)
    {
        $file = self::pathname($file);
        $output = false;
        try
        {
            if ($time == 0) $time = time();
            if ($atime == 0) $atime = time();
            if (!self::exists($file)) self::write($file,'');
            if (!$touch = $time ? @touch($file, $time, $atime) : @touch($file))
                throw new \RuntimeException(sprintf('Failed to touch "%s".', $file));
            $output = true;
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::write : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Get the file type of a given file.
     *
     * @param  string  $path
     * @return string
     */
    public static function type($path)
    {
        $path = self::pathname($path);
        $output = null;
        try
        {
            if(!self::exists($path))
                throw new \RuntimeException(sprintf(
                    'File or directory "%s" does not exist.',
                    $path
                ));
            $output = filetype($path);
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::type : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }

    /**
     * create file with content, and create folder structure if doesn't exist
     *
     * @param $file
     * @param $content
     * @param int $flags
     * @param null $context
     * @return bool
     */
    public static function write($file,$content, $flags = 0, $context = null)
    {
        $output = false;
        $file = self::pathname($file);
        try{
            $folder = dirname($file);
            if (!self::exists($file))
            {
                if (!self::isDir($folder))
                {
                    if(self::mkdir($folder))
                        throw new \RuntimeException(sprintf(
                            'Unable to create "%s" directory . Permission Denied!',
                            $folder
                        ));
                }
            }
            if (!self::isWritable($folder))
                throw new \RuntimeException(sprintf('Unable to write to the "%s" directory.', $folder));

            if(!file_put_contents($file, $content, $flags, $context))
                throw new \RuntimeException(sprintf(
                    'Failed to write in file "%s".',
                    $file
                ));
            $output = true;
        }
        catch (\RuntimeException $runtimeException)
        {
            error_log('FileSystem::write : '.$runtimeException->getMessage());
            trigger_error($runtimeException->getMessage(),E_USER_ERROR);
        }
        return $output;
    }
}
