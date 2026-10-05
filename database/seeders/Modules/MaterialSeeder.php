<?php

namespace Database\Seeders\Modules;

use App\Models\Material;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{code: string, limits: list<array<string, string|null>>}> $materials */
        $materials = File::json(database_path('demo/materials.json'), JSON_THROW_ON_ERROR);

        foreach ($materials as $data) {
            $material = Material::query()->updateOrCreate(['code' => $data['code']], Arr::except($data, 'limits'));

            if (! $material->limits()->exists()) {
                $material->limits()->createMany($data['limits']);
            }
        }
    }
}
