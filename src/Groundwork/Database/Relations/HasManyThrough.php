<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Table;
use Groundwork\Database\Model;

class HasManyThrough extends Relation
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

        /** @var Table $through */
        $through = $this->through::where(
            $this->firstForeign,
            $this->parent->{$this->firstOwner ?? $this->parent->getIdentifierKey()}
        )
            ->get();

        $this->whereIn(
            $this->secondForeign,
            $through->map(fn (Model $item) => $item->{$this->secondOwner ?? $item->getIdentifierKey()})
                ->all()
        );
    }
}