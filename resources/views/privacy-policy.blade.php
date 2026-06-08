<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo-meta
        title="Privacy Policy — Wowlo"
        description="How Wowlo collects, uses and protects personal data for tutors, students and parents — in line with Singapore's PDPA." />
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-cream text-ink">

@php
    // All four official languages of Singapore. Each entry holds the full
    // translated policy. Sections are { h: heading, body: HTML }.
    $policy = [
        'en' => [
            'name' => 'English',
            'title' => 'Privacy Policy',
            'updated' => 'Last updated',
            'back' => '← Back to Login',
            'sections' => [
                ['h' => '1. About This Policy', 'body' => '<p>Wowlo is a private tuition management application operated by an individual tutor based in Singapore. This Privacy Policy explains how we collect, use, and protect your personal data in accordance with the <strong>Personal Data Protection Act 2012 (PDPA)</strong> of Singapore.</p>'],
                ['h' => '2. What Data We Collect', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li><strong>Account information:</strong> Full name, email address, phone number(s), and home address.</li><li><strong>Google account data:</strong> When you log in with Google, we receive your name, email address, and Google account ID. We do not receive your Google password.</li><li><strong>Homework and academic records:</strong> Assignments, submissions, due dates, and completion status.</li><li><strong>Messages:</strong> Communications sent between tutor and students through the app.</li><li><strong>Fee records:</strong> Lesson dates, hours, and fee amounts.</li></ul>'],
                ['h' => '3. How We Use Your Data', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li>To manage tuition sessions, homework, and academic progress.</li><li>To communicate between tutor and students/parents.</li><li>To generate fee statements and billing records.</li><li>To provide a secure login experience via email/password or Google Sign-In.</li></ul><p class="mt-2">We do <strong>not</strong> use your data for advertising, marketing, or share it with third parties for commercial purposes.</p>'],
                ['h' => '4. Who Can See Your Data', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li>The <strong>tutor</strong> can see all student records they manage.</li><li>Each <strong>student/parent</strong> can only see their own records — not those of other students.</li><li>No other parties have access to your data.</li></ul>'],
                ['h' => '5. Data Storage &amp; Security', 'body' => '<p>Your data is stored in a secure cloud database (Neon PostgreSQL, AWS Asia Pacific — Singapore region). Uploaded files are stored on Cloudflare R2 private cloud storage and are not publicly accessible. All connections use HTTPS encryption.</p>'],
                ['h' => '6. Account Creation', 'body' => '<p>Accounts are created exclusively by the tutor. Students and parents cannot self-register. Your account will only be created after the tutor has obtained your consent to use this application.</p>'],
                ['h' => '7. Your Rights Under PDPA', 'body' => '<p>Under the PDPA, you have the right to:</p><ul class="list-disc list-outside ml-5 space-y-1 mt-2"><li>Request access to the personal data we hold about you.</li><li>Request correction of inaccurate personal data.</li><li>Withdraw consent and request deletion of your account and data.</li></ul><p class="mt-2">To exercise any of these rights, please contact the tutor directly.</p>'],
                ['h' => '8. Data Retention', 'body' => '<p>Your data is retained for as long as you are an active student. Upon request, your account and associated data will be deleted within a reasonable time, except where retention is required by law.</p>'],
                ['h' => '9. Cookies', 'body' => '<p>This application uses session cookies solely to keep you logged in. No tracking or advertising cookies are used.</p>'],
                ['h' => '10. Changes to This Policy', 'body' => '<p>We may update this policy from time to time. The date at the top of this page reflects the most recent update. Continued use of the application after changes constitutes acceptance of the updated policy.</p>'],
            ],
        ],
        'ms' => [
            'name' => 'Bahasa Melayu',
            'title' => 'Dasar Privasi',
            'updated' => 'Kemas kini terakhir',
            'back' => '← Kembali ke Log Masuk',
            'sections' => [
                ['h' => '1. Mengenai Dasar Ini', 'body' => '<p>Wowlo ialah aplikasi pengurusan tuisyen persendirian yang dikendalikan oleh seorang guru tuisyen di Singapura. Dasar Privasi ini menerangkan cara kami mengumpul, menggunakan dan melindungi data peribadi anda selaras dengan <strong>Akta Perlindungan Data Peribadi 2012 (PDPA)</strong> Singapura.</p>'],
                ['h' => '2. Data Yang Kami Kumpul', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li><strong>Maklumat akaun:</strong> Nama penuh, alamat e-mel, nombor telefon, dan alamat rumah.</li><li><strong>Data akaun Google:</strong> Apabila anda log masuk dengan Google, kami menerima nama, alamat e-mel dan ID akaun Google anda. Kami tidak menerima kata laluan Google anda.</li><li><strong>Rekod kerja rumah dan akademik:</strong> Tugasan, penghantaran, tarikh akhir dan status penyiapan.</li><li><strong>Mesej:</strong> Komunikasi antara guru dan pelajar melalui aplikasi.</li><li><strong>Rekod yuran:</strong> Tarikh pelajaran, jam, dan jumlah yuran.</li></ul>'],
                ['h' => '3. Cara Kami Menggunakan Data Anda', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li>Untuk menguruskan sesi tuisyen, kerja rumah dan kemajuan akademik.</li><li>Untuk berkomunikasi antara guru dengan pelajar/ibu bapa.</li><li>Untuk menjana penyata yuran dan rekod bil.</li><li>Untuk menyediakan log masuk yang selamat melalui e-mel/kata laluan atau Log Masuk Google.</li></ul><p class="mt-2">Kami <strong>tidak</strong> menggunakan data anda untuk pengiklanan, pemasaran, atau berkongsi dengan pihak ketiga untuk tujuan komersial.</p>'],
                ['h' => '4. Siapa Yang Boleh Melihat Data Anda', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li><strong>Guru</strong> boleh melihat semua rekod pelajar yang diuruskannya.</li><li>Setiap <strong>pelajar/ibu bapa</strong> hanya boleh melihat rekod mereka sendiri — bukan rekod pelajar lain.</li><li>Tiada pihak lain mempunyai akses kepada data anda.</li></ul>'],
                ['h' => '5. Penyimpanan &amp; Keselamatan Data', 'body' => '<p>Data anda disimpan dalam pangkalan data awan yang selamat (Neon PostgreSQL, AWS Asia Pacific — wilayah Singapura). Fail yang dimuat naik disimpan di storan awan persendirian Cloudflare R2 dan tidak boleh diakses secara umum. Semua sambungan menggunakan penyulitan HTTPS.</p>'],
                ['h' => '6. Penciptaan Akaun', 'body' => '<p>Akaun dicipta secara eksklusif oleh guru. Pelajar dan ibu bapa tidak boleh mendaftar sendiri. Akaun anda hanya akan dicipta selepas guru memperoleh persetujuan anda untuk menggunakan aplikasi ini.</p>'],
                ['h' => '7. Hak Anda Di Bawah PDPA', 'body' => '<p>Di bawah PDPA, anda mempunyai hak untuk:</p><ul class="list-disc list-outside ml-5 space-y-1 mt-2"><li>Meminta akses kepada data peribadi yang kami simpan tentang anda.</li><li>Meminta pembetulan data peribadi yang tidak tepat.</li><li>Menarik balik persetujuan dan meminta pemadaman akaun serta data anda.</li></ul><p class="mt-2">Untuk melaksanakan mana-mana hak ini, sila hubungi guru secara terus.</p>'],
                ['h' => '8. Pengekalan Data', 'body' => '<p>Data anda dikekalkan selagi anda merupakan pelajar aktif. Atas permintaan, akaun dan data berkaitan anda akan dipadamkan dalam masa yang munasabah, kecuali jika pengekalan dikehendaki oleh undang-undang.</p>'],
                ['h' => '9. Kuki', 'body' => '<p>Aplikasi ini menggunakan kuki sesi semata-mata untuk memastikan anda kekal log masuk. Tiada kuki penjejakan atau pengiklanan digunakan.</p>'],
                ['h' => '10. Perubahan Pada Dasar Ini', 'body' => '<p>Kami mungkin mengemas kini dasar ini dari semasa ke semasa. Tarikh di bahagian atas halaman ini menunjukkan kemas kini terkini. Penggunaan berterusan aplikasi selepas perubahan merupakan penerimaan dasar yang dikemas kini.</p>'],
            ],
        ],
        'zh' => [
            'name' => '中文',
            'title' => '隐私政策',
            'updated' => '最后更新',
            'back' => '← 返回登录',
            'sections' => [
                ['h' => '1. 关于本政策', 'body' => '<p>Wowlo 是一款由新加坡个人补习老师运营的私人补习管理应用程序。本隐私政策说明我们如何根据新加坡《<strong>2012年个人资料保护法令 (PDPA)</strong>》收集、使用和保护您的个人资料。</p>'],
                ['h' => '2. 我们收集哪些资料', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li><strong>账户信息：</strong>全名、电子邮件地址、电话号码和住家地址。</li><li><strong>Google 账户资料：</strong>当您使用 Google 登录时，我们会收到您的姓名、电子邮件地址和 Google 账户 ID。我们不会收到您的 Google 密码。</li><li><strong>作业及学业记录：</strong>作业、提交、截止日期和完成状态。</li><li><strong>消息：</strong>老师与学生通过应用程序进行的沟通。</li><li><strong>费用记录：</strong>课程日期、时数和费用金额。</li></ul>'],
                ['h' => '3. 我们如何使用您的资料', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li>管理补习课程、作业和学业进度。</li><li>促进老师与学生/家长之间的沟通。</li><li>生成费用账单和账单记录。</li><li>通过电子邮件/密码或 Google 登录提供安全的登录体验。</li></ul><p class="mt-2">我们<strong>不会</strong>将您的资料用于广告、营销，或为商业目的与第三方共享。</p>'],
                ['h' => '4. 谁可以查看您的资料', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li><strong>老师</strong>可以查看其管理的所有学生记录。</li><li>每位<strong>学生/家长</strong>只能查看自己的记录，无法查看其他学生的记录。</li><li>没有其他方可以访问您的资料。</li></ul>'],
                ['h' => '5. 资料存储与安全', 'body' => '<p>您的资料存储在安全的云数据库中（Neon PostgreSQL，AWS 亚太区 — 新加坡区域）。上传的文件存储在 Cloudflare R2 私人云存储中，无法公开访问。所有连接均使用 HTTPS 加密。</p>'],
                ['h' => '6. 账户创建', 'body' => '<p>账户仅由老师创建。学生和家长无法自行注册。只有在老师获得您使用本应用程序的同意后，才会为您创建账户。</p>'],
                ['h' => '7. 您在 PDPA 下的权利', 'body' => '<p>根据 PDPA，您有权：</p><ul class="list-disc list-outside ml-5 space-y-1 mt-2"><li>要求查阅我们持有的关于您的个人资料。</li><li>要求更正不准确的个人资料。</li><li>撤回同意并要求删除您的账户和资料。</li></ul><p class="mt-2">如需行使上述任何权利，请直接联系老师。</p>'],
                ['h' => '8. 资料保留', 'body' => '<p>只要您仍是活跃学生，您的资料便会保留。根据要求，您的账户及相关资料将在合理时间内删除，但法律要求保留的情况除外。</p>'],
                ['h' => '9. Cookie', 'body' => '<p>本应用程序仅使用会话 Cookie 以保持您的登录状态。不使用任何跟踪或广告 Cookie。</p>'],
                ['h' => '10. 本政策的变更', 'body' => '<p>我们可能会不时更新本政策。本页顶部的日期反映最近一次更新。变更后继续使用本应用程序即表示接受更新后的政策。</p>'],
            ],
        ],
        'ta' => [
            'name' => 'தமிழ்',
            'title' => 'தனியுரிமைக் கொள்கை',
            'updated' => 'கடைசியாக புதுப்பிக்கப்பட்டது',
            'back' => '← உள்நுழைவுக்குத் திரும்பு',
            'sections' => [
                ['h' => '1. இந்தக் கொள்கை பற்றி', 'body' => '<p>Wowlo என்பது சிங்கப்பூரை தளமாகக் கொண்ட தனிநபர் கல்வி ஆசிரியரால் இயக்கப்படும் தனியார் டியூஷன் மேலாண்மை செயலி ஆகும். சிங்கப்பூரின் <strong>தனிப்பட்ட தரவு பாதுகாப்புச் சட்டம் 2012 (PDPA)</strong> இன்படி உங்கள் தனிப்பட்ட தரவை நாங்கள் எவ்வாறு சேகரிக்கிறோம், பயன்படுத்துகிறோம், பாதுகாக்கிறோம் என்பதை இந்தத் தனியுரிமைக் கொள்கை விளக்குகிறது.</p>'],
                ['h' => '2. நாங்கள் சேகரிக்கும் தரவு', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li><strong>கணக்குத் தகவல்:</strong> முழுப் பெயர், மின்னஞ்சல் முகவரி, தொலைபேசி எண்(கள்), வீட்டு முகவரி.</li><li><strong>Google கணக்குத் தரவு:</strong> Google மூலம் உள்நுழையும்போது, உங்கள் பெயர், மின்னஞ்சல் முகவரி, Google கணக்கு ஐடி ஆகியவற்றைப் பெறுகிறோம். உங்கள் Google கடவுச்சொல்லை நாங்கள் பெறுவதில்லை.</li><li><strong>வீட்டுப்பாடம் மற்றும் கல்விப் பதிவுகள்:</strong> பணிகள், சமர்ப்பிப்புகள், கடைசித் தேதிகள், நிறைவு நிலை.</li><li><strong>செய்திகள்:</strong> செயலி வழியாக ஆசிரியருக்கும் மாணவர்களுக்கும் இடையேயான தொடர்புகள்.</li><li><strong>கட்டணப் பதிவுகள்:</strong> பாடத் தேதிகள், மணிநேரம், கட்டணத் தொகைகள்.</li></ul>'],
                ['h' => '3. உங்கள் தரவை நாங்கள் எவ்வாறு பயன்படுத்துகிறோம்', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li>டியூஷன் வகுப்புகள், வீட்டுப்பாடம், கல்வி முன்னேற்றத்தை நிர்வகிக்க.</li><li>ஆசிரியருக்கும் மாணவர்கள்/பெற்றோர்களுக்கும் இடையே தொடர்பு கொள்ள.</li><li>கட்டண அறிக்கைகள் மற்றும் பில் பதிவுகளை உருவாக்க.</li><li>மின்னஞ்சல்/கடவுச்சொல் அல்லது Google உள்நுழைவு மூலம் பாதுகாப்பான உள்நுழைவை வழங்க.</li></ul><p class="mt-2">விளம்பரம், சந்தைப்படுத்தலுக்கு உங்கள் தரவை நாங்கள் <strong>பயன்படுத்துவதில்லை</strong>, வணிக நோக்கங்களுக்காக மூன்றாம் தரப்பினருடன் பகிர்வதில்லை.</p>'],
                ['h' => '4. உங்கள் தரவை யார் பார்க்க முடியும்', 'body' => '<ul class="list-disc list-outside ml-5 space-y-1"><li><strong>ஆசிரியர்</strong> தாம் நிர்வகிக்கும் அனைத்து மாணவர் பதிவுகளையும் பார்க்க முடியும்.</li><li>ஒவ்வொரு <strong>மாணவர்/பெற்றோரும்</strong> தங்கள் சொந்தப் பதிவுகளை மட்டுமே பார்க்க முடியும் — மற்ற மாணவர்களுடையதை அல்ல.</li><li>வேறு எந்தத் தரப்பினருக்கும் உங்கள் தரவுக்கான அணுகல் இல்லை.</li></ul>'],
                ['h' => '5. தரவு சேமிப்பு &amp; பாதுகாப்பு', 'body' => '<p>உங்கள் தரவு பாதுகாப்பான கிளவுட் தரவுத்தளத்தில் (Neon PostgreSQL, AWS Asia Pacific — சிங்கப்பூர் பகுதி) சேமிக்கப்படுகிறது. பதிவேற்றப்பட்ட கோப்புகள் Cloudflare R2 தனியார் கிளவுட் சேமிப்பகத்தில் சேமிக்கப்படுகின்றன, பொதுவில் அணுக முடியாது. அனைத்து இணைப்புகளும் HTTPS குறியாக்கத்தைப் பயன்படுத்துகின்றன.</p>'],
                ['h' => '6. கணக்கு உருவாக்கம்', 'body' => '<p>கணக்குகள் ஆசிரியரால் மட்டுமே உருவாக்கப்படுகின்றன. மாணவர்களும் பெற்றோர்களும் தாங்களாகவே பதிவு செய்ய முடியாது. இந்தச் செயலியைப் பயன்படுத்த உங்கள் ஒப்புதலை ஆசிரியர் பெற்ற பிறகே உங்கள் கணக்கு உருவாக்கப்படும்.</p>'],
                ['h' => '7. PDPA இன் கீழ் உங்கள் உரிமைகள்', 'body' => '<p>PDPA இன் கீழ், உங்களுக்கு பின்வரும் உரிமைகள் உள்ளன:</p><ul class="list-disc list-outside ml-5 space-y-1 mt-2"><li>உங்களைப் பற்றி நாங்கள் வைத்திருக்கும் தனிப்பட்ட தரவை அணுக கோர.</li><li>தவறான தனிப்பட்ட தரவைத் திருத்தக் கோர.</li><li>ஒப்புதலைத் திரும்பப் பெற்று, உங்கள் கணக்கு மற்றும் தரவை நீக்கக் கோர.</li></ul><p class="mt-2">இந்த உரிமைகள் எதையேனும் பயன்படுத்த, தயவுசெய்து ஆசிரியரை நேரடியாகத் தொடர்பு கொள்ளவும்.</p>'],
                ['h' => '8. தரவு வைத்திருத்தல்', 'body' => '<p>நீங்கள் செயலில் உள்ள மாணவராக இருக்கும் வரை உங்கள் தரவு வைத்திருக்கப்படும். கோரிக்கையின் பேரில், சட்டப்படி வைத்திருக்க வேண்டிய சந்தர்ப்பங்களைத் தவிர, உங்கள் கணக்கும் அதனுடன் தொடர்புடைய தரவும் நியாயமான நேரத்திற்குள் நீக்கப்படும்.</p>'],
                ['h' => '9. குக்கீகள்', 'body' => '<p>இந்தச் செயலி உங்களை உள்நுழைந்த நிலையில் வைத்திருக்க அமர்வு குக்கீகளை மட்டுமே பயன்படுத்துகிறது. கண்காணிப்பு அல்லது விளம்பர குக்கீகள் எதுவும் பயன்படுத்தப்படவில்லை.</p>'],
                ['h' => '10. இந்தக் கொள்கையின் மாற்றங்கள்', 'body' => '<p>நாங்கள் அவ்வப்போது இந்தக் கொள்கையைப் புதுப்பிக்கலாம். இந்தப் பக்கத்தின் மேலே உள்ள தேதி சமீபத்திய புதுப்பிப்பைக் காட்டுகிறது. மாற்றங்களுக்குப் பிறகு செயலியைத் தொடர்ந்து பயன்படுத்துவது புதுப்பிக்கப்பட்ட கொள்கையை ஏற்பதாகும்.</p>'],
            ],
        ],
    ];
@endphp

    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8"
         x-data="{ lang: 'en' }">
        <div class="max-w-3xl mx-auto">

            <!-- Header -->
            <div class="mb-6 text-center">
                <a href="{{ route('login') }}">
                    <x-application-logo class="h-20 w-auto mx-auto" />
                </a>
            </div>

            <!-- Language switcher -->
            <div class="mb-8 flex flex-wrap justify-center gap-2">
                @foreach ($policy as $code => $lang)
                    <button type="button"
                            @click="lang = '{{ $code }}'"
                            :class="lang === '{{ $code }}'
                                ? 'bg-primary text-white shadow'
                                : 'bg-white text-ink hover:bg-primary/10'"
                            class="rounded-full border border-gray-200 px-4 py-1.5 text-sm font-semibold transition-colors duration-200 cursor-pointer">
                        {{ $lang['name'] }}
                    </button>
                @endforeach
            </div>

            @foreach ($policy as $code => $lang)
                <div x-show="lang === '{{ $code }}'" x-cloak>
                    <div class="mb-8 text-center">
                        {{-- One H1 per page: the English title is the H1; the other three
                             official-language versions use H2 (same visual size). --}}
                        @if ($code === 'en')
                            <h1 class="text-3xl font-bold text-primary-dark">{{ $lang['title'] }}</h1>
                        @else
                            <h2 class="text-3xl font-bold text-primary-dark">{{ $lang['title'] }}</h2>
                        @endif
                        <p class="mt-1 text-sm text-muted">{{ $lang['updated'] }}: {{ date('d F Y') }}</p>
                    </div>

                    <div class="bg-white shadow-md rounded-lg px-8 py-10 space-y-8 text-sm leading-relaxed text-ink">
                        @foreach ($lang['sections'] as $section)
                            <section>
                                <h2 class="text-base font-bold text-primary-dark mb-2">{!! $section['h'] !!}</h2>
                                {!! $section['body'] !!}
                            </section>
                        @endforeach
                    </div>

                    <div class="mt-8 text-center">
                        <a href="{{ route('login') }}" class="text-sm text-primary-dark hover:underline font-semibold">
                            {{ $lang['back'] }}
                        </a>
                    </div>
                </div>
            @endforeach

        </div>
    </div>

    <style>[x-cloak]{display:none!important;}</style>

</body>
</html>
