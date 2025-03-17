<?php

declare(strict_types=1);

namespace Retumador;

use Retumador\Command\CrawlCommand;
use Retumador\Command\WatchCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\KernelInterface;

class Application extends BaseApplication
{
    private bool $commandsRegistered = false;

    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct($kernel);
        $this->setName('Retumador');
        $this->setDefaultCommand('crawl');
    }

    protected function registerCommands(): void
    {
        if ($this->commandsRegistered) {
            return;
        }

        $this->commandsRegistered = true;

        $this->kernel->boot();

        $container = $this->kernel->getContainer();

        /** @var Command $crawlCommand */
        $crawlCommand = $container->get(CrawlCommand::class);
        /** @var Command $watchCommand */
        $watchCommand = $container->get(WatchCommand::class);

        $this->add($crawlCommand);
        $this->add($watchCommand);
    }
}
