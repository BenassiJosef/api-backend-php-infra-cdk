<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 28/06/2017
 * Time: 14:47
 */

namespace App\Controllers\Clients;

use Doctrine\ORM\EntityManager;

class _ClientsUpdateController
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    public function update($download = 0, $upload = 0, string $mac = '', string $serial = '', $ip)
    {
        $query = 'UPDATE user_data u SET 
                  u.data_down = :download, 
                  u.data_up = :upload, 
                  u.ip = :ip,
                  u.lastupdate = NOW()
                  WHERE mac = :mac OR mac = :macShadowed
                  AND serial = :serial 
                  ORDER BY u.timestamp 
                  DESC LIMIT 1';

        $builder = $this->em->getConnection();
        $stmt    = $builder->prepare($query);

        $q = $stmt->execute([
            'download'    => $download,
            'upload'      => $upload,
            'ip'          => $ip,
            'serial'      => $serial,
            'mac'         => $mac,
            'macShadowed' => hash('sha512', $mac)
        ]);

        $builder->close();

        if ($q === 1) {
            return true;
        }

        return false;
    }
}
