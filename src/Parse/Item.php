<?php

declare(strict_types=1);

namespace Retumador\Parse;

final readonly class Item
{
    public function __construct(
        public string $title,
        public string $link,
        public string $description,
        public \DateTimeImmutable $publicationDate,
        public ?string $image,
    ) {
    }
}
