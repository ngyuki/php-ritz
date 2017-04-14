<?php
namespace ngyuki\Ritz\Exception;

use Throwable;
use Zend\Diactoros\Response;

class HttpException extends \RuntimeException
{
    public function __construct($message = null, $code = 500, Throwable $previous = null)
    {
        if ($message === null) {
            $message = (new Response())->withStatus($code)->getReasonPhrase();
        }
        parent::__construct($message, $code, $previous);
    }
}
