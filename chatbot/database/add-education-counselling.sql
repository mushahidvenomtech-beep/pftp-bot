-- ===================================================================
-- PFTP Website AI Chatbot - Student Education Status & Discovery Counselling
-- 15 New Entries (Main Discovery Question + 4 Response Scenarios in EN, UR, Roman Urdu)
-- ===================================================================

USE `pftp_chatbot`;

INSERT INTO `knowledge_base`
(`category`, `language`, `question`, `question_variations`, `answer`, `keywords`, `possible_careers`, `status`)
VALUES

-- ===================================================================
-- MAIN EDUCATION DISCOVERY QUESTION
-- ===================================================================
(
  'Student Discovery',
  'en',
  'Are you currently studying, or have you completed your education?',
  'Am I eligible while studying?|Can I join while in college?|Are you currently studying or finished education?|Education background check',
  'PFTP welcomes both current students and graduates! Could you please share your current educational status:\n\n1. Currently studying (school, college, degree, or semester)\n2. Completed education (Matric, Inter, Graduation, Masters)\n3. Discontinued or taking a gap in studies\n4. Not currently studying (working or job hunting)\n\nTell me which one applies to you, and I will recommend the best course according to your background!',
  'currently studying, completed education, education status, studying or finished, student discovery, qualification background',
  'Tailored Career Consultation',
  'active'
),
(
  'Student Discovery',
  'ur',
  'کیا آپ اس وقت تعلیم حاصل کر رہے ہیں یا اپنی تعلیم مکمل کر چکے ہیں؟',
  'کیا پڑھائی کے دوران داخلہ ہو سکتا ہے؟|تعلیمی قابلیت کیا ہے؟|کیا آپ پڑھ رہے ہیں یا تعلیم مکمل ہو گئی ہے؟',
  'پی ایف ٹی پی میں زیرِ تعلیم طلبہ اور فارغ التحصیل تمام افراد کے لیے کورسز موجود ہیں! براہِ کرم اپنی تعلیمی صورتحال بتائیں:\n\n1. اس وقت زیرِ تعلیم ہیں (کلاس، ڈگری یا سمسٹر)\n2. تعلیم مکمل کر چکے ہیں (میٹرک، انٹر، گریجویشن وغیرہ)\n3. پڑھائی جاری نہیں رکھ سکے\n4. اس وقت نہیں پڑھ رہے (ملازمت کر رہے ہیں یا تلاش میں ہیں)\n\nاپنی موجودہ کیفیت بتائیں تاکہ آپ کے لیے موزوں ترین کورس تجویز کیا جا سکے!',
  'تعلیم حاصل کر رہے ہیں, تعلیم مکمل کر چکے ہیں, زیر تعلیم, تعلیمی صورتحال, اہلیت, اسٹوڈنٹ تعارف',
  'Tailored Career Consultation',
  'active'
),
(
  'Student Discovery',
  'roman_ur',
  'Kya aap abhi study kar rahe hain ya apni education complete kar chuke hain?',
  'Kya parhai ke sath course kar sakte hain?|Kya aap student hain ya graduate?|Education complete hai ya parh rahe hain?|Abhi study kar rahe hain?',
  'PFTP mein students aur graduates dono ke liye behtareen practical courses hain! Barah-e-karam apni current situation batayein:\n\n1. Currently study kar rahe hain (school, college ya university)\n2. Education complete kar chuke hain (Matric, Inter, Graduation)\n3. Parhai discontinue ho gayi hai\n4. Abhi parh nahi rahe (job kar rahe hain ya search kar rahe hain)\n\nAap batayein taake main aapke background ke mutabiq best course recommend kar sakun!',
  'abhi study kar rahe hain, education complete kar chuke hain, student hain ya pass out, study status, qualification',
  'Tailored Career Consultation',
  'active'
),

