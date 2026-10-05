<?php

namespace Database\Seeders\Modules;

use App\Models\Certificate;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class CertificateSeeder extends Seeder
{
    /**
     * Two industry profiles: steel mill test certificates and resin CoAs, each with
     * one certificate whose results break a limit.
     */
    public function run(): void
    {
        /** @var list<array{supplier: string, number: string, status: string, lots: list<array<string, mixed>>}> $certificates */
        $certificates = File::json(database_path('demo/certificates.json'), JSON_THROW_ON_ERROR);
        $decider = User::query()->where('email', 'quality-manager@example.com')->value('id');

        foreach ($certificates as $data) {
            $supplier = Supplier::query()->where('code', $data['supplier'])->firstOrFail();

            if ($supplier->certificates()->where('number', $data['number'])->exists()) {
                continue;
            }

            $certificate = $supplier->certificates()->create(Arr::except($data, ['supplier', 'status', 'lots']));
            $certificate->forceFill($data['status'] === 'received' ? [] : [
                'status' => $data['status'], 'decided_by' => $decider, 'decided_at' => $certificate->issued_on->addDays(3),
            ])->save();

            foreach ($data['lots'] as $lot) {
                /** @var array<string, string> $results */
                $results = $lot['results'];

                $certificate->lots()->create([
                    ...Arr::except($lot, ['material', 'results']),
                    'material_id' => Material::query()->where('code', $lot['material'])->value('id'),
                ])->results()->createMany(
                    collect($results)->map(fn (string $value, string $property) => ['property' => $property, 'value' => $value])->values()->all(),
                );
            }
        }
    }
}
