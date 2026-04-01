import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const read = (relativePath) => readFileSync(path.join(repoRoot, relativePath), 'utf8');

test('app blade renders initial SEO title and meta tags from Inertia page props for crawlers', () => {
    const appBlade = read('resources/views/app.blade.php');

    assert.match(appBlade, /\$initialSeoTitle\s*=\s*data_get\(\$page,\s*'props\.seo\.title'/);
    assert.match(appBlade, /\$initialSeoDescription\s*=\s*data_get\(\$page,\s*'props\.seo\.description'/);
    assert.match(appBlade, /\$initialSeoCanonical\s*=\s*data_get\(\$page,\s*'props\.seo\.canonical'/);
    assert.match(appBlade, /<title inertia>\{\{\s*\$initialSeoTitle\s*\}\}<\/title>/);
    assert.match(appBlade, /@if\(\$initialSeoDescription\)/);
    assert.match(appBlade, /<meta name="description" content="\{\{\s*\$initialSeoDescription\s*\}\}">/);
    assert.match(appBlade, /@if\(\$initialSeoCanonical\)/);
    assert.match(appBlade, /<link rel="canonical" href="\{\{\s*\$initialSeoCanonical\s*\}\}">/);
    assert.match(appBlade, /<meta property="og:title" content="\{\{\s*\$initialSeoTitle\s*\}\}">/);
});
