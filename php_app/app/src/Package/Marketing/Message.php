<?php

namespace App\Package\Marketing;

use App\Models\MarketingCampaigns;
use App\Models\MarketingMessages;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;

class Message
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * Message constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function get(string $id): ?MarketingMessages
    {
        return $this->entityManager->getRepository(MarketingMessages::class)->find($id);
    }

    public function delete(string $id)
    {

        $campaign = $this->entityManager->getRepository(MarketingCampaigns::class)->findOneBy([
            'messageId' => $id,
            'deleted' => false,
        ]);
        if (!is_null($campaign)) {
            return Http::status(403, 'CAMPAIGN_ATTACHED_TO_MESSAGE');
        }
        $message = $this->entityManager->getRepository(MarketingMessages::class)->find($id);
        if (is_null($message)) {
            return Http::status(403, 'CANT_FIND_MESSAGE');
        }
        $message->setDeleted(true);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return Http::status(200);
    }
}
