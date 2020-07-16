<?php

declare(strict_types=1);

namespace Solido\Atlante\Exception;

use Throwable;

class BadRequestHttpException extends HttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct(?string $message = null, ?Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(400, $message, $previous, $headers, $code);
    }
}