<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\AlertType;
use MauticPlugin\AivieDripifyBundle\Integration\AivieDripifyIntegration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigAuthType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AivieDripifyIntegration $integration */
        $integration = $options['integration'];
        $apiKeys     = $integration->getIntegrationConfiguration()
            ? $integration->getIntegrationConfiguration()->getApiKeys()
            : [];
        $webhookSecret = trim((string) ($apiKeys[$integration->getWebhookSecretName()] ?? ''));
        $callbackUrl   = rtrim($integration->getRedirectUri(), '/').'/';

        if ('' !== $webhookSecret) {
            $callbackUrl .= rawurlencode($webhookSecret);
        }

        $builder->add(
            'callback_url',
            TextType::class,
            [
                'label'      => 'URL for Dripify',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'mapped'     => false,
                'data'       => $callbackUrl,
                'attr'       => [
                    'class'    => 'form-control',
                    'readonly' => true,
                    'tooltip'  => 'mautic.dripify.integration.callback_url.tooltip',
                ],
            ]
        );

        $builder->add(
            $integration->getWebhookSecretName(),
            TextType::class,
            [
                'label'      => 'Webhook secret',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'data'       => $webhookSecret,
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'Optional. If set, it is appended to the callback URL.',
                ],
            ]
        );

        $builder->add(
            'webhook_url_info',
            AlertType::class,
            [
                'message'      => $this->translator->trans('mautic.dripify.integration.webhook_url.info'),
                'message_type' => 'info',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'integration' => null,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'dripify_integration';
    }
}
