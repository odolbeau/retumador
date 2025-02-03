<?php

declare(strict_types=1);

namespace Retumador\Crawl;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Crawler
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    public function crawl(string $url, Browser $browser = Browser::BASIC): string
    {
        $client = match ($browser) {
            Browser::BASIC => new HttpBrowser($this->client),
            Browser::CHROMIUM => PantherClient::createChromeClient(),
            Browser::FIREFOX => PantherClient::createFirefoxClient(),
        };

        $client->request('GET', $url);
        $response = $client->getInternalResponse();

        if (200 !== $response->getStatusCode()) {
            throw new \InvalidArgumentException('Requested URL is invalid.');
        }

        return $response->getContent();
    }
}
