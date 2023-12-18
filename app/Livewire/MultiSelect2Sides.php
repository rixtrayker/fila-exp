<?php

namespace App\Livewire;

use App\Filament\Resources\PlanResource\Pages\ViewPlan;
use Closure;
use Str;
use LucasGiovanny\FilamentMultiselectTwoSides\Forms\Components\Fields\MultiselectTwoSides;

class MultiSelect2Sides extends MultiselectTwoSides
{
    protected string $view = 'livewire.multi-select2-sides';

    public function defaultSelectOptions(array |Closure | null $options): static {
        $this->afterStateHydrated(fn($component)=>$component->state($options));
        return $this;
    }

    public function selectOption(string $value): void
    {
        if(Str::contains(request()->fingerprint['name'],'list-plans')) return;
        if(Str::contains(request()->fingerprint['name'],'view-plan')) return;

        $state = array_unique(array_merge($this->getState(), [$value]));
        $this->state($state);
    }

    public function unselectOption(string $value): void
    {
        if(Str::contains(request()->fingerprint['name'],'view-plan')) return;
        if(Str::contains(request()->fingerprint['name'],'list-plans')) return;

        $state = $this->getState();
        unset($state[array_search($value, $state)]);
        $this->state($state);
    }

    public function selectAll(): void
    {
        if(Str::contains(request()->fingerprint['name'],'view-plan')) return;
        if(Str::contains(request()->fingerprint['name'],'list-plans')) return;

        $this->state(array_keys($this->getOptions()));
    }

    public function unselectAll(): void
    {
        if(Str::contains(request()->fingerprint['name'],'view-plan')) return;
        if(Str::contains(request()->fingerprint['name'],'list-plans')) return;

        $this->state([]);
    }
    public function getSelectableLabel(): string
    {
        if(request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans'))
            return 'Coming visits';
        return $this->selectableLabel;
    }
    public function getSelectedLabel(): string
    {
        if(request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans'))
            return 'Done Visits';
        return $this->selectedLabel;
    }
    public function showArrows(): string
    {
        if(request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans'))
            return false;
        return true;
    }
}
