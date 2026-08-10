<?php

namespace App\Http\Controllers;

use App\Models\UserDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureDocumentController extends Controller
{
    /**
     * Stream a private ID document. Access is granted solely by a valid,
     * unexpired signature (the `signed` middleware) — signatures are only ever
     * minted for authorized viewers (owner, admin, or a vendor with a booking).
     */
    /** Only these document types may be served, with a forced safe content-type. */
    private const CONTENT_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
    ];

    public function show(UserDocument $userDocument, string $field): StreamedResponse
    {
        abort_unless(in_array($field, UserDocument::FILE_FIELDS, true), 404);

        $key = UserDocument::storageKey($userDocument->{$field});
        abort_if($key === null, 404);

        $extension = strtolower(pathinfo($key, PATHINFO_EXTENSION));
        $contentType = self::CONTENT_TYPES[$extension] ?? null;
        abort_if($contentType === null, 404);

        $disk = Storage::disk('upcloud');
        abort_unless($disk->exists($key), 404);

        // Served from the app origin, so lock the response down: force a safe
        // content-type (never text/html), forbid MIME sniffing, sandbox with a
        // strict CSP, and download PDFs rather than render them. This prevents a
        // malicious upload from executing as stored XSS on our own domain.
        $disposition = $extension === 'pdf' ? 'attachment' : 'inline';

        $response = $disk->response($key, basename($key), [
            'Cache-Control' => 'private, max-age=0, no-store',
        ], $disposition);

        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; sandbox");

        return $response;
    }
}
