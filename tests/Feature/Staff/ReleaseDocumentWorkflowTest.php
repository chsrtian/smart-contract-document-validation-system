<?php

namespace Tests\Feature\Staff;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReleaseDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_log_release_when_record_has_legacy_confirmed_timestamp(): void
    {
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('staff');

        $scan = Scan::create([
            'document_id' => 'MAR-TEST-REL-0001',
            'document_type' => 'marriage_certificate',
            'title' => 'Release Test Document',
            'file_path' => 'documents/test/release-test.pdf',
            'verification_status' => 'completed',
            'blockchain_enabled' => true,
            // Legacy inconsistent state: pending status with confirmed timestamp.
            'blockchain_status' => 'pending',
            'blockchain_confirmed_at' => now(),
            'copies_printed' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(
            "/corrections/requests/staff/scans/{$scan->id}/log-release",
            [
                'copies' => 2,
                'client_name' => 'Test Client',
                'reason' => 'Claimed certified copy',
                'released_at' => now()->toISOString(),
            ]
        );

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Document release logged successfully',
        ]);

        $scan->refresh();

        $this->assertSame('released', $scan->status);
        $this->assertTrue((bool) $scan->released);
        $this->assertSame($user->id, $scan->released_by);
        $this->assertSame('Test Client', $scan->released_to);
        $this->assertSame(2, (int) $scan->copies_printed);
        $this->assertNotNull($scan->released_at);
    }

    public function test_release_endpoint_returns_422_for_non_releasable_document_state(): void
    {
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('staff');

        $scan = Scan::create([
            'document_id' => 'MAR-TEST-REL-0002',
            'document_type' => 'marriage_certificate',
            'title' => 'Not Ready Document',
            'file_path' => 'documents/test/not-ready.pdf',
            'verification_status' => 'pending',
            'blockchain_enabled' => true,
            'blockchain_status' => 'pending',
            'copies_printed' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(
            "/corrections/requests/staff/scans/{$scan->id}/log-release",
            [
                'copies' => 1,
                'client_name' => 'Test Client',
                'reason' => 'Attempt release',
                'released_at' => now()->toISOString(),
            ]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Document cannot be released',
        ]);
    }
}
