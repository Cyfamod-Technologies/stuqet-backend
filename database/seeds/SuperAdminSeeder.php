<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $role = SpatieRole::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );

        $user = User::updateOrCreate(
            ['email' => 'incrisz4luv@gmail.com'],
            [
                'name' => 'Admin',
                'user_type' => 'admin',
                'password' => Hash::make('1ncrease'),
                'email_verified_at' => now(),
                'verification_status' => 1,
                'banned' => 0,
            ]
        );

        $user->syncRoles([$role->name]);
    }
}
