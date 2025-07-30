<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter',   'invoice_limit' => 10,   'price' => 0.00],
            ['name' => 'Basic',     'invoice_limit' => 50,   'price' => 9.99],
            ['name' => 'Pro',       'invoice_limit' => 200,  'price' => 29.99],
            ['name' => 'Business',  'invoice_limit' => 1000, 'price' => 79.99],
            ['name' => 'Enterprise','invoice_limit' => 10000,'price' => 199.99],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
