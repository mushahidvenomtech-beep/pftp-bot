<?php
/**
 * Migration Script: Set up Mushahid Majeed trilingual complete admission & lead conversion conversation
 */

declare(strict_types=1);

$jsonPath = __DIR__ . '/../chatbot/config/static_data.json';
if (!file_exists($jsonPath)) {
    die("Error: static_data.json not found.\n");
}

$data = json_decode(file_get_contents($jsonPath), true);
if (!is_array($data)) {
    die("Error: Invalid JSON in static_data.json\n");
}

// 1. Update Visitor 1 to Mushahid Majeed
$data['visitors'][0] = [
    'id'         => 1,
    'full_name'  => 'Mushahid Majeed',
    'email'      => 'mushahid@gmail.com',
    'phone'      => '+923001234567',
    'ip_address' => '127.0.0.1',
    'created_at' => '2026-11-14 07:18:01'
];

// Ensure Muhammad Bilal is kept as a secondary test visitor so nothing ever breaks
$hasBilal = false;
foreach ($data['visitors'] as $v) {
    if (mb_stripos($v['full_name'], 'bilal') !== false) {
        $hasBilal = true;
        break;
    }
}
if (!$hasBilal) {
    $data['visitors'][] = [
        'id'         => count($data['visitors']) + 1,
        'full_name'  => 'Muhammad Bilal',
        'email'      => 'bilal.student@example.com',
        'phone'      => '+923009876543',
        'ip_address' => '127.0.0.1',
        'created_at' => '2026-11-14 07:18:01'
    ];
}

