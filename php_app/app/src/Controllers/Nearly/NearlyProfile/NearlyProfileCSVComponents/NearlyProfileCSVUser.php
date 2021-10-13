<?php
/**
 * Created by jamieaitken on 15/05/2018 at 12:36
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents;


class NearlyProfileCSVUser extends NearlyProfileCSVComponent
{

    public function create()
    {
        $this->contents = [
            $this->formatDetails('Name', $this->contents['name']),
            $this->formatDetails('Email', $this->contents['email']),
            $this->formatDetails('Gender', $this->contents['gender']),
            $this->formatDetails('Phone', $this->contents['phone']),
            $this->formatDetails('Age', $this->contents['age']),
            $this->formatDetails('Postcode', $this->contents['postcode']),
            $this->formatDetails('Postcode Valid', $this->contents['postcodeValid']),
            $this->formatDetails('Birth Month', $this->contents['birthMonth']),
            $this->formatDetails('Birth Day', $this->contents['birthDay']),
            $this->formatDetails('Verified', $this->contents['verified'])
        ];
    }
}