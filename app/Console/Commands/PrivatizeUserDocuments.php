<?php

namespace App\Console\Commands;

use App\Models\UserDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PrivatizeUserDocuments extends Command
{
    protected $signature = 'documents:privatize
        {--force : Actually set objects private and rewrite DB values. Without this flag it is a dry run.}';

    protected $description = 'Make existing customer ID documents private on upcloud and store object keys (dry-run by default)';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $disk = Storage::disk('upcloud');

        $privatized = 0;
        $rewritten = 0;
        $missing = 0;

        UserDocument::query()->orderBy('id')->chunkById(200, function ($documents) use (&$privatized, &$rewritten, &$missing, $force, $disk) {
            foreach ($documents as $document) {
                $dirty = false;

                foreach (UserDocument::FILE_FIELDS as $field) {
                    $value = $document->{$field};
                    if (! $value) {
                        continue;
                    }

                    $key = UserDocument::storageKey($value);
                    if (! $key || ! $disk->exists($key)) {
                        $missing++;

                        continue;
                    }

                    if ($force) {
                        try {
                            $disk->setVisibility($key, 'private');
                        } catch (\Throwable $e) {
                            Log::warning('documents:privatize could not set object private', [
                                'key' => $key,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    $privatized++;

                    // Legacy rows stored a full public URL; rewrite to the key.
                    if (str_contains($value, '://')) {
                        $rewritten++;
                        if ($force) {
                            $document->{$field} = $key;
                            $dirty = true;
                        }
                    }
                }

                if ($force && $dirty) {
                    $document->save();
                }
            }
        });

        $mode = $force ? 'FORCE (applied)' : 'DRY RUN (no changes — pass --force to apply)';
        $this->info("documents:privatize — {$mode}");
        $this->line(sprintf('  objects set private   %d', $privatized));
        $this->line(sprintf('  DB values rewritten   %d', $rewritten));
        $this->line(sprintf('  missing on storage    %d', $missing));

        Log::notice('documents:privatize summary', compact('force', 'privatized', 'rewritten', 'missing'));

        return self::SUCCESS;
    }
}
