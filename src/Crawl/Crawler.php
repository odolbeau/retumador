<?php

declare(strict_types=1);

namespace Retumador\Crawl;

use Psr\Log\LoggerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class Crawler
{
    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ?string $waitFor wait for an element to appear before returning the response content
     */
    public function crawl(string $url, Browser $browser = Browser::BASIC, ?string $waitFor = null): string
    {
        if (Browser::BASIC === $browser && null !== $waitFor) {
            throw new \InvalidArgumentException('Cannot use waitFor parameter with basic browser.');
        }

        $client = match ($browser) {
            Browser::BASIC => new HttpBrowser($this->client),
            Browser::CHROMIUM => PantherClient::createChromeClient(),
            Browser::FIREFOX => PantherClient::createFirefoxClient(),
        };

        $crawler = $client->request('GET', $url);

        $response = $client->getInternalResponse();

        if (200 !== $response->getStatusCode()) {
            throw new \InvalidArgumentException('Requested URL is invalid.');
        }

        if ($client instanceof PantherClient && null !== $waitFor) {
            $this->logger->debug("Waiting for element \"{$waitFor}\" to appear.");

            $client->waitFor($waitFor);
        }

        return $crawler->html();
    }
}
