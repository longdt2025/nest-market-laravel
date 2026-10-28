<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['admin@nest.test', 'Nguyễn Minh (Admin)', 'admin'], ['customer@nest.test', 'Khách hàng Demo', 'customer']] as [$email,$name,$role]) {
            $user = User::firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->forceFill(['name' => $name, 'password' => bcrypt('Password@123'), 'role' => $role])->save();
            }
        }
        $this->call(FurnitureCatalogSeeder::class);
    }
}
