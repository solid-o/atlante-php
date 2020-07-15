<?php

declare(strict_types=1);

namespace Solido\Atlante\Exception;

use Throwable;

class AccessDeniedHttpException extends HttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct(?string $message = null, ?Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(403, $message, $previous, $headers, $code);
    }
}
