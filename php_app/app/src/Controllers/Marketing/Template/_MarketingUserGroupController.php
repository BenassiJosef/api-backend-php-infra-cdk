<?php

/**
 * Created by jamieaitken on 18/01/2018 at 16:43
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Marketing\Template;

use App\Models\Marketing\CustomerTemplate;
use App\Models\Marketing\TemplateSettings;
use App\Models\MarketingCampaigns;

use App\Models\Organization;

use App\Package\Organisations\OrganizationProvider;

use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _MarketingUserGroupController
{
    protected $em;

    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;

    public function __construct(EntityManager $em)
    {
        $this->em                   = $em;
        $this->organisationProvider = new OrganizationProvider($this->em);
    }

    public function createOrUpdateRoute(Request $request, Response $response)
    {

        $send         = $this->createOrUpdate($this->organisationProvider->organizationForRequest($request), $request->getParsedBody());
        return $response->withJson($send, $send['status']);
    }

    public function getTemplateRoute(Request $request, Response $response): Response
    {
        $send = $this->getTemplate($request->getAttribute('id'));
        return $response->withJson($send, $send['status']);
    }

    public function getTemplate(string $id)
    {

        $template = $this->em->getRepository(TemplateSettings::class)->find($id);

        if (empty($template)) {
            return Http::status(404, 'NOT_A_VALID_TEMPLATE');
        }

        return Http::status(200, $template);
    }

    public function getAllExistingRoute(Request $request, Response $response): Response
    {
        $send = $this->getAllExisting($request->getAttribute('orgId'));
        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response): Response
    {
        $send = $this->delete($request->getAttribute('id'));
        return $response->withJson($send, $send['status']);
    }

    public function createOrUpdate(Organization $organization, array $body)
    {
        if (array_key_exists('id', $body) && !is_null($body['id'])) {
            /**
             * @var TemplateSettings $template
             */
            $template = $this->em->getRepository(TemplateSettings::class)->find($body['id']);
            $template->updateFromArray($body);
        } else {
            $template = new TemplateSettings($body['send_from'], $body['reply_to']);
        }

        $this->em->persist($template);
        $newTemplateList = new CustomerTemplate($organization, $template->getId());

        $this->em->persist($newTemplateList);

        $this->em->flush();

        return Http::status(200, $template->jsonSerialize());
    }

    public function getAllExisting(string $orgId)
    {

        $qb = $this->em->createQueryBuilder();


        /**
         * @var TemplateSettings $get
         */
        $get = $qb
            ->select('ts')
            ->from(CustomerTemplate::class, 'u')
            ->leftJoin(TemplateSettings::class, 'ts', 'WITH', 'u.templateId = ts.id')
            ->where('u.organizationId = :orgId') // TODO OrgId replace
            ->andWhere('ts.deleted = :f')
            ->setParameter('orgId', $orgId)
            ->setParameter('f', false)
            ->getQuery()
            ->getResult();

        $res = [];
        foreach ($get as $template) {
            $res[] = $template->jsonSerialize();
        }

        return Http::status(200, $res);
    }


    public function delete(string $id)
    {
        $getTemplate = $this->em->getRepository(TemplateSettings::class)->findOneBy([
            'id' => $id
        ]);

        if (is_null($getTemplate)) {
            return Http::status(404, 'FAILED_TO_LOCATE_TEMPLATE');
        }

        $this->marketingCache->delete('marketingTemplates:' . $getTemplate->id);

        $campaigns = $this->em->getRepository(MarketingCampaigns::class)->findBy([
            'templateId' => $getTemplate->id
        ]);

        foreach ($campaigns as $campaign) {
            $campaign->templateId = null;
        }

        $this->em->remove($getTemplate);

        $this->em->flush();

        return Http::status(200, ['id' => $id]);
    }
}
