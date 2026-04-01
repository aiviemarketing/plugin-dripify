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
                'path'       => '/dripify/webhook/{secret}',
                'controller' => 'MauticPlugin\AivieDripifyBundle\Controller\PublicController::webhookAction',
                'defaults'   => [
                    'secret' => '',
                ],
                'method'     => 'POST',
            ],
        ],
        'api'    => [],
    ],
    'services'    => [
        'integrations' => [
            'mautic.integration.aiviedripify' => [
                'class'     => \MauticPlugin\AivieDripifyBundle\Integration\AivieDripifyIntegration::class,
                'arguments' => [
                    'router',
                ],
                'tags'      => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'plugin.aivie_dripify.integration.configuration' => [
                'class'     => \MauticPlugin\AivieDripifyBundle\Integration\Support\ConfigSupport::class,
                'arguments' => [
                    'router',
                ],
                'tags'      => [
                    'mautic.config_integration',
                ],
            ],
        ],
    ],
];
