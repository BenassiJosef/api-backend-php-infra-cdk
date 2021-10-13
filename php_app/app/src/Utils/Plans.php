<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 05/04/2017
 * Time: 15:45
 */

namespace App\Utils;

class Plans
{
    public static function getPlansByCountry($country)
    {
        $baseCountryBasePlans = [
            'GB_SMALL',
            'GB_SMALL_AN',
            'GB_MEDIUM',
            'GB_MEDIUM_AN',
            'GB_LARGE',
            'GB_LARGE_AN'
        ];

        $baseCountryAddOns = [
            'GB_CONTENT_FILTER',
            'GB_CONTENT_FILTER_AN'
        ];

        $baseCountryTopUps = [
            'GB_MARKETING_SM',
            'GB_MARKETING_SM_AN',
            'GB_MARKETING_MD',
            'GB_MARKETING_MD_AN',
            'GB_MARKETING_LG',
            'GB_MARKETING_LG_AN'
        ];

        $plans = [
            'base'      => [],
            'content'   => [],
            'marketing' => []
        ];

        foreach ($baseCountryBasePlans as $plan) {
            str_replace('GB', $country, $plan);
            $plans['base'][] = $plan;
        }

        foreach ($baseCountryAddOns as $plan) {
            str_replace('GB', $country, $plan);
            $plans['content'][] = $plan;
        }

        foreach ($baseCountryTopUps as $plan) {
            str_replace('GB', $country, $plan);
            $plans['marketing'][] = $plan;
        }

        return $plans;
    }
}