// 2. Build the Full Trilingual Admission & Lead Conversion Roadmap Messages
$messages = [
    // ==========================================
    // MODULE 1: ENGLISH MODE (Full Journey)
    // ==========================================
    [
        'id'               => 1,
        'sender_type'      => 'bot',
        'message'          => "Hello Mushahid Majeed! 👋 Welcome to PFTP (Professional Freelancing Training Program). I am your Senior Admissions & Academic Advisor. How can I assist you with your education and career goals today?",
        'matched_kb_id'    => null,
        'is_fallback'      => 0,
        'possible_careers' => null,
        'created_at'       => '2026-11-16 08:00:10'
    ],
    [
        'id'               => 2,
        'sender_type'      => 'visitor',
        'message'          => "Hello! My name is Mushahid Majeed. I have recently completed my Intermediate in Computer Science (ICS / FSc Pre-Engineering). I want to build a career in software development and earn through freelancing, but I am confused about which field to choose. What course do you suggest for my study background?",
        'matched_kb_id'    => null,
        'is_fallback'      => 0,
        'possible_careers' => null,
        'created_at'       => '2026-11-16 08:01:25'
    ],
    [
        'id'               => 3,
        'sender_type'      => 'bot',
        'message'          => "Thank you for sharing your academic background, Mushahid! With your ICS / Pre-Engineering foundation, **Full Stack Web Development & Modern Software Engineering** is the highest-ROI pathway for you.\n\nIn this program, you will master frontend UI engineering (HTML5, CSS3, JavaScript, Tailwind) and robust backend architecture (PHP, MySQL, RESTful APIs). Full stack developers are in enormous demand on Upwork, Fiverr, and across technology firms globally.",
        'matched_kb_id'    => 201,
        'is_fallback'      => 0,
        'possible_careers' => 'Full Stack Web Developer; Frontend Specialist; Backend Developer; Freelance Web Master',
        'created_at'       => '2026-11-16 08:01:40'
    ],
    [
        'id'               => 4,
        'sender_type'      => 'visitor',
        'message'          => "That aligns well with my goals! What is the admission fee, class schedule, and duration? Will I receive an official certificate, and will you guide me on how to get freelance clients?",
        'matched_kb_id'    => null,
        'is_fallback'      => 0,
        'possible_careers' => null,
        'created_at'       => '2026-11-16 08:02:50'
    ],
    [
        'id'               => 5,
        'sender_type'      => 'bot',
        'message'          => "Great questions, Mushahid! Here are the full admission details tailored for you:\n\n- **Program Duration**: 3 Months (12 Weeks) with hands-on live online evening sessions.\n- **LMS Portal**: 24/7 access to recorded lectures, coding exercises, and instructor support.\n- **Subsidized Fee**: Nominal one-time admission processing fee with no recurring monthly tuition charges.\n- **Verifiable Certification**: Government-recognized, verifiable PFTP Certificate upon project completion.\n- **Freelance Launchpad**: Dedicated live bidding workshops on Upwork and Fiverr to help you land your first paying client before the batch ends!",
        'matched_kb_id'    => 202,
        'is_fallback'      => 0,
        'possible_careers' => 'PFTP Admissions Desk; Student Counselling; Career Services',
        'created_at'       => '2026-11-16 08:03:05'
    ],
    [
        'id'               => 6,
        'sender_type'      => 'visitor',
        'message'          => "Excellent! I am ready to enroll. Could you provide me with the complete week-by-week roadmap of how the course will be completed, and where should I submit my application?",
        'matched_kb_id'    => null,
        'is_fallback'      => 0,
        'possible_careers' => null,
        'created_at'       => '2026-11-16 08:04:15'
    ],
    [
        'id'               => 7,
        'sender_type'      => 'bot',
        'message'          => "Welcome aboard, Mushahid! Here is your complete 12-Week Course Completion Roadmap:\n\n🚀 **Phase 1 (Weeks 1–4): Core Web Foundations & Responsive UI**\n- Semantic HTML5, CSS3 Modern Layouts (Flexbox & Grid), Mobile-First Design, JavaScript Core Concepts and DOM manipulation.\n\n🚀 **Phase 2 (Weeks 5–8): Backend Engineering & Database Systems**\n- Server-side programming with PHP, Relational Database Design with MySQL, CRUD logic, user authentication, and secure API endpoints.\n\n🚀 **Phase 3 (Weeks 9–10): Capstone Commercial Web Application**\n- Architecting, developing, and deploying a full-featured commercial web portal for your professional portfolio.\n\n🚀 **Phase 4 (Weeks 11–12): Freelancing Launch & Lead Closing**\n- Creating winning Upwork & Fiverr profiles, proposal crafting strategies, client communication mastery, and official PFTP Certification issuance.\n\n👉 **Final Step to Finalize Your Admission:**\nVisit the official admissions portal at https://pftpedu.org/registration to fill out your application. Your fee voucher will be generated instantly for online payment via JazzCash, Easypaisa, or Bank Transfer. Congratulations on taking the first step towards a bright future!",
        'matched_kb_id'    => 203,
        'is_fallback'      => 0,
        'possible_careers' => 'Full Stack Web Developer; Freelance Software Engineer; Certified PFTP Graduate',
        'created_at'       => '2026-11-16 08:04:30'
    ],

    // ==========================================
    // MODULE 2: ROMAN URDU MODE (Full Journey)
    // ==========================================
    [
        'id'               => 8,
        'sender_type'      => 'visitor',
        'message'          => "Bohat zabardast! Agar main Roman Urdu mein sawal poochun toh kya mujhe poora dakhla aur course ka roadmap Roman Urdu mein mil sakta hai? Main freelancing aur coding mein bilkul naya hoon.",
        'matched_kb_id'    => null,
        'is_fallback'      => 0,
        'possible_careers' => null,
        'created_at'       => '2026-11-16 08:05:40'
    ],
    [
        'id'               => 9,
        'sender_type'      => 'bot',
        'message'          => "Jee bilkul Mushahid Majeed sb! Aap ka background ICS / Intermediate hai, is liye **Full Stack Web Development** aap ke liye 100% perfect hai.\n\nIs course mein zero se le kar advance tak website banana sikhaya jata hai. Pehle maah mein frontend, doosre maah mein backend aur database, aur teesre maah mein Upwork/Fiverr par orders lena sikhaya jata hai.",
        'matched_kb_id'    => 204,
        'is_fallback'      => 0,
        'possible_careers' => 'Full Stack Web Developer; Frontend Specialist; Freelancer',
        'created_at'       => '2026-11-16 08:05:55'
    ],
    [
        'id'               => 10,
        'sender_type'      => 'visitor',
        'message'          => "Mujhe fees, class timings aur certificate ke baray mein mazeed wazahat chahiye, taake main apna dakhla confirm kar sakoon.",
        'matched_kb_id'    => null,
        'is_fallback'      => 0,
        'possible_careers' => null,
        'created_at'       => '2026-11-16 08:06:50'
    ],
    [
        'id'               => 11,
        'sender_type'      => 'bot',
        'message'          => "Mushahid sb, PFTP ka model har student ke liye nihayat aasan hai:\n\n- **Munasib Fees**: Sirf nominal admission processing fee, koi mahana fees nahi.\n- **Live & Recorded**: Shaam ke waqt live interactive classes aur portal par recorded lectures.\n- **Manzoor-shuda Sanad**: Course mukammal honay par verified PFTP Certificate.\n- **Freelance Earning**: Upwork aur Fiverr par gigs rank karwana aur proposal likhna sikhaya jata hai.",
        'matched_kb_id'    => 205,
        'is_fallback'      => 0,
        'possible_careers' => 'PFTP Admissions Desk; Career Mentorship',
        'created_at'       => '2026-11-16 08:07:05'
    ],
    [
        'id'               => 12,
        'sender_type'      => 'visitor',
        'message'          => "Mujhe course complete karne ka step-by-step roadmap samjha dein aur batayein dakhla kahan se lena hai?",
        'matched_kb_id'    => null,
        'is_fallback'      => 0,
        'possible_careers' => null,
        'created_at'       => '2026-11-16 08:08:10'
    ],
    [
        'id'               => 13,
        'sender_type'      => 'bot',
        'message'          => "Yeh raha aap ka mukammal 12 Hafton (3 Maah) ka Study Roadmap:\n\n📌 **Hafta 1 se 4 (Frontend UI Mastery)**:\nHTML5, CSS3, Flexbox, Responsive Design aur JavaScript ki bunyadi logic.\n\n📌 **Hafta 5 se 8 (Backend aur Database)**:\nPHP server coding, MySQL database, secure login systems aur live forms.\n\n📌 **Hafta 9 aur 10 (Live Commercial Project)**:\nEk mukammal online web app banakar portfolio mein live karna.\n\n📌 **Hafta 11 aur 12 (Freelancing aur Earning Launch)**:\nFiverr aur Upwork par profile setup, proposals bhejna aur PFTP ki verified digital sanad ka husool.\n\n👉 **Dakhla Finalize Karne Ka Tareeqa:**\nAbhi https://pftpedu.org/registration par ja kar apna form fill karein aur JazzCash ya Easypaisa se fee jama kar ke apni seat mehfooz karein!",
        'matched_kb_id'    => 206,
        'is_fallback'      => 0,
        'possible_careers' => 'Web Developer; Freelance Specialist; Certified PFTP Graduate',
        'created_at'       => '2026-11-16 08:08:25'
    ],

    // ==========================================
    // MODULE 3: URDU MODE (Full Journey)
    // ==========================================
    [
        'id'               => 14,
        'sender_type'      => 'visitor',
        'message'          => "السلام علیکم! میرا نام مشاہد مجید ہے۔ میں نے انٹرمیڈیٹ کیا ہے اور میں اردو میں داخلے کا مکمل طریقہ کار، تعلیمی تفصیلات اور کورس کی تکمیل کا روڈ میپ جاننا چاہتا ہوں۔",
        'matched_kb_id'    => null,
        'is_fallback'      => 0,
        'possible_careers' => null,
        'created_at'       => '2026-11-16 08:09:30'
    ],
    [
        'id'               => 15,
        'sender_type'      => 'bot',
        'message'          => "وعلیکم السلام مشاہد مجید صاحب! پی ایف ٹی پی (PFTP) میں خوش آمدید۔\n\nآپ کے انٹرمیڈیٹ (ICS / پری انجینئرنگ) کے پس منظر کے مطابق **فل اسٹیک ویب ڈویلپمنٹ اور ماڈرن سافٹ ویئر انجینئرنگ** آپ کے لیے سب سے بہترین فیلڈ ہے۔ اس میں آپ ویب سائٹس اور سوفٹ ویئر بنانا سیکھتے ہیں جس کی بین الاقوامی مارکیٹ اور اپ ورک (Upwork) پر زبردست مانگ ہے۔",
        'matched_kb_id'    => 207,
        'is_fallback'      => 0,
        'possible_careers' => 'ویب ڈویلپر؛ سافٹ ویئر انجینئر؛ فری لانسر',
        'created_at'       => '2026-11-16 08:09:45'
    ],
    [
        'id'               => 16,
        'sender_type'      => 'visitor',
        'message'          => "کورس کی فیس، کلاسوں کے اوقات اور سرٹیفکیٹ کی کیا تفصیلات ہیں؟ کیا کورس مکمل ہونے کے بعد فری لانسنگ میں رہنمائی ملے گی؟",
        'matched_kb_id'    => null,
        'is_fallback'      => 0,
        'possible_careers' => null,
        'created_at'       => '2026-11-16 08:10:40'
    ],
    [
        'id'               => 17,
        'sender_type'      => 'bot',
        'message'          => "مشاہد صاحب، پی ایف ٹی پی آپ کو مکمل پریکٹیکل سہولیات فراہم کرتا ہے:\n\n- **انتہائی مناسب فیس**: حکومتی ویژن کے تحت صرف برائے نام داخلہ پروسیسنگ فیس۔\n- **لائیو اور ریکارڈ شدہ کلاسز**: شام کے اوقات میں لائیو کلاسز اور ایل ایم ایس پورٹل پر 24 گھنٹے ریکارڈنگز۔\n- **مستند سرٹیفکیٹ**: کورس اور پروجیکٹ کی تکمیل پر کیو آر کوڈ سے تصدیق شدہ آفیشل سرٹیفکیٹ۔\n- **فری لانسنگ رہنمائی**: اپ ورک اور فائیور پر پہلی کمائی حاصل کرنے کے لیے سینیئر مینٹورز کی خصوصی کوچنگ۔",
        'matched_kb_id'    => 208,
        'is_fallback'      => 0,
        'possible_careers' => 'پی ایف ٹی پی ایڈمیشنز؛ اسٹوڈنٹ کاؤنسلنگ',
        'created_at'       => '2026-11-16 08:10:55'
    ],
    [
        'id'               => 18,
        'sender_type'      => 'bot',
        'message'          => "کورس کی مرحلہ وار تکمیل کا حتمی 12 ہفتوں کا روڈ میپ درج ذیل ہے:\n\n🎯 **پہلا مرحلہ (ہفتہ 1 تا 4) - فرنٹ اینڈ ویب ڈیزائننگ**:\nایچ ٹی ایم ایل (HTML5)، سی ایس ایس (CSS3)، جاوا اسکرپٹ اور موبائل فرینڈلی جدید لے آؤٹس کی تیاری۔\n\n🎯 **دوسرا مرحلہ (ہفتہ 5 تا 8) - بیک اینڈ اور ڈیٹا بیس سسٹمز**:\nپی ایچ پی (PHP) سرور پروگرامنگ، مائی ایس کیو ایل (MySQL) ڈیٹا بیس اور محفوظ ویب سسٹمز۔\n\n🎯 **تیسرا مرحلہ (ہفتہ 9 تا 10) - لائیو پورٹ فولیو پروجیکٹ**:\nمارکیٹ کی سطح کی مکمل ویب ایپلیکیشن بنانا تاکہ آپ بین الاقوامی کلائنٹس کو اپنا کام دکھا سکیں۔\n\n🎯 **چوتھا مرحلہ (ہفتہ 11 تا 12) - فری لانسنگ اور سند کا حصول**:\nاپ ورک اور فائیور پر پروفائل اپروول، موثر پرپوزل رائٹنگ اور پی ایف ٹی پی کی باضابطہ سند کا اجراء۔\n\n👉 **داخلہ مکمل کرنے کے لیے فوری طور پر:**\nسرکاری پورٹل https://pftpedu.org/registration پر تشریف لے جائیں، اپنا آن لائن فارم مکمل کریں اور جاز کیش، ایزی پیسہ یا بینک کے ذریعے فیس ادا کر کے اپنی نشست محفوظ فرمائیں!",
        'matched_kb_id'    => 209,
        'is_fallback'      => 0,
        'possible_careers' => 'فل اسٹیک ویب ڈویلپر؛ تصدیق شدہ گریجویٹ؛ پی ایف ٹی پی ایڈمیشنز',
        'created_at'       => '2026-11-16 08:11:15'
    ]
];

