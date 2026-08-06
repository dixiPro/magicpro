<?php

namespace MagicProSrc\Lenta;

use Exception;
use MagicProSrc\MagicLang;

/**
 * Validation of a record failed.
 *
 * Every other error of the feeds api is a plain exception with a human text.
 * This one carries more: the errors laid out per logical field name, because
 * the form in the admin panel has to highlight the input that went wrong.
 *
 *     throw new FeedValidationException([
 *         'title' => ['Поле обязательно'],
 *         'price' => ['Должно быть числом'],
 *     ]);
 */
class FeedValidationException extends Exception
{
    /** @var array<string, array<int, string>> logical field name => messages */
    protected array $errors;

    public function __construct(array $errors, string $message = '')
    {
        // текст берётся из словаря пакета, поэтому идёт на языке админки
        parent::__construct($message !== '' ? $message : MagicLang::getMsg('feed_err_values'));

        $this->errors = $errors;
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
