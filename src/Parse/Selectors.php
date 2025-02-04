<?php

declare(strict_types=1);

namespace Retumador\Parse;

use Symfony\Component\Validator\Constraints as Assert;

final class Selectors
{
    #[Assert\NotNull]
    public string $item;

    #[Assert\NotNull]
    public string $title;

    public string $link = './/a/@href';

    public string $image = './/img/@src';

    public string $content = '.';
}
