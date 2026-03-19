import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const controllerPath = path.join(repoRoot, 'app/Http/Controllers/Auth/SocialAuthController.php');

test('social auth callback uses transactional account linking and safe profile defaults', () => {
    const source = readFileSync(controllerPath, 'utf8');

    assert.match(source, /DB::transaction\(/);
    assert.match(source, /SocialAccount::firstOrCreate\(/);
    assert.match(source, /address_line1/);
    assert.match(source, /postal_code/);
    assert.match(source, /sanitizeAvatarUrl/);
});

test('social auth notifications can still reach an admin if env email is missing', () => {
    const source = readFileSync(controllerPath, 'utf8');

    assert.match(source, /User::where\('role',\s*'admin'\)/);
});
