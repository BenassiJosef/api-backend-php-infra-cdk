<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 26/03/2017
 * Time: 00:12
 */

namespace App\Utils;

use App\Models\Billing\Quotes\Quotes;
use App\Models\Integrations\Airship\AirshipListLocation;
use App\Models\Integrations\Airship\AirshipUserDetails;
use App\Models\Integrations\CampaignMonitor\CampaignMonitorListLocation;
use App\Models\Integrations\CampaignMonitor\CampaignMonitorUserDetails;
use App\Models\Integrations\ConstantContact\ConstantContactListLocation;
use App\Models\Integrations\ConstantContact\ConstantContactUserDetails;
use App\Models\Integrations\DotMailer\DotMailerAddressLocation;
use App\Models\Integrations\DotMailer\DotMailerUserDetails;
use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Models\Integrations\MailChimp\MailChimpContactListLocation;
use App\Models\Integrations\MailChimp\MailChimpUserDetails;
use App\Models\Integrations\TextLocal\TextLocalContactListLocation;
use App\Models\Integrations\TextLocal\TextLocalUserDetails;
use App\Models\Locations\Reviews\LocationReviewErrors;
use App\Models\Locations\Reviews\LocationReviews;
use App\Models\Auth\Provider;
use App\Models\Device\DeviceBrowser;
use App\Models\Device\DeviceOs;
use App\Models\Integrations\SNS\Event;
use App\Models\Integrations\SNS\Subscription;
use App\Models\Integrations\SNS\Topic;
use App\Models\Invoices;
use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\LocationPolicyGroup;
use App\Models\Locations\Other\LocationOther;
use App\Models\Locations\Position\LocationPosition;
use App\Models\Locations\Reviews\LocationReviewsTimeline;
use App\Models\Locations\Schedule\LocationSchedule;
use App\Models\Locations\Schedule\LocationScheduleDay;
use App\Models\Locations\Reports\EmailReport;
use App\Models\Locations\Schedule\LocationScheduleTime;
use App\Models\Locations\Social\LocationSocial;
use App\Models\Locations\WiFi\LocationWiFi;
use App\Models\Marketing\MarketingDeliverable;
use App\Models\Marketing\MarketingDeliverableEvent;
use App\Models\Marketing\ShortUrl;
use App\Models\Marketing\Template;
use App\Models\Marketing\TemplateSettings;
use App\Models\MarketingCampaignEvents;
use App\Models\MarketingCampaigns;
use App\Models\MarketingEventOptions;
use App\Models\MarketingEvents;
use App\Models\MarketingLocations;
use App\Models\MarketingMessages;
use App\Models\Members\StandalonePartnerBranding;
use App\Models\Nearly\Impressions;
use App\Models\Nearly\ImpressionsAggregate;
use App\Models\Nearly\Stories\NearlyStory;
use App\Models\Nearly\Stories\NearlyStoryPage;
use App\Models\Nearly\Stories\NearlyStoryPageActivity;
use App\Models\Nearly\Stories\NearlyStoryPageActivityAggregate;
use App\Models\Nearly\Stories\NearlyStoryPageEvent;
use App\Models\NetworkAccess;
use App\Models\Notifications\FCMNotificationTokens;
use App\Models\Notifications\FeatureRequest;
use App\Models\Notifications\FeatureRequestVote;
use App\Models\Notifications\UserNotifications;
use App\Models\PartnerQuotes;
use App\Models\User\UserAgent;
use App\Models\User\UserBlocked;
use App\Models\User\UserDevice;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use App\Models\Members\Groups\Group;

