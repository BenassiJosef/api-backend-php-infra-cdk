<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 04/01/2017
 * Time: 10:55
 */

namespace App\Controllers\Payments;

use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Locations\Alerts\_EmailAlertsController;
use Doctrine\ORM\EntityManager;

class _ReceiptController
{

    protected $mail;
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->mail = new _MailController($this->em);
    }

    /**
     * @param array $items [transaction items]
     * @param array $to [name => 'User Name', to => 'email@email.com']
     * @param string $serial
     */

    public function send(array $items = [], array $to = [], string $serial = '')
    {

        $emailAlerts = new _EmailAlertsController($this->em, $this->mail);
        $alerts      = $emailAlerts->findBySerial($serial);

        $subject   = $alerts['alias'] . ': Receipt';
        $time      = new \DateTime();
        $cleanTime = $time->format('jS \of F Y h:i a');
        $expires   = $time->modify('+ ' . $items['duration'] . ' hour')->format('jS \of F Y h:i a');

        $args = [
            'alias'     => $alerts['alias'],
            'text'      => $alerts['alias'] . ': Payment Confirmation',
            'title'     => 'Payment Receipt',
            'time'      => $time,
            'cleanTime' => $cleanTime,
            'expires'   => $expires,
            'serial'    => $serial,
            'items'     => $items,
            'name'      => $to[0]['name']
        ];

        return $this->mail->send($to, $args, 'PaymentReceipt', $subject);
    }
}
