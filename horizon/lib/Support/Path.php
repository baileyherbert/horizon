<?php

namespace Horizon\Support;

use Horizon\Support\Arr;
use Horizon\Support\Str;

class Path
{

    /**
     * Joins two or more paths together, separating them with the system's directory separator.
     *
     * @param string $path,...
     * @return string
     */
    public static function join()
    {
        $paths = func_get_args();
        $path = array_shift($paths);

        foreach ($paths as $i => $segment) {
            while (strlen($segment) > 0 && substr($segment, 0, 1) == '\\' || substr($segment, 0, 1) == '/') {
				$segment = substr($segment, 1);
            }

            if (empty($segment)) {
                continue;
            }

            $path = rtrim($path, '/\\');
            $path .= DIRECTORY_SEPARATOR . $segment;
        }

        $path = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $path);

        return static::makeProperPath($path);
    }

    /**
     * Joins two or more paths together and resolves them.
     *
     * @param string $path,...
     * @return string
     */
    public static function resolve()
    {
        $paths = func_get_args();
        $path = array_shift($paths);

        foreach ($paths as $segment) {
            if (!preg_match('/^([\/\\\]|[A-Z]:\\\)/', $segment)) {
                if ($segment == '.') continue;
                if (Str::startsWith($segment, './')) $segment = substr($segment, 2);

                $path = rtrim($path, '/\\');
                $path .= DIRECTORY_SEPARATOR . $segment;
            }
            else {
                $path = $segment;
            }
        }

        $path = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $path);

        return static::makeProperPath($path);
    }

    protected static function makeProperPath($path)
    {
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $i = 0;

        while (isset($parts[$i])) {
            $part = $parts[$i];
            $previous = isset($parts[$i - 1]) ? ($i - 1) : null;

            if ($part == '.') {
                unset($parts[$i]);
                continue;
            }

            if ($part == '..' && $previous !== null) {
                unset($parts[$previous]);
                unset($parts[$i]);
                $i--;
                continue;
            }

            if ($part == '' && $i != 0) {
                unset($parts[$i]);
                continue;
            }

            $i++;
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * Gets the name of the resource which a path points to. If $extension is supplied, any file names with that
     * extension will be trimmed.
     *
     * @param string $fileName
     * @param string|null $extension
     * @return string
     */
    public static function basename($fileName, $extension = null)
    {
        return basename($fileName, $extension);
    }

    /**
     * Calculates and returns the relative path required to go from $currentPath to $targetPath in a browser
     * link.
     *
     * @param string $currentPath
     * @param string $targetPath
     * @return string
     */
    public static function getRelative($currentPath, $targetPath, $subdirectory = '')
    {
        $currentPath = trim($currentPath);
        $targetPath = trim($targetPath);

        // Parse the current and target paths into an array of directories and files
        $path = static::parse($currentPath);
        $target = static::parse($targetPath);

        // For an identical path, return it without any modification
        if ($currentPath == $targetPath) {
			if (!Str::startsWith($targetPath, '/' . $subdirectory)) {
				$targetPath = '/' . ltrim($subdirectory . $targetPath, '/');
			}

            return $targetPath;
        }

        // For an absolute path, return it without any modification
        if (Str::startsWith($targetPath, '/')) {
            $newPath = $subdirectory . $targetPath;

            if (!empty($subdirectory)) {
                $newPath = '/' . $newPath;
            }

            return $newPath;
        }

        // Loop through the target path nodes and apply them to the current path
        foreach ($target as $node) {
            if ($node->name == '.' && !empty($path) && Arr::last($path)->file) {
                array_pop($path);
            }
            elseif ($node->name == '..') {
                if (!empty($path)) {
                    if (\Horizon\Support\Arr::last($path)->file) {
                        array_pop($path);
                    }

                    if (!empty($path)) {
                        array_pop($path);
                    }
                }
            }
            else {
                if (!empty($path)) {
                    if (Arr::last($path)->file) {
                        array_pop($path);
                    }
                }

                $path[] = $node;
            }
        }

        // Return the compiled path
        return static::compile($path);
    }

    /**
     * Parses the provided path into an array of path information, consisting of a (string) name, (bool) file, and
     * (bool) directory as an object.
     *
     * @param string $path
     * @return object[]
     */
    public static function parse($path)
    {
        if (strpos($path, '?') !== false) {
            $path = substr($path, 0, strpos($path, '?'));
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        $parts = array();
        $exploded = explode('/', $path);

        foreach ($exploded as $i => $name) {
            $file = !($i < count($exploded) - 1);

            if ($i == count($exploded) - 2 && empty($exploded[count($exploded) - 1])) {
                $file = false;
            }

            if (empty($name)) {
                continue;
            }

            if ($name == '..' || $name == '.') {
                $file = false;
            }

            $parts[] = (object) array(
                'name' => $name,
                'file' => $file,
                'directory' => !$file
            );
        }

        return $parts;
    }

    /**
     * Compiles an array of path nodes (from the parse() method) back into a path. The returned path always begins
     * with a forward slash and will only end with a forward slash if the last node is a directory.
     *
     * @param array $nodes
     * @return string
     */
    public static function compile(array $nodes)
    {
        $path = '/';

        foreach ($nodes as $node) {
            $path .= $node->name;

            if ($node->directory) {
                $path .= '/';
            }
        }

        return $path;
    }

}
