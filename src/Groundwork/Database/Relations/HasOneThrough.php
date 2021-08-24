<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Table;
use Groundwork\Database\Model;

class HasOneThrough extends Relation
{
    protected string $through;
    protected string $firstForeign;
    protected string $secondForeign;
    protected ?string $firstOwner;
    protected ?string $secondOwner;

    public function __construct(Model $parent, string $related, string $through, string $firstForeign, string $secondForeign, string $firstOwner = null, string $secondOwner = null)
    {
        $this->through = $through;
        $this->firstForeign = $firstForeign;
        $this->secondForeign = $secondForeign;
        $this->firstOwner = $firstOwner;
        $this->secondOwner = $secondOwner;

        parent::__construct($parent, new $related);
   }

    public function setQuery()
    {
        // @todo: transform this into a sub-query once those are implementable in the Query class.

        /** @var Model $through */
        $through = $this->through::where(
            $this->firstForeign,
            $this->parent->{$this->firstOwner ?? $this->parent->getIdentifierKey()}
        )
            ->first();

        if (is_null($through)) {
            $this->whereIn($this->secondForeign, [])
                ->limit();
            return;
        }

        $this->where(
            $this->secondForeign,
            $through->{$this->secondOwner ?? $through->getIdentifierKey()}
        )
            ->limit(); // it's called 'hasONE' after all.
    }

    public function processResult(Table $results) : ?Model
    {
        return $results->first();
    }
}