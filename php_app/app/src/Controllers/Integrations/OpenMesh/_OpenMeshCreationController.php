<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 01/02/2017
 * Time: 21:39
 */

namespace App\Controllers\Integrations\OpenMesh;

use App\Controllers\Locations\_LocationCreationController;
use App\Models\RadiusVendor;
use Doctrine\ORM\EntityManager;

class _OpenMeshCreationController extends _LocationCreationController
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function createLocation(?string $serial)
    {
        if (is_null($serial)) {
            $serial = parent::serialGenerator();
        }

        $openMesh = new RadiusVendor($serial, null, 'OPENMESH');
        $this->em->persist($openMesh);
        $this->em->flush();

        return $serial;
    }
}
