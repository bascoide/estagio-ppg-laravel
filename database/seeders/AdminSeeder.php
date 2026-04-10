<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * este ficheiro cria um admin de teste para ecutar o script basta inserir no terminal "php artisan db:seed --class=AdminSeeder"
     */
    public function run(): void
    {
        User::create([
            'name' => 'admin',
            'email' => 'admin@admin.admin',
            'password' => Hash::make('wJjaCMLNIG'),
            'admin' => true,
            'verified' => true,
        ]);

        echo "Admin user created successfully!\n";
    }
}
