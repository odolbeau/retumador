<?php

declare(strict_types=1);

namespace Retamador\Crawl;

use Symfony\Component\Validator\Constraints as Assert;

final class CrawlRequest
{
    #[Assert\NotNull]
    public string $name;

    #[Assert\NotNull]
    #[Assert\Url(requireTld: true)]
    public string $url;

    #[Assert\NotNull]
    public Browser $browser = Browser::BASIC;

    #[Assert\NotNull]
    public string $itemSelector;

    #[Assert\NotNull]
    public string $titleSelector;

    #[Assert\NotNull]
    public string $linkSelector;

    public string $imageSelector = './/img/@src';
}
