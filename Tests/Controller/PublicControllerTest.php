<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Tests\Controller;

use MauticPlugin\AivieDripifyBundle\Controller\PublicController;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PublicControllerTest extends TestCase
{
    private function callValidatePayload(array $payload, LoggerInterface $logger): void
    {
        $controller = $this->getMockBuilder(PublicController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $method = new \ReflectionMethod(PublicController::class, 'validatePayload');
        $method->invoke($controller, $payload, $logger);
    }

    public function testConversationAndCampaignNameAreAllowed(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $this->callValidatePayload([
            'link'         => 'https://www.linkedin.com/in/williamhgates/',
            'conversation' => [],
            'campaignName' => 'Spring Outreach',
        ], $logger);
    }
}
