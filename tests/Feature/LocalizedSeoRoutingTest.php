<?php

namespace Tests\Feature;

use App\Helpers\HreflangHelper;
use App\Models\Blog;
use App\Models\BlogTranslation;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocalizedSeoRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_register_redirects_to_affiliate_register(): void
    {
        $response = $this->get('/es/business/register');

        $response->assertStatus(301);
        $response->assertRedirect(route('affiliate.register', ['locale' => 'es']));
    }

    public function test_custom_page_slug_redirects_to_localized_canonical_page_slug(): void
    {
        $this->createLocalizedPage(
            'terms-conditions',
            'terms-and-conditions',
            [
                'en' => ['Terms & Conditions', 'terms-conditions'],
                'es' => ['Terminos y condiciones', 'terminos-y-condiciones'],
            ]
        );

        $response = $this->get('/es/terms-and-conditions');

        $response->assertStatus(301);
        $response->assertRedirect(route('pages.show', [
            'locale' => 'es',
            'slug' => 'terminos-y-condiciones',
        ]));
    }

    public function test_page_path_with_known_slug_in_wrong_locale_redirects_to_localized_page_slug(): void
    {
        $this->createLocalizedPage(
            'privacy-policy',
            'privacy-policy',
            [
                'en' => ['Privacy Policy', 'privacy-policy'],
                'es' => ['Politica de privacidad', 'politica-de-privacidad'],
            ]
        );

        $response = $this->get('/es/page/privacy-policy');

        $response->assertStatus(301);
        $response->assertRedirect(route('pages.show', [
            'locale' => 'es',
            'slug' => 'politica-de-privacidad',
        ]));
    }

    public function test_legacy_seo_redirects_apply_to_head_requests(): void
    {
        DB::table('seo_redirects')->insert([
            'from_url' => '/es/page/terms-and-conditions',
            'to_url' => '/es/page/terminos-y-condiciones',
            'status_code' => 301,
            'hits' => 0,
            'note' => 'test redirect',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->head('/es/page/terms-and-conditions');

        $response->assertStatus(301);
        $response->assertRedirect('/es/page/terminos-y-condiciones');
    }

    public function test_hreflang_uses_real_affiliate_registration_route(): void
    {
        config(['app.available_locales' => ['en', 'es']]);

        $alternates = HreflangHelper::getAlternateUrls('affiliate.register', [], 'en');

        $this->assertSame(url('/en/affiliate/register'), $alternates['en']);
        $this->assertSame(url('/es/affiliate/register'), $alternates['es']);
    }

    public function test_page_hreflang_alternates_use_translated_page_slugs(): void
    {
        config(['app.available_locales' => ['en', 'fr', 'es']]);

        $this->createLocalizedPage(
            'terms-conditions',
            'terms-and-conditions',
            [
                'en' => ['Terms & Conditions', 'terms-conditions'],
                'fr' => ['Conditions generales', 'conditions-generales'],
                'es' => ['Terminos y condiciones', 'terminos-y-condiciones'],
            ]
        );

        $alternates = HreflangHelper::getAlternateUrls(
            'pages.show',
            ['slug' => 'terminos-y-condiciones'],
            'es'
        );

        $this->assertSame(url('/en/page/terms-conditions'), $alternates['en']);
        $this->assertSame(url('/fr/page/conditions-generales'), $alternates['fr']);
        $this->assertSame(url('/es/page/terminos-y-condiciones'), $alternates['es']);
    }

    public function test_page_can_be_created_before_translations_without_activity_log_crash(): void
    {
        $page = Page::create([
            'slug' => 'future-page',
            'template' => 'default',
            'custom_slug' => null,
            'status' => 'draft',
            'sort_order' => 0,
        ]);

        $this->assertTrue($page->exists);
        $this->assertNull($page->title);
        $this->assertNull($page->content);
        $this->assertNull($page->slug);
    }

    public function test_page_slug_updates_and_deletes_are_seo_safe(): void
    {
        $pageId = $this->createLocalizedPage(
            'future-page',
            'future-page',
            [
                'en' => ['Future Page', 'future-page'],
                'es' => ['Pagina futura', 'pagina-futura'],
            ]
        );

        $translation = PageTranslation::query()
            ->where('page_id', $pageId)
            ->where('locale', 'es')
            ->firstOrFail();

        $this->head('/es/future-page')
            ->assertStatus(301)
            ->assertRedirect('/es/page/pagina-futura');

        $translation->update(['slug' => 'pagina-futura-editada']);

        $this->head('/es/page/pagina-futura')
            ->assertStatus(301)
            ->assertRedirect('/es/page/pagina-futura-editada');

        $this->get('/es/page/pagina-futura-editada')
            ->assertOk()
            ->assertSee('/es/page/pagina-futura-editada', false);

        $translation->delete();

        $this->head('/es/page/pagina-futura-editada')
            ->assertStatus(410);
    }

    public function test_page_delete_creates_gone_for_translated_urls(): void
    {
        $pageId = $this->createLocalizedPage(
            'delete-me-page',
            'delete-me-page',
            [
                'en' => ['Delete Me', 'delete-me'],
                'fr' => ['Supprimez moi', 'supprimez-moi'],
            ]
        );

        Page::findOrFail($pageId)->delete();

        $this->head('/fr/page/supprimez-moi')
            ->assertStatus(410);
    }

    public function test_blog_translation_updates_and_deletes_are_seo_safe(): void
    {
        $blog = Blog::create([
            'slug' => 'future-blog',
            'image' => null,
            'countries' => ['us', 'be'],
            'canonical_country' => 'us',
            'is_published' => true,
        ]);

        BlogTranslation::create([
            'blog_id' => $blog->id,
            'locale' => 'en',
            'title' => 'Future Blog',
            'slug' => 'future-blog',
            'excerpt' => 'Future excerpt',
            'content' => 'Future content',
        ]);

        $translation = BlogTranslation::create([
            'blog_id' => $blog->id,
            'locale' => 'es',
            'title' => 'Blog futuro',
            'slug' => 'blog-futuro',
            'excerpt' => 'Extracto futuro',
            'content' => 'Contenido futuro',
        ]);

        $translation->update(['slug' => 'blog-futuro-editado']);

        $this->head('/es/us/blog/blog-futuro')
            ->assertStatus(301)
            ->assertRedirect('/es/us/blog/blog-futuro-editado');

        $this->head('/es/be/blog/blog-futuro')
            ->assertStatus(301)
            ->assertRedirect('/es/be/blog/blog-futuro-editado');

        $this->get('/es/us/blog/blog-futuro-editado')
            ->assertOk()
            ->assertSee('/es/us/blog/blog-futuro-editado', false);

        $translation->delete();

        $this->head('/es/us/blog/blog-futuro-editado')
            ->assertStatus(410);
    }

    public function test_legacy_blog_slug_redirects_to_localized_canonical_blog_slug(): void
    {
        $this->createLocalizedBlog([
            'en' => ['Future Blog', 'future-blog'],
            'es' => ['Blog futuro', 'blog-futuro'],
        ]);

        $this->get('/es/us/blog/future-blog')
            ->assertStatus(301)
            ->assertRedirect(route('blog.show', [
                'locale' => 'es',
                'country' => 'us',
                'blog' => 'blog-futuro',
            ]));

        $this->get('/es/blog/future-blog')
            ->assertStatus(301)
            ->assertRedirect(route('blog.show', [
                'locale' => 'es',
                'country' => 'us',
                'blog' => 'blog-futuro',
            ]));

        $this->get('/blog/future-blog')
            ->assertStatus(301)
            ->assertRedirect(route('blog.show', [
                'locale' => 'en',
                'country' => 'us',
                'blog' => 'future-blog',
            ]));
    }

    public function test_blog_detail_uses_runtime_canonical_over_stale_admin_override(): void
    {
        $blog = $this->createLocalizedBlog([
            'en' => ['Canonical Blog', 'canonical-blog'],
        ]);

        SeoMeta::create([
            'seoable_type' => $blog->getMorphClass(),
            'seoable_id' => $blog->id,
            'seo_title' => 'Canonical Blog',
            'meta_description' => 'Canonical blog description.',
            'canonical_url' => url('/blog/old-canonical-blog'),
        ]);

        $response = $this->get('/en/us/blog/canonical-blog');

        $response->assertOk();
        $response->assertSee(url('/en/us/blog/canonical-blog'), false);
        $response->assertDontSee(url('/blog/old-canonical-blog'), false);

        $this->get('/blog/old-canonical-blog')
            ->assertStatus(301)
            ->assertRedirect(route('blog.show', [
                'locale' => 'en',
                'country' => 'us',
                'blog' => 'canonical-blog',
            ]));
    }

    public function test_auth_pages_emit_noindex_seo_head_data(): void
    {
        $this->get('/es/login')
            ->assertOk()
            ->assertSee('noindex,follow', false)
            ->assertSee(route('login', ['locale' => 'es']), false)
            ->assertSee('Sign in to Vrooem', false);

        $this->get('/es/register')
            ->assertOk()
            ->assertSee('noindex,follow', false)
            ->assertSee(route('register', ['locale' => 'es']), false)
            ->assertSee('Create your Vrooem account', false);
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $translations
     */
    private function createLocalizedPage(string $slug, string $customSlug, array $translations): int
    {
        $pageId = DB::table('pages')->insertGetId([
            'slug' => $slug,
            'template' => 'default',
            'custom_slug' => $customSlug,
            'status' => 'published',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($translations as $locale => [$title, $translationSlug]) {
            DB::table('page_translations')->insert([
                'page_id' => $pageId,
                'locale' => $locale,
                'title' => $title,
                'slug' => $translationSlug,
                'content' => "{$title} content",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $pageId;
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $translations
     */
    private function createLocalizedBlog(array $translations): Blog
    {
        $blog = Blog::create([
            'slug' => 'future-blog',
            'image' => null,
            'countries' => ['us'],
            'canonical_country' => 'us',
            'is_published' => true,
        ]);

        foreach ($translations as $locale => [$title, $slug]) {
            BlogTranslation::create([
                'blog_id' => $blog->id,
                'locale' => $locale,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => "{$title} excerpt",
                'content' => "{$title} content with enough words for tests.",
            ]);
        }

        return $blog;
    }
}
