<?php
/**
 * Created by jamieaitken on 27/04/2018 at 16:43
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations;


class DebuggerStatus
{
    private $category;
    private $show;
    private $description;
    private $categories = [
        'danger',
        'danger-light',
        'warning',
        'success'
    ];
    private $debuggerKind;

    public function __construct()
    {
        $this->description  = '';
        $this->category     = 'danger';
        $this->show         = true;
        $this->debuggerKind = '';
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category): void
    {
        if (in_array($category, $this->categories)) {
            $this->category = $category;
        }
    }


    public function setToShow($toShow): void
    {
        $this->show = $toShow;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setDebuggerKind(string $kind): void
    {
        $this->debuggerKind = $kind;
    }

    public function serialize()
    {
        return get_object_vars($this);
    }

}