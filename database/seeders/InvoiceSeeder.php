<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use TomatoPHP\FilamentInvoices\Models\Invoice;
use TomatoPHP\FilamentInvoices\Models\InvoicesItem;
use TomatoPHP\FilamentTypes\Models\Type;
use App\Models\User;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        InvoicesItem::query()->delete();
        Invoice::query()->delete();
        Customer::query()->delete();
        Account::query()->delete();
        // Create invoice types if they don't exist
        // $this->createInvoiceTypes();

        // Create accounts
        $accounts = Account::factory(5)->create();

        // Create customers with locations
        $customers = Customer::factory(10)->create()
       ;

        // Arabic items and descriptions
        $arabicItems = [
            'أجهزة كمبيوتر' => 'أجهزة كمبيوتر مكتبية عالية الأداء',
            'طابعات' => 'طابعات ليزر ملونة',
            'شاشات' => 'شاشات LED عالية الدقة',
            'أثاث مكتبي' => 'طاولات وكراسي مكتبية',
            'أدوات مكتبية' => 'أقلام ودفاتر وملفات',
            'برامج' => 'برامج إدارة الأعمال',
            'خدمات صيانة' => 'خدمات صيانة دورية',
            'أجهزة شبكات' => 'راوترات وسويتشات',
            'كاميرات مراقبة' => 'أنظمة مراقبة متكاملة',
            'أجهزة اتصال' => 'هواتف وأنظمة اتصال'
        ];

        // Create invoices
        foreach ($accounts as $account) {
            // Create 2-5 invoices per account
            $invoiceCount = rand(2, 5);

            for ($i = 0; $i < $invoiceCount; $i++) {
                $customer = $customers->random();

                $invoice = Invoice::create([
                    'uuid' => 'INV-' . strtoupper(uniqid()),
                    'from_type' => Account::class,
                    'from_id' => $account->id,
                    'for_type' => Customer::class,
                    'for_id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'user_id' => User::first()->id,
                    'address' => fake()->randomElement([
                        'شارع الملك فهد، حي العليا، الرياض',
                        'شارع الأمير محمد بن عبدالعزيز، جدة',
                        'شارع الخليج، حي الشاطئ، الدمام',
                        'طريق الملك عبدالله، حي النزهة، مكة المكرمة',
                        'شارع الأمير سلطان، حي الخبر الشمالية، الخبر',
                        'طريق الملك فيصل، حي النسيم، المدينة المنورة',
                        'شارع الستين، حي النخيل، تبوك',
                        'طريق الملك خالد، حي الروضة، أبها',
                        'شارع الثلاثين، حي الفيصلية، الطائف',
                        'طريق الأمير نايف، حي الورود، بريدة'
                    ]),
                    'date' => now(),
                    'due_date' => now()->addDays(rand(30,100)),
                    'type' => 'push',
                    'status' => 'draft',
                    'currency_id' => 120,
                    'shipping' => rand(0, 100),
                    'vat' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'paid' => 0,
                    'tenant_id' => $account->tenant_id
                ]);

                // Create invoice items
                $itemCount = rand(1, 5);
                $total = 0;
                $usedItems = [];

                for ($j = 0; $j < $itemCount; $j++) {
                    $quantity = rand(1, 10);
                    $price = rand(100, 5000);
                    $vat= $price*0.15;
                    $itemTotal = ($quantity * $price)+ ($vat * $quantity);
                    $total += $itemTotal;

                    // Get a random item that hasn't been used yet
                    $availableItems = array_diff_key($arabicItems, array_flip($usedItems));
                    $item = array_rand($availableItems);
                    $usedItems[] = $item;

                    InvoicesItem::create([
                        'invoice_id' => $invoice->id,
                        'item' => $item,
                        'description' => $arabicItems[$item],
                        'qty' => $quantity,
                        'price' => $price,
                        'discount' => 0,
                        'vat' => $price,
                        'total' => $itemTotal,
                    ]);
                }

                // Update invoice total
                $invoice->update([
                    'total' => $total,
                    'vat' => $total,
                    'status' => collect(['draft', 'sent', 'paid'])->random(),
                ]);
            }
        }
    }

    private function createInvoiceTypes(): void
    {
        // Create invoice types
        $types = [
            ['for' => 'invoices', 'type' => 'type', 'key' => 'push', 'name' => 'فاتورة بيع'],
            ['for' => 'invoices', 'type' => 'type', 'key' => 'pull', 'name' => 'فاتورة شراء'],
            ['for' => 'invoices', 'type' => 'status', 'key' => 'draft', 'name' => 'مسودة'],
            ['for' => 'invoices', 'type' => 'status', 'key' => 'sent', 'name' => 'مرسلة'],
            ['for' => 'invoices', 'type' => 'status', 'key' => 'paid', 'name' => 'مدفوعة'],
        ];

        foreach ($types as $type) {
            Type::firstOrCreate(
                ['for' => $type['for'], 'type' => $type['type'], 'key' => $type['key']],
                $type
            );
        }
    }
}
