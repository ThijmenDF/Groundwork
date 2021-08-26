<?php

namespace Groundwork\Utils\Files;

use Groundwork\Utils\Table;

class FileInfo
{
    private string $path;
    private bool $isDir;
    private Table $files;

    public function __construct(string $path, bool $isDir = null)
    {
        $this->path = $path;
        $this->isDir = $isDir ?? is_dir($path);
    }

    /**
     * Returns a table of all files and folders within this directory.
     *
     * @return Table|null
     */
    public function files() : ?Table
    {
        if (!$this->isDir) {
            return null;
        }

        if (!isset($this->files)) {
            $this->loadFiles();
        }

        return $this->files;
    }

    /**
     * Loads the files within this directory.
     */
    private function loadFiles()
    {
        $this->files = FileHandler::scan($this->path);
    }

    /**
     * Returns whether this is a directory or not.
     *
     * @return bool
     */
    public function isDir() : bool
    {
        return $this->isDir;
    }

    /**
     * Returns the absolute path of the file or directory.
     *
     * @return string
     */
    public function path() : string
    {
        return $this->path;
    }

    /**
     * Returns the extension.
     *
     * @return string
     */
    public function ext() : string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Returns the file or directory name, with extension.
     *
     * @return string
     */
    public function name() : string
    {
        return pathinfo($this->path, PATHINFO_BASENAME);
    }
}