<?php


namespace App\Controllers\Integrations\Mail;

/**
 * Interface MailSender
 * @package App\Controllers\Integrations\Mail
 */
interface MailSender
{
    /**
     * @param array $sendTo
     * @param array $args
     * @param string $template
     * @param string $subject
     * @return mixed
     */
    public function send(array $sendTo, array $args, string $template, string $subject);
}