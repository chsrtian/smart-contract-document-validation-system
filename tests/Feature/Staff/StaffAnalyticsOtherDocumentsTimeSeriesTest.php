<?php

namespace Tests\Feature\Staff;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffAnalyticsOtherDocumentsTimeSeriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_others_time_series_uses_dynamic_document_type_grouping(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Analytics chart-data grouping query uses MySQL DATE_FORMAT and is not supported by sqlite tests.');
        }

        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('staff');

        Scan::create([
            'document_id' => 'AFF-ANL-0001',
            'document_type' => 'affidavit',
            'title' => 'Affidavit',
            'file_path' => 'documents/tests/analytics/affidavit.pdf',
            'verification_status' => 'completed',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        Scan::create([
            'document_id' => 'AUS-ANL-0001',
            'document_type' => 'ausf',
            'title' => 'Affidavit',
            'file_path' => 'documents/tests/analytics/ausf.pdf',
            'verification_status' => 'completed',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Non-others type should not appear in the others section charts.
        Scan::create([
            'document_id' => 'BIR-ANL-0001',
            'document_type' => 'birth_certificate',
            'title' => 'Birth',
            'file_path' => 'documents/tests/analytics/birth.pdf',
            'verification_status' => 'completed',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/staff/analytics/chart-data?section=others&period=month');

        $response->assertOk();

        $payload = $response->json();

        $distributionLabels = collect($payload['distribution']['labels'] ?? []);
        $timeSeriesLabels = collect($payload['timeSeries']['datasets'] ?? [])->pluck('label');

        $this->assertTrue($distributionLabels->contains('Affidavit'));
        $this->assertTrue($distributionLabels->contains('AUSF'));

        $this->assertTrue($timeSeriesLabels->contains('Affidavit'));
        $this->assertTrue($timeSeriesLabels->contains('AUSF'));
        $this->assertFalse($timeSeriesLabels->contains('Admission of Paternity'));

        $timeSeriesTotals = collect($payload['timeSeries']['datasets'] ?? [])->mapWithKeys(function ($dataset) {
            return [$dataset['label'] => array_sum($dataset['data'] ?? [])];
        });

        $this->assertSame(1, (int) ($timeSeriesTotals['Affidavit'] ?? 0));
        $this->assertSame(1, (int) ($timeSeriesTotals['AUSF'] ?? 0));
    }
}
