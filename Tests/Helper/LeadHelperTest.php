<?php

declare(strict_types=1);

namespace MauticPlugin\AivieDripifyBundle\Tests\Helper;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\AivieDripifyBundle\Helper\LeadHelper;
use PHPUnit\Framework\TestCase;

class LeadHelperTest extends TestCase
{
    public function testUpsertCreatesContactUsingFallbackEmailAndMappedFields(): void
    {
        $leadModel    = $this->createMock(LeadModel::class);
        $companyModel = $this->createMock(CompanyModel::class);
        $lead         = $this->createMock(Lead::class);

        $lead->expects($this->once())
            ->method('setNewlyCreated')
            ->with(true);
        $lead->expects($this->once())
            ->method('setManipulator');
        $lead->method('getId')
            ->willReturnOnConsecutiveCalls(0, 123);

        $leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->with(['email' => 'manual@example.com'])
            ->willReturn($lead);
        $leadModel->expects($this->once())
            ->method('setFieldValues')
            ->with(
                $lead,
                $this->callback(function (array $fields): bool {
                    self::assertSame('Jane', $fields['firstname']);
                    self::assertSame('Doe', $fields['lastname']);
                    self::assertSame('manual@example.com', $fields['email']);
                    self::assertSame('manual@example.com', $fields['dripify_manual_email']);
                    self::assertSame('1', $fields['dripify_premium']);
                    self::assertSame('2026-04-01', $fields['dripify_hook_date']);
                    self::assertSame('120', $fields['dripify_company_employees']);
                    self::assertSame('750', $fields['dripify_company_followers']);

                    return !isset($fields['dripify_corporate_email']);
                }),
                false,
                false
            );
        $leadModel->expects($this->once())
            ->method('saveEntity')
            ->with($lead);
        $leadModel->expects($this->never())
            ->method('setPrimaryCompany');

        $companyModel->expects($this->never())
            ->method('setFieldValues');

        $helper = new LeadHelper($leadModel, $companyModel);

        $result = $helper->upsert([
            'firstName'                => 'Jane',
            'lastName'                 => 'Doe',
            'premium'                  => 'Yes',
            'manualEmail'              => 'manual@example.com',
            'corporateEmail'           => 'invalid-email',
            'hookDate'                 => '01/04/2026',
            'numberOfCompanyEmployees' => '120',
            'numberOfCompanyFollowers' => '750',
        ]);

        self::assertSame([
            'action'    => 'created',
            'contactId' => 123,
            'message'   => 'Contact created successfully.',
        ], $result);
    }

    public function testUpsertUpdatesCompanyFieldsForExistingContact(): void
    {
        $leadModel             = $this->createMock(LeadModel::class);
        $companyModel          = $this->createMock(CompanyModel::class);
        $companyLeadRepository = $this->createMock(CompanyLeadRepository::class);
        $lead                  = $this->createMock(Lead::class);
        $company               = $this->createMock(Company::class);

        $lead->expects($this->never())
            ->method('setNewlyCreated');
        $lead->expects($this->once())
            ->method('setManipulator');
        $lead->method('getId')
            ->willReturn(42);

        $company->method('getId')
            ->willReturn(77);
        $company->method('getProfileFields')
            ->willReturn(['companyname' => 'Acme']);

        $companyLeadRepository->expects($this->once())
            ->method('getCompaniesByLeadId')
            ->with(42, 77)
            ->willReturn([]);

        $leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->with(['email' => 'owner@example.com'])
            ->willReturn($lead);
        $leadModel->expects($this->once())
            ->method('setFieldValues')
            ->with($lead, $this->arrayHasKey('company'), false, false);
        $leadModel->expects($this->once())
            ->method('saveEntity')
            ->with($lead);
        $leadModel->expects($this->once())
            ->method('setPrimaryCompany')
            ->with(77, 42);

        $companyModel->expects($this->once())
            ->method('fetchCompanyFields')
            ->willReturn([
                ['alias' => 'companyname'],
                ['alias' => 'companywebsite'],
                ['alias' => 'companyindustry'],
            ]);
        $companyModel->expects($this->once())
            ->method('checkForDuplicateCompanies')
            ->with($this->callback(function (array $fields): bool {
                return 'Acme Inc' === $fields['companyname']
                    && 'https://acme.example' === $fields['companywebsite']
                    && 'Software' === $fields['companyindustry'];
            }))
            ->willReturn([$company]);
        $companyModel->expects($this->once())
            ->method('getCompanyLeadRepository')
            ->willReturn($companyLeadRepository);
        $companyModel->expects($this->once())
            ->method('setFieldValues')
            ->with(
                $company,
                $this->callback(function (array $fields): bool {
                    return 'Acme Inc' === $fields['company']
                        && 'https://acme.example' === $fields['companywebsite']
                        && 'Software' === $fields['companyindustry'];
                })
            );
        $companyModel->expects($this->once())
            ->method('saveEntity')
            ->with($company);
        $companyModel->expects($this->once())
            ->method('addLeadToCompany')
            ->with($company, $lead);

        $helper = new LeadHelper($leadModel, $companyModel);

        $result = $helper->upsert([
            'email'          => 'owner@example.com',
            'company'        => 'Acme Inc',
            'companyWebsite' => 'https://acme.example',
            'industry'       => 'Software',
        ]);

        self::assertSame([
            'action'    => 'updated',
            'contactId' => 42,
            'message'   => 'Contact updated successfully.',
        ], $result);
    }
}
