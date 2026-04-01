<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AivieDripifyIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME         = 'AivieDripify';
    public const DISPLAY_NAME = 'Dripify';
    public const WEBHOOK_SECRET_KEY = 'webhook_secret';

    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/AivieDripifyBundle/Assets/img/dripify.png';
    }

    public function getWebhookSecretName(): string
    {
        return self::WEBHOOK_SECRET_KEY;
    }

    public function getRedirectUri(): string
    {
        return $this->router->generate('aivie_dripify_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
