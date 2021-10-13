<?php

namespace App\Controllers\Integrations\Mail;

use App\Controllers\Branding\_BrandingController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Models\Marketing\TemplateSettings;
use App\Models\MarketingCampaigns;
use App\Models\Reviews\ReviewSettings;
use App\Templates\TwigEnvironmentLoader;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Pelago\Emogrifier;
use phpmailerException;
use Slim\Http\Response;
use Slim\Http\Request;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class _MailController
 */
class _MailController implements MailSender
{

    protected $mail;
    protected $branding;
    protected $view;
    protected $em;
    protected $mixpanel;

    public static $ignoreEmailTracking = ['MagicLink', 'LoyaltyStampTemplate'];

    /**
     * _MailController constructor.
     * @param EntityManager $em
     * @throws phpmailerException
     */

    public function __construct(EntityManager $em)
    {
        $this->em       = $em;
        $mail           = new Mailer();
        $this->mail     = $mail->getConfig();
        $createView     = new TwigEnvironmentLoader();
        $this->view     = $createView->getView();
        $this->branding = new _BrandingController($this->em);
        $this->mixpanel = new _Mixpanel();
    }

    public function test(Request $request, Response $response)
    {
        $mail = new Mailer();
        return $response->withJson($mail->test(), 200);
    }

    /**
     * @param array $sendTo
     * @param array $args
     * @param string $subject
     * @param array $message
     * @return array
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws phpmailerException
     */
    public function plain(array $sendTo, array $args, string $subject, array $message)
    {

        $this->mail->CharSet = 'UTF-8';
        $args['branding']    = $this->branding->defaults();


        foreach ($sendTo as $key => $value) {
            $this->mail->addAddress($value['to'], $value['name']);
        }

        if ($args['branding']['name'] === 'Stampede') {
            $defaultFromName = 'Stampede';
            $defaultReply    = 'feedback@stampede.ai';
        } else {
            $defaultFromName = 'Connecting Mailer';
            $defaultReply    = 'notification@email.getconnecting.io';
        }

        if (isset($message['sendFrom']) && !empty($message['sendFrom'])) {
            $defaultFromName = $message['sendFrom'];
        }

        if (isset($message['replyTo']) && !empty($message['replyTo'])) {
            $defaultReply = $message['replyTo'];
        }

        if ($args['branding']['name'] === 'Stampede') {
            $this->mail->addReplyTo($defaultReply, $defaultFromName);
            $this->mail->setFrom('mail@stampede.ai', $defaultFromName);
        } else {
            $this->mail->setFrom($defaultReply, $defaultFromName);
        }

        $args = array_merge($args, $message);

        $this->mail->Subject = $subject;

        /**
         * Legacy Campaigns
         */
        $legacy = false;
        if (is_null($args['templateType'])) {
            $this->mail->AltBody = $this->view->render('Emails/MarketingEmail/plain.twig', $args);
            $template            = $this->view->render('Emails/MarketingEmail/html.twig', $args);
            $legacy              = true;
        } else {
            if ($args['templateType'] === 'builder') {
                $this->mail->AltBody = $this->view->render('Emails/MarketingHTMLTemplate/plain.twig', $args);
                $template            = $this->view->render('Emails/MarketingHTMLTemplate/html.twig', $args);
            } elseif ($args['templateType'] === 'html') {
                $this->mail->AltBody = $this->view->render('Emails/MarketingHTMLTemplate/plain.twig', $args);
                $template            = $this->view->render('Emails/MarketingHTMLTemplate/html.twig', $args);
            } elseif ($args['templateType'] === 'review') {
                $this->mail->AltBody = $this->view->render('Emails/ReviewTemplate/plain.twig', $args);
                $template            = $this->view->render('Emails/ReviewTemplate/html.twig', $args);
            }
        }

        if (!$legacy && $args['templateType'] !== 'html') {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($template);
            $styles      = $dom->getElementsByTagName('style');
            $styleString = '';

            foreach ($styles as $style) {
                $styleString .= $style->nodeValue;
            }

            $merge = new Emogrifier($template);
            $merge->setCss($styleString);
            $template = $merge->emogrify();
        }

        $this->mail->msgHTML($template);


        if (!$this->mail->send()) {
            return Http::status(400);
        } else {
            if (extension_loaded('newrelic')) {
                foreach ($sendTo as $sentTo) {
                    newrelic_record_custom_event('EmailSent', $sentTo);
                }
            }
        }

        $this->mail->clearReplyTos();
        $this->mail->clearAddresses();

        return Http::status(200, 'MESSAGE_SENT');
    }

