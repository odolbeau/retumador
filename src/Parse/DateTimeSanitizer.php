<?php

declare(strict_types=1);

namespace Retumador\Parse;

readonly class DateTimeSanitizer
{
    public function sanitize(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
