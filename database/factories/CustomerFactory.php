<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $arabicFirstNames = [
            'أحمد', 'محمد', 'علي', 'عبدالله', 'خالد',
            'سارة', 'فاطمة', 'نور', 'مريم', 'ليلى'
        ];

        $arabicLastNames = [
            'علي', 'محمد', 'أحمد', 'حسن', 'حسين',
            'عباس', 'جعفر', 'موسى', 'رضا', 'كاظم'
        ];

        return [
            'name' => fake()->randomElement($arabicFirstNames) . ' ' . fake()->randomElement($arabicLastNames),
            'phone' => '+966' . fake()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'tenant_id' => Tenant::factory()->create()->id
        ];
    }
}
