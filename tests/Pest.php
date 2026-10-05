<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Function-style (Pest) tests get the app TestCase and a fresh database. The older
| class-style tests extend TestCase themselves and keep working unchanged.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
