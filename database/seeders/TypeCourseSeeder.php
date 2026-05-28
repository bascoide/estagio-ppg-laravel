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
        $types = [
            'Licenciatura',
            'Mestrado',
            'Doutoramento',
            'Pós-graduação',
            'Curso Técnico',
        ];

        foreach ($types as $name) {
            TypeCourse::create(['name' => $name]);
            echo "TypeCourse '{$name}' created successfully!\n";
        }
    }
}
