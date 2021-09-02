<?php

namespace Groundwork\Exceptions\Http;

use Groundwork\Config\Config;

class InternalServerErrorException extends HttpException {

    public function __construct(string $message = '')
    {
        parent::__construct("Internal Server Error", 500);
        if (Config::isProd()) {
            $this->comment = "There was an error processing the request.";
            error_log($message);
        } else {
            $this->comment = $message;
        }
    }
}