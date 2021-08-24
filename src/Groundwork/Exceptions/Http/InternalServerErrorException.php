<?php

namespace Groundwork\Exceptions\Http;

class InternalServerErrorException extends HttpException {

    public function __construct(string $message = '')
    {
        parent::__construct("Internal Server Error", 500);
        $this->comment = "There was an error processing the request.";

        error_log($message);
    }
}