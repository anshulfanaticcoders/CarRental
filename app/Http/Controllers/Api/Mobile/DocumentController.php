<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Helpers\ImageCompressionHelper;
use App\Http\Controllers\Controller;
use App\Models\UserDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    private const FIELDS = [
        'driving_license_front',
        'driving_license_back',
        'passport_front',
        'passport_back',
    ];

    public function show(Request $request): JsonResponse
    {
        $document = UserDocument::where('user_id', $request->user()->id)->first();

        return response()->json([
            'document' => $this->transform($document),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'driving_license_front' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'driving_license_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'passport_front' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'passport_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $document = UserDocument::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['verification_status' => 'pending']
        );

        $changed = false;
        foreach (self::FIELDS as $field) {
            if ($request->hasFile($field)) {
                if ($document->{$field}) {
                    $this->deleteFile($document->{$field});
                }
                $document->{$field} = $this->storeFile($request->file($field));
                $changed = true;
            }
        }

        if ($changed) {
            $document->verification_status = 'pending';
            $document->verified_at = null;
            $document->save();
        }

        return response()->json([
            'document' => $this->transform($document->fresh()),
        ]);
    }

    public function destroy(Request $request, string $field): JsonResponse
    {
        if (! in_array($field, self::FIELDS, true)) {
            throw ValidationException::withMessages([
                'field' => ['Invalid document type.'],
            ]);
        }

        $document = UserDocument::where('user_id', $request->user()->id)->first();
        if (! $document || ! $document->{$field}) {
            return response()->json([
                'document' => $this->transform($document),
            ]);
        }

        $this->deleteFile($document->{$field});
        $document->{$field} = null;
        $document->save();

        return response()->json([
            'document' => $this->transform($document->fresh()),
        ]);
    }

    private function storeFile(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'pdf') {
            $path = $file->store('documents', 'upcloud');
            return Storage::disk('upcloud')->url($path);
        }

        $quality = $ext === 'png' ? 60 : 85;
        $compressedPath = ImageCompressionHelper::compressImage(
            $file,
            'documents',
            $quality,
            1200,
            900
        );

        if (! $compressedPath) {
            throw ValidationException::withMessages([
                'document' => ['Image compression failed.'],
            ]);
        }

        return Storage::disk('upcloud')->url($compressedPath);
    }

    private function deleteFile(?string $url): void
    {
        if (! $url) {
            return;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (! $path) {
            return;
        }
        $relative = ltrim($path, '/');
        $disk = Storage::disk('upcloud');
        if ($disk->exists($relative)) {
            $disk->delete($relative);
        }
    }

    private function transform(?UserDocument $document): ?array
    {
        if (! $document) {
            return null;
        }

        return [
            'id' => $document->id,
            'driving_license_front' => $document->driving_license_front,
            'driving_license_back' => $document->driving_license_back,
            'passport_front' => $document->passport_front,
            'passport_back' => $document->passport_back,
            'verification_status' => $document->verification_status,
            'verified_at' => $document->verified_at?->toIso8601String(),
        ];
    }
}
