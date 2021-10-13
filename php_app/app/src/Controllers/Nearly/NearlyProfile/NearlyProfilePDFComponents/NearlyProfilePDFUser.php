<?php
/**
 * Created by jamieaitken on 03/05/2018 at 20:33
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents;


class NearlyProfilePDFUser extends NearlyProfilePDFComponent
{

    public function create()
    {
        $profileHtmlLeft = "<table>" . $this->formatDetails('Name', $this->contents['name']);
        $profileHtmlLeft .= $this->formatDetails('Email', $this->contents['email']);
        $profileHtmlLeft .= $this->formatDetails('Gender', $this->contents['gender']);
        $profileHtmlLeft .= $this->formatDetails('Phone', $this->contents['phone']);
        $profileHtmlLeft .= $this->formatDetails('Age', $this->contents['age']) . "</table>";

        $profileHtmlRight = "<table>" . $this->formatDetails('Postcode', $this->contents['postcode']);
        $profileHtmlRight .= $this->formatDetails('Postcode Valid', $this->contents['postcodeValid']);
        $profileHtmlRight .= $this->formatDetails('Birth Month', $this->contents['birthMonth']);
        $profileHtmlRight .= $this->formatDetails('Birth Day', $this->contents['birthDay']);
        $profileHtmlRight .= $this->formatDetails('Verified', $this->contents['verified']) . "</table></br>";

        $this->contents = [$profileHtmlLeft, $profileHtmlRight];
    }
}