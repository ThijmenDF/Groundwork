<?php

namespace Groundwork\Database\Pagination;

use Groundwork\Database\Query;
use Groundwork\Database\Table;
use Groundwork\Exceptions\Database\QueryException;
use LogicException;

class Pagination
{
    public int $total = 0;
    public int $perPage = 15;
    public int $currentPage = 1;
    public int $lastPage = 1;

    /** @var callable */
    protected $urlHandler;

    protected Query $query;

    public function __construct(Query $query, int $perPage = 15, int $currentPage = null, callable $urlHandler = null)
    {
        $this->perPage = $perPage;
        if (is_null($currentPage)) {
            // check the route params for any potential ?page parameters.
            $page = request()->get('page');

            if (is_numeric($page)) {
                $this->currentPage = (int) $page;
            }
        } else {
            $this->currentPage = $currentPage;
        }

        $this->urlHandler = $urlHandler;

        $this->query = $query;

        $this->recount();

        $this->query->limit($this->perPage);
    }

    /**
     * Creates a table with total, per_page, current_page, last_page and the data.
     *
     * @param Table $data
     *
     * @return PaginatedResult
     */
    protected function make(Table $data) : PaginatedResult
    {
        return new PaginatedResult(
            $this->total,
            $this->perPage,
            $this->currentPage,
            $this->lastPage,
            $data,
            [$this, 'generateUrlForPage']
        );
    }

    /**
     * Attempts to fetch the given page from the database and returns a formatted table with all the parameters.
     *
     * @param int|null $page Gets the given page, or the current page.
     *
     * @return PaginatedResult
     * @throws QueryException
     */
    public function get(int $page = null) : PaginatedResult
    {
        $this->currentPage = clamp($page ?? $this->currentPage, 1, $this->lastPage);

        $this->query->offset(($this->currentPage - 1) * $this->perPage);

        return $this->make($this->query->get());
    }

    /**
     * Attempts to fetch the next page.
     *
     * @return PaginatedResult
     * @throws QueryException
     */
    public function next() : PaginatedResult
    {
        if ($this->currentPage === $this->lastPage) {
            throw new LogicException('Cannot fetch the next page (current page is ' . $this->currentPage . ')');
        }

        return $this->get($this->currentPage+1);
    }

    /**
     * Attempts to fetch the previous page.
     *
     * @return PaginatedResult
     * @throws QueryException
     */
    public function previous() : PaginatedResult
    {
        if ($this->currentPage === 1) {
            throw new LogicException('Cannot fetch the previous page (current page is 1)');
        }

        return $this->get($this->currentPage-1);
    }

    /**
     * Attempts to fetch the first page.
     *
     * @return PaginatedResult
     * @throws QueryException
     */
    public function first() : PaginatedResult
    {
        return $this->get();
    }

    /**
     * Attempts to fetch the last page.
     *
     * @return PaginatedResult
     * @throws QueryException
     */
    public function last() : PaginatedResult
    {
        return $this->get($this->lastPage);
    }

    /**
     * Builds a list of clickable links. PaginationLinks can be cast to string.
     *
     * @return PaginationLinks
     */
    public function links() : PaginationLinks
    {
        return new PaginationLinks($this->lastPage, $this->currentPage, [$this, 'generateUrlForPage'] );
    }

    /**
     * Generates a URL for a specific page.
     *
     * @param int $page
     *
     * @return string
     */
    public function generateUrlForPage(int $page) : string
    {
        if ($this->urlHandler) {
            return ($this->urlHandler)($page);
        }
        return '?page=' . $page;
    }


    /**
     * Runs a 'count' query on a copy of the query and updates the total and lastPage fields
     */
    protected function recount()
    {
        // Runs a 'count' query on a copy of the query
        $clone = clone $this->query;

        // reset the limit and offsets
        $clone->limit(-1)->offset();

        // perform the clone operation
        $this->total = $clone->count();
        $this->lastPage = ceil($this->total / $this->perPage);
    }
}