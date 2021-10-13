<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 19/05/2017
 * Time: 13:07
 */

namespace App\Controllers\Integrations\SNS;

use App\Models\Integrations\SNS\BaseEvent;
use App\Models\Integrations\SNS\Event;
use App\Models\Integrations\SNS\Subscription;
use App\Models\Integrations\SNS\Topic;
use App\Utils\Http;
use Aws\Sns\SnsClient;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _QueueController
{
    protected $sns;
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em  = $em;
        $this->sns = SnsClient::factory([
            'version'   => 'latest',
            'region'    => 'eu-west-1',
            'signature' => 'v4'
        ]);
    }

    public function postRoute(Request $request, Response $response)
    {
        $mandatoryHeader = $request->getHeader('x-amz-sns-message-type');

        if (empty($mandatoryHeader)) {
            return $response->withJson(Http::status(400, 'NOT_A_SNS_REQUEST'), 400);
        }

        $body = $request->getParsedBody();

        $send = $this->post($body);

        return $response->withJson($send, $send['status']);
    }

    public function post(array $body)
    {

        $dataInCertificate = [];

        $dataInCertificate['Message']   = $body['Message'];
        $dataInCertificate['MessageId'] = $body['MessageId'];
        $dataInCertificate['Timestamp'] = $body['Timestamp'];
        $dataInCertificate['TopicArn']  = $body['TopicArn'];
        $dataInCertificate['Type']      = $body['Type'];

        if ($body['type'] === 'Notification') {
            if (array_key_exists('Subject', $body)) {
                $dataInCertificate['Subject'] = $body['Subject'];
            }
        } elseif ($body['type'] === 'SubscriptionConfirmation' || $body['type'] === 'UnsubscribeConfirmation') {
            $dataInCertificate['SubscribeURL'] = $body['SubscribeURL'];
            $dataInCertificate['Token']        = $body['Token'];
        }


        $validate = $this->verifyCertificate(json_encode($dataInCertificate), $body['SigningCertURL'], $body['Signature']);

        if ($validate['message'] === false) {
            return Http::status(400, 'FAILED_TO_VERIFY_SIGNATURE');
        }

        return Http::status(200);
    }

    public function verifyCertificate(string $data, string $file, string $signature)
    {
        $publicCertificate = openssl_pkey_get_public($file);

        $verify = openssl_verify($data, $signature, $publicCertificate);

        $isValid = false;

        if ($verify === 1) {
            $isValid = true;
        }

        /*
        $k = openssl_free_key($publicCertificate);

        $decodedSignature = base64_decode($signature);

        if ($decodedSignature === $k) {

        }*/

        return Http::status(200, $isValid);
    }

    public function createTopicRoute(Request $request, Response $response)
    {
        $user = $request->getAttribute('accessUser');
        $body = $request->getParsedBody();

        $send = $this->createTopic($body['topic'], $user);

        return $response->withJson($send, $send['status']);
    }

    public function createTopic(string $topic, $user)
    {
        $userCanUseTopic = $this->em->createQueryBuilder()
            ->select('u')
            ->from(BaseEvent::class, 'u')
            ->where('u.name = :name')
            ->andWhere('u.minimumRole >= :role')
            ->setParameter('name', strtoupper($topic))
            ->setParameter('role', $user['role'])
            ->getQuery()
            ->getArrayResult();

        if (empty($userCanUseTopic)) {
            return Http::status(409, 'USER_NOT_VALID_TO_USE_TOPIC');
        }

        $topicName = $topic . '_';
        if ($user['role'] >= 0 && $user['role'] <= 2) {
            $topicName .= $user['uid'];
        } else {
            $topicName .= $user['admin'];
        }

        $validate = $this->em->getRepository(Topic::class)->findOneBy([
            'name' => $topicName
        ]);

        if (is_object($validate)) {
            return Http::status(409, 'TOPIC_ALREADY_EXISTS');
        }

        $topicResult = $this->sns->createTopic([
            'Name' => $topicName
        ]);

        $newTopic = new Topic($topicResult['TopicArn'], substr($topicName, strrpos($topicName, '_') + 1));
        $this->em->persist($newTopic);
        $this->em->flush();

        return Http::status(200, 'TOPIC_CREATED');
    }

    public function subscribeToTopic(Event $event)
    {
        $subscription = $this->sns->subscribe([
            'TopicArn' => $event->topic,
            'Protocol' => 'https',
            'Endpoint' => 'https://api.blackbx.io/webhooks/blackbx'
        ]);

        $newSubscription = new Subscription($subscription['SubscriptionArn']);
        $this->em->persist($newSubscription);
        $this->em->flush();
    }

    public function publishToTopic(string $topic, string $message)
    {
        $this->sns->publish([
            'TopicArn' => $topic,
            'Message'  => $message
        ]);
    }

    public function unsubscribe(string $subscription)
    {
        $this->sns->unsubscribe([
            'SubscriptionArn' => $subscription
        ]);
    }
}
