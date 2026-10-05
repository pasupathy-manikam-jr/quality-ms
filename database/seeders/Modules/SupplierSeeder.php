<?php

namespace Database\Seeders\Modules;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array<string, mixed>> $suppliers */
        $suppliers = File::json(database_path('demo/suppliers.json'), JSON_THROW_ON_ERROR);

        foreach ($suppliers as $supplier) {
            Supplier::query()->updateOrCreate(['code' => $supplier['code']], $supplier);
        }
    }
}
