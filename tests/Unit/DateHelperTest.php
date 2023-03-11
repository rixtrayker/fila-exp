<?php

namespace Tests\Unit;

use App\Helpers\DateHelper;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testGetFirstOfWeek()
    {
        Carbon::setTestNow(Carbon::create(2023, 3, 10)); // Set the current date to a Friday

        $this->assertEquals('2023-03-11', DateHelper::getFirstOfWeek(true)->format('Y-m-d')); // Expected output: next Saturday
        $this->assertEquals('2023-03-04', DateHelper::getFirstOfWeek()->format('Y-m-d')); // Expected output: last Saturday

        Carbon::setTestNow(Carbon::create(2023, 3, 11)->format('Y-m-d')); // Set the current date to a Saturday

        $this->assertEquals('2023-03-11', DateHelper::getFirstOfWeek(true)->format('Y-m-d')); // Expected output: today
        $this->assertEquals('2023-03-11', DateHelper::getFirstOfWeek()->format('Y-m-d')); // Expected output: last Saturday

        Carbon::setTestNow(Carbon::create(2023, 3, 12)->format('Y-m-d')); // Set the current date to a Sunday

        $this->assertEquals('2023-03-18', DateHelper::getFirstOfWeek(true)->format('Y-m-d')); // Expected output: today
        $this->assertEquals('2023-03-11', DateHelper::getFirstOfWeek()->format('Y-m-d')); // Expected output: today
    }
}
