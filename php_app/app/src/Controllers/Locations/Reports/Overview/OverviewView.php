<?php


namespace App\Controllers\Locations\Reports\Overview;


use Doctrine\ORM\EntityManager;

/**
 * Class OverviewView
 * @package App\Controllers\Locations\Reports\Overview
 */
final class OverviewView implements View
{
    /**
     * @var View[] $views
     */
    private $views;

    /**
     * OverviewView constructor.
     * @param View ...$views
     */
    public function __construct(View ...$views)
    {
        $this->views = $views;
    }

    /**
     * @param Overview $overview
     * @param array $serials
     * @return Overview
     */
    public function addDataToOverview(Overview $overview, array $serials): Overview
    {
        foreach ($this->views as $view){
            $overview = $view->addDataToOverview($overview, $serials);
        }
        return $overview;
    }
}