<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Seed sample orders from customers and guests.
     */
    public function run(): void
    {
        $customers = User::role('customer')->get();

        Order::factory()->count(25)->recycle($customers)->withItems()->create();
        Order::factory()->count(5)->guest()->withItems()->create();
    }
}
