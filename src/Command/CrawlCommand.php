<?php

declare(strict_types=1);

namespace Retumador\Command;

use Retumador\FeedBuilder;
use Retumador\FeedRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'retumador:crawl',
    description: 'Add a short description for your command',
)]
#[Autoconfigure(public: true)]
final class CrawlCommand extends Command
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly FeedBuilder $feedBuilder,
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

        $feedRequest = $this->serializer->deserialize(file_get_contents($file), FeedRequest::class, 'json');

        $io->info("Creating RSS for {$feedRequest->name}");

        $feed = $this->feedBuilder->build($feedRequest);

        /** @var string $outputFile */
        $outputFile = $input->hasOption('output-file') ? $input->getOption('output-file') : sys_get_temp_dir().'/retumador-rss.xml';

        file_put_contents($outputFile, $feed);

        $io->success('Feed generated successfully!');

        return Command::SUCCESS;
    }
}
