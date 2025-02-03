<?php

declare(strict_types=1);

namespace Retumador\Parse;

final readonly class Parser
{
    /** @return Item[] */
    public function parse(string $content, Selectors $selectors, string $baseUrl): array
    {
        $document = new \DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML($content);
        libxml_clear_errors();

        $xpath = new \DOMXPath($document);

        if (false === $nodes = $xpath->query($selectors->item)) {
            throw new \RuntimeException('Unable to find articles with given selector');
        }

        $items = [];
        foreach ($nodes as $node) {
            $title = $this->extractContent($xpath, $node, $selectors->title);
            $link = $this->extractContent($xpath, $node, $selectors->link);
            $image = $this->extractContent($xpath, $node, $selectors->image);
            $description = $document->saveHTML($node);

            $items[] = new Item(
                title: $title,
                link: $this->sanitizeLink($baseUrl, $link),
                description: $description ?: '',
                publicationDate: new \DateTimeImmutable(),
                image: $image,
            );
        }

        return $items;
    }

    private function extractContent(\DOMXPath $xpath, \DOMNode $node, string $selector): string
    {
        if (false === $extractedNode = $xpath->query($selector, $node)) {
            throw new \RuntimeException('Given selector looks invalid');
        }
        if (null === $childNode = $extractedNode->item(0)) {
            throw new \RuntimeException('No node matching the given selector');
        }

        return $childNode->textContent;
    }

    private function sanitizeLink(string $baseUrl, string $link): string
    {
        if (str_starts_with($link, 'http')) {
            return $link;
        }

        return $baseUrl.$link;
    }
}
