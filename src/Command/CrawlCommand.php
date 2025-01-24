<?php

declare(strict_types=1);

namespace Retamador\Command;

use Retamador\Crawl\CrawlRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'retumador:crawl',
    description: 'Add a short description for your command',
)]
final class CrawlCommand extends Command
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path of the file containing all instructions for crawl')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $file */
        $file = $input->getArgument('file');

        if (!file_exists($file)) {
            throw new \InvalidArgumentException('Given file does not exists.');
        }

        $crawlRequest = $this->serializer->deserialize(file_get_contents($file), CrawlRequest::class, 'json');
        $violations = $this->validator->validate($crawlRequest);
        if (0 < $violations->count()) {
            $io->error('The given file is not a valid crawl request.');

            throw new ValidationFailedException($crawlRequest, $violations);
        }

        $io->success('File looks good');

        return Command::SUCCESS;
    }
}
