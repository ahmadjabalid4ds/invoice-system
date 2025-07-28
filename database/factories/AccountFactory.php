<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $arabicCompanies = [
            'الحساب الرئيسي',
            'مجموعة الأعمال الدولية',
            'شركة التطوير العقاري',
            'مجموعة الخدمات المالية',
            'شركة النقل والتجارة',
            'مجموعة الاستثمار العقاري',
            'شركة الاتصالات الحديثة',
            'مجموعة الصناعات الغذائية',
            'شركة البناء والتشييد',
            'مجموعة الخدمات اللوجستية'
        ];

        return [
            'name' => fake()->randomElement($arabicCompanies),
            'iban' => fake()->iban(),
            'balance' => fake()->randomFloat(2, 1000, 100000),
        ];
    }
}
