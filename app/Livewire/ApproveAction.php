<?php

namespace App\Livewire;

use Closure;
use Filament\Pages\Actions\Action;
use Filament\Support\Actions\Concerns\CanCustomizeProcess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ApproveAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'approve';
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->label('');

        $this->successNotificationTitle('Approved successfully');

        $this->color('success');

        $this->groupedIcon('heroicon-o-check');
    //     // $this->mountUsing(function (ComponentContainer $form, Model $record): void {
    //     //     $data = $record->attributesToArray();

    //     //     if ($this->mutateRecordDataUsing) {
    //     //         $data = $this->evaluate($this->mutateRecordDataUsing, ['data' => $data]);
    //     //     }

    //     //     $form->fill($data);
    //     // });

        $this->action(function (): void {
            $result = $this->process(static fn (Model $record) => $record->approveBatch());

            if (! $result) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }




        // $this->hidden(static function (Model $record): bool {
        //     if (! method_exists($record, 'trashed')) {
        //         return false;
        //     }

        //     return $record->trashed();
        // });


    // public function process(?Closure $default, array $parameters = [])
    // {
    //     // return $this->evaluate($this->using ?? $default, $parameters);
    // }

}
