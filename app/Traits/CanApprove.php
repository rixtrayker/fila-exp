<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait CanApprove {
    protected function approvalOrder(){
        if(auth()->user()->hasRole('district-manager'))
            return 2;

        if(auth()->user()->hasRole('country-manager'))
            return 3;

        if(auth()->user()->hasRole('super-admin'))
            return 5;

        return 0;
    }
    public function approve()
    {
        $this->update(['approved' => $this->approvalOrder(), 'approved_at' => now() ]);
    }
    public function reject()
    {
        $this->approved = - $this->approvalOrder();
        $this->approved_at = now();
        $this->save();
    }

    public function canApprove()
    {
        $modelName = class_basename(self::class);
        $permissionName = 'approve '.Str::kebab($modelName);

        if(auth()->user()->can($permissionName) && $this->approved === 0 )
            return true;

        return false;
    }

    public function canDecline()
    {
        $modelName = class_basename(self::class);
        $permissionName = 'approve '.Str::kebab($modelName);
        if(!auth()->user()->can($permissionName))
            return false;

        if($this->approved < 0 || $this->approved == $this->approvalOrder())
            return false;

        $roleApprovalOrder = $this->approvalOrder();
        return abs($this->approved) < $roleApprovalOrder;
    }

    public function getApprovedByAttribute(){
        if(abs($this->approved) == 1)
            return 'area-manager';
        if(abs($this->approved) == 2)
            return 'district-manager';
        if(abs($this->approved) == 3)
            return 'country-manager';
        if(abs($this->approved) == 5)
            return 'super-admin';
        return '';
    }
}
