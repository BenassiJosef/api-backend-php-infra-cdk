<?php

/**
 * Created by jamieaitken on 22/11/2017 at 09:25
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Notifications;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Notification
 *
 * @ORM\Table(name="notification")
 * @ORM\Entity
 */
class Notification implements JsonSerializable
{

    private $statusViaKind = [
        'billing_invoice_ready' => 'ok',
        'capture_connected' => 'ok',
        'capture_payment' => 'ok',
        'capture_validated' => 'ok',
        'network_online' => 'ok',
        'review_received' => 'ok',
        'insight_daily' => 'ok',
        'insight_weekly' => 'ok',
        'insight_biWeekly' => 'ok',
        'insight_monthly' => 'ok',
        'insight_biMonthly' => 'ok',
        'billing_error' => 'warning',
        'card_expiry_reminder' => 'warning',
        'network_offline' => 'warning',
        'gift_card' => 'ok',
        'campaign' => 'ok'
    ];


    public function __construct(string $objectId, string $title, string $kind, string $link)
    {
        $this->id = Strings::idGenerator('not');
        $this->objectId = $objectId;
        $this->title = $title;
        $this->kind = $kind;
        $this->status = $this->statusViaKind[$kind];
        $this->link = $link;
        $this->externalLink = false;
        if (strpos($link, 'http://') !== false) {
            $this->externalLink = true;
        }
        $newDateTime = new \DateTime();
        $this->createdAt = $newDateTime;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="id_object", type="string")
     */
    private $objectId;

    /**
     * @var string
     * @ORM\Column(name="title", type="string")
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(name="kind", type="string")
     */
    private $kind;

    /**
     * @var string
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="link", type="string")
     */
    private $link;

    /**
     * @var boolean
     * @ORM\Column(name="externalLink", type="boolean")
     */
    private $externalLink;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    private $fcmMessage = '';
    /**
     * @var string $orgId
     */
    private $orgId = null;

    private $fcmParams = [];

    public function getRoute()
    {
        switch ($this->kind) {
            case 'capture_registered':
                return 'Registrations';
                break;
            case 'campaign':
                return 'MarketingNavigator';
                break;
            case 'capture_connected':
                return 'Connections';
                break;
            case 'review_received':
                return 'ReviewNavigator';
                break;
            case 'gift_card':
                return 'GiftingNavigator';
            case 'capture_validated':
                return 'Customers';
                break;

            default:
                return 'Main';
        }
    }

    public function getRouteScreen()
    {
        switch ($this->kind) {

            case 'campaign':

                return 'Campaign';
                break;
            case 'review_received':
                return 'Review';
                break;
            case 'gift_card':
                return 'Gift Cards';


            default:
                return null;
        }
    }

    public function getOrgId(): ?string
    {
        return $this->orgId;
    }

    public function getProductRoute()
    {
        $basePath = '';
        if (!is_null($this->getOrgId())) {
            $basePath = $this->getOrgId() . '/';
        }
        switch ($this->kind) {
            case 'capture_registered':
                return $basePath . 'venue/analytics/registrations';
                break;
            case 'campaign':
                return $basePath . 'marketing';
                break;
            case 'capture_connected':
                return $basePath . 'venue/analytics/visits';
                break;
            case 'review_received':
                return $basePath . 'reviews/responses';
                break;
            case 'gift_card':
                return $basePath . 'gifting/activations';
            case 'capture_validated':
                return $basePath . 'venue/analytics/customers';
                break;
            default:
                return '';
        }
    }



    /**
     * @param array $params
     */

    public function setParamsRoute(array $params)
    {
        $this->fcmParams = array_merge($this->fcmParams, $params);
    }

    public function getMessage()
    {
        switch ($this->kind) {
            case 'gift_card':
                return 'Someone just bought a gift card';
                break;
            case 'billing_invoice_ready':
                return 'Hey! you have a new invoice.';
                break;
            case 'capture_validated':
                return 'A user just validated their email at your venue.';
                break;
            case 'card_expiry_reminder':
                return 'Just a reminder that the card we have on file for you is about to expire.';
                break;
            case 'capture_registered':
                return 'A user has just registered at your venue.';
                break;
            case 'capture_payment':
                return 'Hey! You have just received a payment from a user for the WiFI.';
                break;
            case 'billing_error':
                return 'There is something wrong with your payment method as we are unable to take payment.';
                break;
            case 'capture_connected':
                return 'A user has just connected to your WiFi';
                break;
            case 'insight_daily':
                return 'Your Daily report is ready.';
                break;
            case 'insight_weekly':
                return 'Your Weekly report is ready.';
                break;
            case 'insight_biWeekly':
                return 'Your Bi Weekly report is ready.';
                break;
            case 'insight_monthly':
                return 'Your Monthly report is ready.';
                break;
            case 'insight_biMonthly':
                return 'Your Bi Monthly report is ready.';
                break;
            case 'network_online':
                return 'Your Network is now Online.';
                break;
            case 'network_offline':
                return 'Your Network is now Offline, this is either down to lack of power or internet.';
                break;
            case 'review_received':
                return 'You have just received a review';
                break;
        }
    }

    public function setOrgId(string $orgId)
    {
        $this->orgId = $orgId;
    }

    public function setMessage(string $message)
    {
        $this->fcmMessage = $message;
    }


    public function getKind(): string
    {
        return $this->kind;
    }

    public function getFCMParams()
    {
        return array_merge($this->fcmParams, ['screen' => $this->getRouteScreen()]);
    }

    public function getFCMMessage()
    {
        return $this->fcmMessage;
    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function jsonSerialize()
    {
        return [
            'message' => $this->getMessage(),
            'title' => $this->title,
            'kind' => $this->getKind(),
            'fcm_message' => $this->getFCMMessage(),
            'fcm_params' => $this->getFCMParams(),
            'product_link' => $this->getProductRoute()
        ];
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }
}
