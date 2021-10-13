<?php


namespace App\Package\Async\Email;


use App\Controllers\Marketing\Campaign\CampaignEmailSender;
use App\Models\MarketingCampaigns;
use App\Models\MarketingMessages;
use App\Package\Async\Flusher;
use App\Package\Async\FlushException;

class BatchCampaignEmailSender implements CampaignEmailSender, Flusher
{

    public function sendEmail($campaignId, $profile, MarketingCampaigns $campaign, $messageId, MarketingMessages $message): void
    {
        // TODO: Implement sendEmail() method.
    }

    /**
     * @throws FlushException
     */
    public function flush(): void
    {
        // TODO: Implement flush() method.
    }
}