<?php

namespace Tests\Feature;

use App\Helpers\DateHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VisitDatesFeatureTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCalculateVisitDates()
    {
        $visitDates = DateHelper::calculateVisitDates();

        $this->assertIsArray($visitDates);
        $this->assertCount(7, $visitDates);

        // Assert that each date is in the correct format and is a valid date
        foreach ($visitDates as $date) {
            $this->assertIsString($date);
            $this->assertNotEmpty($date);
            $this->assertRegExp('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date);
            $this->assertTrue(checkdate(substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4)));
        }

        // Assert that the dates are in the correct order
        $this->assertTrue($visitDates[0] < $visitDates[1]);
        $this->assertTrue($visitDates[1] < $visitDates[2]);
        $this->assertTrue($visitDates[2] < $visitDates[3]);
        $this->assertTrue($visitDates[3] < $visitDates[4]);
        $this->assertTrue($visitDates[4] < $visitDates[5]);
        $this->assertTrue($visitDates[5] < $visitDates[6]);
    }
}
