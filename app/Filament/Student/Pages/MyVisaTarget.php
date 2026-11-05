<?php

namespace App\Filament\Student\Pages;

use App\Models\PartnerCampus;
use App\Models\StudentDocument;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\VisaCategory;
use App\Notifications\VisaTargetRequested;
use App\Support\Certificates;
use App\Support\SiteSettings;
use App\Support\VisaRequirements;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MyVisaTarget extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-flag';
    protected static ?int $navigationSort = 11;
    protected string $view = 'filament.student.pages.my-visa-target';

    public ?array $data = [];

    public static function getNavigationLabel(): string { return __('Visa Target'); }
    public function getTitle(): string                  { return __('Visa Target'); }

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $user->profile(); // ensure exists
        $user->refresh()->load('studentProfile.primaryVisa');
        $profile = $user->studentProfile;
        $request = $profile?->visa_request ?? [];

        $this->form->fill([
            'primary_visa_category_id' => $profile?->primary_visa_category_id,
            'internship_campus'        => $profile?->internship_campus_id ?? ($profile?->internship_campus_other ? 'other' : null),
            'internship_campus_other'  => $profile?->internship_campus_other,
            'language_type'            => $request['language']['type'] ?? null,
            'language_year'            => $request['language']['year'] ?? null,
            'language_month'           => $request['language']['month'] ?? null,
            'ssw_type'                 => $request['ssw']['type'] ?? null,
            'ssw_year'                 => $request['ssw']['year'] ?? null,
            'ssw_month'                => $request['ssw']['month'] ?? null,
            'ktm_semester'             => $request['ktm']['semester'] ?? null,
            'visa_target_notes'        => null, // request notes, blank by default
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $slug = fn (Get $get) => VisaCategory::find($get('primary_visa_category_id'))?->slug;

        return $schema
            ->components([
                Section::make(__('Choose your visa target'))
                    ->description(__('This determines which documents you need to upload. Your choice will be reviewed by an admin.'))
                    ->components([
                        Radio::make('primary_visa_category_id')
                            ->label(__('Visa target'))->hiddenLabel()
                            ->options(fn () => VisaCategory::orderBy('sort_order')->get()
                                ->mapWithKeys(fn ($v) => [$v->id => $v->t('name')]))
                            ->descriptions(fn () => VisaCategory::orderBy('sort_order')->get()
                                ->mapWithKeys(fn ($v) => [$v->id => $v->t('description') ?: ''])
                                ->all())
                            ->required()
                            ->live(),
                    ]),

                Section::make(__('Your campus'))
                    ->description(__('Internships are only open to students of our partner campuses.'))
                    ->visible(fn (Get $get) => VisaRequirements::needsCampus($slug($get)))
                    ->columns(2)
                    ->components([
                        Select::make('internship_campus')->label(__('Campus'))
                            ->options(fn () => PartnerCampus::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->label])
                                ->put('other', __('My campus is not on the list')))
                            ->searchable()->native(false)->required()->live(),
                        TextInput::make('internship_campus_other')->label(__('Campus name'))
                            ->helperText(__('The admin will check whether your campus can join the programme.'))
                            ->maxLength(160)->required()
                            ->visible(fn (Get $get) => $get('internship_campus') === 'other'),
                    ]),

                Section::make(__('Japanese language certificate'))
                    ->description(fn (Get $get) => $slug($get) === 'engineering'
                        ? __('Gijinkoku needs JLPT N3 or higher.')
                        : __('Tokutei Ginou needs JFT A2 or JLPT N4 or higher.'))
                    ->visible(fn (Get $get) => VisaRequirements::language($slug($get)) !== [])
                    ->columns(4)
                    ->components($this->certificateFields('language', fn (Get $get) => VisaRequirements::language($slug($get)))),

                Section::make(__('SSW certificate'))
                    ->description(__('The Tokutei Ginou skills test certificate for your field.'))
                    ->visible(fn (Get $get) => VisaRequirements::needsSsw($slug($get)))
                    ->columns(4)
                    ->components([
                        ...$this->certificateFields('ssw', fn () => array_keys(Certificates::GROUPS['ssw'])),
                        Callout::make(__('No SSW certificate yet?'))
                            ->description(__('Study for the test in the PJI Learning app, or join the offline training at Passion Japan Indonesia. Request this visa again once you have passed.'))
                            ->info()
                            ->actions(array_filter([
                                SiteSettings::academyUrl()
                                    ? Action::make('pjiLearning')->label(__('Open PJI Learning'))->url(SiteSettings::academyUrl())->openUrlInNewTab()
                                    : null,
                                Action::make('offlineTraining')->label(__('Ask about offline training'))->color('gray')
                                    ->url('https://wa.me/'.SiteSettings::contact('whatsapp').'?text='.rawurlencode(__('Hello, I would like to join the SSW offline training.')))
                                    ->openUrlInNewTab(),
                            ]))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Student ID card & transcript'))
                    ->description(__('Internship is for students in semester 3 to 5.'))
                    ->visible(fn (Get $get) => VisaRequirements::needsCampus($slug($get)))
                    ->columns(2)
                    ->components([
                        Select::make('ktm_semester')->label(__('Current semester'))
                            ->options(collect(VisaRequirements::INTERNSHIP_SEMESTERS)->mapWithKeys(fn ($n) => [$n => __('Semester :n', ['n' => $n])]))
                            ->helperText(__('Students in semester 1–2 or 6 and above cannot join the internship yet.'))
                            ->required()->native(false)->columnSpanFull(),
                        $this->upload('ktm', __('Student ID card (KTM)')),
                        $this->upload('transcript', __('Latest transcript')),
                    ]),

                Section::make(__('Diploma & transcript'))
                    ->visible(fn (Get $get) => VisaRequirements::needsDiploma($slug($get)))
                    ->columns(2)
                    ->components([
                        $this->upload('ijazah', __('Diploma (ijazah)')),
                        $this->upload('transcript', __('Transcript')),
                    ]),

                Section::make()->components([
                    Textarea::make('visa_target_notes')
                        ->label(__('Notes for the admin (optional)'))
                        ->rows(3)
                        ->placeholder(__('Tell us about your background, motivation, or any special considerations.'))
                        ->maxLength(1000),
                ]),
            ])
            ->statePath('data');
    }

    /** Certificate, year and month of passing, and the file. */
    private function certificateFields(string $part, \Closure $allowed): array
    {
        return [
            Select::make("{$part}_type")->label(__('Certificate'))
                ->options(fn (Get $get) => array_intersect_key(
                    Certificates::options($part), array_flip($allowed($get))))
                ->required()->native(false)->columnSpan(2),
            TextInput::make("{$part}_year")->label(__('Year passed'))
                ->numeric()->minValue(2000)->maxValue((int) date('Y'))->required(),
            Select::make("{$part}_month")->label(__('Month'))
                ->options(array_combine(range(1, 12), range(1, 12)))->native(false),
            $this->upload($part, __('Certificate file'))->columnSpanFull(),
        ];
    }

    /** A file for one request part; optional when one is already on file. */
    private function upload(string $part, string $label): FileUpload
    {
        $existing = fn (Get $get) => $this->existingDocument($part, $get("{$part}_type"));

        return FileUpload::make("{$part}_file")->label($label)
            ->disk('public')->directory('students/documents')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(5120)
            ->required(fn (Get $get) => ! $existing($get))
            ->helperText(fn (Get $get) => $existing($get)
                ? __('Already uploaded on :date. Upload again only to replace it.', ['date' => $existing($get)->created_at->format('d M Y')])
                : __('PDF or photo, at most 5 MB.'));
    }

    /**
     * The latest upload of this part's document type that was not
     * rejected; for a certificate, only one of the certificate picked.
     */
    private function existingDocument(string $part, ?string $certificate = null): ?StudentDocument
    {
        return StudentDocument::where('user_id', auth()->id())
            ->where('type', VisaRequirements::DOCUMENTS[$part])
            ->where('status', '!=', 'rejected')
            ->when(in_array($part, ['language', 'ssw'], true), fn ($q) => $q->where('label', $certificate ? Certificates::label($certificate) : null))
            ->latest('id')->first();
    }

    public function getStudentProfile(): ?StudentProfile
    {
        return auth()->user()->studentProfile;
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        $user = auth()->user();
        $profile = $user->profile();
        $slug = VisaCategory::find($state['primary_visa_category_id'])?->slug;

        // The uploads go to My Documents; the request remembers which ones.
        $request = [];
        foreach (VisaRequirements::parts($slug) as $part) {
            $type = $state["{$part}_type"] ?? null;
            $file = $state["{$part}_file"] ?? null;
            $document = $file
                ? StudentDocument::create([
                    'user_id'   => $user->id,
                    'type'      => VisaRequirements::DOCUMENTS[$part],
                    'label'     => $type ? Certificates::label($type) : null,
                    'file_path' => $file,
                    'status'    => 'pending',
                ])
                : $this->existingDocument($part, $type);

            $request[$part] = array_filter([
                'type'        => $type,
                'year'        => $state["{$part}_year"] ?? null,
                'month'       => $state["{$part}_month"] ?? null,
                'document_id' => $document?->id,
                'semester'    => $part === 'ktm' ? ($state['ktm_semester'] ?? null) : null,
            ]);
        }

        $campus = VisaRequirements::needsCampus($slug) ? ($state['internship_campus'] ?? null) : null;

        // Track if visa is being changed vs first request
        $isChange = $profile->visa_target_status === 'confirmed'
            && $profile->primary_visa_category_id !== (int) $state['primary_visa_category_id'];

        $newStatus = $isChange ? 'changed' : 'pending';

        $profile->update([
            'primary_visa_category_id' => $state['primary_visa_category_id'],
            'visa_target_status'       => $newStatus,
            'visa_target_requested_at' => now(),
            'visa_target_notes'        => $state['visa_target_notes'] ?: $profile->visa_target_notes,
            // Parts of an earlier request for another visa are kept for switching back.
            'visa_request'             => array_merge($profile->visa_request ?? [], $request),
            'internship_campus_id'     => is_numeric($campus) ? (int) $campus : null,
            'internship_campus_other'  => $campus === 'other' ? $state['internship_campus_other'] : null,
            'licenses'                 => $this->withCertificates($profile->licenses, $request),
            // Clear previous review when re-requesting
            'visa_target_reviewed_at'  => null,
            'visa_target_reviewed_by'  => null,
        ]);

        foreach (User::studentManagers() as $admin) {
            $admin->notify(new VisaTargetRequested($profile->fresh()));
        }
        \App\Models\AccessLog::record('visa_requested', VisaCategory::find($state['primary_visa_category_id'])?->t('name'));

        Notification::make()
            ->title(__('Request submitted'))
            ->body(__('An admin will review your visa target shortly.'))
            ->success()
            ->send();

        // Reload form with the new state
        $this->mount();
    }

    /** Add the certificates from the request to the profile's licences, so they are on the CV. */
    private function withCertificates(?array $licenses, array $request): array
    {
        $licenses = $licenses ?? [];
        foreach (['language', 'ssw'] as $part) {
            $type = $request[$part]['type'] ?? null;
            if (! $type || collect($licenses)->contains('type', $type)) {
                continue;
            }
            $licenses[] = array_filter([
                'group' => $part,
                'type'  => $type,
                'year'  => $request[$part]['year'] ?? null,
                'month' => $request[$part]['month'] ?? null,
            ]);
        }

        return $licenses;
    }
}
