<?php

use App\Services\PlanDataService;

it('uses canonical weekday keys for all seven plan days', function () {
    expect(PlanDataService::DAYS)->toBe([
        'sat',
        'sun',
        'mon',
        'tue',
        'wed',
        'thu',
        'fri',
    ]);
});

it('extracts clients shift ids and time strings without losing their values', function () {
    $data = [
        'tue_clients' => ['12', '13', '12'],
        'tue_am_shift' => '21',
        'tue_am_time' => '09:30',
        'tue_pm_shift' => '22',
        'tue_pm_time' => '15:45',
        'unrelated' => 'preserved',
    ];

    $planData = PlanDataService::extract($data);

    expect($planData)->toBe([
        'tue_clients' => [12, 13],
        'tue_am_shift' => 21,
        'tue_pm_shift' => 22,
        'tue_am_time' => '09:30',
        'tue_pm_time' => '15:45',
    ])->and($data)->toBe(['unrelated' => 'preserved']);
});

it('keeps shift data when no general clients are selected', function () {
    $data = [
        'wed_clients' => [],
        'wed_am_shift' => '31',
        'wed_am_time' => '08:00',
    ];

    expect(PlanDataService::extract($data))->toBe([
        'wed_am_shift' => 31,
        'wed_am_time' => '08:00',
    ]);
});

it('normalizes legacy Tuesday through Thursday plan keys', function () {
    expect(PlanDataService::normalize([
        'tues_clients' => [1],
        'wednes_am_shift' => 2,
        'thurs_pm_time' => '16:00',
        'fri_clients' => [3],
    ]))->toBe([
        'fri_clients' => [3],
        'tue_clients' => [1],
        'wed_am_shift' => 2,
        'thu_pm_time' => '16:00',
    ]);
});