-- ===================================================================
-- SCENARIO 1: STUDENT IS CURRENTLY STUDYING
-- ===================================================================
(
  'Student Discovery',
  'en',
  'I am currently studying',
  'I am a student|I am currently enrolled|Still studying|I am in college|I am doing university|I am in school|Currently studying',
  'That''s great! Which class, degree, or semester are you currently enrolled in?\n\nSharing this helps us recommend a course that comfortably fits alongside your study schedule (such as Graphic Design, Web Development, Python, or Digital Marketing).',
  'currently studying, i am a student, still studying, college student, university student, semester, enrolled',
  'Student Skill-Building Track',
  'active'
),
(
  'Student Discovery',
  'ur',
  'میں اس وقت زیرِ تعلیم ہوں',
  'میں ابھی پڑھ رہا ہوں|میں اسٹوڈنٹ ہوں|میری پڑھائی جاری ہے|میں کالج میں ہوں|میں یونیورسٹی کا طالب علم ہوں',
  'یہ بہت اچھی بات ہے! آپ اس وقت کس کلاس، ڈگری یا سمسٹر میں زیرِ تعلیم ہیں؟\n\nیہ معلومات ہمیں آپ کے تعلیمی اوقات اور صلاحیت کے مطابق بہترین کورس منتخب کرنے میں مدد دے گی۔',
  'میں اس وقت زیر تعلیم ہوں, میں ابھی پڑھ رہا ہوں, میں اسٹوڈنٹ ہوں, پڑھائی جاری ہے, کالج اسٹوڈنٹ, یونیورسٹی طالب علم',
  'Student Skill-Building Track',
  'active'
),
(
  'Student Discovery',
  'roman_ur',
  'Main currently study kar raha hoon',
  'Main abhi parh raha hoon|Main student hoon|Meri study jari hai|Main college mein hoon|Main university student hoon|Still studying',
  'Yeh bohat achi baat hai! Aap is waqt kis class, degree ya semester mein study kar rahe hain?\n\nYeh batane se hum aapke study schedule ke mutabiq behtareen flexible course recommend kar sakenge.',
  'main currently study kar raha hoon, main abhi parh raha hoon, main student hoon, study jari hai, semester mein hoon',
  'Student Skill-Building Track',
  'active'
),

-- ===================================================================
-- SCENARIO 2: STUDENT HAS COMPLETED EDUCATION
-- ===================================================================
(
  'Student Discovery',
  'en',
  'I have completed my education',
  'I finished my studies|I graduated|Education completed|Completed my degree|I am a graduate|Done with studies',
  'That''s great! What is your highest completed qualification?\n\nWhether you have completed Matric, Intermediate, Graduation (BS/BA), or a Master''s degree, PFTP offers career-oriented courses to help you build practical, job-ready, and freelancing skills.',
  'completed education, i have completed my education, finished studies, graduated, degree completed, highest qualification',
  'Graduate & Professional Track',
  'active'
),
(
  'Student Discovery',
  'ur',
  'میں اپنی تعلیم مکمل کر چکا ہوں',
  'میری پڑھائی مکمل ہو چکی ہے|میں گریجویٹ ہوں|تعلیم مکمل ہے|ڈگری مکمل ہو گئی ہے|میں نے پڑھائی مکمل کر لی ہے',
  'بہت خوب! آپ نے اپنی تعلیم میں سب سے آخری کون سی ڈگری یا کلاس مکمل کی ہے؟\n\nخواہ آپ نے میٹرک، انٹر، گریجویشن یا ماسٹرز کیا ہو، PFTP کے عملی کورسز آپ کو ڈیجیٹل انڈسٹری میں قدم رکھنے میں مدد فراہم کرتے ہیں۔',
  'میں اپنی تعلیم مکمل کر چکا ہوں, پڑھائی مکمل ہو چکی ہے, میں گریجویٹ ہوں, ڈگری مکمل ہے, تعلیم مکمل ہے',
  'Graduate & Professional Track',
  'active'
),
(
  'Student Discovery',
  'roman_ur',
  'Maine apni education complete kar li hai',
  'Main education complete kar chuka hoon|Meri study complete ho chuki hai|Main graduate hoon|Degree complete hai|Parhai mukammal hai',
  'Bohat khoob! Aap ne apni education mein sab se aakhri konsi degree ya class complete ki hai?\n\nChahe aapne Matric, Inter, Graduation ya Masters kiya ho, PFTP ke career-focused courses aapko practical skills aur earning opportunities sikhate hain.',
  'maine apni education complete kar li hai, education complete kar chuka hoon, study complete, graduate hoon, degree complete',
  'Graduate & Professional Track',
  'active'
),

