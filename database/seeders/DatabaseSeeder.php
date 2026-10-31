<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['admin@nest.test', 'Admin', 'admin'], ['manager@nest.test', 'Quản lý NEST', 'manager'], ['customer@nest.test', 'Huy Nguyễn', 'customer']] as [$email,$name,$role]) {
            $user = User::firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->forceFill(['name' => $name, 'password' => bcrypt($role === 'admin' ? 'admin123' : 'Password@123'), 'role' => $role, 'username' => in_array($role, ['admin', 'manager']) ? $role : null])->save();
            }
        }
        $this->call(FurnitureCatalogSeeder::class);
    }
}
