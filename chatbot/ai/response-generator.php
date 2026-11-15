<?php
/**
 * AI Decision-Making Engine & Dynamic Response Generator
 *
 * Evaluates internal query analysis and grounded knowledge retrieval,
 * decides the response type (direct_answer, detailed_explanation, recommendation,
 * clarification, follow_up, unavailable_info, human_assistance, manipulation_defense),
 * and generates conversational, grounded responses without exposing internal reasoning.
 */

declare(strict_types=1);

require_once __DIR__ . '/context-manager.php';

class ResponseGenerator
{
    private ContextManager $contextManager;

    public function __construct(ContextManager $contextManager)
    {
        $this->contextManager = $contextManager;
    }

    /**
     * Generate final conversational response based on analysis and retrieved knowledge
     *
     * @param array $analysis Structured output from QueryAnalyzer
     * @param array $retrieval Result from KnowledgeRetriever
     * @param int $conversationId
     * @param array $context Current conversation context
     * @return array Response payload for visitor message
     */
    public function generate(array $analysis, array $retrieval, int $conversationId, array $context, ?string $visitorName = null): array
    {
        $lang = $analysis['language'] ?? 'en';

        // 1. Defense against adversarial manipulation / prompt injection
        if (!empty($analysis['is_manipulation'])) {
            return $this->buildDefenseResponse($lang);
        }

        // 2. Ambiguity / Clarification Needed
        if (!empty($analysis['needs_clarification'])) {
            return $this->buildClarificationResponse($analysis, $lang, $conversationId, $context);
        }

        // 3. Human Handoff Request
        if ($analysis['primary_intent'] === 'human_handoff') {
            return $this->buildHumanHandoffResponse($lang);
        }

        // 4. Greeting Intent
        if ($analysis['primary_intent'] === 'greeting') {
            return $this->buildGreetingResponse($analysis, $lang, $conversationId, $visitorName);
        }

        // 5. Grounded Knowledge Match Available
        if (!empty($retrieval['found']) && !empty($retrieval['answer'])) {
            return $this->buildGroundedAnswerResponse($analysis, $retrieval, $conversationId, $context);
        }

        // 6. Unavailable Information / Grounded Fallback
        return $this->buildUnavailableResponse($lang);
    }

    /**
     * Build grounded answer response and update context memory
     */
    private function buildGroundedAnswerResponse(array $analysis, array $retrieval, int $conversationId, array $context): array
    {
        $answerText = $retrieval['answer'];
        $possibleCareers = $retrieval['possible_careers'] ?? null;
        $category = $retrieval['category'] ?? 'General';
        $confidence = $retrieval['confidence'] ?? 0.85;

        // Determine specific response type
        $responseType = 'direct_answer';
        if ($category === 'Alternative Course Recommendations' || $analysis['primary_intent'] === 'alternative_course_recommendations') {
            $responseType = 'recommendation';
            $answerText = $this->adaptAlternativeCoursesResponse($answerText, $analysis, $context);
        } elseif (!empty($possibleCareers) || mb_strlen($answerText) > 280) {
            $responseType = 'detailed_explanation';
        } elseif ($analysis['primary_intent'] === 'education_status') {
            $responseType = 'follow_up';
        } elseif ($analysis['primary_intent'] === 'course_inquiry') {
            $responseType = 'recommendation';
        }

        // Update conversation context memory
        $updatedContext = $context;
        $updatedContext['current_topic'] = $category;
        $updatedContext['last_intent'] = $analysis['primary_intent'];

        // If a specific course was identified, update context
        $mentionedCourse = $analysis['entities']['course'] ?? $this->contextManager->extractCourseMention($retrieval['question'] . ' ' . $category);
        if ($mentionedCourse !== null) {
            $updatedContext['last_mentioned_course'] = $mentionedCourse;
            if (!in_array($mentionedCourse, $updatedContext['discussed_courses'] ?? [], true)) {
                $updatedContext['discussed_courses'][] = $mentionedCourse;
            }
        }

        // If education status was identified, update context
        if (!empty($analysis['entities']['education_status'])) {
            $updatedContext['user_education_status'] = $analysis['entities']['education_status'];
        }

        $this->contextManager->saveContext($conversationId, $updatedContext);

        return [
            'response_type'    => $responseType,
            'message'          => $answerText,
            'matched_kb_id'    => $retrieval['matched_kb_id'],
            'is_fallback'      => false,
            'category'         => $category,
            'possible_careers' => $possibleCareers,
            'confidence'       => $confidence
        ];
    }

