<?php

/**
 * Created by jamieaitken on 25/10/2018 at 12:37
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\RabbitMQ\AirshipWorker;
use App\Controllers\Integrations\RabbitMQ\CampaignMonitorWorker;
use App\Controllers\Integrations\RabbitMQ\DotMailerSendWorker;
use App\Controllers\Integrations\RabbitMQ\EmailValidationWorker;
use App\Controllers\Integrations\RabbitMQ\FileExportWorker;
use App\Controllers\Integrations\RabbitMQ\GDPRNotifierWorker;
use App\Controllers\Integrations\RabbitMQ\InformWorker;
use App\Controllers\Integrations\RabbitMQ\MailChimpSendWorker;
use App\Controllers\Integrations\RabbitMQ\NotificationWorker;
use App\Controllers\Integrations\RabbitMQ\OptOutWorker;
use App\Controllers\Integrations\RabbitMQ\SyncDevicesWorker;
use App\Controllers\Integrations\RabbitMQ\TextLocalSendWorker;

use App\Controllers\Integrations\RabbitMQ\ZapierWorker;
use App\Package\Reviews\Scrapers\FacebookScraper;
use App\Package\Reviews\Scrapers\GoogleScraper;
use App\Package\Reviews\Scrapers\TripadvisorScraper;

$app->group(
    '/workers',
    function () {
        $this->post('/opt_out_queue', OptOutWorker::class . ':runWorkerRoute');
        $this->post('/inform_queue', InformWorker::class . ':runWorkerRoute');
        $this->post('/file_export_queue', FileExportWorker::class . ':runWorkerRoute');
        $this->post('/sync_devices_queue', SyncDevicesWorker::class . ':runWorkerRoute');
        $this->post('/zapier_queue', ZapierWorker::class . ':runWorkerRoute');
        $this->post('/notification_queue', NotificationWorker::class . ':runWorkerRoute');
        $this->post('/email_validation_queue', EmailValidationWorker::class . ':runWorkerRoute');
        $this->post('/gdpr_notifier_queue', GDPRNotifierWorker::class . ':runWorkerRoute');
        $this->post('/google_reviews', GoogleScraper::class . ':scrapeFromSnsRequest');
        $this->post('/facebook_reviews', FacebookScraper::class . ':scrapeFromSnsRequest');
        $this->post('/tripadvisor_reviews', TripadvisorScraper::class . ':scrapeFromSnsRequest');
        $this->post('/text_local_queue', TextLocalSendWorker::class . ':runWorkerRoute');
        $this->post('/mail_chimp_queue', MailChimpSendWorker::class . ':runWorkerRoute');
        $this->post('/dot_mailer_queue', DotMailerSendWorker::class . ':runWorkerRoute');
        $this->post('/campaign_monitor_queue', CampaignMonitorWorker::class . ':runWorkerRoute');
        $this->post('/airship_queue', AirshipWorker::class . ':runWorkerRoute');
    }
);
