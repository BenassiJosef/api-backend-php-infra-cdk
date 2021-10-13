<?php

namespace StampedeTests\app\src\Package\Segments\Values\YearDate;

use App\Package\Segments\Values\YearDate\YearDate;
use App\Package\Segments\Values\YearDate\YearDateRangeFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class YearDateRangeFactoryTest extends TestCase
{
    public function testFromString()
    {
        $factory = new YearDateRangeFactory(
            YearDateRangeFactory::WEEK_START_MONDAY,
            DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                '2021-02-01 11:12:54'
            ),
        );

        $expectedDates = [];
        for ($i = 1; $i <= 28; $i++) {
            $expectedDates[] = [
                'Month' => 'Feb',
                'Day'   => $i,
            ];
        }

        $range    = $factory->fromString('this-month');
        $gotDates = [];
        foreach ($range->values() as $value) {
            if ($value instanceof YearDate) {
                $gotDates[] = [
                    'Month' => $value->getMonth(),
                    'Day'   => $value->getDay(),
                ];
            }
        }
        self::assertEquals($expectedDates, $gotDates);
    }

}
