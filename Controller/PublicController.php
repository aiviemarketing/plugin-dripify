<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Exception\ImportFailedException;
use MauticPlugin\AivieDripifyBundle\Handler\WebhookHandler;
use MauticPlugin\AivieDripifyBundle\Integration\AivieDripifyIntegration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonController
{
    /**
     * @throws IntegrationNotFoundException
     */
    public function webhookAction(
        Request $request,
        WebhookHandler $webhookHandler,
        IntegrationsHelper $integrationHelper
    ): Response
    {
        try {
            $dripifyIntegration = $integrationHelper->getIntegration(AivieDripifyIntegration::NAME);

            if (!$dripifyIntegration->getIntegrationConfiguration()->getIsPublished()) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => 'integration_disabled',
                    'message' => 'The Dripify integration is disabled.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($payload)) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => 'invalid_payload',
                    'message' => 'Request body must be a JSON object.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = $webhookHandler->process($payload);
            $statusCode = 'created' === $result['action'] ? Response::HTTP_CREATED : Response::HTTP_OK;

            return new JsonResponse([
                'success'   => true,
                'action'    => $result['action'],
                'contactId' => $result['contactId'],
                'message'   => $result['message'],
            ], $statusCode);
        } catch (\JsonException) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'invalid_payload',
                'message' => 'Invalid JSON payload.',
            ], Response::HTTP_BAD_REQUEST);
        } catch (IntegrationNotFoundException) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'integration_disabled',
                'message' => 'The Dripify integration is not installed or published.',
            ], Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'validation_error',
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ImportFailedException $exception) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'import_failed',
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
