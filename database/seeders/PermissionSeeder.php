<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $writer = Role::create(['name' => 'writer']);
        $userAdmin = User::query()->where('email', 'alireza@gmail.com')->first();
        $userAdmin->syncRoles([$writer->id]);
        $create_post = Permission::create(['name' => 'create post']);
        $writer->givePermissionTo([$create_post]);
    }
}
