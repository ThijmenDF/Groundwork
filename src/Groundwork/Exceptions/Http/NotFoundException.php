<?php

namespace Groundwork\Exceptions\Http;

class NotFoundException extends HttpException {

    public function __construct(string $path = '')
    {
        parent::__construct("Page Not Found", 404);

        $this->comment = "We couldn't find what you are looking for. Maybe it has been deleted.";
    }
}