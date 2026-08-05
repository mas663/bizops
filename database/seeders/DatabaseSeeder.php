<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Channel;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $organization = Organization::create([
            'name' => 'Bizops',
        ]);

        Store::create([
            'organization_id' => $organization->id,
            'name' => 'Toko Utama',
        ]);

        User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner',
            'email' => env('OWNER_EMAIL'),
            'password' => env('OWNER_PASSWORD'),
            'role' => UserRole::Owner,
        ]);

        $channels = [
            ['name' => 'WhatsApp', 'code' => 'whatsapp'],
            ['name' => 'GoFood', 'code' => 'gofood'],
            ['name' => 'GrabFood', 'code' => 'grabfood'],
            ['name' => 'ShopeeFood', 'code' => 'shopeefood'],
            ['name' => 'Lainnya', 'code' => 'lainnya'],
        ];

        foreach ($channels as $sortOrder => $channel) {
            Channel::create([
                'organization_id' => $organization->id,
                'name' => $channel['name'],
                'code' => $channel['code'],
                'commission_rate' => null,
                'sort_order' => $sortOrder,
            ]);
        }

        foreach (['receipt_header', 'receipt_footer'] as $key) {
            Setting::create([
                'organization_id' => $organization->id,
                'key' => $key,
                'value' => null,
            ]);
        }
    }
}
