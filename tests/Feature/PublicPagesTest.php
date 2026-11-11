<?php

namespace Tests\Feature;

use App\Models\Setting;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Every public page opens on a new, empty install, and nothing empty or sample shows. */
class PublicPagesTest extends TestCase
{
    public static function pages(): array
    {
        return [
            'home'      => ['/'],
            'about'     => ['/about'],
            'contact'   => ['/contact'],
            'companies' => ['/contact?for=employer'],
            'services'  => ['/services'],
            'jobs'      => ['/jobs'],
            'events'    => ['/event'],
            'blog'      => ['/blog'],
            'gallery'   => ['/gallery'],
            'terms'     => ['/terms'],
            'privacy'   => ['/privacy-policy'],
            'login'     => ['/dashboard/login'],
            'register'  => ['/dashboard/register'],
            'admin'     => ['/admin/login'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_opens(string $url): void
    {
        $this->get($url)->assertOk();
    }

    #[DataProvider('pages')]
    public function test_page_opens_in_every_language(string $url): void
    {
        foreach (['en', 'ja'] as $locale) {
            $this->withSession(['locale' => $locale])->get($url)->assertOk();
        }
    }

    public function test_a_job_page_opens(): void
    {
        $job = $this->vacancy();

        $this->get('/jobs/'.$job->slug)->assertOk()->assertSee('Pengemudi Truk');
        $this->get('/jobs')->assertOk()->assertSee('Pengemudi Truk');
    }

    public function test_an_unpublished_job_is_not_shown(): void
    {
        $job = $this->vacancy(attributes: ['published_at' => null]);

        $this->get('/jobs/'.$job->slug)->assertNotFound();
    }

    public function test_the_old_fees_page_leads_to_the_fraud_warning(): void
    {
        $this->get('/fees')->assertRedirect('/contact#waspada');
        $this->get('/contact')->assertSee('id="waspada"', false);
    }

    public function test_sitemap_lists_pages_but_not_fees(): void
    {
        $this->get('/sitemap.xml')->assertOk()
            ->assertSee(url('/jobs'))
            ->assertDontSee('/fees');
    }

    public function test_unknown_page_is_404(): void
    {
        $this->get('/no-such-page')->assertNotFound();
    }

    public function test_empty_site_hides_numbers_claims_and_faq(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('Your career in Japan starts with :brand.'))
            ->assertDontSee(__('Hundreds of Indonesians have already departed with :brand.'))
            ->assertDontSee(__('Frequently Asked Questions'))
            ->assertDontSee('wa.me/', false);

        $this->get('/about')->assertDontSee(__('Years of Experience'));
    }

    public function test_numbers_and_contacts_show_once_filled_in(): void
    {
        Setting::set('stats.students', '120+', 'stats');
        Setting::set('stats.years', '3', 'stats');
        Setting::set('contact.whatsapp', '628123456789', 'contact');

        $this->get('/')
            ->assertSee('120+')
            ->assertSee(__('Hundreds of Indonesians have already departed with :brand.'))
            ->assertSee('wa.me/628123456789', false);

        $this->get('/about')->assertSee(__('Years of Experience'));
    }

    public function test_footer_credits_the_maker(): void
    {
        config(['brand.maker' => 'Foxream']);
        $this->get('/')->assertSee(__('Made by :maker', ['maker' => 'Foxream']));

        config(['brand.maker' => '']);
        $this->get('/')->assertDontSee(__('Made by :maker', ['maker' => '']));
    }

    public function test_no_fee_amounts_on_public_pages(): void
    {
        foreach (['/', '/services', '/about', '/contact', '/terms'] as $url) {
            $html = $this->get($url)->getContent();
            $this->assertDoesNotMatchRegularExpression('/Deposit Rp|Pembiayaan Tahap|Gaji Bersih|Rp ?\d[\d.]*(\s?(juta|Juta))/', strip_tags($html), $url);
        }
    }
}
