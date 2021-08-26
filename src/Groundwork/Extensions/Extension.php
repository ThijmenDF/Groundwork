<?php

namespace Groundwork\Extensions;

interface Extension
{
    /**
     * Called when the extension is loaded. The passed variable is the parent object that can get extended.
     *
     * @param object $object
     *
     * @return void
     * @noinspection PhpMissingParamTypeInspection
     */
    public function build($object) : void;
}