<?php

declare(strict_types=1);

namespace Retumador\Parse;

final readonly class Parser
{
    private DateTimeSanitizer $dateTimeSanitizer;

    public function __construct(?DateTimeSanitizer $dateTimeSanitizer = null,
    ) {
        $this->dateTimeSanitizer = $dateTimeSanitizer ?? new DateTimeSanitizer();
    }

    /** @return Item[] */
    public function parse(string $content, Selectors $selectors, string $baseUrl): array
    {
        $document = new \DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML($content);
        libxml_clear_errors();

        $xpath = new \DOMXPath($document);

        if (false === $nodes = $xpath->query($selectors->item)) {
            throw new \RuntimeException('Given selector to find items looks invalid');
        }

        $items = [];
        foreach ($nodes as $node) {
            $title = $this->extractContent($xpath, $node, $selectors->title);
            $link = $this->extractContent($xpath, $node, $selectors->link);
            $image = $this->extractContent($xpath, $node, $selectors->image);
            $description = $this->extractHTML($xpath, $node, $selectors->content);

            $items[] = new Item(
                title: $title,
                link: $this->sanitizeLink($baseUrl, $link),
                description: $description ?: '',
                publicationDate: $this->dateTimeSanitizer->sanitize(),
                image: $image,
            );
        }

        return $items;
    }

    private function extractContent(\DOMXPath $xpath, \DOMNode $node, string $selector): string
    {
        if (false === $extractedNode = $xpath->query($selector, $node)) {
            throw new \RuntimeException("Given selector looks invalid ($selector).");
        }
        if (null === $childNode = $extractedNode->item(0)) {
            $nodeHTML = $xpath->document->saveHTML($node);

            throw new \RuntimeException("No node matching the given selector ($selector) inside \"$nodeHTML\"");
        }

        return trim($childNode->textContent);
    }

    private function extractHTML(\DOMXPath $xpath, \DOMNode $node, string $selector): string
    {
        if (false === $extractedNode = $xpath->query($selector, $node)) {
            throw new \RuntimeException("Given selector looks invalid ($selector).");
        }
        if (null === $childNode = $extractedNode->item(0)) {
            $nodeHTML = $xpath->document->saveHTML($node);

            throw new \RuntimeException("No node matching the given selector ($selector) inside \"$nodeHTML\"");
        }

        if (false === $html = $xpath->document->saveHTML($childNode)) {
            throw new \RuntimeException('Unable to export node into HTML.');
        }

        return $html;
    }

    private function sanitizeLink(string $baseUrl, string $link): string
    {
        if (str_starts_with($link, 'http')) {
            return $link;
        }

        return $baseUrl.$link;
    }
}
