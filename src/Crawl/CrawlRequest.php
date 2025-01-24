<?php

declare(strict_types=1);

namespace Retamador\Crawl;

use Symfony\Component\Validator\Constraints as Assert;

final class CrawlRequest
{
    #[Assert\NotNull]
    #[Assert\Url]
    public string $url;

    #[Assert\NotNull]
    public string $title;
}
