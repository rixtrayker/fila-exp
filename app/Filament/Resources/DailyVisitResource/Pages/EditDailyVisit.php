<?php

namespace App\Filament\Resources\DailyVisitResource\Pages;

use App\Filament\Resources\DailyVisitResource;
use App\Models\ProductVisit;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Log;

class EditDailyVisit extends EditRecord
{
    protected static string $resource = DailyVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
    public $isRegularVisit;
    protected static $templates = [
        1 =>'Regular',
        2 =>'GroupMeeting',
        3 =>'Conference',
        4 =>'HealthDay',
    ];
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $oldData = $data;
        $data = [];
        $data['template'] = $oldData['visit_type_id'];

        $data['temp_content'][self::$templates[$oldData['visit_type_id']]] = $oldData;
        return $data;
    }

    public function afterSave()
    {
        $this->saveProducts();
    }

    private function saveProducts()
    {
        $data = $this->form->getRawState()['temp_content']['Regular'];
        if(!isset($data['products'])){
            return;
        }

        $products = $data['products'];

        $insertData = [];
        $now = now();

        foreach($products as $product){
            $insertData[] = [
                'visit_id' => $this->record->id,
                'product_id' =>  $product['product_id'],
                'count' => $product['count'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ProductVisit::insert($insertData);
    }


    public function save(bool $shouldRedirect = true): void
    {
        $this->authorizeAccess();

        try {
            $this->callHook('beforeValidate');

            $data = collect($this->form->getRawState()['temp_content']['Regular'])->only(['next_visit','call_type_id','comment'])->toArray();
            $data['status'] = 'visited';

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $this->handleRecordUpdate($this->getRecord(), $data);

            $this->callHook('afterSave');
        } catch (Halt $exception) {
            return;
        }

        $this->getSavedNotification()?->send();

        if ($shouldRedirect && ($redirectUrl = $this->getRedirectUrl())) {
            $this->redirect($redirectUrl);
        }
    }
}