class CustomId extends AbstractIdGenerator
{
    public function generate(EntityManager $em, $entity)
    {
        $prefix = '';

        switch ($entity) {
            case $entity instanceof Inform:
                $prefix = 'inf';
                break;
            case $entity instanceof Invoices:
                $prefix = 'inv';
                break;
            case $entity instanceof PartnerQuotes:
                $prefix = 'pq';
                break;
            case $entity instanceof EmailReport:
                $prefix = 'rpt';
                break;
            case $entity instanceof Group:
                $prefix = 'grp';
                break;
            case $entity instanceof NetworkAccess:
                $prefix = 'na';
                break;
            case $entity instanceof Event:
                $prefix = 'evt';
                break;
            case $entity instanceof Subscription:
                $prefix = 'sub';
                break;
            case $entity instanceof Topic:
                $prefix = 'tpc';
                break;
            case $entity instanceof MarketingCampaigns:
                $prefix = 'mkc';
                break;
            case $entity instanceof MarketingLocations:
                $prefix = 'mkl';
                break;
            case $entity instanceof MarketingCampaignEvents:
                $prefix = 'mke';
                break;
            case $entity instanceof MarketingEvents:
                $prefix = 'me';
                break;
            case $entity instanceof MarketingEventOptions:
                $prefix = 'mkeo';
                break;
            case $entity instanceof MarketingMessages:
                $prefix = 'mkm';
                break;
            case $entity instanceof FeatureRequest:
                $prefix = 'fer';
                break;
            case $entity instanceof FeatureRequestVote:
                $prefix = 'fev';
                break;
            case $entity instanceof UserNotifications:
                $prefix = 'rel';
                break;
            case $entity instanceof Template:
                $prefix = 'mkTpl';
                break;
            case $entity instanceof TemplateSettings:
                $prefix = 'mkTplSet';
                break;
            case $entity instanceof Provider:
                $prefix = 'op';
                break;
            case $entity instanceof DeviceOs:
                $prefix = 'dos';
                break;
            case $entity instanceof DeviceBrowser:
                $prefix = 'db';
                break;
            case $entity instanceof UserAgent:
                $prefix = 'ua';
                break;
            case $entity instanceof UserDevice:
                $prefix = 'ud';
                break;
            case $entity instanceof LocationSchedule:
                $prefix = 'lsch';
                break;
            case $entity instanceof LocationScheduleDay:
                $prefix = 'lschday';
                break;
            case $entity instanceof LocationScheduleTime:
                $prefix = 'lschtime';
                break;
            case $entity instanceof LocationBranding:
                $prefix = 'brnd';
                break;
            case $entity instanceof LocationWiFi:
                $prefix = 'wifi';
                break;
            case $entity instanceof LocationPosition:
                $prefix = 'locpos';
                break;
            case $entity instanceof LocationOther:
                $prefix = 'locoth';
                break;
            case $entity instanceof LocationSocial:
                $prefix = 'locsoc';
                break;
            case $entity instanceof StandalonePartnerBranding:
                $prefix = 'stpabr';
                break;
            case $entity instanceof ShortUrl:
                $prefix = 'shorturl';
                break;
            case $entity instanceof LocationPolicyGroup:
                $prefix = 'locpol';
                break;
            case $entity instanceof LocationReviews:
                $prefix = 'locrw';
                break;
            case $entity instanceof LocationReviewsTimeline:
                $prefix = 'locrwtime';
                break;
            case $entity instanceof FCMNotificationTokens:
                $prefix = 'fcmtok';
                break;
            case $entity instanceof TextLocalUserDetails:
                $prefix = 'txtlocal';
                break;
            case $entity instanceof TextLocalContactListLocation:
                $prefix = 'txtlocallist';
                break;
            case $entity instanceof IntegrationEventCriteria:
                $prefix = 'integevent';
                break;
            case $entity instanceof MailChimpUserDetails:
                $prefix = 'mailchimp';
                break;
            case $entity instanceof MailChimpContactListLocation:
                $prefix = 'mailchimplist';
                break;
            case $entity instanceof DotMailerUserDetails:
                $prefix = 'dotmailer';
                break;
            case $entity instanceof DotMailerAddressLocation:
                $prefix = 'dotmailerbook';
                break;
            case $entity instanceof FilterEventList:
                $prefix = 'filter';
                break;
            case $entity instanceof ConstantContactUserDetails:
                $prefix = 'constcont';
                break;
            case $entity instanceof ConstantContactListLocation:
                $prefix = 'constcontlist';
                break;
            case $entity instanceof CampaignMonitorUserDetails:
                $prefix = 'campmoni';
                break;
            case $entity instanceof CampaignMonitorListLocation:
                $prefix = 'campmonilist';
                break;
            case $entity instanceof Impressions:
                $prefix = 'impr';
                break;
            case $entity instanceof ImpressionsAggregate:
                $prefix = 'impragg';
                break;
            case $entity instanceof LocationReviewErrors:
                $prefix = 'locrwerr';
                break;
            case $entity instanceof UserBlocked:
                $prefix = 'usrblk';
                break;
            case $entity instanceof Quotes:
                $prefix = 'quo';
                break;
            case $entity instanceof NearlyStory:
                $prefix = 'story';
                break;
            case $entity instanceof NearlyStoryPage:
                $prefix = 'storypage';
                break;
            case $entity instanceof NearlyStoryPageActivity:
                $prefix = 'storypageacti';
                break;
            case $entity instanceof NearlyStoryPageActivityAggregate:
                $prefix = 'storypageactiagg';
                break;
            case $entity instanceof NearlyStoryPageEvent:
                $prefix = 'storypageeve';
                break;
            case $entity instanceof MarketingDeliverable:
                $prefix = 'marketdeliv';
                break;
            case $entity instanceof MarketingDeliverableEvent:
                $prefix = 'marketdeliveve';
                break;
            case $entity instanceof AirshipUserDetails:
                $prefix = 'airshcont';
                break;
            case $entity instanceof AirshipListLocation:
                $prefix = 'airshlist';
                break;
        }

        return Strings::idGenerator($prefix);
    }
}
