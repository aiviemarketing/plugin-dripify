<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Exception\ImportFailedException;
use MauticPlugin\AivieDripifyBundle\Handler\WebhookHandler;
use MauticPlugin\AivieDripifyBundle\Integration\AivieDripifyIntegration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PublicController extends CommonController
{
    private const int MAX_BODY_BYTES = 65536;

    /**
     * @throws IntegrationNotFoundException
     */
    public function webhookAction(
        Request $request,
        string $secret,
        WebhookHandler $webhookHandler,
        IntegrationsHelper $integrationHelper,
        LoggerInterface $logger
    ): Response {
        $clientIp = (string) ($request->getClientIp() ?? 'unknown');

        try {
            $dripifyIntegration = $integrationHelper->getIntegration(AivieDripifyIntegration::NAME);

            if (!$dripifyIntegration->getIntegrationConfiguration()->getIsPublished()) {
                throw new \InvalidArgumentException('The Dripify integration is disabled.');
            }

            if ('POST' !== $request->getMethod()) {
                throw new \InvalidArgumentException('Only POST is allowed.');
            }

            $contentType = (string) $request->headers->get('Content-Type', '');
            if (!$this->isJsonContentType($contentType)) {
                throw new \InvalidArgumentException('Content-Type must be application/json.');
            }

            $this->validateWebhookSecret($secret, $dripifyIntegration, $logger, $clientIp, $request->getPathInfo());

            $rawBody = $request->getContent();
            if (strlen($rawBody) > self::MAX_BODY_BYTES) {
                throw new \InvalidArgumentException('Payload too large.');
            }

            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($payload) || array_is_list($payload)) {
                throw new \InvalidArgumentException('Request body must be a JSON object.');
            }

            $logger->debug('Dripify webhook received.', $payload);

            $this->validatePayload($payload);

            $result = $webhookHandler->process($payload);
            $statusCode = 'created' === ($result['action'] ?? null)
                ? Response::HTTP_CREATED
                : Response::HTTP_OK;

            return new JsonResponse([
                'success'   => true,
                'action'    => $result['action'] ?? null,
                'contactId' => $result['contactId'] ?? null,
                'message'   => $result['message'] ?? 'Webhook processed.',
            ], $statusCode);
        } catch (\JsonException $exception) {
            $logger->debug('Dripify webhook rejected.', [
                'ip'      => $clientIp,
                'reason'  => 'invalid_json',
                'message' => $exception->getMessage(),
            ]);

            return $this->jsonError();
        } catch (IntegrationNotFoundException) {
            $logger->debug('Dripify webhook rejected.', [
                'ip'     => $clientIp,
                'reason' => 'integration_not_found',
            ]);

            return $this->jsonError();
        } catch (AccessDeniedHttpException $exception) {
            $logger->debug('Dripify webhook rejected.', [
                'ip'      => $clientIp,
                'reason'  => 'invalid_secret',
                'message' => $exception->getMessage(),
            ]);

            return $this->jsonError();
        } catch (\InvalidArgumentException $exception) {
            $logger->debug('Dripify webhook rejected.', [
                'ip'      => $clientIp,
                'reason'  => 'invalid_request',
                'message' => $exception->getMessage(),
            ]);

            return $this->jsonError();
        } catch (ImportFailedException $exception) {
            $logger->debug('Dripify webhook rejected.', [
                'ip'      => $clientIp,
                'reason'  => 'import_failed',
                'message' => $exception->getMessage(),
            ]);

            return $this->jsonError();
        } catch (\Throwable $exception) {
            $logger->debug('Dripify webhook rejected.', [
                'ip'        => $clientIp,
                'reason'    => 'unexpected_error',
                'message'   => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->jsonError();
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload): void
    {
        $allowedFields = [
            'firstName',
            'lastName',
            'location',
            'city',
            'country',
            'premium',
            'link',
            'website',
            'email',
            'manualEmail',
            'corporateEmail',
            'linkedInEmail',
            'phone',
            'company',
            'companyWebsite',
            'position',
            'industry',
            'education',
            'hookDate',
            'numberOfCompanyEmployees',
            'numberOfCompanyFollowers',
        ];

        $unknownFields = array_diff(array_keys($payload), $allowedFields);
        if ([] !== $unknownFields) {
            throw new \InvalidArgumentException('Unknown fields are not allowed.');
        }

        if (empty($payload['link']) || !is_string($payload['link'])) {
            throw new \InvalidArgumentException('Field "link" is required and must be a string.');
        }

        if (false === filter_var($payload['link'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Field "link" must be a valid URL.');
        }

        $stringFields = [
            'firstName',
            'lastName',
            'location',
            'city',
            'country',
            'premium',
            'website',
            'email',
            'manualEmail',
            'corporateEmail',
            'linkedInEmail',
            'phone',
            'company',
            'companyWebsite',
            'position',
            'industry',
            'education',
            'hookDate',
            'numberOfCompanyEmployees',
            'numberOfCompanyFollowers',
        ];

        foreach ($stringFields as $field) {
            if (!array_key_exists($field, $payload) || null === $payload[$field] || '' === $payload[$field]) {
                continue;
            }

            if (!is_string($payload[$field])) {
                throw new \InvalidArgumentException(sprintf('Field "%s" must be a string.', $field));
            }

            if (mb_strlen($payload[$field]) > 1000) {
                throw new \InvalidArgumentException(sprintf('Field "%s" is too long.', $field));
            }
        }

        foreach (['website', 'companyWebsite'] as $urlField) {
            if (!empty($payload[$urlField]) && false === filter_var($payload[$urlField], FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException(sprintf('Field "%s" must be a valid URL.', $urlField));
            }
        }

        foreach (['email', 'manualEmail', 'corporateEmail', 'linkedInEmail'] as $emailField) {
            if (!empty($payload[$emailField]) && false === filter_var($payload[$emailField], FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException(sprintf('Field "%s" must be a valid email.', $emailField));
            }
        }
    }

    private function isJsonContentType(string $contentType): bool
    {
        return str_starts_with(strtolower(trim($contentType)), 'application/json');
    }

    private function validateWebhookSecret(
        string $providedSecret,
        AivieDripifyIntegration $integration,
        LoggerInterface $logger,
        string $clientIp,
        string $path
    ): void {
        $apiKeys          = $integration->getIntegrationConfiguration()->getApiKeys();
        $configuredSecret = trim((string) ($apiKeys[$integration->getWebhookSecretName()] ?? ''));

        if ('' === $configuredSecret) {
            return;
        }

        $providedSecret = trim($providedSecret);

        if ('' === $providedSecret || !hash_equals($configuredSecret, $providedSecret)) {
            $logger->warning('Dripify webhook rejected invalid secret.', [
                'ip'              => $clientIp,
                'path'            => $path,
                'provided_secret' => $providedSecret,
            ]);

            throw new AccessDeniedHttpException('Invalid webhook secret.');
        }
    }

    private function jsonError(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
        ], Response::HTTP_BAD_REQUEST);
    }
}