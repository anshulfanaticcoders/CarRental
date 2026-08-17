<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NoAutomaticRefundCodeTest extends TestCase
{
    public function test_application_contains_no_automatic_stripe_refund_execution(): void
    {
        $forbidden = [
            '/Refund::create\s*\(/',
            '/refunds->create\s*\(/',
            '/stripe->refunds->create\s*\(/',
        ];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = $file->getContents();
            foreach ($forbidden as $pattern) {
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $contents,
                    'Automatic refund execution is forbidden; use manual-review status and admin notification only. File: '.$file->getPathname()
                );
            }
        }
    }
}
