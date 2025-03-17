<?php

declare(strict_types=1);

namespace Retumador\Command;

use Psr\Log\LoggerInterface;
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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'watch',
    description: 'Watch a given folder containing one or several configuration files.',
)]
#[Autoconfigure(public: true)]
final class WatchCommand extends Command
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly FeedBuilder $feedBuilder,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('config-path', InputArgument::REQUIRED, 'Path of the file containing all instructions for crawl')
            ->addOption('output-path', 'o', InputOption::VALUE_REQUIRED, 'Path for the generated feed.', null)
            ->addOption('expires-after', null, InputOption::VALUE_REQUIRED, 'Time (in seconds) to consider a generated feed old enough to be regenerated', 3600)
            ->setHelp(<<<'EOF'
The <info>%command.name%</> command will watch the given folder and
create feeds for all available json files.

If a recent enough feed is found, it won't be re-generated. You can configure
this behavior using <comment>--expires-after</> option.

By default, all feeds will be generated in an <comment>output</comment> folder
inside the given path.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $configPath */
        $configPath = $input->getArgument('config-path');

        if (!is_dir($configPath)) {
            $io->error('Given path is not valid.');

            return Command::FAILURE;
        }

        $finder = new Finder();
        $finder->files()->name('*.json')->in($configPath);

        if (!$finder->hasResults()) {
            $io->error('No configuration file found in given folder.');

            return Command::FAILURE;
        }

        if (null === $outputDirectory = $input->getOption('output-path')) {
            $outputDirectory = $configPath.'/output';
        }
        /* @phpstan-ignore cast.string */
        $outputDirectory = (string) $outputDirectory;
        if (!is_dir($outputDirectory)) {
            if (false === mkdir($outputDirectory)) {
                $io->error('Output path does not exists and cannot be created.');

                return Command::FAILURE;
            }
        }

        /* @phpstan-ignore cast.int */
        $expirationTime = time() - (int) $input->getOption('expires-after');

        $io->progressStart(\count($finder));
        foreach ($finder as $file) {
            $io->progressAdvance();

            $feedName = $file->getFilenameWithoutExtension();
            $this->logger->debug("Start to handle feed \"$feedName\".");

            $outputFile = "{$outputDirectory}/{$feedName}.rss.xml";

            if (file_exists($outputFile) && filemtime($outputFile) > $expirationTime) {
                $this->logger->debug('Feed is not expired, skip generation.');

                continue;
            }

            $feedRequest = $this->serializer->deserialize($file->getContents(), FeedRequest::class, 'json');

            file_put_contents($outputFile, $this->feedBuilder->build($feedRequest));

            $this->logger->info('Feed generated successfully!');
        }
        $io->progressFinish();

        $io->success('All feeds have been updated');

        return Command::SUCCESS;
    }
}
