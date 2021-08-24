<?php

namespace Groundwork\Database\WhereStatements;

class WhereGroupChange
{
    /**
     * @var int The new group depth
     */
    public int $depth = 0;

    /**
     * @var string|null The join mode ('AND' or 'OR')
     */
    public ?string $mode = null;

    /**
     * WhereGroupChange constructor.
     *
     * @param int         $depth The new depth
     * @param string|null $mode  The join mode
     */
    public function __construct(int $depth = 0, string $mode = null)
    {
        $this->depth = $depth;
        $this->mode = $mode;
    }
}