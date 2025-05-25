<?php

namespace App\Events\VisitsEvents;

use App\Models\Visit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $visit;
    public $created;

    /**
     * Create a new event instance.
     */
    public function __construct(Visit $visit, $created = false)
    {
        $this->visit = $visit;
        $this->created = $created;
    }
}