    public function preparePlainStructure(string $messageId, string $contents, $templateType, ?array $profile)
    {
        if (!empty($profile)) {
            $contents = str_replace(
                [
                    '{{ profile.first }}', '{{ profile.last }}', '{{ profile.email }}', '{{ profile.company }}'
                ],
                [$profile['first'], $profile['last'], $profile['email'], $profile['company']],
                $contents
            );
        }

        if (preg_match_all('/<a\s[^>]*href=(\"??)(http[^\" >]*?)\\1[^>]*>(.*)<\/a>/siU', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $contents = str_replace(
                    $match[2],
                    'https://api.stampede.ai/public/redirect?url=' . urlencode($match[2]) . '&profileId=' . $profile['id'],
                    $contents
                );
            }
        }

        $messageArray = [
            'logo'        => '',
            'message'     => $contents,
            'facebook'    => '',
            'twitter'     => '',
            'youtube'     => '',
            'linkedIn'    => '',
            'tripAdvisor' => '',
            'instagram'   => '',
            'company'     => '',
            'line1'       => '',
            'line2'       => '',
            'city'        => '',
            'sendFrom'    => '',
            'replyTo'     => '',
            'profile'     => $profile
        ];

        return $messageArray;
    }

    /**
     * @param array $sendTo
     * @param array $args
     * @param string $template
     * @param string $subject
     * @return array
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws phpmailerException
     */

    public function send(array $sendTo, array $args, string $template, string $subject)
    {

        $templateBeingUsed = $template;
        if (in_array($templateBeingUsed, $this::$ignoreEmailTracking)) {
            $this->mail->clearCustomHeaders();
        }
        $this->mail->CharSet = 'UTF-8';
        $args['branding']    = $this->branding->defaults();

        foreach ($sendTo as $key => $value) {
            $this->mail->addAddress($value['to'], $value['name']);
        }

        if (array_key_exists('send_to', $args)) {
            $this->mail->addReplyTo($args['send_to']['reply_to'], $args['send_to']['send_from']);
            $this->mail->setFrom('mail@stampede.ai', $args['send_to']['send_from']);
        } else {
            $this->mail->addReplyTo('feedback@stampede.ai', 'Stampede Support');
            $this->mail->setFrom('mail@stampede.ai', 'Stampede');
        }

        $this->mail->Subject = $subject;
        $this->mail->AltBody = $this->view->render('Emails/' . $template . '/plain.twig', $args);
        $template            = $this->view->render('Emails/' . $template . '/html.twig', $args);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($template);
        $styles      = $dom->getElementsByTagName('style');
        $styleString = '';

        foreach ($styles as $style) {
            if ($style->getAttribute('data-premailer') === 'ignore') {
                continue;
            }

            $styleString .= $style->nodeValue;
        }

        $merge = new Emogrifier($template);
        $merge->setCss($styleString);
        $template = $merge->emogrify();

        $template = str_replace('</head>', '<style type="text/css">' . $styleString . '</style></head>', $template);

        if (array_key_exists('profile_id', $args) && !is_null($args['profile_id'])) {
            if (preg_match_all('/<a\s[^>]*href=(\"??)(http[^\" >]*?)\\1[^>]*>(.*)<\/a>/siU', $template, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $template = str_replace(
                        $match[2],
                        'https://api.stampede.ai/public/redirect?url=' . urlencode($match[2]) . '&profileId=' . $args['profile_id'],
                        $template
                    );
                }
            }
        }

        $this->mail->msgHTML($template);

        $return = [
            'status'  => 200,
            'message' => 'Message has been sent'
        ];

        if ($templateBeingUsed === 'RegistrationValidation') {

            $this->mail->addCustomHeader(
                "X-SMTPAPI",
                json_encode(
                    [
                        'unique_args' => [
                            'serial'       => $args['serial'],
                            'profileId'    => (string)$args['profileId'],
                            'templateType' => 'validation'
                        ]
                    ]
                )
            );
        }


        if (!$this->mail->send()) {
            $return['status']  = 400;
            $return['message'] = 'Mailer Error: ' . $this->mail->ErrorInfo;
        } else {
            if (extension_loaded('newrelic')) {
                foreach ($sendTo as $sentTo) {
                    newrelic_record_custom_event('EmailSent', $sentTo);
                }
            }
        }

        $this->mail->clearReplyTos();
        $this->mail->clearAddresses();
        return $return;
    }

    public function testRoute(Request $request, Response $response)
    {
        $branding = $this->branding->defaults();

        $settings = $this->em->getRepository(ReviewSettings::class)->find('fcbffe71-7c20-11eb-9334-02a1eec2ac56');
        $template = $this->view->render(
            'Emails/' . $request->getAttribute('template') . '/html.twig',
            $settings->emailArray()
        );
        $dom      = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($template);
        $styles      = $dom->getElementsByTagName('style');
        $styleString = '';

        foreach ($styles as $style) {

            //$styleString .= $style->nodeValue;
        }

        $merge = new Emogrifier($template);
        $merge->setCss($styleString);
        $template = $merge->emogrify();
        $template = str_replace('</head>', '<style type="text/css">' . $styleString . '</style></head>', $template);

        $this->mail->addAddress('patrick@stampede.ai', 'patrick clover');
        $this->mail->msgHTML($template);
        $this->mail->setFrom('mail@stampede.ai', 'Stampede');


        $this->mail->send();
        $response->write($template);

        return $response->withHeader('Content-type', 'text/html');
    }

    public function getMail(): \PHPMailer
    {
        return $this->mail;
    }
};
