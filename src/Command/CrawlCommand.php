<?php

declare(strict_types=1);

namespace Retamador\Command;

use Dom\XPath;
use Retamador\Crawl\Browser;
use Retamador\Crawl\CrawlRequest;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

#[AsCommand(
    name: 'retumador:crawl',
    description: 'Add a short description for your command',
)]
#[Autoconfigure(public: true)]
final class CrawlCommand extends Command
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly HttpClientInterface $client,
        private readonly Environment $twig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('config-file', InputArgument::REQUIRED, 'Path of the file containing all instructions for crawl')
            ->addOption('output-file', 'o', InputOption::VALUE_REQUIRED, 'Path for the generated feed.', sys_get_temp_dir().'/retumador-rss.xml')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $file */
        $file = $input->getArgument('config-file');

        if (!file_exists($file)) {
            throw new \InvalidArgumentException('Given file does not exists.');
        }

        $crawlRequest = $this->serializer->deserialize(file_get_contents($file), CrawlRequest::class, 'json');
        $violations = $this->validator->validate($crawlRequest);
        if (0 < $violations->count()) {
            $io->error('The given file is not a valid crawl request.');

            throw new ValidationFailedException($crawlRequest, $violations);
        }

        $io->info("Creating RSS for {$crawlRequest->name}");

        $client = match ($crawlRequest->browser) {
            Browser::BASIC => new HttpBrowser($this->client),
            Browser::CHROMIUM => PantherClient::createChromeClient(),
            Browser::FIREFOX => PantherClient::createFirefoxClient(),
        };

        $client->request('GET', $crawlRequest->url);
        $response = $client->getInternalResponse();

        if (200 !== $response->getStatusCode()) {
            throw new \InvalidArgumentException('Requested URL is invalid.');
        }

        $content = $response->getContent();

        // $content = file_get_contents('result.html');
        $document = new \DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML($content);
        libxml_clear_errors();

        $xpath = new \DOMXPath($document);

        $items = [];
        // Requête XPath pour récupérer les noeuds "div" avec la classe "item"
        if (false === $nodes = $xpath->query($crawlRequest->itemSelector)) {
            throw new \RuntimeException('Unable to find articles with given selector');
        }
        foreach ($nodes as $node) {
            $title = $this->extractContent($xpath, $node, $crawlRequest->titleSelector);
            $link = $this->extractContent($xpath, $node, $crawlRequest->linkSelector);
            $image = null !== $crawlRequest->imageSelector ? $this->extractContent($xpath, $node, $crawlRequest->imageSelector) : null;

            $items[] = [
                'title' => $title,
                'link' => $this->sanitizeLink($crawlRequest, $link),
                'description' => $document->saveHTML($node),
                'publicationDate' => new \DateTimeImmutable(),
                'image' => $image,
            ];
        }

        /** @var string $outputFile */
        $outputFile = $input->hasOption('output-file') ? $input->getOption('output-file') : sys_get_temp_dir().'/retumador-rss.xml';

        file_put_contents($outputFile, $this->twig->render('rss.xml.twig', [
            'title' => $crawlRequest->name,
            'link' => $crawlRequest->url,
            'items' => $items,
        ]));

        $io->success('Feed generated successfully!');

        return Command::SUCCESS;
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

    private function sanitizeLink(CrawlRequest $crawlRequest, string $link): string
    {
        if (str_starts_with($link, 'http')) {
            return $link;
        }

        $urlParts = parse_url($crawlRequest->url);

        return $urlParts['scheme']."://".$urlParts['host'].$link;
    }
}
