<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $methods = [
            [
                "name" => "Kpay",
                "account_name" => "Min Htet Kyaw",
                "account_number" => "09688683805",
                "logo" => "https://www.calamuseducation.com/uploads/payment/kpay.png",
                "sort_order" => 1
            ],
            [
                "name" => "Wave Pay",
                "account_name" => "Min Htet Kyaw",
                "account_number" => "09688683805",
                "logo" => "https://www.calamuseducation.com/uploads/payment/wave.png",
                "sort_order" => 2
            ],
            [
                "name" => "KBZ Banking",
                "account_name" => "Kaung Htet Tin",
                "account_number" => "585123654789",
                "logo" => "https://www.calamuseducation.com/uploads/payment/kbz.png",
                "sort_order" => 3
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(['name' => $method['name']], $method);
        }
    }
}
