<?php

namespace App\Filament\Resources\PlanResource;

use App\Filament\Resources\PlanResource;
use App\Helpers\DateHelper;
use App\Models\Visit;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Illuminate\Support\Str;

class FormBuilder
{
    // Days of the week mapping
    private static array $daysOfWeek = [
        "sat",
        "sun",
        "mon",
        "tues",
        "wednes",
        "thurs",
        "fri",
    ];

    /**
     * Build the form for creating/editing a Plan
     */
    public static function buildForm(Form $form): Form
    {
        return $form->schema([self::makeForm()]);
    }

    /**
     * Get the clients for a specific day in a plan
     */
    public static function getPlanDayState($record, $day)
    {
        if (!$record) {
            return [];
        }

        $dates = self::dates();
        if (!isset($dates[$day])) {
            return [];
        }

        return Visit::where("plan_id", $record->id)
            ->where("visit_date", $dates[$day])
            ->pluck("client_id")
            ->toArray();
    }

    /**
     * Get visit dates from helper
     */
    public static function dates($startDate = null): array
    {
        return DateHelper::calculateVisitDates($startDate);
    }
    /**
     * Build the form tabs for the weekly plan
     */
    private static function makeForm()
    {
        $tabs = [];
        foreach (self::dates() as $day => $date) {
            $tabs[] = self::makeTab($day);
        }

        return Tabs::make("Weekly Plan")->tabs($tabs)->columnSpanFull();
    }

    /**
     * Create a tab for a specific day
     */
    private static function makeTab($key)
    {
        $day = self::$daysOfWeek[$key];
        $isListPlanPage = fn($record) => request()->fingerprint &&
            Str::contains(request()->fingerprint["name"], "list-plans");

        return Tabs\Tab::make($day)
            ->label(function ($record) use ($key) {
                $startDate = $record?->start_at ?? DateHelper::getFirstOfWeek();
                $date = $startDate->addDays($key)->format("D M-d");
                return $date;
            })
            ->schema([
                Select::make($day . "_am_shift")
                    ->label("AM shift")
                    ->searchable()
                    ->default(
                        state: fn($record) => $isListPlanPage($record)
                            ? $record->shiftClient($day)?->am_shift
                            : null
                    )
                    // ->getSearchResultsUsing(
                    //     fn(
                    //         string $search
                    //     ) => ClientManager::searchClientsByType($search)
                    // )
                    ->options(PlanResource::getClients("am", $day))
                    ->searchable()
                    ->preload(),

                TimePicker::make($day . "_time_am")
                    ->default(
                        fn($record) => $isListPlanPage($record)
                            ? $record->shiftClient($day)?->am_time
                            : null
                    )
                    ->label("AM time")
                    ->native(false)
                    ->withoutSeconds(),

                Select::make($day . "_pm_shift")
                    ->label("PM shift")
                    ->searchable()
                    ->default(
                        fn($record) => $isListPlanPage($record)
                            ? $record->shiftClient($day)?->pm_shift
                            : null
                    )
                    // ->getSearchResultsUsing(
                    //     fn(
                    //         string $search
                    //     ) => ClientManager::searchClientsByType($search, "pm")
                    // )
                    ->searchable()
                    ->options(PlanResource::getClients("pm", $day))
                    ->preload(),

                TimePicker::make($day . "_time_pm")
                    ->default(
                        fn($record) => $isListPlanPage($record)
                            ? $record->shiftClient($day)?->pm_time
                            : null
                    )
                    ->label("PM time")
                    ->native(false)
                    ->withoutSeconds(),

                Select::make($day . "_clients")
                    ->label("Clients")
                    ->multiple()
                    ->options(PlanResource::getClients("all", $day))
                    ->searchable()
                    ->getSearchResultsUsing(
                        fn(string $search) => ClientManager::searchClients(
                            $search
                        )
                    )
                    ->preload(),
            ]);
    }
}
