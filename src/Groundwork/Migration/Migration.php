<?php

namespace Groundwork\Migration;

interface Migration
{
    public function up() : bool;
    public function down() : bool;
}