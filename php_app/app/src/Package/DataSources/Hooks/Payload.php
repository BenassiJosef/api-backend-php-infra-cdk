<?php

namespace App\Package\DataSources\Hooks;

use App\Models\DataSources\DataSource;
use App\Models\DataSources\Interaction;
use App\Models\UserProfile;

interface Payload
{
    /**
     * @return DataSource
     */
    public function getDataSource(): DataSource;

    /**
     * @return Interaction
     */
    public function getInteraction(): Interaction;

    /**
     * @return UserProfile
     */
    public function getUserProfile(): UserProfile;
}