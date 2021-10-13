<?php
/**
 * Created by jamieaitken on 03/05/2018 at 16:50
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents;

class NearlyProfilePDFMarketing extends NearlyProfilePDFComponent
{

    public function create()
    {
        $html = "";
        foreach ($this->contents as $key => $value) {
            if ($this->contents['marketing']['totals']['sms'] > 0 && $this->contents['marketing']['totals']['email'] > 0) {
                foreach ($this->contents['marketing']['locations'] as $k => $location) {

                    $html .= "<tr><td>Location:</td><td>" . $location['name'] . "</td></tr>
                    <tr><td>Emails Received:</td><td>" . $location['email'] . "</td></tr>
                    <tr><td>SMS Received:</td><td>" . $location['sms'] . "</td></tr></table>";

                    foreach ($location['events'] as $event) {
                        $html .= "<tr><td>Sent:</td><td>" . $event['timestamp']->format('g:ia \o\n l jS F Y') . "</td></tr>";
                    }
                }
            }
        }

        return $html;
    }
}