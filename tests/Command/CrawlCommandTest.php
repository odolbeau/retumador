<?php

declare(strict_types=1);

namespace Retumador\Tests\Parse;

use PHPUnit\Framework\Attributes\DataProvider;
use Retumador\Crawl\Crawler;
use Retumador\Parse\DateTimeSanitizer;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CrawlCommandTest extends KernelTestCase
{
    #[DataProvider('sampleProvider')]
    public function testCommand(string $sample, \DateTimeImmutable $sampleGeneratedAt): void
    {
        // Arrange
        self::bootKernel();

        $sampleDirectory = __DIR__."/../samples/$sample";
        /** @var string $outputFile */
        /* @phpstan-ignore binaryOp.invalid */
        $outputFile = self::getContainer()->getParameter('kernel.cache_dir')."/$sample.rss.xml";

        $crawler = $this->createMock(Crawler::class);
        $crawler->method('crawl')->willReturn(file_get_contents("$sampleDirectory/response.html"));
        self::getContainer()->set(Crawler::class, $crawler);

        $dateTimeSanitizer = $this->createMock(DateTimeSanitizer::class);
        $dateTimeSanitizer->method('sanitize')->willReturn($sampleGeneratedAt);
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
        self::assertSame(file_get_contents("$sampleDirectory/rss.xml"), file_get_contents($outputFile));
    }

    /** @return iterable<array{string, \DateTimeImmutable}> */
    public static function sampleProvider(): iterable
    {
        yield ['human-immobilier', new \DateTimeImmutable('Tue, 04 Feb 2025 18:32:35 +0000')];
        yield ['la-montagne', new \DateTimeImmutable('Tue, 04 Feb 2025 18:59:08 +0000')];
        yield ['leggett-immo', new \DateTimeImmutable('Tue, 04 Feb 2025 20:01:49 +0000')];
    }
}
