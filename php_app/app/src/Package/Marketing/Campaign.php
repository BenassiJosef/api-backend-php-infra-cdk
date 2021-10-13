<?php

namespace App\Package\Marketing;

use App\Models\MarketingCampaigns;
use App\Models\MarketingMessages;
use App\Models\Organization;
use Doctrine\ORM\EntityManager;

class Campaign
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Message $message
     */
    private $message;

    /**
     * MarketingController constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->message = new Message($this->entityManager);
    }

    public function get(string $campaignId): ?MarketingCampaigns
    {
/**
 * LEGACY!!
 */
        if (strpos($campaignId, 'REVIEW_') !== false) {
            return $this->entityManager->getRepository(MarketingCampaigns::class)->findOneBy(['name' => $campaignId]);
        }
        return $this->entityManager->getRepository(MarketingCampaigns::class)->find($campaignId);
    }

    public function create(array $data, Organization $organization): ?MarketingCampaigns
    {
        $campaign = new MarketingCampaigns($organization);
        $this->entityManager->persist($campaign);
        if (empty($data['messageId']) || is_null($data['messageId'])) {
            $message = new MarketingMessages($organization);
            $this->entityManager->persist($message);
            if (empty($data['message'])) {
                $data['message']['name'] = $data['name'];
            }
        } else {
            $message = $this->message->get($data['messageId']);
        }
        $campaign->setMessage($message);

        $res = MarketingCampaigns::fromArray($data, $organization, $campaign);

        $this->entityManager->persist($res);
        $this->entityManager->flush();
        return $res;
    }

    public function update(array $data, Organization $organization): ?MarketingCampaigns
    {
        $campaign = $this->get($data['id']);
        $res = MarketingCampaigns::fromArray($data, $organization, $campaign);

        $this->entityManager->persist($res);
        $this->entityManager->flush();
        return $res;
    }

}
