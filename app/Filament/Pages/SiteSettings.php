<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use UnitEnum;

class SiteSettings extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string | UnitEnum | null $navigationGroup = 'CMS';
    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public static function getNavigationLabel(): string { return __('Website Settings'); }
    public static function getNavigationGroup(): ?string { return __('CMS'); }
    public function getTitle(): string { return __('Website Settings'); }

    private const KEYS = [
        // group => [keys]
        'contact' => ['contact.email', 'contact.phone', 'contact.whatsapp'],
        'social'  => ['social.instagram', 'social.facebook', 'social.tiktok'],
        'stats'   => ['stats.students', 'stats.workers', 'stats.companies', 'stats.years'],
        'academy' => ['academy.url'],
    ];

    public function mount(): void
    {
        $data = [];
        foreach (self::KEYS as $group => $keys) {
            foreach ($keys as $key) {
                $data[$this->keyToFormName($key)] = Setting::get($key, $this->defaultFor($key));
            }
        }
        // Offices are stored as a JSON array under the key contact.offices
        $offices = Setting::get('contact.offices', config('site.contact.offices', []));
        $data['contact__offices'] = is_array($offices) ? $offices : [];

        // Company profile PDF path
        $data['company__profile_pdf_path'] = Setting::get('company.profile_pdf_path');

        // Company profile video (file OR YouTube URL + optional poster)
        $data['company__video_path']         = Setting::get('company.video_path');
        $data['company__video_youtube_url']  = Setting::get('company.video_youtube_url');
        $data['company__video_poster_path']  = Setting::get('company.video_poster_path');

        // Theme audio file path
        $data['audio__theme_path'] = Setting::get('audio.theme_path');

        // Hero slideshow images (4 slots)
        $slides = Setting::get('hero.slides', []);
        $data['hero__slides'] = is_array($slides) ? $slides : [];

        // Login page wallpaper
        $data['login__background_path'] = Setting::get('login.background_path');

        // Page title-band pictures
        foreach (array_keys(\App\Support\SiteSettings::PAGE_HEROES) as $page) {
            $data["page_hero__{$page}"] = Setting::get("page_hero.{$page}");
        }

        // Homepage scrolling bands: [{id, en, ja}, …]
        foreach (['red', 'ink'] as $band) {
            $items = Setting::get("homepage.marquee_{$band}", []);
            $data["marquee__{$band}"] = is_array($items) ? $items : [];
        }

        $this->form->fill($data);
    }

    /** One scrolling band: a list of phrases, each in id / en / ja. */
    private function marqueeRepeater(string $name, string $label, string $hint): Repeater
    {
        return Repeater::make($name)
            ->label($label)
            ->helperText($hint)
            ->itemLabel(fn (array $state): ?string => $state['id'] ?? null)
            ->collapsible()
            ->collapsed()
            ->reorderable()
            ->defaultItems(0)
            ->maxItems(12)
            ->addActionLabel(__('Add text'))
            ->components([
                TextInput::make('id')->label('Indonesia')->required()->maxLength(60),
                TextInput::make('en')->label('English')->maxLength(60)->placeholder(__('Optional — falls back to Indonesian')),
                TextInput::make('ja')->label('日本語')->maxLength(60)->placeholder(__('Optional — falls back to Indonesian')),
            ]);
    }

    private function keyToFormName(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    private function defaultFor(string $key): ?string
    {
        return data_get(config('site'), $key) ?? null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('settings')->tabs([
                Tab::make(__('Contact'))->icon('heroicon-o-phone')->schema([
                    Section::make()->columns(1)->components([
                        TextInput::make('contact__email')->label(__('Email'))->email(),
                        TextInput::make('contact__phone')->label(__('Phone')),
                        TextInput::make('contact__whatsapp')->label('WhatsApp')->helperText(__('Digits only, no spaces or plus sign.')),
                    ]),
                ]),
                Tab::make(__('Social media'))->icon('heroicon-o-share')->schema([
                    Section::make()->columns(1)->components([
                        TextInput::make('social__instagram')->label('Instagram')->url()->prefix('https://'),
                        TextInput::make('social__facebook')->label('Facebook')->url()->prefix('https://'),
                        TextInput::make('social__tiktok')->label('TikTok')->url()->prefix('https://'),
                    ]),
                ]),
                Tab::make(\App\Support\Brand::learning())->icon('heroicon-o-academic-cap')->schema([
                    Section::make()->columns(1)->components([
                        TextInput::make('academy__url')
                            ->label(__(':learning website URL'))
                            ->url()
                            ->placeholder('https://learning.example.com')
                            ->helperText(__('The "E-Learning" menu opens this link in a new tab. Leave empty to hide the menu until :learning is online.')),
                    ]),
                ]),
                Tab::make(__('Stats'))->icon('heroicon-o-chart-bar')->schema([
                    Section::make()->columns(['default' => 1, 'sm' => 2, 'lg' => 4])->components([
                        TextInput::make('stats__students')->label(__('Trained Students')),
                        TextInput::make('stats__workers')->label(__('Workers')),
                        TextInput::make('stats__companies')->label(__('Partner Companies')),
                        TextInput::make('stats__years')->label(__('Years of Experience'))
                            ->placeholder('8+')
                            ->helperText(__('Shown on the About page. Leave empty to hide it.')),
                    ]),
                ]),
                Tab::make(__('Company'))->icon('heroicon-o-building-office-2')->schema([
                    Section::make(__('Company Profile'))
                        ->description(__('Upload the company profile document. Visitors can download it from the About page.'))
                        ->components([
                            FileUpload::make('company__profile_pdf_path')
                                ->label(__('Company Profile file (PDF / DOC / image)'))
                                ->disk('public')
                                ->directory('company')
                                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'])
                                ->maxSize(10240)
                                ->columnSpanFull(),
                        ]),
                    Section::make(__('Company Profile Video'))
                        ->description(__('Upload a short video (mp4 / webm) OR paste a YouTube URL. Shown on the homepage under the "About" section.'))
                        ->columns(2)
                        ->components([
                            FileUpload::make('company__video_path')
                                ->label(__('Video file (mp4 / webm)'))
                                ->disk('public')
                                ->directory('company')
                                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                                ->maxSize(1024000) // 1 GB — also requires php.ini limits raised
                                ->previewable(false)
                                ->openable()
                                ->downloadable()
                                ->helperText(__('Up to 1 GB. For large files, raise upload_max_filesize + post_max_size in php.ini.')),
                            TextInput::make('company__video_youtube_url')
                                ->label(__('YouTube URL (alternative)'))
                                ->url()
                                ->placeholder('https://www.youtube.com/watch?v=...')
                                ->helperText(__('Use this instead of uploading if your video is hosted on YouTube.')),
                            FileUpload::make('company__video_poster_path')
                                ->label(__('Poster image (optional)'))
                                ->image()
                                ->disk('public')
                                ->directory('company')
                                ->imageEditor()
                                ->maxSize(5120)
                                ->helperText(__('Shown before the video plays. Recommended 1920×1080.'))
                                ->columnSpanFull(),
                        ]),
                ]),
                Tab::make(__('Homepage'))->icon('heroicon-o-home')->schema([
                    Section::make(__('Hero background photos'))
                        ->description(__('Up to 4 photos that fade behind the top of the homepage (headline and job search), down to the scrolling text. With no photos uploaded, 3 default Japan photos are shown.'))
                        ->components([
                            Repeater::make('hero__slides')
                                ->label(__('Hero background photos'))->hiddenLabel()
                                ->itemLabel(fn (array $state): ?string =>
                                    filled($state['image_path'] ?? null) ? basename($state['image_path']) : __('New slide'))
                                ->minItems(0)
                                ->maxItems(4)
                                ->collapsible()
                                ->reorderable()
                                ->components([
                                    FileUpload::make('image_path')
                                        ->label(__('Background image'))
                                        ->image()
                                        ->disk('public')
                                        ->directory('hero')
                                        ->imageEditor()
                                        ->maxSize(5120),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel(__('Add slide')),
                        ]),
                    Section::make(__('Scrolling text'))
                        ->description(__('The two moving bands under the homepage hero. Leave a band empty to use its automatic text.'))
                        ->columns(2)
                        ->components([
                            $this->marqueeRepeater('marquee__red', __('Red band'), __('Empty = the job category names from Job Categories.')),
                            $this->marqueeRepeater('marquee__ink', __('Dark band'), __('Empty = "Official job openings", "Verified partner companies", …')),
                        ]),
                ]),
                Tab::make(__('Page headers'))->icon('heroicon-o-rectangle-group')->schema([
                    Section::make(__('Page header pictures'))
                        ->description(__('The picture behind the title at the top of each page. Leave empty to use the default illustration.'))
                        ->columns(2)
                        ->components(collect(\App\Support\SiteSettings::PAGE_HEROES)
                            ->map(fn (string $label, string $page) => FileUpload::make("page_hero__{$page}")
                                ->label(__($label))
                                ->image()
                                ->disk('public')
                                ->directory('page-hero')
                                ->imageEditor()
                                ->maxSize(5120)
                                ->helperText(__('Landscape, 2000×1116 or larger. The left side sits behind the title.')))
                            ->values()
                            ->all()),
                ]),
                Tab::make(__('Login background'))->icon('heroicon-o-photo')->schema([
                    Section::make(__('Login page wallpaper'))
                        ->description(__('Background photo shown on every login / register / password-reset page (admin & student). Leave empty to use the default Mt Fuji photo.'))
                        ->components([
                            FileUpload::make('login__background_path')
                                ->label(__('Wallpaper image'))
                                ->image()
                                ->disk('public')
                                ->directory('login')
                                ->imageEditor()
                                ->maxSize(5120)
                                ->helperText(__('Recommended: landscape 1920×1080 or larger. JPG / PNG / WebP.'))
                                ->columnSpanFull(),
                        ]),
                ]),
                Tab::make(__('Theme audio'))->icon('heroicon-o-musical-note')->schema([
                    Section::make(__('Background music'))
                        ->description(__('Upload an MP3 file (OGG / WAV also work). Plays on the homepage; visitors can mute via the floating speaker button.'))
                        ->components([
                            FileUpload::make('audio__theme_path')
                                ->label(__('Theme song'))
                                ->disk('public')
                                ->directory('audio')
                                ->maxSize(51200)             // 50 MB
                                ->previewable(false)         // skip the audio-preview metadata read that was hanging
                                ->openable()                 // give admin a way to open / play the saved file
                                ->downloadable()
                                ->columnSpanFull(),
                        ]),
                ]),
                Tab::make(__('Offices'))->icon('heroicon-o-map-pin')->schema([
                    Section::make()
                        ->description(__('Offices shown in the homepage footer, About, and Contact pages. Each address links to Google Maps when clicked. Leave Maps URL blank to auto-search by address.'))
                        ->components([
                            Repeater::make('contact__offices')
                                ->label(__('Offices'))->hiddenLabel()
                                ->columns(2)
                                ->itemLabel(fn (array $state): ?string =>
                                    trim(($state['city'] ?? '').', '.($state['country'] ?? ''), ', ') ?: __('New office'))
                                ->collapsible()
                                ->reorderable()
                                ->components([
                                    TextInput::make('city')->label(__('City'))->required()->maxLength(120),
                                    TextInput::make('country')->label(__('Country'))->maxLength(80),
                                    TextInput::make('address')->label(__('Full address'))->columnSpanFull()
                                        ->placeholder(__('Street, district, city, postal code'))
                                        ->helperText(__('Used for Google Maps search if Maps URL is empty.')),
                                    TextInput::make('maps_url')->label(__('Google Maps URL (optional)'))->columnSpanFull()
                                        ->url()
                                        ->placeholder('https://maps.google.com/?q=...')
                                        ->helperText(__('Leave blank to auto-generate from address.')),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel(__('Add office')),
                        ]),
                ]),
            ])->columnSpanFull(),
        ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save'))
                ->color('primary')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        foreach (self::KEYS as $group => $keys) {
            foreach ($keys as $key) {
                $value = $state[$this->keyToFormName($key)] ?? null;
                Setting::set($key, $value, $group);
            }
        }

        // Offices: array of {city, country, address, maps_url}
        $offices = collect($state['contact__offices'] ?? [])
            ->map(fn ($o) => [
                'city'     => $o['city'] ?? null,
                'country'  => $o['country'] ?? null,
                'address'  => $o['address'] ?? null,
                'maps_url' => $o['maps_url'] ?? null,
            ])
            ->filter(fn ($o) => filled($o['city']))
            ->values()
            ->all();
        Setting::set('contact.offices', $offices, 'contact');

        // Company profile single-file upload
        Setting::set('company.profile_pdf_path', $state['company__profile_pdf_path'] ?? null, 'company');

        // Company profile video — store both file path and YouTube URL;
        // SiteSettings::companyVideo() picks file first, YouTube fallback.
        Setting::set('company.video_path',         $state['company__video_path']        ?? null, 'company');
        Setting::set('company.video_youtube_url',  $state['company__video_youtube_url'] ?? null, 'company');
        Setting::set('company.video_poster_path',  $state['company__video_poster_path'] ?? null, 'company');

        // Theme audio single-file upload
        Setting::set('audio.theme_path', $state['audio__theme_path'] ?? null, 'audio');

        // Hero slideshow images
        $slides = collect($state['hero__slides'] ?? [])
            ->map(fn ($s) => ['image_path' => $s['image_path'] ?? null])
            ->filter(fn ($s) => filled($s['image_path']))
            ->values()
            ->all();
        Setting::set('hero.slides', $slides, 'hero');

        foreach (['red', 'ink'] as $band) {
            $items = collect($state["marquee__{$band}"] ?? [])
                ->map(fn ($i) => ['id' => trim((string) ($i['id'] ?? '')), 'en' => trim((string) ($i['en'] ?? '')), 'ja' => trim((string) ($i['ja'] ?? ''))])
                ->filter(fn ($i) => $i['id'] !== '')
                ->values()
                ->all();
            Setting::set("homepage.marquee_{$band}", $items, 'homepage');
        }

        // Login page wallpaper single-file upload
        Setting::set('login.background_path', $state['login__background_path'] ?? null, 'login');

        foreach (array_keys(\App\Support\SiteSettings::PAGE_HEROES) as $page) {
            Setting::set("page_hero.{$page}", $state["page_hero__{$page}"] ?? null, 'page_hero');
        }

        Notification::make()
            ->title(__('Settings saved'))
            ->success()
            ->send();
    }
}
