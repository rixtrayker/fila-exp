<?php

namespace App\Filament\Resources\VisitResource\Pages\Traits;

use Illuminate\Support\Facades\Auth;

trait UserDataHandler
{
    protected function setUserData(array &$data): void
    {
        $isMedicalRep = $this->isMedicalRep();

        if ($isMedicalRep) {
            $data['user_id'] = Auth::id();
            $data['visit_date'] = today();
        }

        $data['status'] = 'visited';
    }

    protected function isMedicalRep(): bool
    {
        return auth()->user()?->hasRole('medical-rep');
    }
}
