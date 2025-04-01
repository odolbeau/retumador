<?php

declare(strict_types=1);

namespace Retumador\AI;

use PhpLlm\LlmChain\ChainInterface;
use PhpLlm\LlmChain\Model\Message\Message;
use PhpLlm\LlmChain\Model\Message\MessageBag;
use Retumador\Parse\Selectors;

final class SelectorsFinder
{
    public function __construct(
        private readonly ChainInterface $chain,
    ) {
    }

    /**
     * Find relevant selectors in a HTML content using AI.
     */
    public function find(string $content): Selectors
    {
        $messages = new MessageBag(
            Message::ofUser($content),
        );

        $response = $this->chain->call($messages, ['output_structure' => Selectors::class]);

        /* @phpstan-ignore return.type */
        return $response->getContent();
    }
}
