<?php
/**
 * Created by jamieaitken on 08/12/2017 at 15:24
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Utils;


use BrianMcdo\ImagePalette\ImagePalette;
use ColorThief\ColorThief;

class DominantImageColor
{
    private $palette;

    public function __construct(string $imageUrl)
    {
        //$this->palette = new ImagePalette($imageUrl, 100, 3);
        $this->palette = ColorThief::getPalette($imageUrl, 2, 10);
    }

    public function getColors()
    {
        $colors = $this->palette->getColors();
        foreach ($colors as $key => $value) {
            $colors[$key] = $value->rgbaString;
        }

        return $colors;
    }

    /**
     * @return mixed
     */
    public function getPalette()
    {
        foreach ($this->palette as $key => $color) {
            $this->palette[$key] = 'rgba(' . $color[0] . ',' . $color[1] . ',' . $color[2] . ',1)';
        }

        return $this->palette;
    }
}