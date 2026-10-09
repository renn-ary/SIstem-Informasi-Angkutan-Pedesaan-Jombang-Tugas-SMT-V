<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobSeeder extends Seeder
{
    public function run(): void
    {
        $sampleJobs = [
            [
                'queue'    => 'default',
                'payload'  => json_encode([
                    'displayName' => 'SampleJob',
                    'job'         => 'SampleJobHandler',
                    'data'        => ['message' => 'Sample seeded job payload'],
                ]),
                'attempts'     => 0,
                'reserved_at'  => null,
                'available_at' => now()->timestamp,
                'created_at'   => now()->timestamp,
            ],
        ];

        foreach ($sampleJobs as $job) {
            DB::table('jobs')->insertOrIgnore($job);
        }
    }
}
