<?php
/**
 * Created by chrisgreening on 18/02/2020 at 11:45
 * Copyright © 2020 Captive Ltd. All rights reserved.
 */

namespace StampedeTests\app\src\Package\Organisations;

use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganisationIdProviderException;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

class OrganisationIdProviderTest extends TestCase
{

    private $em;
    private $logger;
    /**
     * @var \App\Models\OauthUser
     */
    private $owner;
    /**
     * @var \App\Models\Organization
     */
    private $org;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();
        $this->logger = $this->createMock(Logger::class);

        $this->owner = EntityHelpers::createOauthUser($this->em, "bob@banana.com", "password1", "", "aaa");
        $this->org = EntityHelpers::createOrganisation($this->em, "Org1", $this->owner);

        $this->em->clear();
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }

    public function testGetIds()
    {
        $idPRovider = new OrganisationIdProvider($this->em);
        try {
            $foundOrg = $idPRovider->getIds($this->owner->getUid());
            self::assertNotNull($foundOrg, "Should find by owner id");
        } catch(\Throwable $ex) {
            self::fail($ex->getMessage());
        }

        try {
            $foundOrg = $idPRovider->getIds($this->org->getId());
            self::assertNotNull($foundOrg, "Should find by org id");
        } catch(\Throwable $ex) {
            self::fail($ex->getMessage());
        }

        self::expectException(OrganisationIdProviderException::class);
        $idPRovider->getIds("811338DC-59F1-47F1-8949-2D4AE2A4D647");
    }
}
