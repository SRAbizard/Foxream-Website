@php
    $accounts = \App\Support\DemoAccounts::enabled() ? \App\Support\DemoAccounts::users() : collect();
    $me = auth()->id();
@endphp

{{-- Local install only: sign in as a demo account in one click (php artisan demo:setup). --}}
@if($accounts->isNotEmpty())
    <div class="pj-demo" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
        <div class="pj-demo-menu" x-show="open" x-transition.origin.bottom.right x-cloak>
            <p class="pj-demo-head">{{ __('Sign in as') }} <small>{{ __('demo accounts, this laptop only') }}</small></p>
            <ul>
                @foreach($accounts as $user)
                    <li>
                        <a href="{{ route('dev.login-as', ['user' => $user, 'back' => request()->fullUrl()]) }}" @class(['is-me' => $user->id === $me])>
                            <x-filament::icon :icon="$user->hasRole('student') ? 'heroicon-o-academic-cap' : 'heroicon-o-shield-check'" class="h-4 w-4 shrink-0" />
                            <span class="min-w-0">
                                <b>{{ $user->name }}</b>
                                <small>{{ $user->hasRole('student') ? __('Student') : __('Admin') }} · {{ $user->email }}</small>
                            </span>
                            @if($user->id === $me) <em>{{ __('now') }}</em> @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="pj-demo-btn" @click="open = ! open">
            <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-4 w-4" />
            Demo · {{ auth()->user()?->name ?? __('Sign in as') }}
        </button>
    </div>
@endif
