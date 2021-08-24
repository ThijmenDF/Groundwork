<?php

namespace Groundwork\Exceptions\Http;

use Exception;
use Groundwork\Response\View;

class HttpException extends Exception 
{
    protected string $comment = '';

    public function toView() : View
    {
        $data = [
            "code" => $this->getCode(),
            "message" => $this->getMessage(),
            "comment" => $this->getComment()
        ];

        return view('Groundwork/Exception', $data);
    }

    public function getComment() : string
    {
        return $this->comment;
    }
}