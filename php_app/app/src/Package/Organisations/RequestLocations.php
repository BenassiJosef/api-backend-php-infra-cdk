<?php


namespace App\Package\Organisations;


use App\Models\Locations\LocationSettings;
use App\Models\Organization;

class RequestLocations
{

    /**
     * @var Organization $commonParent
     */
    private $commonParent;

    /**
     * @var string[] $serials
     */
    private $serials;

    /**
     * RequestLocations constructor.
     * @param Organization $commonParent
     * @param string[] $serials
     */
    public function __construct(Organization $commonParent, array $serials = [])
    {
        $this->commonParent = $commonParent;
        $this->serials      = $serials;
    }


    /**
     * @return Organization
     */
    public function commonParent(): Organization
    {
        return $this->commonParent;
    }

    /**
     * @return string[]
     */
    public function serials(): array
    {
        return $this->serials;
    }
}