    /**
     * Build clarification request when query is ambiguous or pronoun has no referent
     */
    private function buildClarificationResponse(array $analysis, string $lang, int $conversationId, array $context): array
    {
        $prompt = $analysis['clarification_prompt'];

        if (empty($prompt)) {
            if ($lang === 'ur') {
                $prompt = 'براہ کرم واضح کریں کہ آپ کس کورس کے بارے میں پوچھ رہے ہیں؟ ہم ویب ڈویلپمنٹ، گرافک ڈیزائن، ڈیجیٹل مارکیٹنگ اور اے آئی سمیت 16 کورسز کرواتے ہیں۔';
            } elseif ($lang === 'roman_ur') {
                $prompt = 'Aap kis course ke baare mein baat kar rahe hain? Hum Web Development, Graphic Design, AI, Digital Marketing aur Freelancing samait 16 courses offer karte hain.';
            } else {
                $prompt = 'Could you please clarify which course you are inquiring about? We offer 16 courses including Web Development, Digital Marketing, AI, Graphic Design, and Freelancing.';
            }
        }

        // Update context to record pending clarification
        $context['pending_clarification'] = $analysis['raw_query'];
        $this->contextManager->saveContext($conversationId, $context);

        return [
            'response_type'    => 'clarification',
            'message'          => $prompt,
            'matched_kb_id'    => null,
            'is_fallback'      => false,
            'category'         => 'Clarification',
            'possible_careers' => null,
            'confidence'       => 0.70
        ];
    }

    /**
     * Response for prompt injection / manipulation defense
     */
    private function buildDefenseResponse(string $lang): array
    {
        $message = "I am the PFTP Student & Admission Advisor. I can only assist you with verified information about PFTP training programs, admissions, syllabus, and course inquiries. How can I help you with your education today?";

        if ($lang === 'ur') {
            $message = "میں PFTP کا اسٹوڈنٹ ایڈوائزر ہوں۔ میں صرف PFTP کے کورسز، داخلوں اور تعلیمی معلومات کے متعلق آپ کی رہنمائی کر سکتا ہوں۔ آپ کس کورس کے بارے میں جاننا چاہتے ہیں؟";
        } elseif ($lang === 'roman_ur') {
            $message = "Main PFTP ka Student Advisor hoon. Main sirf PFTP ke courses, admission process aur training programs ke baare mein guide kar sakta hoon. Aap kis course ke baare mein janna chahte hain?";
        }

        return [
            'response_type'    => 'manipulation_defense',
            'message'          => $message,
            'matched_kb_id'    => null,
            'is_fallback'      => false,
            'category'         => 'Security Defense',
            'possible_careers' => null,
            'confidence'       => 1.0
        ];
    }

