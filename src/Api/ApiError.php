<?php

namespace MagicProSrc\Api;

/**
 * An error a page is expected to react to.
 *
 * AbstractApi::run() puts its code into `data.errorCode`: the page chooses its
 * text by the code, the message stays for the log and for a developer.
 */
class ApiError extends \Exception
{
    public function __construct(public readonly string $errorCode, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $errorCode);
    }
}
