<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Handler;

use MauticPlugin\AivieDripifyBundle\Helper\LeadHelper;

class WebhookHandler
{
    public function __construct(
        private LeadHelper $leadHelper
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{action: string, contactId: int, message: string}
     */
    public function process(array $payload): array
    {
        return $this->leadHelper->upsert($payload);
    }
}
