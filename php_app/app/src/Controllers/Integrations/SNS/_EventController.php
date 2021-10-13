<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 20/05/2017
 * Time: 14:02
 */

namespace App\Controllers\Integrations\SNS;

use App\Models\Integrations\SNS\Event;
use App\Models\Integrations\SNS\Topic;
use App\Utils\Http;
use Doctrine\Common\Cache\PredisCache;
use Doctrine\ORM\EntityManager;

class _EventController
{
    protected $em;
    protected $queueController;
    protected $cache;

    public function __construct(EntityManager $em, PredisCache $cache)
    {
        $this->em              = $em;
        $this->cache           = $cache;
        $this->queueController = new _QueueController($this->em);
    }

    public function createEvent(string $topic, string $joiningId, $user)
    {

        $personalisedTopicName = $topic . '_' . $user['admin'];

        $checkIfTopicExists = $this->em->getRepository(Topic::class)->findOneBy([
            'name' => $personalisedTopicName
        ]);

        if (is_null($checkIfTopicExists)) {
            return Http::status(409, 'TOPIC_DOES_NOT_EXIST');
        }

        $createEvent = new Event($topic, $joiningId);
        $this->em->persist($createEvent);
        $this->em->flush();

        $this->queueController->subscribeToTopic($createEvent);
    }

    public function createEventFromInformId(string $topic, string $informId)
    {
        $checkInCache = $this->cache->fetch('informIds:' . $informId);
    }
}
