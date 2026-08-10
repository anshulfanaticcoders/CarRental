<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecureDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function valid_signed_url_streams_the_document(): void
    {
        Storage::fake('upcloud');
        $key = 'documents/license_front.jpg';
        Storage::disk('upcloud')->put($key, 'fake-image-bytes');

        $document = $this->makeDocument(['driving_license_front' => $key]);

        $url = $document->signedDocumentUrl('driving_license_front');
        $this->assertNotNull($url);

        $response = $this->get($url);
        $response->assertOk();
        // Hardened against stored-XSS on the app origin.
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
    }

    #[Test]
    public function disallowed_file_type_is_rejected_even_when_signed(): void
    {
        Storage::fake('upcloud');
        $key = 'documents/evil.html';
        Storage::disk('upcloud')->put($key, '<script>alert(1)</script>');
        $document = $this->makeDocument(['driving_license_front' => $key]);

        $url = $document->signedDocumentUrl('driving_license_front');
        $this->get($url)->assertNotFound();
    }

    #[Test]
    public function unsigned_request_is_rejected(): void
    {
        Storage::fake('upcloud');
        $document = $this->makeDocument(['driving_license_front' => 'documents/x.jpg']);

        $this->get(route('secure-documents.show', [
            'userDocument' => $document->id,
            'field' => 'driving_license_front',
        ]))->assertForbidden();
    }

    #[Test]
    public function expired_signature_is_rejected(): void
    {
        Storage::fake('upcloud');
        Storage::disk('upcloud')->put('documents/x.jpg', 'bytes');
        $document = $this->makeDocument(['driving_license_front' => 'documents/x.jpg']);

        $expired = URL::temporarySignedRoute('secure-documents.show', now()->subMinute(), [
            'userDocument' => $document->id,
            'field' => 'driving_license_front',
        ]);

        $this->get($expired)->assertForbidden();
    }

    #[Test]
    public function invalid_field_is_not_found_even_when_signed(): void
    {
        Storage::fake('upcloud');
        $document = $this->makeDocument(['driving_license_front' => 'documents/x.jpg']);

        $url = URL::temporarySignedRoute('secure-documents.show', now()->addMinutes(15), [
            'userDocument' => $document->id,
            'field' => 'not_a_real_field',
        ]);

        $this->get($url)->assertNotFound();
    }

    #[Test]
    public function storage_key_normalizes_url_and_key(): void
    {
        $this->assertSame(
            'documents/a.jpg',
            UserDocument::storageKey('https://my-public-bucket.example.com/documents/a.jpg')
        );
        $this->assertSame('documents/a.jpg', UserDocument::storageKey('documents/a.jpg'));
        $this->assertSame('documents/a.jpg', UserDocument::storageKey('/documents/a.jpg'));
        $this->assertNull(UserDocument::storageKey(null));
        $this->assertNull(UserDocument::storageKey(''));
    }

    #[Test]
    public function empty_field_yields_no_signed_url(): void
    {
        $document = $this->makeDocument(['driving_license_front' => null]);
        $this->assertNull($document->signedDocumentUrl('driving_license_front'));
    }

    private function makeDocument(array $overrides = []): UserDocument
    {
        $user = User::factory()->create();

        return UserDocument::create(array_merge([
            'user_id' => $user->id,
            'verification_status' => 'pending',
        ], $overrides));
    }
}
