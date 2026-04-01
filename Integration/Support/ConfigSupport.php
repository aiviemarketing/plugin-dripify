<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\AivieDripifyBundle\Form\Type\ConfigAuthType;
use MauticPlugin\AivieDripifyBundle\Integration\AivieDripifyIntegration;

class ConfigSupport extends AivieDripifyIntegration implements ConfigFormInterface, ConfigFormAuthInterface
{
    use DefaultConfigFormTrait;

    public function getAuthConfigFormName(): string
    {
        return ConfigAuthType::class;
    }

    public function isAuthorized(): bool
    {
        return true;
    }
}
