<?php

declare(strict_types=1);

namespace Retumador\Tests\Parse;

use PHPUnit\Framework\TestCase;
use Retumador\Parse\Parser;
use Retumador\Parse\Selectors;

final class ParserTest extends TestCase
{
    public function testParseHumanImmobilier(): void
    {
        // Arrange
        $content = (string) file_get_contents(__DIR__.'/../samples/human-immobilier/response.html');
        /** @var array{selectors: array{item: string, title: string, link: string, image: string}} $config */
        $config = json_decode((string) file_get_contents(__DIR__.'/../samples/human-immobilier/config.json'), true, flags: \JSON_THROW_ON_ERROR);

        $selectors = new Selectors();
        $selectors->item = $config['selectors']['item'];
        $selectors->title = $config['selectors']['title'];
        $selectors->link = $config['selectors']['link'];
        $selectors->image = $config['selectors']['image'];

        // Act
        $items = (new Parser())->parse($content, $selectors, 'http://foobar.org');

        // Assert
        self::assertCount(7, $items);

        $latestItem = $items[0];

        self::assertSame('Vente Corps de Ferme  FURSAC (23290)  - 6 pièces  - 153 m²', $latestItem->title);
        self::assertSame('http://foobar.org/annonce-achat-maison-fursac_355-1400', $latestItem->link);
        self::assertSame('https://www.human-immobilier.fr/images/355-1400_071124023701.jpg', $latestItem->image);
    }

    public function testParseLaMontagne(): void
    {
        // Arrange
        $content = (string) file_get_contents(__DIR__.'/../samples/la-montagne/response.html');
        /** @var array{selectors: array{item: string, title: string, link: string, image: string}} $config */
        $config = json_decode((string) file_get_contents(__DIR__.'/../samples/la-montagne/config.json'), true, flags: \JSON_THROW_ON_ERROR);

        $selectors = new Selectors();
        $selectors->item = $config['selectors']['item'];
        $selectors->title = $config['selectors']['title'];

        // Act
        $items = (new Parser())->parse($content, $selectors, 'http://foobar.org');

        // Assert
        self::assertCount(10, $items);

        $latestItem = $items[0];

        self::assertSame('Maison habitable de suite', $latestItem->title);
        self::assertSame('http://foobar.org/immobilier/fursac-23290/vente/maison-neuve/maison-habitable-de-suite-30510019', $latestItem->link);
        self::assertSame('https://media.studio-net.fr/biens/30510019/x679d79643abfb?width=300&height=200&func=crop', $latestItem->image);
    }

    public function testParseLeggettImmo(): void
    {
        // Arrange
        $content = (string) file_get_contents(__DIR__.'/../samples/leggett-immo/response.html');
        /** @var array{selectors: array{item: string, title: string, link: string, image: string}} $config */
        $config = json_decode((string) file_get_contents(__DIR__.'/../samples/leggett-immo/config.json'), true, flags: \JSON_THROW_ON_ERROR);

        $selectors = new Selectors();
        $selectors->item = $config['selectors']['item'];
        $selectors->title = $config['selectors']['title'];

        // Act
        $items = (new Parser())->parse($content, $selectors, 'http://foobar.org');

        // Assert
        self::assertCount(16, $items);

        $latestItem = $items[0];

        self::assertSame('Maison indépendant de 2 chambres et grange, situé dans un hameau de la commune de Chatelus le marcheix', $latestItem->title);
        self::assertSame('http://foobar.org/acheter-vendre-une-maison/view/A33470DLO23/maison-a-vendre-a-châtelus-le-marcheix-creuse-limousin-france', $latestItem->link);
        self::assertSame('https://image.hestia.immo/wVd_WlESIApk50EEHxw0jhEijTqJFmFQHqIS2hLRTD4/w:400/h:300/rt:fit/dpr:1/el:false/ex:false/mb:0/aHR0cHM6Ly9jZG4uaGVzdGlhLmltbW8vaW1hZ2UvVEVOM1hQTkMvMjAyNC0xMS9ET0MzNDZDQUZWM1YuanBn.jpg', $latestItem->image);
    }
}
