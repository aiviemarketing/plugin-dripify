<?php

return [
    'name'        => 'Dripify',
    'description' => 'Webhook endpoint for importing Dripify contacts into Aivie or Mautic.',
    'version'     => '6.0.0',
    'author'      => 'Aivie',
    'routes'      => [
        'main'   => [],
        'public' => [
            'aivie_dripify_webhook' => [
                'path'       => '/dripify/webhook',
                'controller' => 'MauticPlugin\AivieDripifyBundle\Controller\PublicController::webhookAction',
                'method'     => 'POST',
            ],
        ],
        'api'    => [],
    ],
    'services'    => [
        'integrations' => [
            'mautic.integration.aiviedripify' => [
                'class' => \MauticPlugin\AivieDripifyBundle\Integration\AivieDripifyIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'plugin.aivie_dripify.integration.configuration' => [
                'class' => \MauticPlugin\AivieDripifyBundle\Integration\Support\ConfigSupport::class,
                'tags'  => [
                    'mautic.config_integration',
                ],
            ],
        ],
    ],
];
