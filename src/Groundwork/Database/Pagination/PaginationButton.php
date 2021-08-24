<?php

namespace Groundwork\Database\Pagination;

class PaginationButton
{
    protected string $content;
    protected bool $disabled = false;
    protected string $url;

    public function __construct(string $content, string $url, bool $disabled = false)
    {
        $this->content = $content;
        $this->url = $url;
        $this->disabled = $disabled;
    }

    public function getContent() : string
    {
        return $this->content;
    }

    public function getUrl() : string
    {
        return $this->url;
    }

    public function getDisabled() : bool
    {
        return $this->disabled;
    }
}