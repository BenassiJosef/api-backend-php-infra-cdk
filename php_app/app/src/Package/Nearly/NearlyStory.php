<?php

namespace App\Package\Nearly;

use App\Controllers\Integrations\S3\S3;
use Doctrine\ORM\EntityManager;
use App\Models\Nearly\Stories\NearlyStory as NearlyStoriesNearlyStory;
use App\Models\Nearly\Stories\NearlyStoryPage;
use App\Models\Nearly\Stories\NearlyStoryPageActivity;

class NearlyStory
{


    /**
     * @var S3 $s3
     */
    protected $s3;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * MarketingController constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager

    ) {
        $this->entityManager = $entityManager;
        $this->s3          = new S3('nearly.online', 'eu-west-1');
    }

    public function load(NearlyOutput $output)
    {

        if ($output->getBlocked() || !$output->getProfile()) {
            return;
        }
        $serial = $output->getLocation()->getSerial();
        /**
         *@var NearlyStoriesNearlyStory $page
         */
        $page =  $this->entityManager
            ->getRepository(NearlyStoriesNearlyStory::class)->findOneBy(['serial' =>
            $serial]);

        if (is_null($page)) {
            return;
        }
        if (!is_null($page)) {
            /**
             *@var NearlyStoryPage[] $pages
             */
            $pages =  $this->entityManager
                ->getRepository(NearlyStoryPage::class)->findBy(['storyId' => $page->getId(), 'isArchived' => false]);
            $this->trackActivity($pages, $serial, $output->getProfile()->getId());
            //$this->parsePages($pages, $serial);
            $page->setPages($pages);
        }
        $output->setStory($page);
    }

    /**
     * @param NearlyStoryPage[] $pages
     */

    public function trackActivity(array $pages, string $serial, int $profileId)
    {
        foreach ($pages as $page) {

            $newActivity = new NearlyStoryPageActivity(
                $page->getId(),
                $profileId,
                $serial
            );
            $this->entityManager->persist($newActivity);
            $page->setTrackingId($newActivity->id);
        }
    }

    /**
     * @param NearlyStoryPage[] $pages
     */
    public function parsePages(array $pages, string $serial)
    {
        $base = 'static/media/stories/';

        foreach ($pages as $page) {
            $img = $page->getImageSrc();

            $tmp = explode('.', $img);
            $extension = end($tmp);
            $file      = $base . $serial . '/' . $page->getId() . '.' . $extension;
            $newImage = $img;
            if (!$this->s3->doesObjectExist($file)) {
                $upload = $this->s3->upload(
                    $file,
                    'string',
                    $img,
                    'public-read',
                    [
                        'CacheControl' => 'max-age=3600'
                    ]
                );

                $newImage = $upload;
            } else {
                $newImage = $this->s3->AbsoluteBucket() . $file;
            }
            $page->setImageSrc($newImage);
        }
    }
}
