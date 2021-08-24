<?php

namespace Groundwork\Database\Pagination;

use Exception;
use Groundwork\Utils\Table;

class PaginationLinks
{
    protected int $lastPage;
    protected int $currentPage;
    private $route;

    /**
     * @var int How many page links may be visible at one time before it starts to cut pages off. This indicates the
     *          amount of links on both sides (6 means 3 on either side of the current page)
     */
    protected int $spliceThreshold = 6;

    /**
     * Creates a new instance of PaginationLinks.
     *
     * @param int            $lastPage    The number of the last page (count + 1)
     * @param int            $currentPage The number of the current page (index + 1)
     * @param callable|array $route       A method to call to get a route
     */
    public function __construct(int $lastPage, int $currentPage, $route)
    {
        $this->lastPage = $lastPage;
        $this->currentPage = $currentPage;
        $this->route = $route;
    }

    /**
     * Sets the new splice threshold.
     *
     * @param int $threshold
     *
     * @return self
     */
    public function setSpliceThreshold(int $threshold) : self
    {
        $this->spliceThreshold = $threshold;

        return $this;
    }

    /**
     * Renders the view
     *
     * @return string
     */
    public function __toString() : string
    {
        try {
            return view('Groundwork/Pagination/Base.html.twig', [
                'lastPage' => $this->lastPage,
                'currentPage' => $this->currentPage,
                'pages' => $this->generatePages()
            ])->handle();
        } catch (Exception $exception) {
            return 'Error generating links!';
        }
    }

    /**
     * Generates each button (first, prev, pages, next, last)
     *
     * @return PaginationButton[]
     */
    protected function generatePages() : array
    {
        $links = table();

        $startNumber = $this->getStartNumber();
        $endNumber = $this->getEndNumber();

        $links->merge($this->generatePreviousButtons($startNumber));

        for ($i = $startNumber; $i <= $endNumber; $i++) {
            // each page
            $links->push(new PaginationButton(
                $i,
                $this->generateUrl($i),
                $i === $this->currentPage
            ));
        }

        $links->merge($this->generateNextButtons($endNumber));

        return $links->all();
    }

    /**
     * Generates the 'first' and 'previous' buttons.
     *
     * @param int $startNumber
     *
     * @return Table
     */
    protected function generatePreviousButtons(int $startNumber) : Table
    {
        $links = table(new PaginationButton(
            '<',
            $this->generateUrl($this->currentPage - 1),
            $this->currentPage <= 1
        ));

        if (
            $this->lastPage >= $this->spliceThreshold
            && $this->currentPage !== 1
            && $startNumber > 1
        ) {
            $links->push(new PaginationButton(
                1,
                $this->generateUrl(1),
            ));

            if ($startNumber > 2) {
                $links->push(new PaginationButton('...', '', true));
            }
        }

        return $links;
    }

    /**
     * Generates the 'next' and 'last' buttons.
     *
     * @param int $endNumber
     *
     * @return Table
     */
    protected function generateNextButtons(int $endNumber) : Table
    {
        $links = table();

        if (
            $this->lastPage >= $this->spliceThreshold
            && $this->currentPage !== $this->lastPage
            && $endNumber < $this->lastPage
        ) {
            if ($endNumber < $this->lastPage - 1) {
                $links->push(new PaginationButton('...', '', true));
            }

            $links->push(new PaginationButton(
                $this->lastPage,
                $this->generateUrl($this->lastPage)
            ));
        }

        $links->push(new PaginationButton(
            '>',
            $this->generateUrl($this->currentPage + 1),
            $this->currentPage >= $this->lastPage
        ));


        return $links;
    }

    /**
     * Gets the 'page number' from where to start listing pages.
     *
     * @return int
     */
    private function getStartNumber() : int
    {
        if ($this->lastPage < $this->spliceThreshold) {
            return 1;
        }

        return max(
            1,
            min(
                $this->currentPage - ceil($this->spliceThreshold / 2),
                $this->lastPage - $this->spliceThreshold
            )
        );
    }

    /**
     * Gets the 'page number' from where to stop listing pages.
     *
     * @return int
     */
    private function getEndNumber() : int
    {
        if ($this->lastPage < $this->spliceThreshold) {
            return $this->lastPage;
        }

        return min($this->lastPage, $this->getStartNumber() + $this->spliceThreshold);
    }

    /**
     * Runs the method to generate a route.
     *
     * @param int $page
     *
     * @return mixed
     */
    private function generateUrl(int $page)
    {
        return ($this->route)($page);
    }

}