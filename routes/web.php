<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SitemapController;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('about',   fn () => view('pages.about'))->name('about');
Route::get('contact', [ContactController::class, 'show'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('blog',          [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{slug}',   [BlogController::class, 'show'])->name('blog.show');
Route::get('event',         [EventController::class, 'index'])->name('event.index');
Route::get('event/{slug}',  [EventController::class, 'show'])->name('event.show');

Route::get('jobs',                [\App\Http\Controllers\JobController::class, 'index'])->name('job.index');
Route::get('jobs/{slug}',         [\App\Http\Controllers\JobController::class, 'show'])->name('job.show');
Route::get('jobs/{slug}/job-order.pdf', [\App\Http\Controllers\JobOrderPdfController::class, 'public'])->name('job.order-pdf');
Route::get('admin/job-vacancies/{vacancy}/job-order.pdf', [\App\Http\Controllers\JobOrderPdfController::class, 'admin'])
    ->name('admin.job-order-pdf');
Route::get('cv/rirekisho.pdf', [\App\Http\Controllers\CvPdfController::class, 'mine'])->name('cv.mine');
Route::get('cv/rirekisho.docx', [\App\Http\Controllers\CvPdfController::class, 'mine'])->defaults('format', 'docx')->name('cv.mine-docx');
Route::get('admin/students/{user}/rirekisho.pdf', [\App\Http\Controllers\CvPdfController::class, 'admin'])->name('admin.student-cv');
Route::get('admin/students/{user}/rirekisho.docx', [\App\Http\Controllers\CvPdfController::class, 'admin'])->defaults('format', 'docx')->name('admin.student-cv-docx');
Route::post('jobs/{slug}/apply',  [\App\Http\Controllers\JobController::class, 'apply'])
    ->middleware('throttle:10,1')
    ->name('job.apply');

Route::get('services', \App\Http\Controllers\ServicesController::class)->name('services');
Route::get('for-employers',  [\App\Http\Controllers\EmployerController::class, 'show'])->name('employers');
Route::post('for-employers', [\App\Http\Controllers\EmployerController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('employers.store');

Route::get('privacy-policy', [\App\Http\Controllers\LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('terms',          [\App\Http\Controllers\LegalController::class, 'terms'])->name('legal.terms');
Route::get('fees',           [\App\Http\Controllers\LegalController::class, 'fees'])->name('fees');

Route::get('gallery',         [\App\Http\Controllers\GalleryController::class, 'index'])->name('gallery.index');
Route::get('gallery/{slug}',  [\App\Http\Controllers\GalleryController::class, 'show'])->name('gallery.show');

// The learning platform is PJI Learning. Old /elearning links forward
// there — or home, until the PJI Learning URL is set in Website Settings.
Route::get('elearning/{any?}', fn () => redirect()->away(SiteSettings::academyUrl() ?? url('/')))
    ->where('any', '.*');

Route::get('locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch')
    ->whereIn('locale', ['id', 'en', 'ja']);

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Trying the whole journey on a laptop: sign in as a demo account in one click.
if (app()->isLocal()) {
    Route::get('dev/login-as/{user}', \App\Http\Controllers\DemoLoginController::class)->name('dev.login-as');
}
