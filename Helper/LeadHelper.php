<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Helper;

use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\Tag;

class LeadHelper
{
    private const EMAIL_FIELDS = [
        'email',
        'manualEmail',
        'corporateEmail',
        'linkedInEmail',
    ];

    public function __construct(
        private LeadModel $leadModel,
        private CompanyModel $companyModel
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{action: string, contactId: int, message: string}
     *
     * @throws ImportFailedException
     */
    public function upsert(array $payload): array
    {
        $primaryEmail = $this->resolvePrimaryEmail($payload);

        if (null === $primaryEmail) {
            throw new \InvalidArgumentException('No usable email field provided.');
        }

        $leadFields = $this->buildLeadFields($payload, $primaryEmail);
        $lead       = $this->leadModel->checkForDuplicateContact(['email' => $primaryEmail]);
        $isNew      = 0 === $lead->getId();

        if ($isNew) {
            $lead->setNewlyCreated(true);
        }

        $lead->setManipulator(new LeadManipulator('dripify', 'webhook', null, 'Dripify webhook'));

        $tagName = sprintf('dripify-campaign-%s', date('Y-m'));
        $lead->addTag(new Tag('dripify-campaign'));
        $lead->addTag(new Tag($tagName));

        $this->leadModel->setFieldValues($lead, $leadFields, false, false);
        $this->leadModel->saveEntity($lead);

        $this->syncCompany($lead, $payload);

        return [
            'action'    => $isNew ? 'created' : 'updated',
            'contactId' => (int) $lead->getId(),
            'message'   => $isNew ? 'Contact created successfully.' : 'Contact updated successfully.',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function buildLeadFields(array $payload, string $primaryEmail): array
    {
        return $this->filterNullValues([
            'firstname'                  => $this->stringValue($payload['firstName'] ?? null),
            'lastname'                   => $this->stringValue($payload['lastName'] ?? null),
            'dripify_location'           => $this->stringValue($payload['location'] ?? null),
            'city'                       => $this->stringValue($payload['city'] ?? null),
            'country'                    => $this->stringValue($payload['country'] ?? null),
            'dripify_premium'            => $this->normalizeBooleanValue($payload['premium'] ?? null),
            'linkedin'                   => $this->stringValue($payload['link'] ?? null),
            'website'                    => $this->stringValue($payload['website'] ?? null),
            'email'                      => $primaryEmail,
            'dripify_manual_email'       => $this->normalizeEmail($payload['manualEmail'] ?? null),
            'dripify_corporate_email'    => $this->normalizeEmail($payload['corporateEmail'] ?? null),
            'dripify_linkedin_email'     => $this->normalizeEmail($payload['linkedInEmail'] ?? null),
            'phone'                      => $this->stringValue($payload['phone'] ?? null),
            'company'                    => $this->stringValue($payload['company'] ?? null),
            'position'                   => $this->stringValue($payload['position'] ?? null),
            'education'                  => $this->stringValue($payload['education'] ?? null),
            'dripify_hook_date'          => $this->normalizeDateValue($payload['hookDate'] ?? null),
            'dripify_company_employees'  => $this->normalizeIntegerValue($payload['numberOfCompanyEmployees'] ?? null),
            'dripify_company_followers'  => $this->normalizeIntegerValue($payload['numberOfCompanyFollowers'] ?? null),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolvePrimaryEmail(array $payload): ?string
    {
        foreach (self::EMAIL_FIELDS as $field) {
            $email = $this->normalizeEmail($payload[$field] ?? null);

            if (null !== $email) {
                return $email;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function syncCompany(Lead $lead, array $payload): void
    {
        $companyName = $this->stringValue($payload['company'] ?? null);

        if (null === $companyName) {
            return;
        }

        $companyFields = $this->filterNullValues([
            'company'         => $companyName,
            'companywebsite'  => $this->stringValue($payload['companyWebsite'] ?? null),
            'companyindustry' => $this->stringValue($payload['industry'] ?? null),
        ]);

        [, $leadAdded, $companyEntity] = IdentifyCompanyHelper::identifyLeadsCompany($companyFields, $lead, $this->companyModel);

        if (!$companyEntity instanceof Company || empty($companyFields)) {
            return;
        }

        $this->companyModel->setFieldValues($companyEntity, $companyFields);
        $this->companyModel->saveEntity($companyEntity);

        if ($leadAdded) {
            $this->companyModel->addLeadToCompany($companyEntity, $lead);
        }

        $this->leadModel->setPrimaryCompany($companyEntity->getId(), $lead->getId());
    }

    private function normalizeBooleanValue(mixed $value): ?string
    {
        $normalized = strtolower((string) $this->stringValue($value));

        return match ($normalized) {
            'yes', 'true', '1' => '1',
            'no', 'false', '0' => '0',
            default => null,
        };
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        $date = $this->stringValue($value);

        if (null === $date) {
            return null;
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('d/m/Y', $date);

        if (!$parsedDate instanceof \DateTimeImmutable) {
            return null;
        }

        return $parsedDate->format('Y-m-d');
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = $this->stringValue($value);

        if (null === $email) {
            return null;
        }

        $email = strtolower($email);

        return false !== filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizeIntegerValue(mixed $value): ?string
    {
        $number = $this->stringValue($value);

        if (null === $number || !is_numeric($number)) {
            return null;
        }

        return (string) (int) $number;
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $stringValue = trim((string) $value);

        return '' === $stringValue ? null : $stringValue;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function filterNullValues(array $values): array
    {
        return array_filter(
            $values,
            static fn (mixed $value): bool => null !== $value
        );
    }
}
