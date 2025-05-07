<?php

namespace App\Filament\Resources\VisitResource\Pages\Traits;

use App\Models\Visit;
use Filament\Notifications\Notification;

trait VisitHandler
{
    protected function respondToExistingVisit(Visit $visit): void
    {
        if ($visit->plan_id) {
            if ($visit->status === 'visited') {
                $this->dailyVisitNotification(true)?->send();
            } else {
                $this->dailyVisitNotification()?->send();
            }
        }

        if ($visit->status === 'visited') {
            $this->alreadyVisitedClientNotification()?->send();
        }

        $this->redirect('/admin/visits');
    }

    protected function dailyVisitNotification(bool $alreadyAdded = false): ?Notification
    {
        if ($alreadyAdded) {
            $title = 'Daily Visit Already Done';
            return Notification::make()
                ->warning()
                ->title($title);
        }

        return Notification::make()
            ->warning()
            ->title('Daily Visit Added')
            ->body('Try to edit the visit from your daily plan');
    }

    protected function alreadyVisitedClientNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Already Visited Client Today');
    }

    protected function resetForm(): void
    {
        $this->form->model($this->record::class);
        $this->record = null;
        $this->fillForm();
    }
}
