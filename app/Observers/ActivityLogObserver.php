<?php

namespace App\Observers;

use App\Helpers\ActivityLogHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLogObserver
{
    /**
     * Field names stripped from properties diff.
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'access_token',
    ];

    public function created(Model $model): void
    {
        ActivityLogHelper::log(
            ActivityLogHelper::categoryFor($model),
            'created',
            $this->describe('Created', $model),
            $model
        );
    }

    public function updated(Model $model): void
    {
        $changed = $this->cleanDirty($model);

        if (empty($changed)) {
            return;
        }

        ActivityLogHelper::log(
            ActivityLogHelper::categoryFor($model),
            'updated',
            $this->describe('Updated', $model),
            $model,
            ['changed' => $changed]
        );
    }

    public function deleted(Model $model): void
    {
        ActivityLogHelper::log(
            ActivityLogHelper::categoryFor($model),
            'deleted',
            $this->describe('Deleted', $model),
            $model,
            ['snapshot' => $this->safeAttributes($model)]
        );
    }

    public function restored(Model $model): void
    {
        ActivityLogHelper::log(
            ActivityLogHelper::categoryFor($model),
            'restored',
            $this->describe('Restored', $model),
            $model
        );
    }

    private function describe(string $verb, Model $model): string
    {
        $label = $this->labelFor($model);
        $name = $this->nameFor($model);

        return $name ? "{$verb} {$label}: {$name}" : "{$verb} {$label} #{$model->getKey()}";
    }

    private function labelFor(Model $model): string
    {
        return Str::headline(class_basename($model));
    }

    private function nameFor(Model $model): ?string
    {
        foreach (['name', 'title', 'place_name', 'company_name', 'first_name', 'email', 'subject', 'slug'] as $key) {
            if (isset($model->{$key}) && filled($model->{$key})) {
                return (string) $model->{$key};
            }
        }
        return null;
    }

    private function cleanDirty(Model $model): array
    {
        $dirty = $model->getDirty();
        $original = $model->getOriginal();
        $out = [];

        foreach ($dirty as $key => $newValue) {
            if (in_array($key, self::SENSITIVE_FIELDS, true)) continue;
            if (in_array($key, ['updated_at'], true)) continue;
            $out[$key] = [
                'from' => $this->scalarize($original[$key] ?? null),
                'to' => $this->scalarize($newValue),
            ];
        }

        return $out;
    }

    private function safeAttributes(Model $model): array
    {
        $attrs = $model->getAttributes();
        foreach (self::SENSITIVE_FIELDS as $field) {
            unset($attrs[$field]);
        }
        return array_map(fn ($v) => $this->scalarize($v), $attrs);
    }

    private function scalarize($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        if (is_string($value) && strlen($value) > 500) {
            return Str::limit($value, 500);
        }
        return $value;
    }
}
