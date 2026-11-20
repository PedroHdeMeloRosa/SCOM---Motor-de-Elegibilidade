<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use RuntimeException;

trait GuardsDemoSeeders
{
    protected function ensureDemoDatabaseIsAllowed(): void
    {
        $database = DB::connection()->getDatabaseName();
        $allowed = app()->environment('testing')
            || (app()->environment('local') && (bool) config('scom.allow_demo_seeders', false));

        if (!$allowed || $database === 'motor_elegibilidade') {
            throw new RuntimeException(
                'Seeder de demonstração bloqueado. Use uma base isolada e habilite SCOM_ALLOW_DEMO_SEEDERS=true explicitamente.'
            );
        }
    }
}
