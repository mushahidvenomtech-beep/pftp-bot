<?php
/**
 * AI Intent Detector & Entity Extractor
 *
 * Identifies primary/secondary intents, institutional entities, topics,
 * keywords, and detects prompt-injection or manipulation attempts.
 */

declare(strict_types=1);

class IntentDetector
{
    // Canonical 16 PFTP Courses & Aliases
    public const COURSES = [
        'Digital Marketing' => [
            'canonical' => 'Digital Marketing',
            'aliases'   => ['digital marketing', 'digital media marketing', 'social media marketing', 'smm', 'meta ads', 'facebook ads', 'google ads', 'lead generation']
        ],
        'Freelancing' => [
            'canonical' => 'Freelancing',
            'aliases'   => ['freelancing', 'fiverr', 'upwork', 'freelance', 'freelancer', 'online earning']
        ],
        'Graphic Design' => [
            'canonical' => 'Graphic Design',
            'aliases'   => ['graphic design', 'graphic designing', 'photoshop', 'illustrator', 'logo design', 'graphics', 'ui ux design']
        ],
        'Video Editing' => [
            'canonical' => 'Video Editing',
            'aliases'   => ['video editing', 'video editor', 'premiere pro', 'capcut', 'reels editing', 'shorts editing', 'after effects']
        ],
        'YouTube Automation' => [
            'canonical' => 'YouTube Automation',
            'aliases'   => ['youtube automation', 'faceless youtube', 'faceless channel', 'youtube channel', 'yt automation']
        ],
        'Content Writing' => [
            'canonical' => 'Content Writing',
            'aliases'   => ['content writing', 'copywriting', 'blog writing', 'article writing', 'creative writing']
        ],
        'Search Engine Optimization (SEO)' => [
            'canonical' => 'Search Engine Optimization (SEO)',
            'aliases'   => ['seo', 'search engine optimization', 'google ranking', 'on-page seo', 'backlinks', 'keyword research']
        ],
        'Full Stack Web Development' => [
            'canonical' => 'Full Stack Web Development',
            'aliases'   => ['full stack web development', 'full stack', 'web development', 'web dev', 'web designing', 'frontend', 'backend', 'html css js', 'mern']
        ],
        'Artificial Intelligence (AI)' => [
            'canonical' => 'Artificial Intelligence (AI)',
            'aliases'   => ['artificial intelligence', 'ai', 'generative ai', 'prompt engineering', 'chatgpt', 'machine learning', 'ai tools']
        ],
        'Cyber Security' => [
            'canonical' => 'Cyber Security',
            'aliases'   => ['cyber security', 'ethical hacking', 'network security', 'penetration testing', 'cybersecurity']
        ],
        'Data Science' => [
            'canonical' => 'Data Science',
            'aliases'   => ['data science', 'data analysis', 'data analytics', 'power bi', 'tableau', 'data scientist']
        ],
        'Python Programming' => [
            'canonical' => 'Python Programming',
            'aliases'   => ['python programming', 'python coding', 'python language', 'learn python', 'python']
        ],
        'E-Commerce' => [
            'canonical' => 'E-Commerce',
            'aliases'   => ['e-commerce', 'ecommerce', 'shopify', 'daraz', 'online store', 'dropshipping', 'amazon fba']
        ],
        'QuickBooks' => [
            'canonical' => 'QuickBooks',
            'aliases'   => ['quickbooks', 'bookkeeping', 'accounting software', 'accounting', 'quick books']
        ],
        'English Language' => [
            'canonical' => 'English Language',
            'aliases'   => ['english language', 'spoken english', 'english speaking', 'communication skills', 'spoken english course']
        ],
        'IELTS Preparation' => [
            'canonical' => 'IELTS Preparation',
            'aliases'   => ['ielts', 'ielts preparation', 'study abroad english', 'ielts course', 'ielts training']
        ]
    ];

