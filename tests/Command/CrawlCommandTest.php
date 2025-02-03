<?php

declare(strict_types=1);

namespace Retumador\Tests\Parse;

use Retumador\Crawl\Crawler;
use Retumador\Parse\DateTimeSanitizer;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CrawlCommandTest extends KernelTestCase
{
    public function testCommand(): void
    {
        // Arrange
        self::bootKernel();

        $sampleDirectory = __DIR__.'/../samples/human-immobilier';
        /** @var string $outputFile */
        /* @phpstan-ignore binaryOp.invalid */
        $outputFile = self::getContainer()->getParameter('kernel.cache_dir').'/human-immobilier.rss.xml';

        $crawler = $this->createMock(Crawler::class);
        $crawler->method('crawl')->willReturn(file_get_contents("$sampleDirectory/response.html"));
        self::getContainer()->set(Crawler::class, $crawler);

        $dateTimeSanitizer = $this->createMock(DateTimeSanitizer::class);
        $dateTimeSanitizer->method('sanitize')->willReturn(new \DateTimeImmutable('Mon, 03 Feb 2025 18:43:42 +0000'));
        self::getContainer()->set(DateTimeSanitizer::class, $dateTimeSanitizer);

        // Act
        /* @phpstan-ignore argument.type */
        $command = (new Application(self::$kernel))->find('retumador:crawl');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // pass arguments to the helper
            'config-file' => "$sampleDirectory/config.json",

            '--output-file' => $outputFile,
        ]);

        // Assert
        $commandTester->assertCommandIsSuccessful();

        self::assertFileExists($outputFile);
        // TODO: improve test (or feed generation?) to get rid of date problem
        // self::assertSame(file_get_contents($outputFile), file_get_contents("$sampleDirectory/rss.xml"));
    }
}
