<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\AivieDripifyBundle\Integration\AivieDripifyIntegration;

class ConfigSupport extends AivieDripifyIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
