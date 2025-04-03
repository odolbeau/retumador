<?php

declare(strict_types=1);

namespace Retumador;

use Retumador\Crawl\Crawler;
use Retumador\Parse\Parser;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

final readonly class FeedBuilder
{
    public function __construct(
        private ValidatorInterface $validator,
        private Crawler $crawler,
        private Parser $parser,
        private Environment $twig,
    ) {
    }

    public function build(FeedRequest $feedRequest): string
    {
        $violations = $this->validator->validate($feedRequest);
        if (0 < $violations->count()) {
            throw new ValidationFailedException($feedRequest, $violations);
        }

        $content = $this->crawler->crawl($feedRequest->url, $feedRequest->browser, $feedRequest->waitFor);

        $items = $this->parser->parse($content, $feedRequest->selectors, $feedRequest->getBaseUrl());

        return $this->twig->render('rss.xml.twig', [
            'title' => $feedRequest->name,
            'link' => $feedRequest->url,
            'items' => $items,
        ]);
    }
}
