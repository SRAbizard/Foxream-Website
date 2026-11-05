<?php

namespace App\Filament\Student\Pages;

use App\Models\JobCategory;
use App\Models\User;
use App\Support\Certificates;
use App\Support\Rirekisho;
use App\Support\JapaneseTranslator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\RateLimiter;

class MyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-identification';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.student.pages.my-profile';

    public ?array $data = [];

    public static function getNavigationLabel(): string { return __('My Profile'); }
    public function getTitle(): string                  { return __('My Profile'); }

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        // Ensure profile row exists
        $user->profile();
        $user->refresh()->load('studentProfile', 'educations', 'workExperiences');

        $this->form->fill([
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'studentProfile' => $user->studentProfile?->only([
                'full_name', 'nickname', 'gender', 'birthdate', 'birthplace', 'religion', 'marital_status',
                'id_number', 'passport_number', 'passport_expires_at',
                'address', 'city', 'province', 'postal_code',
                'emergency_contact_name', 'emergency_contact_relation', 'emergency_contact_phone',
                'height_cm', 'weight_kg', 'blood_type',
                'eats_pork', 'smoker', 'drinker', 'has_tattoo', 'has_debt', 'colour_blind', 'photo_path',
                'name_kana', 'nationality', 'desired_job', 'line_id',
                'japan_visits_study', 'japan_visits_work', 'japan_visits_other', 'self_pr', 'self_pr_ja', 'cv_other',
            ]) ?? [],
            // Only the levels still offered (SMA and up).
            'educations'      => $user->educations->filter(fn ($e) => isset(Rirekisho::EDUCATION_LEVELS[$e->level]))->map->only(['level','institution','major','start_year','start_month','end_year','end_month','is_current'])->all(),
            'workExperiences' => $user->workExperiences->map(fn ($w) => [
                'company' => $w->company, 'position' => $w->position, 'is_current' => $w->is_current,
                'start_year' => $w->start_date?->year, 'start_month' => $w->start_date?->month,
                'end_year' => $w->end_date?->year, 'end_month' => $w->end_date?->month,
            ])->all(),
            'certificates'    => Certificates::byGroup($user->studentProfile?->licenses),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('profile-tabs')->columnSpanFull()->tabs([

                    Tab::make(__('Account'))->icon('heroicon-o-user')->schema([
                        Section::make()->columns(2)->components([
                            TextInput::make('name')->label(__('Display name'))->required()->maxLength(120),
                            TextInput::make('email')->label(__('Email'))->email()->disabled()
                                ->helperText(__('Contact admin to change email')),
                            TextInput::make('phone')->label(__('Phone'))->phone()->maxLength(32),
                            TextInput::make('studentProfile.line_id')->label('LINE ID')->maxLength(80),
                        ]),
                        Section::make(__('Photo'))->columns(3)->components([
                            FileUpload::make('studentProfile.photo_path')->label(__('Profile photo'))
                                ->helperText(__('A formal photo, 3 × 4, on a white background. A transparent background becomes white on the CV. After choosing a file you can crop it.'))
                                ->image()->disk('public')->directory('students/photos')
                                ->imageEditor()
                                ->imageAspectRatio('3:4')
                                ->imageEditorAspectRatioOptions(['3:4'])
                                ->automaticallyOpenImageEditorForAspectRatio()
                                ->automaticallyResizeImagesMode('cover')
                                ->automaticallyResizeImagesToWidth('600')
                                ->automaticallyResizeImagesToHeight('800')
                                ->panelAspectRatio('3:4')
                                ->extraAttributes(['style' => 'max-width: 12rem']),   // passport-photo size, also on phones
                        ]),
                    ]),

                    Tab::make(__('Biodata'))->icon('heroicon-o-identification')->schema([
                        Section::make(__('Personal information'))->columns(2)->components([
                            TextInput::make('studentProfile.full_name')->label(__('Full legal name'))->maxLength(160),
                            TextInput::make('studentProfile.name_kana')->label(__('Name in katakana'))->placeholder('ブディ　サントソ')->maxLength(160),
                            TextInput::make('studentProfile.nickname')->label(__('Nickname'))->maxLength(80),
                            Select::make('studentProfile.gender')->label(__('Gender'))
                                ->options(['male' => __('Male'), 'female' => __('Female')])->native(false),
                            DatePicker::make('studentProfile.birthdate')->label(__('Birth date'))
                                ->maxDate(now())->displayFormat('d M Y'),
                            TextInput::make('studentProfile.birthplace')->label(__('Birth place'))->maxLength(120),
                            Select::make('studentProfile.nationality')->label(__('Nationality'))->options(fn () => collect(Rirekisho::NATIONALITIES)->map(fn ($ja, $en) => __($en)))->default('Indonesia')->native(false),
                            TextInput::make('studentProfile.religion')->label(__('Religion'))->maxLength(40),
                            Select::make('studentProfile.marital_status')->label(__('Marital status'))
                                ->options([
                                    'single'   => __('Single'),
                                    'married'  => __('Married'),
                                    'divorced' => __('Divorced'),
                                    'widowed'  => __('Widowed'),
                                ])->native(false),
                        ]),
                        Section::make(__('ID & Passport'))->columns(2)->components([
                            TextInput::make('studentProfile.id_number')->label(__('ID / KTP number'))->maxLength(32),
                            TextInput::make('studentProfile.passport_number')->label(__('Passport number'))->maxLength(32),
                            DatePicker::make('studentProfile.passport_expires_at')->label(__('Passport expires'))->displayFormat('d M Y'),
                        ]),
                        Section::make(__('Work in Japan'))->columns(3)->components([
                            // Stored as the field slug; the CV prints its Japanese name.
                            Select::make('studentProfile.desired_job')->label(__('Position applied for'))
                                ->options(fn () => JobCategory::orderBy('sort_order')->get()->mapWithKeys(fn ($c) => [$c->slug => $c->t('name')]))
                                ->native(false)->columnSpanFull(),
                            TextInput::make('studentProfile.japan_visits_study')->label(__('Visits to Japan: study'))->numeric()->minValue(0)->maxValue(99)->suffix(__('times')),
                            TextInput::make('studentProfile.japan_visits_work')->label(__('Visits to Japan: work, incl. working holiday'))->numeric()->minValue(0)->maxValue(99)->suffix(__('times')),
                            TextInput::make('studentProfile.japan_visits_other')->label(__('Visits to Japan: other, e.g. travel'))->numeric()->minValue(0)->maxValue(99)->suffix(__('times')),
                        ]),
                        Section::make(__('Self introduction'))->components([
                            Textarea::make('studentProfile.self_pr')->label(__('Your self introduction'))->rows(7)->maxLength(1500)
                                ->helperText(__('Your strengths, motivation to work in Japan and goals, in Indonesian, English or Japanese.'))
                                ->hintAction(
                                    Action::make('translateSelfPr')->label(__('Translate to Japanese'))->icon('heroicon-o-language')
                                        ->action(function (Get $get, Set $set) {
                                            $text = trim((string) $get('studentProfile.self_pr'));
                                            if ($text === '') {
                                                Notification::make()->title(__('Write your self introduction first.'))->warning()->send();

                                                return;
                                            }
                                            // Keeps the monthly DeepL quota safe; ten an hour is plenty for drafting.
                                            if (RateLimiter::tooManyAttempts('self-pr:'.auth()->id(), 10)) {
                                                Notification::make()->title(__('Too many translations. Please try again later.'))->warning()->send();

                                                return;
                                            }
                                            RateLimiter::hit('self-pr:'.auth()->id(), 3600);
                                            try {
                                                $japanese = JapaneseTranslator::translate($text);
                                            } catch (\RuntimeException $e) {
                                                Notification::make()->title($e->getMessage())->danger()->send();

                                                return;
                                            }
                                            $set('studentProfile.self_pr_ja', $japanese);
                                            // Saved straight away so the CV has it even if the page is never saved.
                                            auth()->user()->profile()->update(['self_pr' => $text, 'self_pr_ja' => $japanese]);
                                            Notification::make()->title(__('Translated and saved to your CV. You can still edit the Japanese.'))->success()->send();
                                        }),
                                ),
                            Textarea::make('studentProfile.self_pr_ja')->label(__('Japanese version (goes into the CV)'))->rows(7)->maxLength(3000)
                                ->helperText(__('Filled and saved by the Translate button. If you edit it, click Save changes.')),
                        ]),
                    ]),

                    Tab::make(__('Address'))->icon('heroicon-o-home')->schema([
                        Section::make()->columns(2)->components([
                            Textarea::make('studentProfile.address')->label(__('Street address'))->rows(2)->columnSpanFull(),
                            TextInput::make('studentProfile.city')->label(__('City'))->maxLength(80),
                            TextInput::make('studentProfile.province')->label(__('Province'))->maxLength(80),
                            TextInput::make('studentProfile.postal_code')->label(__('Postal code'))->maxLength(16),
                        ]),
                        Section::make(__('Emergency contact'))->columns(2)->components([
                            TextInput::make('studentProfile.emergency_contact_name')->label(__('Contact name'))->maxLength(120),
                            TextInput::make('studentProfile.emergency_contact_relation')->label(__('Relation'))->maxLength(40),
                            TextInput::make('studentProfile.emergency_contact_phone')->label(__('Contact phone'))->phone()->maxLength(32),
                        ]),
                    ]),

                    Tab::make(__('Education'))->icon('heroicon-o-academic-cap')->schema([
                        Repeater::make('educations')->label(__('Education'))->hiddenLabel()
                            ->addActionLabel(__('Add education'))
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['institution'] ?? __('New entry'))
                            ->components([
                                Select::make('level')->label(__('Level'))
                                    ->options(Rirekisho::EDUCATION_LEVELS)->required()->native(false),
                                TextInput::make('institution')->label(__('Institution'))->required()->maxLength(160),
                                TextInput::make('start_year')->label(__('Start year'))->numeric()->minValue(1950)->maxValue(2100),
                                Select::make('start_month')->label(__('Start month'))->options(array_combine(range(1, 12), range(1, 12)))->native(false),
                                TextInput::make('end_year')->label(__('End year'))->numeric()->minValue(1950)->maxValue(2100),
                                Select::make('end_month')->label(__('End month'))->options(array_combine(range(1, 12), range(1, 12)))->native(false),
                                TextInput::make('major')->label(__('Major / Field'))->maxLength(120),
                                Toggle::make('is_current')->label(__('Currently studying'))->inline(false),
                            ])
                            ->defaultItems(0),
                    ]),

                    Tab::make(__('Work experience'))->icon('heroicon-o-briefcase')->schema([
                        Repeater::make('workExperiences')->label(__('Work experience'))->hiddenLabel()
                            ->addActionLabel(__('Add work experience'))
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['company'] ?? __('New entry'))
                            ->components([
                                TextInput::make('company')->label(__('Company'))->required()->maxLength(160),
                                TextInput::make('position')->label(__('Position'))->required()->maxLength(120),
                                TextInput::make('start_year')->label(__('Start year'))->numeric()->minValue(1950)->maxValue(2100),
                                Select::make('start_month')->label(__('Start month'))->options(array_combine(range(1, 12), range(1, 12)))->native(false),
                                TextInput::make('end_year')->label(__('End year'))->numeric()->minValue(1950)->maxValue(2100),
                                Select::make('end_month')->label(__('End month'))->options(array_combine(range(1, 12), range(1, 12)))->native(false),
                                Toggle::make('is_current')->label(__('Currently working'))->inline(false),
                            ])
                            ->defaultItems(0),
                    ]),

                    Tab::make(__('Licences & certifications'))->icon('heroicon-o-check-badge')->schema([
                        $this->certificateSection('language', __('Language certificates'), __('Add language certificate')),
                        $this->certificateSection('ssw', __('SSW certificates'), __('Add SSW certificate')),
                        $this->certificateSection('other', __('Others'), __('Add other licence')),
                    ]),

                    Tab::make(__('Health'))->icon('heroicon-o-heart')->schema([
                        Section::make()->columns(3)->components([
                            TextInput::make('studentProfile.height_cm')->label(__('Height (cm)'))->numeric()->minValue(50)->maxValue(250),
                            TextInput::make('studentProfile.weight_kg')->label(__('Weight (kg)'))->numeric()->minValue(20)->maxValue(300),
                            Select::make('studentProfile.blood_type')->label(__('Blood type'))
                                ->options(['A'=>'A','B'=>'B','AB'=>'AB','O'=>'O'])->native(false),
                        ]),
                        Section::make()->components([
                            Textarea::make('studentProfile.cv_other')->label(__('Other notes (allergies, medical conditions, etc.)'))->rows(3)->maxLength(1000)
                                ->helperText(__('Allergies, medical conditions, and anything the employer should accommodate, e.g. halal food, prayer times. Whatever you do not tick below, your CV says you do not do it.')),
                            Toggle::make('studentProfile.eats_pork')->label(__('Eats pork'))->inline(false),
                            Toggle::make('studentProfile.drinker')->label(__('Drinks alcohol'))->inline(false),
                            Toggle::make('studentProfile.smoker')->label(__('Smoker'))->inline(false),
                        ]),
                        // Companies ask these; they go on the candidate list we send them.
                        Section::make(__('Questions from the companies'))->columns(3)->components([
                            ToggleButtons::make('studentProfile.has_tattoo')->label(__('Do you have a tattoo?'))->boolean()->inline()->grouped(),
                            ToggleButtons::make('studentProfile.has_debt')->label(__('Do you have any debts?'))->boolean()->inline()->grouped(),
                            ToggleButtons::make('studentProfile.colour_blind')->label(__('Are you colour blind?'))->boolean()->inline()->grouped(),
                        ]),
                    ]),

                ]),
            ])
            ->statePath('data');
    }

    /** One certificate group of the Licences tab: a dropdown per entry, plus a score for TOEFL/IELTS or a name for "Other". */
    private function certificateSection(string $group, string $title, string $addLabel): Section
    {
        $months = array_combine(range(1, 12), range(1, 12));

        return Section::make($title)->components([
            Repeater::make("certificates.{$group}")->label($title)->hiddenLabel()
                ->columns(4)
                ->defaultItems(0)
                ->addActionLabel($addLabel)
                ->components([
                    Select::make('type')->label(__('Certificate'))
                        ->options(fn () => Certificates::options($group))
                        ->required()->native(false)->live()
                        ->when($group !== 'other', fn (Select $s) => $s->distinct()->disableOptionsWhenSelectedInSiblingRepeaterItems())
                        ->columnSpan($group === 'other' ? 1 : 2),
                    TextInput::make('score')->label(__('Score'))
                        ->placeholder(fn (Get $get) => $get('type') === 'ielts' ? '6.5' : '550')
                        ->numeric()->minValue(0)->maxValue(990)->required()
                        ->visible(fn (Get $get) => in_array($get('type'), Certificates::SCORED, true)),
                    TextInput::make('name')->label(__('Certificate name'))->maxLength(160)->required()
                        ->helperText(__('Written on your CV as typed.'))
                        ->visible(fn (Get $get) => $get('type') === 'other'),
                    TextInput::make('year')->label(__('Year'))->numeric()->minValue(1950)->maxValue(2100),
                    Select::make('month')->label(__('Month'))->options($months)->native(false),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ActionGroup::make([
                Action::make('wordCv')->label(__('Download Word (.docx)'))->icon('heroicon-o-document-arrow-down')
                    ->url(fn () => route('cv.mine-docx')),
                Action::make('viewCv')->label(__('View PDF'))->icon('heroicon-o-eye')
                    ->url(fn () => route('cv.mine'))->openUrlInNewTab(),
                Action::make('downloadCv')->label(__('Download PDF'))->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => route('cv.mine', ['download' => 1])),
            ])
                ->label(__('Japanese CV'))
                ->icon('heroicon-o-document-text')
                ->button()
                ->tooltip(__('Built from your saved profile. Save your changes first.')),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save changes'))
                ->submit('save'),
        ];
    }

    /**
     * The CV's address line in Japanese: "City, Province" translated by
     * DeepL (西ジャワ州バンドン) when it changed. If DeepL fails the old
     * translation stays. The name in katakana is typed by the student.
     */
    private function addressKana(\App\Models\StudentProfile $profile, array $data): array
    {
        $fields = [];
        $address = collect([$data['city'] ?? null, $data['province'] ?? null])->map(fn ($v) => trim((string) $v))->filter()->implode(', ');
        $before  = collect([$profile->city, $profile->province])->map(fn ($v) => trim((string) $v))->filter()->implode(', ');
        if ($address === '') {
            $fields['address_kana'] = null;
        } elseif ($address !== $before || blank($profile->address_kana)) {
            try {
                $fields['address_kana'] = JapaneseTranslator::translate($address);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $fields;
    }

    public function save(): void
    {
        $state = $this->form->getState();
        \App\Models\AccessLog::record('profile_saved');

        /** @var User $user */
        $user = auth()->user();

        // Update User core fields
        $user->update([
            'name'  => $state['name'] ?? $user->name,
            'phone' => $state['phone'] ?? null,
        ]);

        // Update 1:1 profile
        $profile = $user->profile();
        $profileData = $state['studentProfile'] ?? [];
        foreach (['japan_visits_study', 'japan_visits_work', 'japan_visits_other'] as $visits) {
            $profileData[$visits] = (int) ($profileData[$visits] ?? 0);   // NOT NULL columns
        }
        $profileData['licenses'] = Certificates::flatten($state['certificates'] ?? []);
        $profileData += $this->addressKana($profile, $profileData);
        $profile->update($profileData);

        // Sync 1:n relations: educations. High-school streams (IPA, IPS) come from a fixed list.
        $educations = collect($state['educations'] ?? []);
        $majorsJa = $this->japaneseTitles(
            $user->educations()->whereNotNull('major_ja')->pluck('major_ja', 'major')->all(),
            $educations->pluck('major')->reject(fn ($m) => isset(Rirekisho::SCHOOL_STREAMS_JA[mb_strtoupper(trim((string) $m))]))->all(),
        );
        $this->syncRelation($user->educations(), $educations->map(fn ($e) => $e + [
            'major_ja' => $majorsJa[trim((string) ($e['major'] ?? ''))] ?? null,
        ])->all());
        // Work history is entered as month + year; stored as the first of the month.
        $monthDate = fn ($y, $m) => $y ? sprintf('%04d-%02d-01', $y, $m ?: 1) : null;
        $positionsJa = $this->japaneseTitles(
            $user->workExperiences()->whereNotNull('position_ja')->pluck('position_ja', 'position')->all(),
            collect($state['workExperiences'] ?? [])->pluck('position')->all(),
        );
        $this->syncRelation($user->workExperiences(), collect($state['workExperiences'] ?? [])->map(fn ($w) => [
            'company' => $w['company'] ?? null, 'position' => $w['position'] ?? null, 'is_current' => (bool) ($w['is_current'] ?? false),
            'position_ja' => $positionsJa[trim((string) ($w['position'] ?? ''))] ?? null,
            'start_date' => $monthDate($w['start_year'] ?? null, $w['start_month'] ?? null),
            'end_date' => $monthDate($w['end_year'] ?? null, $w['end_month'] ?? null),
        ])->all());

        Notification::make()
            ->title(__('Profile updated'))
            ->success()
            ->send();
    }

    /**
     * Japanese for job titles and majors on the CV, keyed by the text as
     * typed. Ones already translated ($known) are reused; new ones go to
     * DeepL in one request. When translation is unavailable the CV keeps
     * the typed text.
     *
     * @param  array<string, string>  $known
     * @return array<string, string>
     */
    private function japaneseTitles(array $known, array $texts): array
    {
        $new = collect($texts)->map(fn ($p) => trim((string) $p))->filter()->unique()
            ->reject(fn ($p) => isset($known[$p]))->values()->all();

        if ($new && JapaneseTranslator::enabled()) {
            try {
                $known += array_combine($new, JapaneseTranslator::many($new));
            } catch (\RuntimeException $e) {
                report($e);
            }
        }

        return $known;
    }

    /**
     * Wipe the relation and recreate from form rows.
     * Simple but reliable for a profile page.
     */
    private function syncRelation($relation, array $items): void
    {
        $relation->delete();
        foreach ($items as $item) {
            // Drop empty items
            $hasContent = collect($item)->filter(fn ($v) => $v !== null && $v !== '' && $v !== false)->isNotEmpty();
            if ($hasContent) {
                $relation->create($item);
            }
        }
    }
}
