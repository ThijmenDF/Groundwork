<?php

namespace Groundwork\Database\Relations\Interfaces;

use Groundwork\Database\Model;

interface Savable
{
    public function save(Model $model) : bool;
}