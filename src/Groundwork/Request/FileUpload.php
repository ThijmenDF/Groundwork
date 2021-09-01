<?php

namespace Groundwork\Request;

use Groundwork\Utils\Table;
use Symfony\Component\Mime\MimeTypes;

class FileUpload
{
    /**
     * The filename from the browser's end.
     *
     * @var string
     */
    protected string $name;

    /**
     * The MIME type, from the browser's end. Unsafe to rely on this information.
     *
     * @var string
     */
    protected string $type;

    /**
     * The file size in bytes.
     *
     * @var int
     */
    protected int $size;

    /**
     * The full path to the temporary file, where it's stored for the request. It'll be deleted after the request has
     * been processed.
     *
     * @var string
     */
    protected string $tmpName;

    /**
     * The error code. If 0, there's no error.
     *
     * @var int
     */
    protected int $error;

    /**
     * The name of the input field this file was uploaded in.
     *
     * @var string
     */
    protected string $inputName;

    /**
     * Creates a new FileUpload instance from an upload global.
     *
     * @param array  $fileData  The array of data from the $_FILES global.
     * @param string $inputName The key under which the file was uploaded.
     *                          e.g. for html: `... name='name' type='file' ...`
     */
    public function __construct(array $fileData, string $inputName)
    {
        $this->name = (string) $fileData['name'];
        //$this->type = (string) $fileData['type']; // disable this as it can be spoofed though the browser.
        $this->size = (int) $fileData['size'];
        $this->tmpName = (string) $fileData['tmp_name'];
        $this->error = (int) $fileData['error'];
        $this->inputName = $inputName;
    }

    /**
     * Returns whether there was an error uploading.
     *
     * @return bool
     */
    public function hasError() : bool
    {
        return $this->error !== UPLOAD_ERR_OK;
    }

    /**
     * Gets the error code. see UPLOAD_ERR_ constants.
     *
     * @return int
     */
    public function error() : int
    {
        return $this->error;
    }

    /**
     * Gets the file size in bytes.
     *
     * @return int
     */
    public function size() : int
    {
        return $this->size;
    }

    /**
     * Attempts to get the file extension.
     *
     * @return string|null
     */
    public function ext() : ?string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    /**
     * Guesses the mime type.
     *
     * @return string|null
     */
    public function type() : ?string
    {
        if (isset($this->type)) {
            // Return the already guessed type.
            return $this->type;
        }

        $mimeTypes = new MimeTypes();
        $this->type = $mimeTypes->guessMimeType($this->tmpName);

        return $this->type;
    }

    /**
     * Returns the file input field name that this file was uploaded with.
     *
     * @return string
     */
    public function inputName() : string
    {
        return $this->inputName;
    }

    /**
     * Returns the file name as it was called on the client's system.
     *
     * @return string
     */
    public function name() : string
    {
        return $this->name;
    }

    /**
     * Gets the path of the temporary file location.
     *
     * @return string
     */
    public function tmpName() : string
    {
        return $this->tmpName;
    }

    /**
     * Saves the file to the given location.
     *
     * @param string $path
     *
     * @return bool
     */
    public function save(string $path) : bool
    {
        return move_uploaded_file($this->tmpName, $path);
    }

    /**
     * Creates a list of new instances of `FileUpload` based on the `$_FILES` global.
     *
     * Supports the HTML array feature, regardless on how badly it's implemented on PHP's side...
     *
     * @return Table
     */
    public static function createFromGlobal() : Table
    {
        $instances = table();

        foreach ($_FILES as $name => $file) {

            if (is_array($file['name'])) {
                // for array uploads
                for ($i = 0; $i < count($file['name']); $i++) {
                    // create a new array
                    $object = [];
                    // for every key in the original $file,
                    foreach ($file as $key => $items) {
                        // pick the ($i)nth item from its internal array and save it to the object.
                        $object[$key] = $items[$i];
                    }
                    // add the object to the instances list.
                    $instances->push(new static($object, $name));
                }
            } else {
                // simply add it as normal.
                $instances->push(new static($file, $name));
            }
        }

        return $instances;
    }
}