    /**
     * Check if query is an adversarial prompt injection or manipulation attempt
     */
    public function detectManipulation(string $text): bool
    {
        $lower = mb_strtolower($text, 'UTF-8');

        $adversarialPatterns = [
            '/\bignore\s+(all\s+|any\s+|the\s+)?(previous|prior|above|former)\s+(instructions|prompts|rules|commands|constraints)\b/i',
            '/\b(system\s+prompt|reveal\s+your\s+prompt|what\s+are\s+your\s+instructions)\b/i',
            '/\byou\s+are\s+now\s+(a|an|in)\b/i',
            '/\b(jailbreak|dan\s+mode|developer\s+mode)\b/i',
            '/\b(disregard\s+all\s+rules|bypass\s+safety)\b/i',
            '/\b(drop\s+table|insert\s+into|union\s+select|delete\s+from|update\s+\w+\s+set)\b/i',
            '/<\s*script\b[^>]*>/i',
            '/\bpretend\s+to\s+be\s+(an\s+unrestricted|someone\s+else)\b/i',
            '/\broleplay\s+as\s+(an\s+evil|an\s+unfiltered)\b/i'
        ];

        foreach ($adversarialPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect user intent from text and context
     */
    public function detectIntent(string $text, string $language, array $context = []): array
    {
        $clean = trim($text);
        $lower = mb_strtolower($clean, 'UTF-8');

        // 1. Adversarial / Prompt Injection Check
        if ($this->detectManipulation($clean)) {
            return [
                'primary'    => 'manipulation_attempt',
                'secondary'  => null,
                'confidence' => 0.99
            ];
        }

        // 2. Greeting Intent
        if ($this->isGreeting($lower)) {
            $gType = $this->classifyGreetingType($clean);
            return [
                'primary'       => 'greeting',
                'secondary'     => $gType,
                'greeting_type' => $gType,
                'confidence'    => 0.98
            ];
        }

        // 3. Unrelated Query Filter (weather, sports, politics, recipes, non-institutional trivia)
        if ($this->isUnrelatedQuery($lower)) {
            return [
                'primary'    => 'unrelated_query',
                'secondary'  => null,
                'confidence' => 0.95
            ];
        }

        // 4. Human Handoff / Contact Intent
        if ($this->isHumanHandoff($lower)) {
            return [
                'primary'    => 'human_handoff',
                'secondary'  => null,
                'confidence' => 0.92
            ];
        }

        // 5. Educational Status / Discovery Response
        if ($this->isEducationStatus($lower, $clean)) {
            return [
                'primary'    => 'education_status',
                'secondary'  => 'course_recommendation',
                'confidence' => 0.90
            ];
        }

        // 6. Company Overview / About PFTP
        if ($this->isCompanyOverview($lower, $clean)) {
            return [
                'primary'    => 'company_overview',
                'secondary'  => 'institution_info',
                'confidence' => 0.92
            ];
        }

        // 7. Job / Earning Guarantee Policy
        if ($this->isJobGuaranteeQuery($lower)) {
            return [
                'primary'    => 'policy_job_guarantee',
                'secondary'  => 'institution_policy',
                'confidence' => 0.93
            ];
        }

        // 8. Payment Instructions
        if ($this->isPaymentInstructions($lower)) {
            return [
                'primary'    => 'payment_instructions',
                'secondary'  => 'admission_process',
                'confidence' => 0.90
            ];
        }

        // 9. Course Execution, Training & Completion Process
        if ($this->isCourseExecutionAndCompletionProcess($lower, $clean)) {
            return [
                'primary'    => 'course_execution_and_completion_process',
                'secondary'  => 'course_roadmap',
                'confidence' => 0.95
            ];
        }

        // 10. Admission Process / Registration Link
        if ($this->isAdmissionProcess($lower)) {
            return [
                'primary'    => 'admission_process',
                'secondary'  => 'registration_link',
                'confidence' => 0.90
            ];
        }

        // 10. Fee Inquiry
        if ($this->isFeeInquiry($lower)) {
            return [
                'primary'    => 'fee_inquiry',
                'secondary'  => !empty($context['last_mentioned_course']) ? 'course_details' : 'courses_overview',
                'confidence' => 0.88
            ];
        }

        // 11. Course Duration Inquiry
        if ($this->isCourseDuration($lower)) {
            return [
                'primary'    => 'course_duration',
                'secondary'  => 'course_details',
                'confidence' => 0.89
            ];
        }

        // 12. Certificate Inquiry
        if ($this->isCertificateInquiry($lower)) {
            return [
                'primary'    => 'certificate_inquiry',
                'secondary'  => 'course_details',
                'confidence' => 0.88
            ];
        }

        // 13. Physical Classes / On-Campus Training Inquiry
        if ($this->isPhysicalClassesQuery($lower, $clean)) {
            return [
                'primary'    => 'physical_classes_inquiry',
                'secondary'  => 'training_mode',
                'confidence' => 0.94
            ];
        }

        // 14. Learning Freelancing & Online Earning Guidance
        if ($this->isFreelancingOnlineEarningGuidance($lower, $clean)) {
            return [
                'primary'    => 'freelancing_and_online_earning_guidance',
                'secondary'  => 'freelancing_guidance',
                'confidence' => 0.95
            ];
        }

        // 15. Alternative Course Recommendations
        if ($this->isAlternativeCourseRecommendations($lower, $clean)) {
            return [
                'primary'    => 'alternative_course_recommendations',
                'secondary'  => 'course_recommendation',
                'confidence' => 0.95
            ];
        }

        // 16. Course Catalog / General Course Inquiry
        if ($this->isCourseCatalogInquiry($lower)) {
            return [
                'primary'    => 'course_inquiry',
                'secondary'  => 'course_catalog',
                'confidence' => 0.90
            ];
        }

        // 15. Specific Course Details / Syllabus / Learning Content
        $courseMention = $this->extractCourseMention($clean);
        if ($courseMention !== null || !empty($context['last_mentioned_course'])) {
            if ($this->isCourseDetailsInquiry($lower)) {
                return [
                    'primary'    => 'course_details',
                    'secondary'  => 'course_inquiry',
                    'confidence' => 0.87
                ];
            }
        }

        // Default: General inquiry
        return [
            'primary'    => 'general_inquiry',
            'secondary'  => null,
            'confidence' => 0.60
        ];
    }

    /**
     * Extract institutional and conversational entities from message
     */
    public function extractEntities(string $text): array
    {
        $entities = [];

        // Course entity
        $course = $this->extractCourseMention($text);
        if ($course !== null) {
            $entities['course'] = $course;
        }

        // Education status entity
        $lower = mb_strtolower($text, 'UTF-8');
        if (preg_match('/\b(currently\s+studying|study\s+kar|parh\s+raha|undergraduate|student|enrolled)\b/iu', $lower) || mb_strpos($text, 'تعلیم حاصل کر رہے') !== false) {
            $entities['education_status'] = 'currently_studying';
        } elseif (preg_match('/\b(completed|graduated|degree\s+ho\s+gayi|pass\s+kar\s+li|mukammal|khatam)\b/iu', $lower) || mb_strpos($text, 'تعلیم مکمل') !== false) {
            $entities['education_status'] = 'completed';
        } elseif (preg_match('/\b(discontinued|dropped\s+out|chor\s+di|parhai\s+chor|chhor)\b/iu', $lower)) {
            $entities['education_status'] = 'discontinued';
        }

        // Payment / banking entity
        if (preg_match('/\b(jazzcash|jazz\s+cash|easypaisa|easy\s+paisa|bank|challan|online\s+transfer|hbl|mcb)\b/iu', $lower)) {
            $entities['payment_method'] = true;
        }

        return $entities;
    }

    /**
     * Extract canonical course name from text
     */
    public function extractCourseMention(string $text): ?string
    {
        $clean = mb_strtolower($text, 'UTF-8');

        // Check longest alias first to prevent shorter partial overlaps
        $candidates = [];
        foreach (self::COURSES as $courseKey => $data) {
            foreach ($data['aliases'] as $alias) {
                $candidates[] = [
                    'canonical' => $data['canonical'],
                    'alias'     => $alias,
                    'len'       => mb_strlen($alias)
                ];
            }
        }

        // Sort descending by length
        usort($candidates, fn($a, $b) => $b['len'] <=> $a['len']);

        foreach ($candidates as $cand) {
            $alias = $cand['alias'];
            // Safe Latin word boundary
            $pattern = '/(?<![a-zA-Z0-9])' . preg_quote($alias, '/') . '(?![a-zA-Z0-9])/iu';
            if (preg_match($pattern, $clean)) {
                return $cand['canonical'];
            }
        }

        // Urdu script course names
        $urduMap = [
            'ڈیجیٹل مارکیٹنگ' => 'Digital Marketing',
            'ویب ڈویلپمنٹ'   => 'Full Stack Web Development',
            'گرافک ڈیزائننگ'  => 'Graphic Design',
            'گرافک ڈیزائن'   => 'Graphic Design',
            'ویڈیو ایڈیٹنگ'   => 'Video Editing',
            'فری لانسنگ'     => 'Freelancing',
            'آرٹیفیشل انٹیلیجنس' => 'Artificial Intelligence (AI)',
            'سائبر سیکیورٹی' => 'Cyber Security',
            'پائتھن'        => 'Python Programming',
            'ایس ای او'      => 'Search Engine Optimization (SEO)',
            'ڈیٹا سائنس'     => 'Data Science',
            'ای کامرس'      => 'E-Commerce',
            'انگلش لینگویج'  => 'English Language',
            'آئلٹس'         => 'IELTS Preparation'
        ];

        foreach ($urduMap as $urduPhrase => $canonical) {
            if (mb_strpos($clean, $urduPhrase) !== false) {
                return $canonical;
            }
        }

        // Fuzzy typo matching for course keywords
        $tokens = preg_split('/\s+/u', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean));
        $anchors = [
            'python'       => 'Python Programming',
            'freelancing'  => 'Freelancing',
            'photoshop'    => 'Graphic Design',
            'marketing'    => 'Digital Marketing',
            'editing'      => 'Video Editing',
            'security'     => 'Cyber Security',
            'quickbooks'   => 'QuickBooks',
            'ecommerce'    => 'E-Commerce',
            'shopify'      => 'E-Commerce',
            'automation'   => 'YouTube Automation',
            'intelligence' => 'Artificial Intelligence (AI)',
            'development'  => 'Full Stack Web Development',
            'analytics'    => 'Data Science'
        ];

        foreach ($tokens as $token) {
            if (mb_strlen($token) >= 5) {
                foreach ($anchors as $anchor => $canonical) {
                    $dist = levenshtein($token, $anchor);
                    $maxLen = max(mb_strlen($token), mb_strlen($anchor));
                    $sim = 1.0 - ($dist / $maxLen);
                    if ($dist <= 2 && $sim >= 0.72) {
                        return $canonical;
                    }
                }
            }
        }

        return null;
    }

    // --- Private Helper Classifiers ---

    public function isGreeting(string $lower): bool
    {
        return (bool) preg_match('/^(hi|hy|hii|hiii|hello|helo|hey|heyy|hiya|hlo|salam|slam|assalam|asalam|assalam\s*o\s*alaikum|asalam\s*o\s*alaikum|asalam\s*o\s*alikum|assalam\s*u\s*alaikum|aslam\s*o\s*alaikum|assalamu\s*alaikum|aoa|good\s+morning|good\s+afternoon|good\s+evening|adab|kese\s+ho|kia\s+hal\s+hai|kya\s+hal\s+hai)[\s!\.\?]*$/iu', $lower)
            || (bool) preg_match('/^(السلام\s*علیکم|سلام|کیسے\s*ہیں)[\s!\.\?]*$/u', $lower);
    }

    public function classifyGreetingType(string $text): string
    {
        $lower = mb_strtolower(trim($text), 'UTF-8');

        // Islamic Greeting
        if (preg_match('/\b(assalam|asalam|aslam|salam|slam|aoa)\b/iu', $lower) || mb_strpos($lower, 'السلام علیکم') !== false || mb_strpos($lower, 'سلام') !== false) {
            return 'islamic';
        }

        // Time-based
        if (preg_match('/\b(good\s+morning|good\s+afternoon|good\s+evening)\b/iu', $lower)) {
            return 'time_based';
        }

        // Casual Hi / Hy
        if (preg_match('/^(hi|hy|hii|hiya)\b/iu', $lower)) {
            return 'hi';
        }

        // Hello
        if (preg_match('/^(hello|helo|hlo)\b/iu', $lower)) {
            return 'hello';
        }

        if (preg_match('/^(hey|heyy)\b/iu', $lower)) {
            return 'hey';
        }

        return 'casual';
    }

    private function isHumanHandoff(string $lower): bool
    {
        return (bool) preg_match('/\b(human|representative|counselor|counsellor|agent|real\s+person|contact\s+number|phone\s+number|call|whatsapp\s+number|baat\s+karni\s+hai|insan\s+se)\b/iu', $lower);
    }

    private function isEducationStatus(string $lower, string $raw): bool
    {
        $patterns = [
            '/\b(currently\s+studying|study\s+kar\s+raha|study\s+kar\s+rahe|studying\b)/i',
            '/\b(completed\s+my\s+education|education\s+complete|degree\s+complete|graduated)/i',
            '/\b(discontinued|left\s+studies|parhai\s+chor\s+di|parhai\s+chhor)/i',
            '/\b(not\s+currently\s+studying|abhi\s+parhai\s+nahi\s+kar\s+raha|not\s+studying)/i',
            '/\b(highest\s+qualification|which\s+class\s+degree|degree\s+or\s+semester)\b/i'
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $lower)) {
                return true;
            }
        }

