<?php

declare(strict_types=1);

namespace Retumador;

use Retumador\Crawl\Browser;
use Retumador\Parse\Selectors;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final class FeedRequest
{
    #[Assert\NotNull]
    public string $name;

    #[Assert\NotNull]
    #[Assert\Url(requireTld: true)]
    public string $url;

    #[Assert\NotNull]
    public Browser $browser = Browser::BASIC;

    #[SerializedName('wait_for')]
    public ?string $waitFor = null;

    #[Assert\Valid]
    public Selectors $selectors;

    public function getBaseUrl(): string
    {
        $urlParts = parse_url($this->url);
        if (!isset($urlParts['host'])) {
            throw new \InvalidArgumentException('Unable to determine host part in given URL.');
        }

        return ($urlParts['scheme'] ?? 'https').'://'.$urlParts['host'];
    }
}
