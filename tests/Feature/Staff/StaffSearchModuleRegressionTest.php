<?php

namespace Tests\Feature\Staff;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffSearchModuleRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
    }

    protected function tearDown(): void
    {
        $fixtureRoot = storage_path('app/public/documents/tests/staff-search');
        if (is_dir($fixtureRoot)) {
            File::deleteDirectory($fixtureRoot);
        }

        parent::tearDown();
    }

    public function test_other_documents_card_counts_all_non_core_document_types(): void
    {
        $user = $this->createStaffUser();

        Scan::create([
            'document_id' => 'BIR-TEST-0001',
            'document_type' => 'birth_certificate',
            'title' => 'Birth Test Record',
            'file_path' => 'documents/tests/staff-search/birth.pdf',
            'verification_status' => 'completed',
        ]);

        Scan::create([
            'document_id' => 'AFF-TEST-0001',
            'document_type' => 'affidavit',
            'title' => 'Affidavit',
            'file_path' => 'documents/tests/staff-search/affidavit.pdf',
            'verification_status' => 'completed',
        ]);

        Scan::create([
            'document_id' => 'AUS-TEST-0001',
            'document_type' => 'ausf',
            'title' => 'Affidavit',
            'file_path' => 'documents/tests/staff-search/ausf.pdf',
            'verification_status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get('/staff/search');

        $response->assertOk();
        $response->assertSee('Other Documents', false);
        $response->assertSee('2 Records', false);
    }

    public function test_live_search_other_without_specific_type_includes_all_non_core_types(): void
    {
        $user = $this->createStaffUser();

        Scan::create([
            'document_id' => 'AFF-TEST-0002',
            'document_type' => 'affidavit',
            'title' => 'Affidavit',
            'file_path' => 'documents/tests/staff-search/affidavit-two.pdf',
            'verification_status' => 'completed',
        ]);

        Scan::create([
            'document_id' => 'AUS-TEST-0002',
            'document_type' => 'ausf',
            'title' => 'Affidavit',
            'file_path' => 'documents/tests/staff-search/ausf-two.pdf',
            'verification_status' => 'completed',
        ]);

        Scan::create([
            'document_id' => 'BIR-TEST-0002',
            'document_type' => 'birth_certificate',
            'title' => 'Affidavit',
            'file_path' => 'documents/tests/staff-search/birth-two.pdf',
            'verification_status' => 'completed',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/staff/search/live?document_type=other&document_title=affidavit');

        $response->assertOk();
        $response->assertJsonCount(2);

        $types = collect($response->json())->pluck('document_type')->all();

        $this->assertContains('affidavit', $types);
        $this->assertContains('ausf', $types);
        $this->assertNotContains('birth_certificate', $types);
    }

    public function test_document_endpoint_returns_pdf_media_urls_for_preview_and_print(): void
    {
        $user = $this->createStaffUser();

        $relativePath = 'documents/tests/staff-search/AFF-TEST-PRINT-0001.pdf';
        $this->createPdfFixture($relativePath);

        $scan = Scan::create([
            'document_id' => 'AFF-TEST-PRINT-0001',
            'document_type' => 'affidavit',
            'title' => 'Affidavit',
            'file_path' => $relativePath,
            'file_mime_type' => 'application/pdf',
            'verification_status' => 'completed',
        ]);

        $response = $this->actingAs($user)->getJson('/staff/search/document/' . $scan->id);

        $response->assertOk();
        $response->assertJsonPath('document.id', $scan->id);
        $response->assertJsonPath('document.is_pdf', true);
        $response->assertJsonPath('document.file_mime_type', 'application/pdf');
        $this->assertStringContainsString('/staff/scans/' . $scan->id . '/image/preview', (string) $response->json('document.preview_url'));
        $this->assertStringContainsString('/staff/scans/' . $scan->id . '/image/preview?raw=1', (string) $response->json('document.preview_raw_url'));
    }

    private function createStaffUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        return $user;
    }

    private function createPdfFixture(string $relativePath): void
    {
        $absolutePath = storage_path('app/public/' . $relativePath);
        $directory = dirname($absolutePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($absolutePath, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");
    }
}
