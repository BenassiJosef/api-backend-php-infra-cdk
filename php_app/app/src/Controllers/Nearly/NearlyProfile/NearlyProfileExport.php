<?php
/**
 * Created by jamieaitken on 03/05/2018 at 11:34
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile;


use Psr\Http\Message\ResponseInterface;

abstract class NearlyProfileExport
{
    protected $userId;
    protected $serials;
    protected $profile;


    public function __construct(string $userId, $serials)
    {
        $this->userId  = $userId;
        $this->serials = $serials;
    }

    public abstract function create(array $profile);

    public abstract function download(
        string $filename,
        string $contents,
        ResponseInterface $response,
        string $profileId
    );
}