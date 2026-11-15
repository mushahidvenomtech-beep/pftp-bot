-- ===================================================================
-- PFTP Website AI Chatbot - Generic Greetings Dataset
-- Supports Assalam o Alaikum, Hi, Hy, Hello, Hey across English, Roman Urdu & Urdu
-- ===================================================================

USE `pftp_chatbot`;

INSERT INTO `knowledge_base` (`category`, `language`, `question`, `question_variations`, `answer`, `keywords`, `possible_careers`, `status`)
VALUES
-- 1. English: Islamic Greeting
('General Greetings', 'en', 'Assalam o Alaikum', 'Asalam o Alaikum|Assalamu Alaikum|Salam|AOA|Assalam-o-Alaikum|Aslam o alaikum', 'Walaikum Assalam! Welcome to PFTP (Professional Freelancing Training Program). How can I assist you today with our courses or admissions?', 'assalam o alaikum, salam, greetings, welcome, walaikum assalam, aoa', 'PFTP Admissions Desk', 'active'),

-- 2. Roman Urdu: Islamic Greeting
('General Greetings', 'roman_ur', 'Assalam o Alaikum kaisay hain aap?', 'Salam|Asalam o Alaikum|Slam|AOA|Assalamu Alaikum|Kese ho aap', 'Walaikum Assalam! PFTP mein khush amdeed. Main aap ka Student Advisor hoon. Aap kis course ya admission process ke baare mein janna chahte hain?', 'salam, assalam o alaikum, khush amdeed, course inquiry, aoa', 'PFTP Admissions Desk', 'active'),

-- 3. Urdu Script: Islamic Greeting
('General Greetings', 'ur', 'السلام علیکم', 'سلام|السلام علیکم ورحمۃ اللہ|کیسے ہیں آپ|وعلیکم السلام', 'وعلیکم السلام! PFTP میں خوش آمدید۔ میں آپ کا اسٹوڈنٹ ایڈوائزر ہوں۔ میں کورسز، فیس یا داخلے کے حوالے سے آپ کی کیا مدد کر سکتا ہوں؟', 'السلام علیکم, وعلیکم السلام, خوش آمدید, کورسز', 'PFTP Admissions Desk', 'active'),

-- 4. English: Casual Hi / Hy / Hello / Hey
('General Greetings', 'en', 'Hi', 'Hy|Hello|Hey|Hi there|Hello there|Good morning|Good evening', 'Hello! 👋 Welcome to PFTP (Professional Freelancing Training Program). How can I help you today with our 16 practical skill courses or admissions?', 'hi, hy, hello, hey, greeting, welcome, good morning', 'PFTP Admissions Desk', 'active'),

-- 5. Roman Urdu: Casual Hi / Hy / Hello
('General Greetings', 'roman_ur', 'Hi kaisay hain?', 'Hy|Hello|Hey|Kese ho|Kia hal hai|Kya hal hai', 'Hello! PFTP mein khush amdeed. Main aap ka Student Advisor hoon. Aap kis course ya field ke baare mein janna chahte hain?', 'hi, hy, hello, hey, kese ho, pftp courses', 'PFTP Admissions Desk', 'active');
