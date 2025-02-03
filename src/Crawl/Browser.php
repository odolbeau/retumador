<?php

declare(strict_types=1);

namespace Retumador\Crawl;

enum Browser: string
{
    case BASIC = 'basic';
    case CHROMIUM = 'chromium';
    case FIREFOX = 'firefox';
}
