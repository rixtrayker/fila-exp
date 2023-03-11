<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
    public function afterCreate()
    {
        $users = $this->form->getRawState()['visibleUsers'];
        $this->record->users()->sync($users);

        if(!$this->form->getRawState()['roles'])
            return
        $usersId = $this->record->rolesUsers()->pluck('users.id');
        $this->record->users()->syncWithPivotValues($usersId,['hidden' => 1]);
    }
}
