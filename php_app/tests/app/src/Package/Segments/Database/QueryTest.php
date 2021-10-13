<?php

namespace StampedeTests\app\src\Package\Segments;

use App\Models\Organization;
use App\Package\Segments\Database\Query;
use App\Package\Segments\Database\QueryFactory;
use App\Package\Segments\Segment;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;

class QueryTest extends TestCase
{

  /**
   * @var EntityManager $entityManager
   */
  private $entityManager;

  /**
   * @var Organization $organization
   */
  private $organization;

  /**
   * @var QueryFactory $queryFactory
   */
  private $queryFactory;

  protected function setUp(): void
  {
    $this->entityManager = DoctrineHelpers::createEntityManager();
    $this->entityManager->beginTransaction();
    $this->organization = $this
      ->entityManager
      ->getRepository(Organization::class)
      ->findOneBy(
        [
          'name' => 'Some Company Ltd'
        ]
      );

    $this->queryFactory = new QueryFactory($this->entityManager);
  }

  protected function tearDown(): void
  {
    $this->entityManager->rollback();
  }


  public function testBuild()
  {
    $segmentJson = '
{
  "root": {
    "operator": "and",
    "nodes": [
      {
        "comparison": "==",
        "value": "wifi",
        "field": "dataSource"
      },
      {
        "comparison": "==",
        "value": "web",
        "field": "dataSource"
      }
    ]
  }
}';
    $query = $this
      ->queryFactory
      ->make(
        $this->organization,
        Segment::fromJsonString($segmentJson)
      );

    echo ($query->build()->getSQL());
    echo ($query->build()->getDQL());
    echo ("pigfucker");
    $expectedQuery =   "SELECT MAX(DISTINCT up.id), MIN(or1.lastInteractedAt), MIN(or1.createdAt), GROUP_CONCAT(DISTINCT up.email), GROUP_CONCAT(DISTINCT up.first), GROUP_CONCAT(DISTINCT up.last), MAX(DISTINCT or1.profileId), GROUP_CONCAT(DISTINCT ds.key) FROM App\Models\DataSources\OrganizationRegistration or1 LEFT JOIN App\Models\UserProfile up WITH or1.profileId = up.id LEFT JOIN App\Models\DataSources\RegistrationSource rs1 WITH or1.id = rs1.organizationRegistrationId LEFT JOIN App\Models\DataSources\DataSource ds WITH rs1.dataSourceId = ds.id WHERE or1.organizationId = :organizationId AND or1.dataOptInAt IS NOT NULL AND ((EXISTS(SELECT ds1.key FROM App\Models\DataSources\RegistrationSource rs LEFT JOIN App\Models\DataSources\DataSource ds1 WITH rs.dataSourceId = ds1.id WHERE or1.id = rs.organizationRegistrationId AND ds1.key = :dataSource)) AND (EXISTS(SELECT ds2.key FROM App\Models\DataSources\RegistrationSource rs2 WHERE or1.id = rs2.organizationRegistrationId AND ds2.key = :dataSource_1))) GROUP BY or1.profileId ORDER BY or1.lastInteractedAt DESC";
    self::assertEquals($expectedQuery, $query->build()->getDQL());
  }
}
