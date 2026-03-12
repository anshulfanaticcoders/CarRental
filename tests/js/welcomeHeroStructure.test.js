import test from 'node:test';
import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(import.meta.dirname, '..', '..');
const welcomePagePath = path.join(repoRoot, 'resources/js/Pages/Welcome.vue');
const heroComponentPath = path.join(repoRoot, 'resources/js/Components/Welcome/WelcomeHero.vue');

test('welcome page delegates hero rendering to the dedicated WelcomeHero component', () => {
    assert.equal(existsSync(heroComponentPath), true, 'Expected WelcomeHero.vue to exist');

    const welcomePage = readFileSync(welcomePagePath, 'utf8');

    assert.match(welcomePage, /import\s+WelcomeHero\s+from\s+["']@\/Components\/Welcome\/WelcomeHero\.vue["'];/);
    assert.match(welcomePage, /<WelcomeHero\b/);
});

test('dedicated hero component keeps the dynamic hero content and visible in-hero search layout', () => {
    assert.equal(existsSync(heroComponentPath), true, 'Expected WelcomeHero.vue to exist');

    const heroComponent = readFileSync(heroComponentPath, 'utf8');

    assert.match(heroComponent, /<SearchBar\b[^>]*:simple="true"/);
    assert.match(heroComponent, /v-html="animatedTagline"/);
    assert.match(heroComponent, /\{\{\s*heroSubtitle\s*\}\}/);
    assert.match(heroComponent, /\{\{\s*displayedText\s*\}\}/);
    assert.match(heroComponent, /hero-search-shell/);
    assert.match(heroComponent, /hero-searchbar/);
    assert.match(heroComponent, /min-h-screen/);
    assert.match(heroComponent, /reveal-up/);
    assert.match(heroComponent, /reveal-scale/);
    assert.match(heroComponent, /justify-center/);
    assert.match(heroComponent, /\.hero-searchbar :deep\(\.search_bar::before\)/);
    assert.match(heroComponent, /content:\s*none\s*!important;/);
});
