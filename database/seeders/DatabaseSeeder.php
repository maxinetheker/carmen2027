<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $principal = User::firstOrNew(['role' => 'ceo']);
        if (! $principal->exists) {
            $email = (string) config('crm.admin_email');
            $password = (string) config('crm.admin_password');
            if (! $email || ! $password || $password === 'CHANGE_ME') {
                throw new RuntimeException('Define CRM_ADMIN_EMAIL y CRM_ADMIN_PASSWORD antes de ejecutar el seeder.');
            }
            $principal->email = $email;
            $principal->password = Hash::make($password);
        }
        $principal->fill([
            'name' => 'Carmen Mestanza',
            'phone' => '+51 987 654 321',
            'role' => 'ceo',
            'active' => true,
        ])->save();

        $this->call(SiteContentSeeder::class);
        if (app()->environment('testing') || config('crm.seed_demo_data', false)) {
            $this->call(CrmDemoSeeder::class);
        }
    }
}