// Assign to Conversation 1
$data['conversations'][0] = [
    'id'            => 1,
    'visitor_id'    => 1,
    'session_token' => '701d83d468bc4a94847e0c14dc8a1f3aa9045e42ae1173565bcaa1883d40e325',
    'status'        => 'active',
    'created_at'    => '2026-11-14 07:18:01',
    'updated_at'    => '2026-11-16 08:11:15',
    'messages'      => $messages
];

// 3. Add Dedicated Knowledge Base Records for Roadmap & Study Background
$newKbRecords = [
    [
        'id' => 201,
        'intent_id' => 'study_course_suggestion_en',
        'category' => 'Full Stack Web Development',
        'language' => 'en',
        'question' => 'I have completed intermediate / ICS. Which course is best suited for my study details?',
        'question_variations' => 'Which course should I choose after intermediate|I completed ICS what should I study|Best course for study background ICS|Suggest a course for my study details|Course suggestion for Mushahid Majeed',
        'answer' => "With your Intermediate (ICS / FSc Pre-Engineering) background, Full Stack Web Development & Modern Software Engineering is the highest-ROI pathway for you. In this course, you will master frontend UI engineering (HTML5, CSS3, JavaScript, Tailwind) and robust backend architecture (PHP, MySQL, RESTful APIs). Full stack developers are in enormous demand on Upwork, Fiverr, and across technology firms globally.",
        'keywords' => 'intermediate, ics, study background, course suggestion, which course, study details, recommend course, mushahid',
        'possible_careers' => 'Full Stack Web Developer; Frontend Specialist; Backend Developer; Freelance Web Master',
        'status' => 'active'
    ],
    [
        'id' => 202,
        'intent_id' => 'course_conversion_en',
        'category' => 'Admission Process',
        'language' => 'en',
        'question' => 'What are the class timings, fee structure, certificate details and freelancing support?',
        'question_variations' => 'Tell me fee structure and timings|Will I get certificate and freelance support|Admission details for course|Lead closing details',
        'answer' => "PFTP provides student-first admissions:\n- Program Duration: 3 Months (12 Weeks) with hands-on live online evening sessions.\n- LMS Portal: 24/7 access to recorded lectures, coding exercises, and instructor support.\n- Subsidized Fee: Nominal one-time admission processing fee with no recurring monthly tuition charges.\n- Verifiable Certification: Government-recognized, verifiable PFTP Certificate upon project completion.\n- Freelance Launchpad: Dedicated live bidding workshops on Upwork and Fiverr to help you land your first paying client before the batch ends!",
        'keywords' => 'fee structure, class timings, duration, certificate, freelancing guidance, lead conversion, lms',
        'possible_careers' => 'PFTP Admissions Desk; Student Counselling; Career Services',
        'status' => 'active'
    ],
    [
        'id' => 203,
        'intent_id' => 'admission_roadmap_en',
        'category' => 'Admission Process',
        'language' => 'en',
        'question' => 'What is the complete 12-week roadmap for course completion and admission?',
        'question_variations' => 'Complete roadmap for course completion|How will the course be completed roadmap|12 week roadmap|Course roadmap and admission closing|Roadmap for Mushahid Majeed',
        'answer' => "Here is your complete 12-Week Course Completion Roadmap:\n\nPhase 1 (Weeks 1–4): Core Web Foundations & Responsive UI (HTML5, CSS3, Flexbox, Grid, JavaScript).\nPhase 2 (Weeks 5–8): Backend Engineering & Database Systems (PHP, MySQL, REST APIs, Authentication).\nPhase 3 (Weeks 9–10): Capstone Commercial Web Application for your portfolio.\nPhase 4 (Weeks 11–12): Freelancing Launch on Upwork & Fiverr, Proposal Mastery & Official PFTP Certification.\n\nFinal Step to Secure Your Seat: Complete your registration at https://pftpedu.org/registration and pay your nominal fee via JazzCash, Easypaisa, or Bank Transfer.",
        'keywords' => 'complete roadmap, 12-week roadmap, course completion roadmap, how course completed, roadmap, closing roadmap, enroll roadmap',
        'possible_careers' => 'Full Stack Web Developer; Freelance Software Engineer; Certified PFTP Graduate',
        'status' => 'active'
    ],
    [
        'id' => 204,
        'intent_id' => 'study_course_suggestion_roman_ur',
        'category' => 'Full Stack Web Development',
        'language' => 'roman_ur',
        'question' => 'Mera study background intermediate / ICS hai, mujhe konsa course karna chahiye?',
        'question_variations' => 'ICS ke baad konsa course best hai|Mera background computer hai konsa course karun|Study details ke mutabiq course suggest karein|Roman urdu course suggestion',
        'answer' => "Aap ka background ICS / Intermediate hai, is liye Full Stack Web Development aap ke liye 100% perfect hai. Is course mein zero se le kar advance tak website banana sikhaya jata hai. Pehle maah mein frontend, doosre maah mein backend aur database, aur teesre maah mein Upwork/Fiverr par orders lena sikhaya jata hai.",
        'keywords' => 'study background, ics, intermediate, konsa course, course suggestion, roman urdu, suggest, parhai',
        'possible_careers' => 'Full Stack Web Developer; Frontend Specialist; Freelancer',
        'status' => 'active'
    ],
    [
        'id' => 205,
        'intent_id' => 'course_conversion_roman_ur',
        'category' => 'Admission Process',
        'language' => 'roman_ur',
        'question' => 'Fees, class timings aur certificate ke baray mein wazahat karein?',
        'question_variations' => 'Fees kitni hai aur class kab hogi|Certificate milega kya|Freelance earning sikhayein gay|Roman urdu conversion',
        'answer' => "PFTP ka model har student ke liye nihayat aasan hai:\n- Munasib Fees: Sirf nominal admission processing fee, koi mahana fees nahi.\n- Live & Recorded: Shaam ke waqt live interactive classes aur portal par recorded lectures.\n- Manzoor-shuda Sanad: Course mukammal honay par verified PFTP Certificate.\n- Freelance Earning: Upwork aur Fiverr par gigs rank karwana aur proposal likhna sikhaya jata hai.",
        'keywords' => 'fees, timings, certificate, freelance earning, roman urdu conversion, dakhla details',
        'possible_careers' => 'PFTP Admissions Desk; Career Mentorship',
        'status' => 'active'
    ],
    [
        'id' => 206,
        'intent_id' => 'admission_roadmap_roman_ur',
        'category' => 'Admission Process',
        'language' => 'roman_ur',
        'question' => 'Course completion ka mukammal roadmap kya hai aur dakhla kaise milega?',
        'question_variations' => '12 hafton ka roadmap roman urdu|Course kaise complete hoga roadmap|Dakhla aur roadmap details|Roman urdu roadmap',
        'answer' => "Yeh raha aap ka mukammal 12 Hafton (3 Maah) ka Study Roadmap:\n\n1. Hafta 1 se 4: HTML5, CSS3, Flexbox, Responsive Design aur JavaScript UI.\n2. Hafta 5 se 8: PHP backend programming, MySQL database management aur secure user logins.\n3. Hafta 9 aur 10: Live Commercial Project banakar portfolio mein add karna.\n4. Hafta 11 aur 12: Fiverr aur Upwork par profile launch, bidding aur PFTP Certificate ka husool.\n\nDakhla Finalize Karne Ka Tareeqa: Abhi https://pftpedu.org/registration par form fill karein aur JazzCash ya Easypaisa se fee ada karein!",
        'keywords' => 'roadmap, course completion roadmap, mukammal roadmap, dakhla roadmap, 12 hafte roadmap, roman urdu roadmap',
        'possible_careers' => 'Web Developer; Freelance Specialist; Certified PFTP Graduate',
        'status' => 'active'
    ],
    [
        'id' => 207,
        'intent_id' => 'study_course_suggestion_ur',
        'category' => 'Full Stack Web Development',
        'language' => 'ur',
        'question' => 'میرا تعلیمی پس منظر انٹرمیڈیٹ ہے، مجھے اپنے کیریئر کے لیے کون سا کورس منتخب کرنا چاہیے؟',
        'question_variations' => 'انٹرمیڈیٹ کے بعد کون سا کورس کروں|آئی سی ایس کے بعد بہترین کورس|تعلیمی تفصیلات کے مطابق کورس کی تجویز|اردو کورس تجویز',
        'answer' => "آپ کے انٹرمیڈیٹ (ICS / پری انجینئرنگ) کے پس منظر کے مطابق فل اسٹیک ویب ڈویلپمنٹ اور ماڈرن سافٹ ویئر انجینئرنگ آپ کے لیے سب سے بہترین فیلڈ ہے۔ اس میں آپ ویب سائٹس اور سوفٹ ویئر بنانا سیکھتے ہیں جس کی بین الاقوامی مارکیٹ اور اپ ورک (Upwork) پر زبردست مانگ ہے۔",
        'keywords' => 'تعلیمی پس منظر, انٹرمیڈیٹ, آئی سی ایس, کورس تجویز, کون سا کورس, اردو تجویز',
        'possible_careers' => 'ویب ڈویلپر؛ سافٹ ویئر انجینئر؛ فری لانسر',
        'status' => 'active'
    ],
    [
        'id' => 208,
        'intent_id' => 'course_conversion_ur',
        'category' => 'Admission Process',
        'language' => 'ur',
        'question' => 'کورس کی فیس، کلاسوں کے اوقات اور سرٹیفکیٹ کی کیا تفصیلات ہیں؟',
        'question_variations' => 'فیس کتنی ہے اور کلاس کب ہوگی|کیا سند ملے گی|فری لانسنگ رہنمائی کی تفصیلات',
        'answer' => "پی ایف ٹی پی آپ کو مکمل پریکٹیکل سہولیات فراہم کرتا ہے:\n- انتہائی مناسب فیس: حکومتی ویژن کے تحت صرف برائے نام داخلہ پروسیسنگ فیس۔\n- لائیو اور ریکارڈ شدہ کلاسز: شام کے اوقات میں لائیو کلاسز اور ایل ایم ایس پورٹل پر 24 گھنٹے ریکارڈنگز۔\n- مستند سرٹیفکیٹ: کورس اور پروجیکٹ کی تکمیل پر کیو آر کوڈ سے تصدیق شدہ آفیشل سرٹیفکیٹ۔\n- فری لانسنگ رہنمائی: اپ ورک اور فائیور پر پہلی کمائی حاصل کرنے کے لیے سینیئر مینٹورز کی خصوصی کوچنگ۔",
        'keywords' => 'فیس, اوقات, سند, سرٹیفکیٹ, فری لانسنگ رہنمائی, داخلہ تفصیلات',
        'possible_careers' => 'پی ایف ٹی پی ایڈمیشنز؛ اسٹوڈنٹ کاؤنسلنگ',
        'status' => 'active'
    ],
    [
        'id' => 209,
        'intent_id' => 'admission_roadmap_ur',
        'category' => 'Admission Process',
        'language' => 'ur',
        'question' => 'کورس کی تکمیل کا مکمل 12 ہفتوں کا روڈ میپ کیا ہے اور داخلہ کیسے ہوگا؟',
        'question_variations' => 'کورس مکمل ہونے کا روڈ میپ|مکمل 12 ہفتوں کا شیڈول|داخلے کا طریقہ اور روڈ میپ|اردو داخلہ روڈ میپ',
        'answer' => "کورس کی مرحلہ وار تکمیل کا حتمی 12 ہفتوں کا روڈ میپ درج ذیل ہے:\n\n1. پہلا مرحلہ (ہفتہ 1 تا 4): ایچ ٹی ایم ایل (HTML5)، سی ایس ایس (CSS3) اور جاوا اسکرپٹ فرنٹ اینڈ لے آؤٹس۔\n2. دوسرا مرحلہ (ہفتہ 5 تا 8): پی ایچ پی (PHP) اور مائی ایس کیو ایل (MySQL) بیک اینڈ سسٹمز۔\n3. تیسرا مرحلہ (ہفتہ 9 تا 10): لائیو کمرشل پورٹ فولیو پروجیکٹ کی تیاری۔\n4. چوتھا مرحلہ (ہفتہ 11 تا 12): اپ ورک اور فائیور پر پروفائل، پرپوزل رائٹنگ اور تصدیق شدہ سند کا اجراء۔\n\nداخلہ حتمی کرنے کا طریقہ: فوری طور پر https://pftpedu.org/registration پر جا کر فارم پر کریں اور جاز کیش یا ایزی پیسہ سے فیس ادا کریں!",
        'keywords' => 'روڈ میپ, کورس تکمیل روڈ میپ, 12 ہفتے, داخلہ طریقہ کار, اردو روڈ میپ',
        'possible_careers' => 'فل اسٹیک ویب ڈویلپر؛ تصدیق شدہ گریجویٹ؛ پی ایف ٹی پی ایڈمیشنز',
        'status' => 'active'
    ]
];

// Merge into KB records
$existingIds = array_column($data['knowledge_base'], 'id');
foreach ($newKbRecords as $nkb) {
    $idx = array_search($nkb['id'], $existingIds, true);
    if ($idx !== false) {
        $data['knowledge_base'][$idx] = $nkb;
    } else {
        $data['knowledge_base'][] = $nkb;
    }
}

file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "SUCCESS: Mushahid Majeed trilingual admission conversation & 9 new roadmap KB records saved.\n";
echo "Total KB Records: " . count($data['knowledge_base']) . "\n";
echo "Visitor 1: " . $data['visitors'][0]['full_name'] . " (" . $data['visitors'][0]['email'] . ")\n";
echo "Conv 1 Messages: " . count($data['conversations'][0]['messages']) . "\n";
