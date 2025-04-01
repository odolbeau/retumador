<?php

declare(strict_types=1);

namespace Retumador\Command;

use Retumador\AI\SelectorsFinder;
use Retumador\Crawl\Browser;
use Retumador\Crawl\Crawler;
use Retumador\FeedRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'ai:generate-config',
    description: 'Generate a configuration file from an URL.',
)]
#[Autoconfigure(public: true)]
final class AIGenerateConfigCommand extends Command
{
    public function __construct(
        private readonly SerializerInterface&NormalizerInterface $serializer,
        private readonly Crawler $crawler,
        private readonly SelectorsFinder $selectorsFinder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the feed')
            ->addArgument('url', InputArgument::REQUIRED, 'URL of the website')
            ->addOption('browser', 'b', InputOption::VALUE_REQUIRED, 'The browser to use (basic, firefox, chromium)', 'basic')
            ->addOption('output-file', 'o', InputOption::VALUE_REQUIRED, 'Output the config to the given file instead of stdout')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Retrieving page content.');

        /** @var string */
        $url = $input->getArgument('url');
        /** @var string */
        $browserString = $input->getOption('browser');
        $browser = Browser::from($browserString);

        $content = $this->crawler->crawl($url, $browser);

        $io->info('Calling SelectorsFinder');

        $selectors = $this->selectorsFinder->find($content);

        /** @var string */
        $name = $input->getArgument('name');

        $feedRequest = new FeedRequest();
        $feedRequest->name = $name;
        $feedRequest->url = $url;
        $feedRequest->browser = $browser;
        $feedRequest->selectors = $selectors;

        $io->info('Generated config');
        $io->writeln((string) json_encode($this->serializer->normalize($selectors), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));

        if (null !== $input->getOption('output-file')) {
            /** @var string $outputFile */
            $outputFile = $input->getOption('output-file');

            file_put_contents($outputFile, $this->serializer->serialize($feedRequest, 'json'));
        }

        $io->success('Selectors generated successfully!');

        return Command::SUCCESS;
    }
}
