<?php

namespace App\Traits;

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
        $this->approved = $this->approvalOrder();
        $this->approved_at = now();
        $this->save();
    }
    public function reject()
    {
        $this->approved = - $this->approvalOrder();
        $this->approved_at = now();
        $this->save();
    }

    public function canApprove()
    {
        if(auth()->user()->hasRole('medical-rep') || auth()->user()->hasRole('area-manager'))
            return false;

        if($this->approved !== 0 )
            return false;

        return true;
    }

    public function canDecline()
    {
        if(auth()->user()->hasRole('medical-rep') || auth()->user()->hasRole('area-manager'))
            return false;

        if($this->approved < 0)
            return false;

        $roleApprovalOrder = $this->approvalOrder();
        return abs($this->approved) < $roleApprovalOrder;
    }
}
