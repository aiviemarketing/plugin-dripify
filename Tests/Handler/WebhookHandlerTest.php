<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Tests\Handler;

use MauticPlugin\AivieDripifyBundle\Handler\WebhookHandler;
use MauticPlugin\AivieDripifyBundle\Helper\LeadHelper;
use PHPUnit\Framework\TestCase;

class WebhookHandlerTest extends TestCase
{
    public function testProcessDelegatesToLeadHelper(): void
    {
        $payload = [
            'email'     => 'owner@example.com',
            'firstName' => 'Jane',
        ];

        $leadHelper = $this->createMock(LeadHelper::class);
        $leadHelper->expects($this->once())
            ->method('upsert')
            ->with($payload)
            ->willReturn([
                'action'    => 'created',
                'contactId' => 123,
                'message'   => 'Contact created successfully.',
            ]);

        $handler = new WebhookHandler($leadHelper);

        self::assertSame([
            'action'    => 'created',
            'contactId' => 123,
            'message'   => 'Contact created successfully.',
        ], $handler->process($payload));
    }
}
