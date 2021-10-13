<?php
/**
 * Created by jamieaitken on 10/01/2019 at 17:27
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Tests\Validators;

use App\Utils\Validators\Email;
use Doctrine\ORM\EntityManager;

class EmailValidationTest extends OrmTest
{
    private $em;

    public function __construct(
        ?string $name = null,
        array $data = [],
        string $dataName = '',
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;
        parent::__construct($name, $data, $dataName);
    }

    public function testIsValidTrue()
    {
        $newValidator = new Email($this->em);

        $validation = $newValidator->isValid([
            'email' => 'dev@stampede.ai'
        ]);

        $this->assertEquals(200, $validation['status']);
    }

    public function testIsValidFalse()
    {
        $newValidator = new Email($this->em);

        $this->

        $validation = $newValidator->isValid([
            'email' => 'dev@sharklasers.com'
        ]);

        $this->assertEquals(400, $validation['status']);
    }

    public function testIsValidNoEmail()
    {
        $newValidator = new Email($this->em);

        $validation = $newValidator->isValid([]);

        $this->assertEquals(400, $validation['status']);
    }
}
