<?php

namespace App\Filament\Resources\EditRequestResource\Pages;

use App\Filament\Resources\EditRequestResource;
use App\Livewire\ApproveAction;
use App\Models\EditRequest;
use Filament\Pages\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords\Concerns\CanViewRecords;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Str;

class ViewEditRequest extends ViewRecord
{
    protected static string $resource = EditRequestResource::class;

    public function mount(int | string $record): void
    {
        static::authorizeResourceAccess();
        $this->record = $this->resolveRecord( EditRequest::find($record)?->editable_id );

        abort_unless(static::getResource()::canView($this->getRecord()), 403);
        $editRequest = EditRequest::find(self::getEditRequestId());
        abort_unless($editRequest->status == 'pending', 401);

        $this->fillForm();
    }

    protected function fillForm(): void
    {
       $this->callHook('beforeFill');

        $data = $this->getRecord()->attributesToArray();

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    protected function refreshFormData(array $attributes): void
    {
        $this->data = array_merge(
            $this->data,
            $this->getRecord()->only($attributes),
        );
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $batch = $this->getRecord()->batch;
        $editRequests = EditRequest::where('batch',$batch)->get();

        foreach($editRequests as $edit)
        {
            $data[$edit->attribute] = $edit->to;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {

        if(Str::contains(request()->path(), 'edit-requests')) {
            return [
                // ApproveAction::make('approve')
                //     ->label('Approve')
                //     ->color('success')
                //     // ->using(fn($record)=> $record->approveBatch())
                //     // ->redirect(ViewEditRequest::getUrl('index'))
                //     ->groupedIcon('heroicon-o-check')
                //     ->successNotificationTitle('Approved successfully')
                //     ->icon('heroicon-s-check'),


            ];
        }
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function resolveRecord($key): Model
    {
        $record = ($this->getModel())::find($key);

        if ($record === null) {
            throw (new ModelNotFoundException())->setModel($this->getModel(), [$key]);
        }

        return $record;
    }
    public static function getEditRequestId()
    {
        $path = explode('/',request()->path());
        return (int) $path[count($path)-1];
    }

    public static function getModelId()
    {
        $path = explode('/',request()->path());
        return (int) $path[count($path)-1];
    }
    public static function getModelName()
    {
        $editRequest = EditRequest::find(self::getModelId());

        if(!$editRequest)
            return 'EditRequest';

        $modelPath = $editRequest->editable_type;

        $modelArray = explode('\\',$modelPath);
        $modelName = $modelArray[count($modelArray)-1];
        return $modelName;
    }
}
