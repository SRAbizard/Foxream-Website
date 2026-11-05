<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Gallery;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Real launch content: blog and event categories, the first blog post,
 * the electrical-internship interview event, the Kaneyo gallery album
 * and avatars for testimonials that have none. Photos are bundled in
 * database/seeders/content and copied to the public disk.
 *
 * Safe to run again: everything is matched by slug and updated in place.
 *
 *   php artisan db:seed --class=LaunchContentSeeder
 */
class LaunchContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBlog();
        $this->seedEvents();
        $this->seedGallery();
        $this->seedTestimonialAvatars();
    }

    private function seedBlog(): void
    {
        $brand = \App\Support\Brand::name();

        $cats = [
            ['slug' => 'tips-persiapan', 'name' => ['id' => 'Tips Persiapan', 'en' => 'Preparation Tips', 'ja' => '準備のコツ'], 'color' => '#b32510'],
            ['slug' => 'budaya-jepang', 'name' => ['id' => 'Budaya Jepang', 'en' => 'Japanese Culture', 'ja' => '日本文化'], 'color' => '#2b4795'],
            ['slug' => 'info-visa-program', 'name' => ['id' => 'Info Visa & Program', 'en' => 'Visa & Program Info', 'ja' => 'ビザ・制度情報'], 'color' => '#4e8a3e'],
            ['slug' => 'kabar', 'name' => ['id' => 'Kabar '.\App\Support\Brand::name(), 'en' => \App\Support\Brand::name().' News', 'ja' => 'お知らせ'], 'color' => '#f1af2e'],
        ];
        foreach ($cats as $i => $c) {
            PostCategory::updateOrCreate(['slug' => $c['slug']], ['name' => $c['name'], 'color' => $c['color'], 'sort_order' => $i + 1]);
        }

        Post::updateOrCreate(['slug' => '7-etika-kerja-di-jepang'], [
            'post_category_id' => PostCategory::where('slug', 'budaya-jepang')->value('id'),
            'author_id'        => User::role(['superadmin', 'admin'])->orderBy('id')->value('id'),
            'title' => [
                'id' => '7 Etika Kerja di Jepang yang Wajib Kamu Pahami Sebelum Berangkat',
                'en' => '7 Japanese Workplace Manners to Learn Before You Go',
                'ja' => '渡航前に知っておきたい日本の職場マナー7つ',
            ],
            'excerpt' => [
                'id' => 'Kemampuan bahasa saja tidak cukup. Inilah tujuh kebiasaan kerja yang membuat pekerja asing cepat dipercaya atasan dan rekan di Jepang.',
                'en' => 'Language skills alone are not enough. These seven work habits help foreign workers earn the trust of bosses and colleagues in Japan quickly.',
                'ja' => '語学力だけでは十分ではありません。日本で上司や同僚からすぐに信頼される、7つの仕事の習慣を紹介します。',
            ],
            'body' => [
                'id' => <<<HTML
<p>Banyak calon pekerja fokus belajar bahasa Jepang dan lulus JLPT, tapi lupa satu hal yang sama pentingnya: <strong>cara bekerja ala Jepang</strong>. Di tempat kerja Jepang, sikap dan kebiasaan sehari-hari sering dinilai sama tingginya dengan keterampilan teknis. Berikut tujuh etika yang perlu kamu latih sejak sekarang.</p>
<h2>1. Tepat waktu, bahkan lebih awal</h2>
<p>Di Jepang, datang tepat pukul 08.00 untuk shift pukul 08.00 sudah dianggap terlambat. Biasakan tiba 10–15 menit lebih awal supaya sempat berganti seragam, menyiapkan alat, dan ikut <em>chōrei</em> (rapat pagi singkat). Kalau terpaksa terlambat, hubungi atasan <strong>sebelum</strong> jam masuk, bukan sesudahnya.</p>
<h2>2. Hō-Ren-Sō: lapor, kabari, konsultasikan</h2>
<p><em>Hōrensō</em> (報告・連絡・相談) adalah singkatan dari <em>hōkoku</em> (melapor), <em>renraku</em> (memberi kabar) dan <em>sōdan</em> (berkonsultasi). Laporkan hasil pekerjaan meski tidak diminta, kabari perubahan sekecil apa pun, dan tanyakan dulu kalau ragu. Kesalahan yang segera dilaporkan jauh lebih dihargai daripada kesalahan yang disembunyikan.</p>
<h2>3. Salam adalah awal kepercayaan</h2>
<p>Ucapkan <em>“Ohayō gozaimasu”</em> saat datang, <em>“Otsukaresama desu”</em> kepada rekan, dan <em>“Osaki ni shitsurei shimasu”</em> saat pulang lebih dulu. Salam yang jelas sambil sedikit membungkuk menunjukkan bahwa kamu menghormati tim.</p>
<h2>4. Ikuti prosedur, jangan pakai cara sendiri</h2>
<p>Perusahaan Jepang punya SOP (<em>manual</em>) untuk hampir semua hal. Meski kamu punya cara yang lebih cepat, ikuti prosedur yang berlaku. Kalau punya ide perbaikan, sampaikan lewat atasan. Budaya <em>kaizen</em> (perbaikan terus-menerus) sangat menghargai usulan, asalkan disampaikan dengan cara yang benar.</p>
<h2>5. Terapkan 5S di area kerja</h2>
<p>5S adalah <em>seiri</em> (memilah), <em>seiton</em> (menata), <em>seisō</em> (membersihkan), <em>seiketsu</em> (merawat) dan <em>shitsuke</em> (membiasakan). Kembalikan alat ke tempatnya, bersihkan area sebelum pulang, dan jaga kerapian. Di pabrik maupun lokasi konstruksi, 5S juga berarti keselamatan kerja.</p>
<h2>6. Utamakan keselamatan</h2>
<p>Di lokasi kerja kamu akan sering mendengar <em>“Anzen daiichi”</em>, keselamatan nomor satu. Gunakan APD dengan benar, lakukan pengecekan sebelum mulai bekerja, dan jangan ragu berhenti lalu bertanya bila ada yang terasa tidak aman.</p>
<h2>7. Hormati aturan di luar jam kerja</h2>
<p>Etika tidak berhenti di kantor. Pisahkan sampah sesuai jadwal dan jenisnya, jaga ketenangan di asrama atau apartemen, dan hindari menelepon di kereta. Perilaku baik di lingkungan sekitar juga menjaga nama baik perusahaan dan sesama pekerja Indonesia.</p>
<h2>Siap berlatih?</h2>
<p>Di {$brand}, etika kerja seperti ini dilatih sejak masa persiapan, mulai dari simulasi <em>mensetsu</em> (wawancara) sampai kebiasaan harian di kelas. Dengan begitu, kamu tidak hanya lolos interview, tapi juga siap bekerja dengan percaya diri di hari pertama.</p>
HTML,
                'en' => <<<HTML
<p>Many job seekers focus on learning Japanese and passing the JLPT, but forget something just as important: <strong>the Japanese way of working</strong>. In Japanese workplaces, attitude and daily habits are often valued as highly as technical skills. Here are seven manners worth practising from today.</p>
<h2>1. Be on time, or better, early</h2>
<p>In Japan, arriving at exactly 8:00 for an 8:00 shift already counts as late. Arrive 10–15 minutes early so you can change, prepare your tools and join <em>chōrei</em>, the short morning meeting. If you must be late, call your supervisor <strong>before</strong> your start time, not after.</p>
<h2>2. Hō-Ren-Sō: report, inform, consult</h2>
<p><em>Hōrensō</em> (報告・連絡・相談) stands for <em>hōkoku</em> (report), <em>renraku</em> (inform) and <em>sōdan</em> (consult). Report your progress even when nobody asks, share every change however small, and ask first when in doubt. A mistake reported right away is valued far more than one that is hidden.</p>
<h2>3. Greetings build trust</h2>
<p>Say <em>“Ohayō gozaimasu”</em> when you arrive, <em>“Otsukaresama desu”</em> to colleagues, and <em>“Osaki ni shitsurei shimasu”</em> when you leave before others. A clear greeting with a small bow shows respect for your team.</p>
<h2>4. Follow the procedure, not your own way</h2>
<p>Japanese companies have a manual for almost everything. Even if you know a faster way, follow the agreed procedure. If you have an improvement idea, raise it with your supervisor: the <em>kaizen</em> culture of continuous improvement welcomes suggestions made through the right channel.</p>
<h2>5. Practise 5S at your workstation</h2>
<p>5S means <em>seiri</em> (sort), <em>seiton</em> (set in order), <em>seisō</em> (shine), <em>seiketsu</em> (standardise) and <em>shitsuke</em> (sustain). Put tools back where they belong, clean your area before going home and keep things tidy. In factories and on construction sites, 5S is also about safety.</p>
<h2>6. Safety comes first</h2>
<p>On site you will often hear <em>“Anzen daiichi”</em>, safety first. Wear your protective equipment properly, run your checks before starting, and never hesitate to stop and ask when something feels unsafe.</p>
<h2>7. Respect the rules outside work, too</h2>
<p>Manners do not stop at the workplace. Sort your rubbish by type and collection day, keep noise down in your dormitory or apartment, and avoid phone calls on the train. Good behaviour in the neighbourhood protects the reputation of your company and of fellow Indonesian workers.</p>
<h2>Ready to practise?</h2>
<p>At {$brand}, these manners are trained from the preparation stage, from <em>mensetsu</em> (interview) simulations to daily classroom habits. That way you do not just pass the interview: you are ready to work with confidence from day one.</p>
HTML,
                'ja' => <<<HTML
<p>日本語の勉強やJLPT合格に力を入れる人は多いですが、同じくらい大切なことを見落としがちです。それは<strong>日本式の働き方</strong>です。日本の職場では、日々の態度や習慣が技術と同じくらい評価されます。今日から練習したい7つのマナーを紹介します。</p>
<h2>1. 時間厳守、できれば早めに</h2>
<p>8時始業なら8時ちょうどの到着はすでに遅刻と見なされます。着替えや道具の準備、朝礼への参加ができるよう、10〜15分前には到着しましょう。やむを得ず遅れる場合は、始業<strong>前</strong>に上司へ連絡します。</p>
<h2>2. 報・連・相</h2>
<p>報告・連絡・相談は日本の職場の基本です。頼まれなくても進捗を報告し、小さな変更も共有し、迷ったらまず相談しましょう。すぐに報告されたミスは、隠されたミスよりもずっと高く評価されます。</p>
<h2>3. あいさつが信頼の第一歩</h2>
<p>出勤時は「おはようございます」、同僚には「お疲れさまです」、先に帰るときは「お先に失礼します」。軽いお辞儀とともにはっきりあいさつすることで、チームへの敬意が伝わります。</p>
<h2>4. 自己流ではなく手順どおりに</h2>
<p>日本の会社にはほとんどの作業にマニュアルがあります。より速い方法を知っていても、決められた手順に従いましょう。改善のアイデアは上司に提案します。改善（カイゼン）の文化では、正しい形で出された提案が歓迎されます。</p>
<h2>5. 職場で5Sを実践する</h2>
<p>5Sとは整理・整頓・清掃・清潔・しつけのことです。道具は元の場所に戻し、帰る前に持ち場を清掃しましょう。工場や建設現場では、5Sは安全にも直結します。</p>
<h2>6. 安全第一</h2>
<p>現場では「安全第一」という言葉をよく耳にします。保護具を正しく着用し、作業前の点検を行い、危ないと感じたらためらわずに手を止めて確認しましょう。</p>
<h2>7. 仕事以外のルールも守る</h2>
<p>マナーは職場だけのものではありません。ごみは種類と収集日ごとに分別し、寮やアパートでは静かに過ごし、電車内での通話は控えましょう。地域での良い振る舞いは、会社や同じインドネシア人労働者の評判も守ります。</p>
<h2>練習を始めよう</h2>
<p>{$brand}では、面接練習から教室での日々の習慣まで、準備期間からこうしたマナーを身につけます。面接に合格するだけでなく、初日から自信を持って働けるようになります。</p>
HTML,
            ],
            'seo_title' => [
                'id' => '7 Etika Kerja di Jepang Sebelum Berangkat | '.\App\Support\Brand::name(),
                'en' => '7 Japanese Workplace Manners Before You Go | '.\App\Support\Brand::name(),
                'ja' => '渡航前に知りたい日本の職場マナー7つ | '.\App\Support\Brand::name(),
            ],
            'seo_description' => [
                'id' => 'Tepat waktu, hōrensō, salam, 5S hingga keselamatan kerja: tujuh etika kerja Jepang yang perlu dilatih calon pekerja Indonesia.',
                'en' => 'Punctuality, hōrensō, greetings, 5S and safety: seven Japanese workplace manners every Indonesian worker should practise.',
                'ja' => '時間厳守、報連相、あいさつ、5S、安全第一。インドネシア人労働者が身につけたい日本の職場マナー7つ。',
            ],
            'thumbnail_path' => $this->asset('blog-etika-kerja.jpg', 'posts'),
            'tags'           => ['etika kerja', 'budaya jepang', 'tips'],
            'published_at'   => '2026-10-26 09:00:00',
            'is_featured'    => true,
        ]);
    }

    private function seedEvents(): void
    {
        $cats = [
            ['slug' => 'interview-perusahaan', 'name' => ['id' => 'Interview Perusahaan', 'en' => 'Company Interview', 'ja' => '企業面接'], 'color' => '#b32510'],
            ['slug' => 'seminar-sosialisasi', 'name' => ['id' => 'Seminar & Sosialisasi', 'en' => 'Seminar & Info Session', 'ja' => 'セミナー・説明会'], 'color' => '#2b4795'],
            ['slug' => 'pelatihan', 'name' => ['id' => 'Pelatihan', 'en' => 'Training', 'ja' => '研修'], 'color' => '#4e8a3e'],
            ['slug' => 'keberangkatan', 'name' => ['id' => 'Keberangkatan', 'en' => 'Departure', 'ja' => '出国'], 'color' => '#f1af2e'],
        ];
        foreach ($cats as $i => $c) {
            EventCategory::updateOrCreate(['slug' => $c['slug']], ['name' => $c['name'], 'color' => $c['color'], 'sort_order' => $i + 1]);
        }

        $companies = '<ul>'.collect([
            'オカデン', '昭和電工社', '東邦電気産業', '中島電気工事', '洛南エンジニアリング',
            '尾形電気工事', '鳳電気土木', '森電気工業', '丹和', '晶和電気工業',
        ])->map(fn ($name) => "<li>{$name}</li>")->implode('').'</ul>';

        Event::updateOrCreate(['slug' => 'interview-bidang-kelistrikan-internship'], [
            'event_category_id' => EventCategory::where('slug', 'interview-perusahaan')->value('id'),
            'title' => [
                'id' => 'Interview Bidang Kelistrikan Internship',
                'en' => 'Electrical Work Internship Interview',
                'ja' => '電気工事分野 技能実習 面接会',
            ],
            'description' => [
                'id' => '<p>'.\App\Support\Brand::name().' menyambut perwakilan sepuluh perusahaan kelistrikan dari Jepang yang datang langsung untuk mewawancarai calon peserta program <strong>Internship (magang) bidang kelistrikan</strong>.</p>'
                    .'<p>Peserta yang telah mengikuti pelatihan bahasa dan persiapan kerja di '.\App\Support\Brand::name().' akan menjalani interview tatap muka dengan masing-masing perusahaan.</p>'
                    .'<h3>Perusahaan yang hadir</h3>'.$companies
                    .'<p>Semoga sukses untuk seluruh peserta! がんばってください！</p>',
                'en' => '<p>'.\App\Support\Brand::name().' welcomes representatives of ten Japanese electrical companies who are visiting in person to interview candidates for the <strong>electrical work Internship programme</strong>.</p>'
                    .'<p>Candidates who completed language training and work preparation at '.\App\Support\Brand::name().' will have face-to-face interviews with each company.</p>'
                    .'<h3>Participating companies</h3>'.$companies
                    .'<p>Good luck to all candidates! がんばってください！</p>',
                'ja' => '<p>'.\App\Support\Brand::name().'は、<strong>電気工事分野の技能実習</strong>候補者の面接のため来訪される、日本の電気工事会社10社の皆さまをお迎えします。</p>'
                    .'<p>'.\App\Support\Brand::name().'で日本語研修と就労準備を受けた候補者が、各社との対面面接に臨みます。</p>'
                    .'<h3>参加企業</h3>'.$companies
                    .'<p>候補者の皆さん、がんばってください！</p>',
            ],
            'organizer' => ['id' => \App\Support\Brand::name(), 'en' => \App\Support\Brand::name(), 'ja' => \App\Support\Brand::name()],
            'location'  => ['id' => 'Kantor '.\App\Support\Brand::name(), 'en' => \App\Support\Brand::name().' office', 'ja' => \App\Support\Brand::name().' 事務所'],
            'image_path'   => $this->asset('kelistrikan-welcome.png', 'events'),
            'starts_at'    => '2026-10-29 09:00:00',
            'ends_at'      => '2026-10-29 16:00:00',
            'published_at' => '2026-10-26 09:00:00',
            'is_featured'  => true,
        ]);
    }

    /** The Kaneyo interview album with its three group photos. */
    private function seedGallery(): void
    {
        // Matched on the earlier slug too, so a database seeded before the
        // rename is updated instead of getting a second album.
        $album = Gallery::whereIn('slug', ['wawancara-offline-kaneyo-2026', 'interview-offline-kaneyo-2026'])->first()
            ?? new Gallery(['sort_order' => 0]);
        $album->fill([
            'slug'  => 'wawancara-offline-kaneyo-2026',
            'title' => [
                'id' => 'Wawancara Offline Driver, Konstruksi Bangunan, Teknik Sipil di Perusahaan Kaneyo',
                'en' => 'Offline Interviews for Driver, Building Construction and Civil Engineering at Kaneyo',
                'ja' => 'カネヨ社 ドライバー・建築・土木 対面面接',
            ],
            'caption' => [
                'id' => 'Dokumentasi wawancara tatap muka calon pekerja bidang Driver, Konstruksi Bangunan dan Teknik Sipil bersama perusahaan Kaneyo, 22 Oktober 2026.',
                'en' => 'Face-to-face interviews of Driver, Building Construction and Civil Engineering candidates with Kaneyo, 22 October 2026.',
                'ja' => '2026年10月22日、カネヨ社によるドライバー・建築・土木分野の候補者との対面面接の記録。',
            ],
            'taken_at'     => '2026-10-22',
            'is_published' => true,
        ])->save();

        foreach ([1, 2, 3] as $i) {
            $album->items()->updateOrCreate(
                ['image_path' => $this->asset("kaneyo-{$i}.jpg", 'gallery')],
                ['type' => 'image', 'sort_order' => $i, 'is_published' => true],
            );
        }
    }

    /** Give each testimonial without a photo one of the bundled avatars. */
    private function seedTestimonialAvatars(): void
    {
        $avatars = collect(range(1, 4))->shuffle()->values();
        Testimonial::whereNull('avatar_path')->orderBy('id')->get()
            ->each(function (Testimonial $t, int $i) use ($avatars) {
                $t->update(['avatar_path' => $this->asset('avatar-'.$avatars[$i % $avatars->count()].'.png', 'testimonials')]);
            });
    }

    /** Copy a bundled file to the public disk; returns its disk path. */
    private function asset(string $file, string $dir): string
    {
        $path = "{$dir}/{$file}";
        Storage::disk('public')->put($path, file_get_contents(__DIR__.'/content/'.$file));

        return $path;
    }
}
