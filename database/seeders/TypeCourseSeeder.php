<?php

namespace Database\Seeders;

use App\Models\TypeCourse;
use Illuminate\Database\Seeder;

class TypeCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeCourse::create([
            'name' => 'Licenciatura',
        ]);

        echo "TypeCourse 'Licenciatura' created successfully!\n";
    }
}
