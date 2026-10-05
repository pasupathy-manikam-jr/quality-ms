<?php

namespace Database\Seeders;

use App\Models\IsoClause;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class IsoClauseSeeder extends Seeder
{
    /**
     * Seed the ISO 9001:2015 clause list from database/demo/iso_clauses.json.
     */
    public function run(): void
    {
        /** @var list<array{number: string, title: string}> $clauses */
        $clauses = File::json(database_path('demo/iso_clauses.json'), JSON_THROW_ON_ERROR);

        IsoClause::query()->upsert($clauses, ['number'], ['title']);
    }
}
