<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'cr_number' => $this->faker->numberBetween(100, 100000),
            'entity_number' => $this->faker->numberBetween(100, 100000),
            'bank_name' => $this->faker->name(),
            'bank_holder_name' => $this->faker->name(),
            'iban' => $this->faker->iban(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Tenant $tenant) {
            $user = User::factory()->create([
                'tenant_id' => $tenant->id,
            ]);

            $tenant->update([
                'owner_id' => $user->id,
            ]);
        });
    }
}
