<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
class FixOrdersWith0Total implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // get all orders with 0 total
        $orders = Order::where('total', 0)->with('productsWithPivot')->get();

        foreach ($orders as $order) {
            $total = 0;
            foreach ($order->productsWithPivot as $product) {
                $total += $product->pivot->item_total;
            }
            $order->update(['total' => $total]);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
