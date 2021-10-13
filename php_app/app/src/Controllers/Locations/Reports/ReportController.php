<?php
/**
 * Created by jamieaitken on 07/06/2018 at 10:08
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports;


use App\Controllers\Integrations\Uploads\_UploadStorageController;
use Doctrine\ORM\EntityManager;

class ReportController
{
    protected $upload;
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em     = $em;
        $this->upload = new _UploadStorageController($em);
    }

    public function fileExists(string $path, string $kind)
    {
        $fileCheck = $this->upload->checkFile($path, $kind);
        if ($fileCheck['status'] === 200) {
            return substr($fileCheck['message'], 0, strlen($fileCheck['message']) - 4);
        }

        return false;
    }


}