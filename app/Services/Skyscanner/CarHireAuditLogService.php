<?php

namespace App\Services\Skyscanner;

use Illuminate\Support\Facades\Cache;

class CarHireAuditLogService
{
    public function append(string $entityType, string $entityId, string $event, array $payload = []): array
    {
        $entry = [
            'case_id' => (string) config('skyscanner.case_id'),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event' => $event,
            'payload' => $payload,
            'logged_at' => now()->toIso8601String(),
        ];

        $logs = $this->get($entityType, $entityId);
        $logs[] = $entry;

        Cache::put($this->key($entityType, $entityId), $logs, now()->addDays(14));

        return $entry;
    }

    public function get(string $entityType, string $entityId): array
    {
        $logs = Cache::get($this->key($entityType, $entityId), []);

        return is_array($logs) ? array_values($logs) : [];
    }

    private function key(string $entityType, string $entityId): string
    {
        return 'skyscanner.audit.' . $entityType . '.' . $entityId;
    }
}