-- ===================================================================
-- SCENARIO 3: STUDENT HAS DISCONTINUED EDUCATION
-- ===================================================================
(
  'Student Discovery',
  'en',
  'I discontinued my education',
  'I dropped out|I left studies|Discontinued education|Studies discontinued|Could not continue education|Had to leave school',
  'No problem. Could you please tell me the last class or qualification you completed?\n\nPFTP strongly believes in skill empowerment. Practical skills like Graphic Design, Video Editing, Freelancing, and Web Development do not require advanced academic degrees to get started!',
  'discontinued education, i discontinued my education, dropped out, left studies, incomplete education, last qualification',
  'Practical Skills Career Track',
  'active'
),
(
  'Student Discovery',
  'ur',
  'میں نے اپنی تعلیم چھوڑ دی ہے',
  'میں پڑھائی جاری نہیں رکھ سکا|پڑھائی چھوٹ گئی تھی|تعلیم ادھوری رہ گئی|میں نے اسکول چھوڑ دیا|ڈراپ آؤٹ',
  'کوئی بات نہیں۔ کیا آپ بتا سکتے ہیں کہ آپ نے آخری کون سی کلاس یا تعلیمی قابلیت مکمل کی تھی؟\n\nپی ایف ٹی پی کا ماننا ہے کہ ہنر سب سے بڑی طاقت ہے۔ گرافک ڈیزائننگ، ویڈیو ایڈیٹنگ، فری لانسنگ اور ای کامرس جیسے کورسز میں کامیابی کے لیے بڑی ڈگریوں کے بجائے عملی محنت کی ضرورت ہوتی ہے۔',
  'میں نے اپنی تعلیم چھوڑ دی ہے, پڑھائی جاری نہیں رکھ سکا, تعلیم ادھوری ہے, پڑھائی چھوٹ گئی, ڈراپ آؤٹ',
  'Practical Skills Career Track',
  'active'
),
(
  'Student Discovery',
  'roman_ur',
  'Maine study discontinue kar di hai',
  'Maine parhai chor di hai|Study incomplete hai|Parhai jari nahi rakh saka|Drop out hoon|Studies left|Parhai discontinue ho gayi',
  'Koi baat nahi. Kya aap bata sakte hain ke aap ne aakhri konsi class ya qualification complete ki thi?\n\nPFTP ka maqsad har fard ko practical skill sikhana hai. Graphic Design, Video Editing, Freelancing aur E-Commerce jaisi skills ke liye bari degree ki shart nahi hoti, balkeh mehnat aur practice ahem hai.',
  'maine study discontinue kar di hai, parhai chor di, study incomplete, parhai jari nahi rakh saka, drop out, aakhri class',
  'Practical Skills Career Track',
  'active'
),

-- ===================================================================
-- SCENARIO 4: STUDENT IS NOT CURRENTLY STUDYING
-- ===================================================================
(
  'Student Discovery',
  'en',
  'I am not currently studying',
  'Not studying right now|I am free currently|Not in any school or college|Currently free|Not studying|Unemployed looking for skills',
  'I understand. Are you currently working, looking for a job, or planning to learn a new skill?\n\nKnowing your current goal allows us to recommend courses that help you upskill, switch fields, or start earning through freelancing.',
  'not currently studying, not studying right now, currently free, looking for job, planning to learn skill, working',
  'Career Upskilling Track',
  'active'
),
(
  'Student Discovery',
  'ur',
  'میں اس وقت پڑھ نہیں رہا',
  'میں اس وقت فارغ ہوں|ابھی پڑھائی نہیں کر رہا|کوئی پڑھائی نہیں ہو رہی|نوکری کی تلاش ہے|کوئی نیا ہنر سیکھنا ہے',
  'میں سمجھ سکتا ہوں۔ کیا آپ اس وقت ملازمت کر رہے ہیں، نوکری تلاش کر رہے ہیں یا کوئی نئی مہارت سیکھنے کا ارادہ رکھتے ہیں؟\n\nاپنا مقصد بتائیں تاکہ ہم آپ کو ایسا کورس بتا سکیں جس سے آپ اپنی آمدنی اور کیریئر کو بہتر بنا سکیں۔',
  'میں اس وقت پڑھ نہیں رہا, میں اس وقت فارغ ہوں, ابھی پڑھائی نہیں کر رہا, نوکری تلاش, نئی مہارت',
  'Career Upskilling Track',
  'active'
),
(
  'Student Discovery',
  'roman_ur',
  'Main abhi study nahi kar raha hoon',
  'Main abhi parh nahi raha|Filhal free hoon|Study nahi kar raha|Job dhoond raha hoon|Nayi skill seekhni hai|Free time hai',
  'Main samajh sakta hoon. Kya aap is waqt job kar rahe hain, job talash kar rahe hain ya koi nayi skill seekhne ka irada rakhte hain?\n\nApna goal share karein taake hum aapko freelancing ya job-oriented best skill recommend kar sakein.',
  'main abhi study nahi kar raha hoon, parh nahi raha, free hoon, job dhoond raha hoon, nayi skill seekhni hai, job kar rahe hain',
  'Career Upskilling Track',
  'active'
);

