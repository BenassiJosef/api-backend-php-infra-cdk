<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 14/12/2016
 * Time: 17:44
 */

namespace App\Controllers\Nodes;

use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Locations\Alerts\_EmailAlertsController;
use App\Models;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;

class _NodesController
{

    protected $em;

    protected $infrastructureCache;

    protected $mail;

    public function __construct(EntityManager $em, _MailController $mail)
    {
        $this->em                  = $em;
        $this->mail                = $mail;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
    }

    /**
     * @param int $id
     * @param int $status
     */

    public function inform(int $id = 0, int $status = 0)
    {

        $node = $this->getById($id);

        if (!empty($node)) {
            $event = [
                'code'   => 2,
                'name'   => '',
                'alerts' => true
            ];

            $serial        = $node['serial'];
            $flickerTime   = date('Y-m-d H:i:s', strtotime('+12 minutes', strtotime($node['lastping'])));
            $deviceFlicker = (new \DateTime() > new \DateTime($flickerTime));

            if ($status === 0) {
                $node['down_count']++;
                $event['name'] = 'NODE_OFFLINE';
                $event['code'] = 0;
            }

            if ($status === 1) {
                $node['up_count']++;
                $node['lastping'] = new \DateTime();
                $event['name']    = 'NODE_ONLINE';
            }

            if ($node['down_count'] >= 50 && $node['up_count'] >= 50) {
                $event['alerts'] = false;
            }

            if ($node['status'] === 3 && $status === 1) {
                //NODE WAS PENDING, NOW ONLINE
            }

            if ($node['status'] === 3 && $status === 0) {
                //NODE WAS PENDING, NOW Offline
            }

            if ($node['status'] === 0 && $status === 1) {
                //NODE WAS OFFLINE, NOW ONLINE
            }

            if ($node['status'] === 1 && $status === 0) {
                //NODE WAS ONLINE, NOW OFFLINE
            }

            if ($event['alerts'] === true && $node['status'] !== $status && $deviceFlicker) {
                $this->mailChange($status, $node['alias'], $node['serial'], $node['lastping']);
            }
        }
    }

    /**
     * @param $mac
     * @return array
     */

    public function getByMac(string $mac)
    {
        $getFromCache = $this->infrastructureCache->fetch('accessPoints:' . $mac);
        if ($getFromCache === false) {
            $results = $this->em->createQueryBuilder()
                ->select('n')
                ->from(Models\NodeDetails::class, 'n')
                ->where('n.mac = :mac')
                ->andWhere('n.deleted = 0')
                ->setParameter('mac', $mac)
                ->setMaxResults(1)
                ->getQuery()
                ->getArrayResult();

            $send = [];

            if (!empty($results)) {
                $send = $results[0];
                $this->infrastructureCache->save('accessPoints:' . $mac, $results[0]);
            }

            return $send;
        }

        return $getFromCache;
    }

    public function getByWanIP(string $ip)
    {
        $results = $this->em->createQueryBuilder()
            ->select('a')
            ->from(Models\NodeDetails::class, 'a')
            ->where('a.wanIp = :ip')
            ->andWhere('a.deleted = 0')
            ->setParameter('ip', $ip)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        $send = [];

        if (!empty($results)) {
            $send = $results[0];
        }

        return $send;
    }

    public function findUnknownNetworksAndAssign(string $wanIp)
    {
        $findSerial = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(Models\NodeDetails::class, 'u')
            ->where('u.wanIp = :wanIp')
            ->where('u.serial <> NULL OR :un')
            ->setParameter('wanIp', $wanIp)
            ->setParameter('un', 'UNKNOWN')
            ->getQuery()
            ->setMaxResults(1)
            ->getArrayResult();
        if (empty($findSerial)) {
            return false;
        }

        $this->em->createQueryBuilder()
            ->update(Models\NodeDetails::class, 'p')
            ->set('p.serial', $findSerial[0])
            ->where('p.wanIp = :wanIp')
            ->setParameter('wanIp', $wanIp)
            ->getQuery()
            ->execute();

        return true;

    }

    /**
     * @param $id
     * @return array
     */

    public function getById($id = 0)
    {
        $results = $this->em->createQueryBuilder()
            ->select('n')
            ->from(Models\NodeDetails::class, 'n')
            ->where('n.id = :id')
            ->andWhere('n.deleted = 0')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        $send = [];

        if (!empty($results)) {
            $send = $results[0];
        }

        return $send;
    }

    /**
     * @param int $status
     * @param string $alias
     * @param string $serial
     * @param string $lastseen
     */

    public function mailChange($status = 0, $alias = '', $serial = '', $lastseen = '')
    {

        $emailAlerts = new _EmailAlertsController($this->em, $this->mail);
        $alerts      = $emailAlerts->findBySerial($serial);

        $eventKey = 'node_online';

        if ($status === 0) {
            $eventKey = 'node_offline';
        }

        if (!empty($alerts)) {
            if ($alerts['types'][$eventKey] === true) {
                $subject = $alerts['alias'] . ': Device ' . $alias . ' Online';

                $args = [
                    'alias'  => $alias,
                    'text'   => $alias . ' has just come back online. The last time this device checked in was ' . $lastseen,
                    'title'  => 'Device UP',
                    'time'   => $lastseen,
                    'serial' => $serial
                ];

                if ($status === 0) {
                    $args['text']  = $alias . 'has just gone offline, this is either down to lack of power or internet.';
                    $args['title'] = 'Device DOWN';
                    $subject       = $alerts['alias'] . ': Device ' . $alias . ' Offline';
                }

                $sendTo = [];

                foreach ($alerts['list'] as $member) {
                    $sendTo[] = [
                        'name' => $member['name'],
                        'to'   => $member['email']
                    ];
                }

                $this->mail->send($sendTo, $args, 'DeviceAlert', $subject);
            }
        }
    }
}
