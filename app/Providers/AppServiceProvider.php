<?php

namespace App\Providers;

use App\Listeners\AssignDefaultRoleOnRegistration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Route post-login redirects by role so /admin/login and
        // /dashboard/login behave identically for both admins and students.
        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LoginResponse::class,
            \App\Filament\Auth\RoleAwareLoginResponse::class,
        );

        // After logout, always go back to the unified student login URL
        // (matches the public navbar entry) — admins won't get bounced
        // back to /admin/login as if they were stuck in the admin panel.
        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LogoutResponse::class,
            \App\Filament\Auth\UnifiedLogoutResponse::class,
        );
    }

    public function boot(): void
    {
        Event::listen(Registered::class, AssignDefaultRoleOnRegistration::class);

        // Labels Filament derives from column names ("Sort order", "Is
        // published") go through lang/*.json like every other label.
        \Filament\Forms\Components\Field::configureUsing(fn ($field) => $field->translateLabel());
        \Filament\Tables\Columns\Column::configureUsing(fn ($column) => $column->translateLabel());
        \Filament\Infolists\Components\Entry::configureUsing(fn ($entry) => $entry->translateLabel());

        // Forms lay out in two columns, which put a section in one half
        // next to an empty one; every section takes the full width, so
        // the sections of a form line up.
        \Filament\Schemas\Components\Section::configureUsing(fn ($section) => $section->columnSpanFull());
    }
}
