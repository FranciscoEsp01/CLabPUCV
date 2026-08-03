<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_php_script_disguised_as_pdf(): void
    {
        Storage::fake('public');
        $teacher = User::factory()->teacher()->create();

        // Create a fake file with PHP payload without valid PDF magic bytes
        $fakeMaliciousFile = UploadedFile::fake()->createWithContent('exploit.pdf', '<?php phpinfo(); ?>');

        $response = $this->actingAs($teacher)->post('/teacher/materials', [
            'title' => 'Malicious Guide',
            'type' => 'documentation',
            'file' => $fakeMaliciousFile,
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_accepts_valid_pdf_with_correct_magic_bytes(): void
    {
        Storage::fake('public');
        $teacher = User::factory()->teacher()->create();

        // Valid PDF file structure with %PDF- header
        $pdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF";
        $validPdf = UploadedFile::fake()->createWithContent('guia_c.pdf', $pdfContent);

        $response = $this->actingAs($teacher)->post('/teacher/materials', [
            'title' => 'Guia Introductoria a C',
            'type' => 'documentation',
            'file' => $validPdf,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('materials', [
            'title' => 'Guia Introductoria a C',
        ]);
    }
}
