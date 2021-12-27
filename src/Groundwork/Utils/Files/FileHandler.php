<?php

namespace Groundwork\Utils\Files;

use Groundwork\Utils\Str;
use Groundwork\Utils\Table;

abstract class FileHandler
{

    /**
     * Scans a given directory for files and other directories. Applies an optional text-based filter as well.
     *
     * @param string      $directory
     * @param string|null $filter
     *
     * @return Table
     */
    public static function scan(string $directory, string $filter = null) : Table
    {
        $files = scandir($directory);
        $result = table();

        foreach($files as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }

            if (! is_null($filter)) {
                if (! Str::contains($file, $filter)) {
                    continue;
                }
            }

            $path = $directory . '/' . $file;

            $result->push(new FileInfo($path, true));
        }

        return $result;
    }

    /**
     * Returns whether the given directory or file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function exists(string $path) : bool
    {
        return file_exists($path);
    }
}