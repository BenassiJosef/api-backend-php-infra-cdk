<?php


namespace App\Package\Location;


use App\Models\Locations\LocationSettings;
use App\Package\Location\Exceptions\LocationNotFoundException;
use App\Package\Location\Exceptions\NoLocationInRequestException;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;

/**
 * Class LocationProvider
 * @package App\Package\Location
 */
class LocationProvider
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * LocationProvider constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Request $request
     * @return LocationSettings
     * @throws LocationNotFoundException
     * @throws NoLocationInRequestException
     */
    public function location(Request $request): LocationSettings
    {
        $serial = $request->getAttribute('serial');
        if ($serial === null) {
            throw new NoLocationInRequestException();
        }
        /** @var LocationSettings | null $locationSettings */
        $locationSettings = $this
            ->entityManager
            ->getRepository(LocationSettings::class)
            ->findOneBy(
                [
                    'serial' => $serial,
                ]
            );
        if ($locationSettings === null) {
            throw new LocationNotFoundException($serial);
        }
        return $locationSettings;
    }
}