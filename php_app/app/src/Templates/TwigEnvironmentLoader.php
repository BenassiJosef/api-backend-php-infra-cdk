<?php
/**
 * Created by jamieaitken on 19/02/2018 at 12:26
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Templates;

use App\Filters\TwigFilters;

class TwigEnvironmentLoader
{
    protected $view;

    public function __construct()
    {
        $loader     = new \Twig_Loader_Filesystem(__DIR__);
        $this->view = new \Twig_Environment($loader);
        $this->view->addExtension(new TwigFilters());
    }

    public function getView()
    {
        return $this->view;
    }
}