        if (mb_strpos($raw, 'تعلیم حاصل کر رہے') !== false || mb_strpos($raw, 'تعلیم مکمل') !== false) {
            return true;
        }

        return false;
    }

    private function isPhysicalClassesQuery(string $lower, string $raw): bool
    {
        return (bool) preg_match('/\b(physical\s+classes|physical\s+courses?|on[\s\-]campus|campus\s+par|campus\s+classes|in[\s\-]person|physically|physical\s+training)\b/iu', $lower)
            || mb_strpos($raw, 'فزیکل') !== false
            || mb_strpos($raw, 'کیمپس') !== false;
    }

    private function isFreelancingOnlineEarningGuidance(string $lower, string $raw): bool
    {
        $patterns = [
            '/\b(learn\s+freelancing|learn\s+online\s+earning|how\s+(can\s+i|to)\s+learn\s+online(\s+earning)?|start\s+freelancing|start\s+online\s+earning|how\s+to\s+earn\s+online|how\s+can\s+i\s+earn\s+online|how\s+can\s+i\s+learn\s+online)\b/i',
            '/\b(freelancing\s+sikhni\s+hy|freelancing\s+seekhni\s+hai|online\s+earning\s+kaisay|online\s+earning\s+kaise|online\s+earning\s+seekh|freelancing\s+start\s+karnay|ghar\s+bethay\s+online\s+kaam|ghar\s+baithe\s+online\s+kaam|ghar\s+bethay\s+online\s+earning)\b/i',
            '/\b(online\s+kaam\s+kaisay|online\s+kam\s+kaise|online\s+earning\s+ka\s+tareeqa)\b/i'
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $lower)) {
                return true;
            }
        }

        if (mb_strpos($raw, 'فری لانسنگ سیکھ') !== false
            || mb_strpos($raw, 'آن لائن کمائی') !== false
            || mb_strpos($raw, 'گھر بیٹھے آن لائن') !== false
            || mb_strpos($raw, 'آن لائن کام کیسے') !== false
            || mb_strpos($raw, 'فری لانسنگ شروع کرنے کا طریقہ') !== false) {
            return true;
        }

        return false;
    }

    private function isCourseExecutionAndCompletionProcess(string $lower, string $raw): bool
    {
        $patterns = [
            '/\b(course\s+execution|how\s+is\s+the\s+course\s+executed|how\s+do\s+you\s+train|course\s+roadmap|course\s+completion\s+process|what\s+happens\s+after\s+admission|after\s+admission\s+what|how\s+are\s+classes\s+conducted|how\s+do\s+students\s+complete(\s+their)?\s+courses?)\b/i',
            '/\b(course\s+ka\s+complete\s+process|training\s+kis\s+tarah\s+di\s+jati\s+hai|admission\s+k(e)?\s+baad\s+kya\s+process|admission\s+k(e)?\s+baad\s+kya\s+hota\s+hai|classes\s+kaisay\s+hoti\s+hain|classes\s+kaise\s+conduct|course\s+completion\s+process\s+kya|training\s+ka\s+process|complete\s+course\s+journey)\b/i'
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $lower)) {
                return true;
            }
        }

        if (mb_strpos($raw, 'کورس کا مکمل طریقہ کار') !== false
            || mb_strpos($raw, 'تربیت کس طرح دی جاتی ہے') !== false
            || mb_strpos($raw, 'داخلے کے بعد کیا پروسیس') !== false
            || mb_strpos($raw, 'کلاسز کیسے کروائی جاتی ہیں') !== false
            || mb_strpos($raw, 'کورس کا روڈ میپ') !== false
            || mb_strpos($raw, 'کورس مکمل کرنے کا طریقہ') !== false
            || mb_strpos($raw, 'طلباء کی تربیت') !== false) {
            return true;
        }

        return false;
    }

    private function isAlternativeCourseRecommendations(string $lower, string $raw): bool
    {
        $patterns = [
            '/\b(no\s*,?\s*i\s+need\s+to\s+select\s+another\s+course|what\s+else\s+can\s+i\s+choose|anything\s+else\??|any\s+other\s+offer\??|any\s+other\s+course\??|do\s+you\s+have\s+another\s+suggestion|can\s+you\s+suggest\s+a\s+different\s+course|what\s+other\s+courses\s+are\s+available|suggest\s+another\s+course|alternative\s+courses?|other\s+options?\s+available|different\s+course|do\s+you\s+have\s+another\s+option)\b/i',
            '/\b(not\s+interested\s+in\s+this\s+(course|field|subject)|don\'?t\s+like\s+this\s+course|no\s+interest\s+in\s+this\s+field|doesn\'?t\s+suit\s+me|don\'?t\s+understand\s+this\s+course|what\s+this\s+course\s+is\s+about|explain\s+it\s+in\s+simple\s+words|not\s+right\s+for\s+me|choose\s+something\s+else|according\s+to\s+my\s+interests?)\b/i',
            '/\b(any\s+other\s+course\s+for\s+a(n)?\s+(matric|fsc|computer\s+science|arts|science|ics|bba|commerce)\s+student)\b/i',
            '/\b(mujhe\s+is\s+course\s+mein\s+interest\s+nahi|yeh\s+course\s+pasand\s+nahi|koi\s+interest\s+nahi\s+hai|dilchaspi\s+nahi|suitable\s+nahi\s+hai|samajh\s+nahi\s+aa\s+raha|kya\s+sikhaya\s+jata\s+hai|asaan\s+alfaaz\s+mein\s+samjha\s+sakte\s+hain|mere\s+liye\s+theek\s+nahi|koi\s+aur\s+course\s+choose|meri\s+interest\s+ke\s+mutabiq|koi\s+aur\s+option\s+hai)\b/i',
            '/\b(mujhe\s+koi\s+aur\s+course|is\s+k(e)?\s+ilawa\s+koi\s+aur|koi\s+aur\s+suggestion|kya\s+koi\s+doosra\s+course|mere\s+liye\s+koi\s+aur\s+course|koi\s+aur\s+option|kuch\s+aur\s+batao|doosra\s+course|doosri\s+suggestion)\b/i'
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $lower)) {
                return true;
            }
        }

        if (mb_strpos($raw, 'کوئی اور کورس') !== false
            || mb_strpos($raw, 'اس کے علاوہ کوئی اور') !== false
            || mb_strpos($raw, 'کوئی اور تجویز') !== false
            || mb_strpos($raw, 'دوسرا کورس') !== false
            || mb_strpos($raw, 'کوئی اور آپشن') !== false
            || mb_strpos($raw, 'متبادل کورس') !== false
            || mb_strpos($raw, 'دلچسپی نہیں ہے') !== false
            || mb_strpos($raw, 'کورس پسند نہیں ہے') !== false
            || mb_strpos($raw, 'شعبے میں کوئی دلچسپی نہیں') !== false
            || mb_strpos($raw, 'مضمون میں دلچسپی نہیں') !== false
            || mb_strpos($raw, 'موزوں نہیں ہے') !== false
            || mb_strpos($raw, 'سمجھ نہیں آ رہا') !== false
            || mb_strpos($raw, 'آسان الفاظ میں سمجھا سکتے ہیں') !== false
            || mb_strpos($raw, 'مناسب نہیں ہے') !== false
            || mb_strpos($raw, 'میری دلچسپی کے مطابق') !== false) {
            return true;
        }

        return false;
    }

    private function isCompanyOverview(string $lower, string $raw): bool
    {
        $patterns = [
            '/\b(about\s+your\s+company|tell\s+me\s+about\s+(your\s+)?company|what\s+is\s+pftp|about\s+pftp)\b/i',
            '/\b(apni\s+company\s+k(e)?\s+ba(a)?re\s+m(e)?in\s+bataye(i)?n|company\s+k(e)?\s+ba(a)?ry\s+m(e)?in)\b/i',
            '/\b(pftp\s+kya\s+hai|pftp\s+stands\s+for)\b/i'
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $lower)) {
                return true;
            }
        }

        if (mb_strpos($raw, 'اپنی کمپنی کے بارے میں بتائیں') !== false || mb_strpos($raw, 'آپ کی کمپنی کے بارے میں') !== false) {
            return true;
        }

        return false;
    }

    private function isJobGuaranteeQuery(string $lower): bool
    {
        return (bool) preg_match('/\b(job\s+guarantee|earning\s+guarantee|100%\s+job|placement\s+guarantee|naukri\s+milegi|job\s+milegi|paisa\s+kamane\s+ki\s+guarantee)\b/iu', $lower);
    }

    private function isPaymentInstructions(string $lower): bool
    {
        return (bool) preg_match('/\b(pay\s+the\s+(admission\s+|processing\s+)?fee|how\s+to\s+pay|fee\s+kaise\s+pay|kaise\s+jama|challan|bank\s+account|easypaisa|jazzcash|payment\s+process|paid\s+fee)\b/iu', $lower);
    }

    private function isAdmissionProcess(string $lower): bool
    {
        return (bool) preg_match('/\b(where\s+should\s+i\s+go\s+to\s+apply|how\s+to\s+apply|admission\s+process|registration\s+form|apply\s+for\s+admission|apply\s+kaise\s+karein|dakhla|kahan\s+jana\s+hoga|apply\s+link|pftpedu\.org\/registration)\b/iu', $lower);
    }

    private function isFeeInquiry(string $lower): bool
    {
        return (bool) preg_match('/\b(fees?|cost|charges?|price|pricing|how\s+much|kitni\s+fees?|kitne\s+paise|discount)\b/iu', $lower);
    }

    private function isCourseDuration(string $lower): bool
    {
        return (bool) preg_match('/\b(duration|how\s+long|how\s+many\s+months|kitne\s+mahine|kitna\s+arse|schedule|timings?|classes\s+per\s+week)\b/iu', $lower);
    }

    private function isCertificateInquiry(string $lower): bool
    {
        return (bool) preg_match('/\b(certificate|certification|sanad|verified\s+certificate|certificate\s+milega|accreditation)\b/iu', $lower);
    }

    private function isCourseCatalogInquiry(string $lower): bool
    {
        return (bool) preg_match('/\b(what\s+courses|which\s+courses|courses\s+do\s+you\s+offer|courses\s+list|courses\s+k(e)?\s+ba(a)?ry\s+m(e)?y\s+janna|kon\s+s(y|e)\s+courses|list\s+of\s+courses|courses\s+available)\b/iu', $lower);
    }

    private function isCourseDetailsInquiry(string $lower): bool
    {
        return (bool) preg_match('/\b(what\s+do\s+you\s+teach|what\s+do\s+we\s+learn|what\s+is\s+taught|syllabus|outline|contents?|kia\s+sikhaty\s+hy|kya\s+sikhaya\s+jata\s+hai|sikhte\s+hain|career|careers?|scope)\b/iu', $lower);
    }

    public function isUnrelatedQuery(string $lower): bool
    {
        return (bool) preg_match('/\b(weather|temperature|forecast|rain|cloudy|sunny|cricket|score|match|ipl|psl|football|fifa|recipe|cook|cooking|biryani|burger|pizza|capital\s+of|president\s+of|prime\s+minister|solve\s+this|integral|derivative|algebra|math\s+problem|poem|poetry|shayari|joke|jokes|movie|film|actor|actress|song|music|bitcoin|crypto|stock\s+market|horoscope|astrology)\b/iu', $lower);
    }
}
