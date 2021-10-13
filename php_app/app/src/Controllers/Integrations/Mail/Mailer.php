<?php


namespace App\Controllers\Integrations\Mail;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{

    /**
     * @var PHPMailer $mail
     */
    public $mail;

    public function __construct()
    {

        $this->mail = new PHPMailer();

        $this->mail->isSMTP();
        $this->mail->SMTPAuth = true;

        if (getenv('mail_secure') !== "false") {
            $this->mail->SMTPSecure = 'tls';
        }
        $this->mail->SMTPDebug = false;
        $this->mail->Host = getenv('mail_host');
        $this->mail->Username = getenv('mail_username');
        $this->mail->Password = getenv('mail_password');
        $this->mail->Port = getenv('mail_port');
        /**Can be used to send callbacks to stage */
        $this->mail->addCustomHeader("X-SES-CONFIGURATION-SET", getenv('mail_config'));
        $this->mail->isHTML(true);
    }



    public function getConfig(): PHPMailer
    {
        return $this->mail;
    }

    public function test()
    {
        try {
            $this->mail->Subject = 'Just Testing';
            $this->mail->Body = '<h1>Email Test</h1>
    <p>This email was sent through the
    <a href="https://aws.amazon.com/ses">Amazon SES</a> SMTP
    interface using the <a href="https://github.com/PHPMailer/PHPMailer">
    PHPMailer</a> class.</p>';
            $this->mail->setFrom('mail@stampede.ai', 'Stampede Test Mailer');
            $this->mail->addAddress('patrickclover@gmail.com', 'Patrick Clover');

            $this->mail->addCustomHeader("X-SES-CONFIGURATION-SET", "Engagement");

            $this->mail->addCustomHeader("X-SES-MESSAGE-SET", json_encode([
                'templateType' => 'marketing',
                'profileId' => 0,
                'campaignId' => 123
            ]));

            if ($this->mail->send()) {
                return true;
            } else {
                return $this->mail->ErrorInfo;
            }
        } catch (Exception $e) {
            return $this->mail->ErrorInfo;
        }
    }
}
