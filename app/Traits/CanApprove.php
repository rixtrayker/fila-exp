<?php

namespace App\Traits;

trait CanApprove {
    protected function approvalOrder(){
        $approvedBy = 0;

        if(auth()->user()->hasRole('district-manager'))
            $approvedBy = 2;

        if(auth()->user()->hasRole('country-manager'))
            $approvedBy = 3;
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

    public function isApproved()
    {
        $roleApprovalOrder = $this->approvalOrder();

        return abs($this->approved) <= $roleApprovalOrder;
    }
}