    /**
     * Resolve visitor name from passed parameter or conversation database record
     */
    private function resolveVisitorName(int $conversationId, ?string $passedName): string
    {
        if (!empty($passedName)) {
            return trim($passedName);
        }

        try {
            $stmt = $this->contextManager->getDb()->prepare("
                SELECT v.full_name
                FROM conversations c
                JOIN visitors v ON c.visitor_id = v.id
                WHERE c.id = :cid
                LIMIT 1
            ");
            $stmt->execute([':cid' => $conversationId]);
            $name = $stmt->fetchColumn();
            if ($name) {
                return trim((string) $name);
            }
        } catch (Throwable $e) {
            // Ignore silently
        }

        return '';
    }

    /**
     * Personalized & Contextual Greeting Response
     * Responds according to the greeting used (Assalam o Alaikum, Hi, Hy, Hello)
     * with the user's name entered earlier.
     */
    private function buildGreetingResponse(array $analysis, string $lang, int $conversationId, ?string $passedName = null): array
    {
        $visitorName = $this->resolveVisitorName($conversationId, $passedName);
        $greetingType = $analysis['greeting_type'] ?? 'casual';
        $displayName = !empty($visitorName) ? $visitorName : '';

        // Case 1: Islamic Greetings ("assalam o alaikum", "asalam o alaikum", "salam", "aoa", "السلام علیکم")
        if ($greetingType === 'islamic') {
            if ($lang === 'ur') {
                $prefix = !empty($displayName) ? "وعلیکم السلام {$displayName} صاحب! " : "وعلیکم السلام! ";
                $message = "{$prefix}PFTP میں خوش آمدید۔ میں آپ کا اسٹوڈنٹ ایڈوائزر ہوں۔ میں کورسز، فیس یا داخلے کے حوالے سے آپ کی کیا مدد کر سکتا ہوں؟";
            } elseif ($lang === 'roman_ur') {
                $prefix = !empty($displayName) ? "Walaikum Assalam {$displayName}! " : "Walaikum Assalam! ";
                $message = "{$prefix}PFTP mein khush amdeed. Main aap ka Student Advisor hoon. Aap kis course ya admission ke baare mein janna chahte hain?";
            } else {
                $prefix = !empty($displayName) ? "Walaikum Assalam {$displayName}! 👋 " : "Walaikum Assalam! 👋 ";
                $message = "{$prefix}Welcome to PFTP (Professional Freelancing Training Program). How can I assist you today with our courses or admissions?";
            }
        }
        // Case 2: Casual "Hi" / "Hy"
        elseif ($greetingType === 'hi') {
            if ($lang === 'ur') {
                $prefix = !empty($displayName) ? "ہیلو {$displayName}! " : "ہیلو! ";
                $message = "{$prefix}PFTP میں خوش آمدید۔ آپ کس کورس کے بارے میں جاننا چاہتے ہیں؟";
            } else {
                $prefix = !empty($displayName) ? "Hi {$displayName}! 👋 " : "Hi there! 👋 ";
                $message = "{$prefix}Welcome to PFTP. How can I help you today with our courses or admissions?";
            }
        }
        // Case 3: "Hello"
        elseif ($greetingType === 'hello') {
            if ($lang === 'ur') {
                $prefix = !empty($displayName) ? "ہیلو {$displayName}! " : "ہیلو! ";
                $message = "{$prefix}خوش آمدید! میں کورسز یا داخلے کے حوالے سے آپ کی کیا مدد کر سکتا ہوں؟";
            } else {
                $prefix = !empty($displayName) ? "Hello {$displayName}! 👋 " : "Hello! 👋 ";
                $message = "{$prefix}Welcome to PFTP (Professional Freelancing Training Program). How can I help you today?";
            }
        }
        // Case 4: Time-based ("Good morning", "Good afternoon", "Good evening")
        elseif ($greetingType === 'time_based') {
            $lower = mb_strtolower($analysis['raw_query']);
            $timeSalutation = 'Good day';
            if (strpos($lower, 'morning') !== false) {
                $timeSalutation = 'Good morning';
            } elseif (strpos($lower, 'afternoon') !== false) {
                $timeSalutation = 'Good afternoon';
            } elseif (strpos($lower, 'evening') !== false) {
                $timeSalutation = 'Good evening';
            }

            $salutation = !empty($displayName) ? "{$timeSalutation} {$displayName}! 👋 " : "{$timeSalutation}! 👋 ";
            $message = "{$salutation}Welcome to PFTP. How can I assist you with your learning goals today?";
        }
        // Case 5: Default / Casual ("Hey")
        else {
            if ($lang === 'ur') {
                $message = !empty($displayName)
                    ? "السلام علیکم {$displayName}! خوش آمدید، میں PFTP کا اسٹوڈنٹ ایڈوائزر ہوں۔ میں آپ کی کیا مدد کر سکتا ہوں؟"
                    : "السلام علیکم! خوش آمدید! میں PFTP کا اسٹوڈنٹ کونسلر ہوں۔ میں کورسز، داخلہ کے طریقہ کار یا فیس کے حوالے سے آپ کی کیا مدد کر سکتا ہوں؟";
            } elseif ($lang === 'roman_ur') {
                $message = !empty($displayName)
                    ? "Assalam-o-Alaikum {$displayName}! PFTP mein khush amdeed. Main aap ka Student Advisor hoon. Aap kis course ya admission ke baare mein janna chahte hain?"
                    : "Assalam-o-Alaikum! PFTP mein khush amdeed. Main aap ka Student Advisor hoon. Aap kis course ya admission ke baare mein janna chahte hain?";
            } else {
                $salutation = !empty($displayName) ? "Hey {$displayName}! 👋 " : "Hello! 👋 ";
                $message = "{$salutation}Welcome to PFTP (Professional Freelancing Training Program). How can I assist you with our courses or admissions today?";
            }
        }

        return [
            'response_type'    => 'direct_answer',
            'message'          => $message,
            'matched_kb_id'    => null,
            'is_fallback'      => false,
            'category'         => 'Greeting',
            'possible_careers' => null,
            'confidence'       => 0.98
        ];
    }

    /**
     * Human handoff response
     */
    private function buildHumanHandoffResponse(string $lang): array
    {
        $phone = getChatbotSetting('support_phone', '+92 300 1234567');
        $email = getChatbotSetting('support_email', 'counselling@pftp.com.pk');
        $hours = getChatbotSetting('office_hours', 'Monday to Saturday, 9:00 AM - 6:00 PM (PKT)');

        if ($lang === 'ur') {
            $message = "آپ ہمارے اسٹوڈنٹ کونسلر سے براہِ راست رابطہ کر سکتے ہیں:\nفون / واٹس ایپ: {$phone}\nای میل: {$email}\nاوقاتِ کار: {$hours}";
        } elseif ($lang === 'roman_ur') {
            $message = "Aap hamare student counselor se direct rabta kar sakte hain:\nPhone / WhatsApp: {$phone}\nEmail: {$email}\nOffice Hours: {$hours}";
        } else {
            $message = "You can directly contact our student counselling team:\nPhone / WhatsApp: {$phone}\nEmail: {$email}\nOffice Hours: {$hours}";
        }

        return [
            'response_type'    => 'human_assistance',
            'message'          => $message,
            'matched_kb_id'    => null,
            'is_fallback'      => false,
            'category'         => 'Human Assistance',
            'possible_careers' => null,
            'confidence'       => 0.92
        ];
    }

    /**
     * Adapt Alternative Courses response dynamically based on prior recommendation and qualification
     */
    private function adaptAlternativeCoursesResponse(string $answer, array $analysis, array $context): string
    {
        $rawQuery = mb_strtolower($analysis['raw_query'] ?? '', 'UTF-8');
        $lastCourse = $context['last_mentioned_course'] ?? null;
        $lang = $analysis['language'] ?? 'en';

        // 1. If previous course is known, personalize opening and filter out that course
        if (!empty($lastCourse)) {
            if ($lang === 'roman_ur') {
                $answer = str_replace(
                    ['Pehle suggest kiye gaye course ke ilawa aap in courses par bhi ghour kar sakte hain:', 'pehle suggest kiya gaya course'],
                    ["{$lastCourse} ke ilawa aap in doosre courses par bhi ghour kar sakte hain:", "{$lastCourse} (jo pehle discuss hua)"],
                    $answer
                );
            } elseif ($lang === 'ur') {
                $answer = str_replace(
                    ['پہلے تجویز کیے گئے کورس کے علاوہ آپ درج ذیل کورسز پر بھی غور کر سکتے ہیں:', 'پہلے تجویز کیا گیا کورس'],
                    ["{$lastCourse} کے علاوہ آپ درج ذیل متبادل کورسز پر بھی غور کر سکتے ہیں:", "{$lastCourse}"],
                    $answer
                );
            } else {
                $answer = str_replace(
                    ['Apart from the course suggested earlier, you can also consider:', 'the previously suggested course'],
                    ["Apart from {$lastCourse} (which was discussed earlier), you can also consider:", "{$lastCourse} (which was discussed earlier)"],
                    $answer
                );
            }

            // Filter out the line mentioning $lastCourse from bullet points
            $lines = explode("\n", $answer);
            $filteredLines = [];
            foreach ($lines as $line) {
                if (preg_match('/^[-*•]\s*' . preg_quote($lastCourse, '/') . '\b/i', trim($line))) {
                    continue;
                }
                $filteredLines[] = $line;
            }
            $answer = implode("\n", $filteredLines);
        }

        // 2. If a specific qualification was requested, highlight qualification-specific recommendations
        if (preg_match('/\b(matric|10th)\b/i', $rawQuery)) {
            if ($lang === 'roman_ur') {
                $prefix = "Matric students ke liye **Full Stack Web Development**, **Python**, **Graphic Design**, aur **Content Writing** behtareen alternatives hain!\n\n";
            } elseif ($lang === 'ur') {
                $prefix = "میٹرک کے طلباء کے لیے **فل اسٹیک ویب ڈویلپمنٹ**، **پائتھن**، **گرافک ڈیزائننگ** اور **کانٹینٹ رائٹنگ** بہترین متبادل ہیں!\n\n";
            } else {
                $prefix = "For a **Matric** student, great alternative options include **Full Stack Web Development**, **Python Programming**, **Graphic Design**, and **Content Writing**!\n\n";
            }
            $answer = $prefix . $answer;
        } elseif (preg_match('/\b(fsc|pre-medical|pre-engineering|inter|intermediate)\b/i', $rawQuery)) {
            if ($lang === 'roman_ur') {
                $prefix = "FSc students ke liye **Data Science**, **Artificial Intelligence (AI)**, **Python Programming**, aur **Digital Marketing** ideal alternatives hain!\n\n";
            } elseif ($lang === 'ur') {
                $prefix = "ایف ایس سی کے طلباء کے لیے **ڈیٹا سائنس**، **مصنوعی ذہانت (AI)**، **پائتھن** اور **ڈیجیٹل مارکیٹنگ** بہترین متبادل ہیں!\n\n";
            } else {
                $prefix = "For an **FSc** student, high-demand alternatives include **Data Science**, **Artificial Intelligence (AI)**, **Python Programming**, and **Digital Marketing**!\n\n";
            }
            $answer = $prefix . $answer;
        } elseif (preg_match('/\b(computer\s+science|ics|bscs|software\s+engineering)\b/i', $rawQuery)) {
            if ($lang === 'roman_ur') {
                $prefix = "Computer Science background ke liye advanced alternatives mein **Cyber Security**, **Artificial Intelligence (AI)**, **Python**, aur **Full Stack Web Development** shamil hain!\n\n";
            } elseif ($lang === 'ur') {
                $prefix = "کمپیوٹر سائنس کے طلباء کے لیے **سائبر سیکیورٹی**، **مصنوعی ذہانت (AI)**، **ڈیٹا سائنس** اور **فل اسٹیک ویب ڈویلپمنٹ** بہترین متبادل ہیں!\n\n";
            } else {
                $prefix = "For a **Computer Science** student, advanced alternatives include **Cyber Security**, **Artificial Intelligence (AI)**, **Data Science**, and **Full Stack Web Development**!\n\n";
            }
            $answer = $prefix . $answer;
        }

        return $answer;
    }

    /**
     * Unavailable information honest fallback (zero hallucination)
     */
    private function buildUnavailableResponse(string $lang): array
    {
        $fallback = getChatbotSetting(
            'fallback_response',
            "I'm sorry, I couldn't find an accurate answer to that question. Please rephrase your question or leave your query so our team can assist you."
        );

        return [
            'response_type'    => 'unavailable_info',
            'message'          => $fallback,
            'matched_kb_id'    => null,
            'is_fallback'      => true,
            'category'         => 'Fallback',
            'possible_careers' => null,
            'confidence'       => 0.0
        ];
    }
}

