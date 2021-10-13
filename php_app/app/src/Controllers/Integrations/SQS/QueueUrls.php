<?php
/**
 * Created by jamieaitken on 05/12/2018 at 17:12
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\SQS;


abstract class QueueUrls
{
    const AIRSHIP = 'https://sqs.eu-west-1.amazonaws.com/354378566817/airship';
    const CAMPAIGN_MONITOR = 'https://sqs.eu-west-1.amazonaws.com/354378566817/campaign-monitor';
    const DOT_MAILER = 'https://sqs.eu-west-1.amazonaws.com/354378566817/dot-mailer';
    const EMAIL_DELIVERY = 'https://sqs.eu-west-1.amazonaws.com/354378566817/email-delivery';
    const EMAIL_VALIDATION = 'https://sqs.eu-west-1.amazonaws.com/354378566817/email-validation';
    const FACEBOOK_REVIEWS = 'https://sqs.eu-west-1.amazonaws.com/354378566817/facebook-reviews';
    const FILE_EXPORT = 'https://sqs.eu-west-1.amazonaws.com/354378566817/file-export';
    const GDPR_NOTIFIER = 'https://sqs.eu-west-1.amazonaws.com/354378566817/gdpr-notifier';
    const GOOGLE_REVIEWS = 'https://sqs.eu-west-1.amazonaws.com/354378566817/google-reviews';
    const INFORM = 'https://sqs.eu-west-1.amazonaws.com/354378566817/inform';
    const MAIL_CHIMP = 'https://sqs.eu-west-1.amazonaws.com/354378566817/mail-chimp';
    const NOTIFICATION = 'https://sqs.eu-west-1.amazonaws.com/354378566817/notification';
    const OPT_OUT = 'https://sqs.eu-west-1.amazonaws.com/354378566817/opt-out';
    const SMS_DELIVERY = 'https://sqs.eu-west-1.amazonaws.com/354378566817/sms-delivery';
    const TEXT_LOCAL = 'https://sqs.eu-west-1.amazonaws.com/354378566817/text-local';
    const TRIPADVISOR_REVIEWS = 'https://sqs.eu-west-1.amazonaws.com/354378566817/tripadvisor-reviews';
    const UNIFI = 'https://sqs.eu-west-1.amazonaws.com/354378566817/unifi-queue';
    const UNIFI_SYNC_DEVICES = 'https://sqs.eu-west-1.amazonaws.com/354378566817/unifi-sync-devices';
    const ZAPIER = 'https://sqs.eu-west-1.amazonaws.com/354378566817/zapier';
}