<?php

namespace App\Console\Commands;

use App\Models\CurrencyRate;
use App\Support\CurrencyRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class RefreshCurrencyRates extends Command
{
    protected $signature = 'currency:refresh-rates {--base=}';
    protected $description = 'Refresh currency rates from HexaRate';

    public function handle(): int
    {
        $registry = app(CurrencyRegistry::class);
        $baseCurrency = $registry->normalize($this->option('base') ?: $registry->baseCurrency(), 'EUR');
        $supported = array_unique(array_map('strtoupper', $registry->rateSyncCodes()));

        if (empty($supported)) {
            $this->error('No rate-sync currencies configured.');
            return Command::FAILURE;
        }

        $targets = array_values(array_filter($supported, fn ($code) => $code !== $baseCurrency));
        $now = Carbon::now();

        $synced = 0;
        $skipped = 0;

        foreach ($targets as $targetCurrency) {
            $url = "https://hexarate.paikama.co/api/rates/{$baseCurrency}/{$targetCurrency}/latest";

            try {
                $response = Http::timeout(10)->get($url);
                if (!$response->successful()) {
                    Log::warning('HexaRate request failed', [
                        'base' => $baseCurrency,
                        'target' => $targetCurrency,
                        'status' => $response->status(),
                    ]);
                    $skipped++;
                    continue;
                }

                $data = $response->json();
                $rate = data_get($data, 'data.mid');
                $timestamp = data_get($data, 'data.timestamp');

                if (!$rate || !$timestamp) {
                    Log::warning('HexaRate response missing data', [
                        'base' => $baseCurrency,
                        'target' => $targetCurrency,
                        'response' => $data,
                    ]);
                    $skipped++;
                    continue;
                }

                CurrencyRate::updateOrCreate([
                    'base_currency' => $baseCurrency,
                    'target_currency' => $targetCurrency,
                ], [
                    'rate' => $rate,
                    'source' => 'hexarate',
                    'fetched_at' => Carbon::parse($timestamp),
                    'updated_at' => $now,
                ]);
                $synced++;
            } catch (\Throwable $e) {
                Log::warning('HexaRate request error', [
                    'base' => $baseCurrency,
                    'target' => $targetCurrency,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        $this->info("Currency rates refresh complete. Synced: {$synced}. Skipped: {$skipped}.");

        return Command::SUCCESS;
    }
}
