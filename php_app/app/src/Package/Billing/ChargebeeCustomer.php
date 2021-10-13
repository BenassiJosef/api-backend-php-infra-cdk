<?php

/**
 * Created by chrisgreening on 05/03/2020 at 09:41
 * Copyright © 2020 Captive Ltd. All rights reserved.
 */

namespace  App\Package\Billing;


use App\Models\OauthUser;
use Ramsey\Uuid\Uuid;

class ChargebeeCustomer
{
    /**
     * @var OauthUser $user
     */
    private $user;

    /**
     * @var string $id
     */
    private $id;

    public function __construct(OauthUser $user)
    {
        $this->id   = Uuid::uuid4();
        $this->user = $user;
    }

    public function toChargeBeeCustomer()
    {
        return array_merge(
            [
                'id' => $this->id
            ],
            $this->toChargeBeeCustomerForUpdate()
        );
    }

    public function toChargeBeeCustomerForUpdate()
    {
        return [
            'first_name'             => $this->user->getFirst() ?? '',
            'last_name'              => $this->user->getLast() ?? '',
            'email'                  => $this->user->getEmail(),
            'company'                => $this->user->getCompany() ?? '',
            'taxability'             => 'taxable',
            'allow_direct_debit'     => true,
            'auto_collection'        => 'on',
            'consolidated_invoicing' => true,
            'billing_address'        => [
                'first_name' => $this->user->getFirst() ?? '',
                'last_name'  => $this->user->getLast() ?? '',
                'email'      => $this->user->getEmail(),
                'company'    => $this->user->getCompany() ?? '',
                'country'    => $this->user->getCountry() ?? ''
            ]
        ];
    }

    /**
     * @return OauthUser
     */
    public function getUser(): OauthUser
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
