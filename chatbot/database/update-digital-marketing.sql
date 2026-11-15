-- ===================================================================
-- PFTP Website AI Chatbot - Update Digital Marketing Variations & Phrasings
-- ===================================================================

USE `pftp_chatbot`;

-- 1. Update English Digital Marketing
UPDATE `knowledge_base`
SET
  `question_variations` = 'Digital Marketing|Digital Media Marketing|Digital Marketing Course|Digital Media Marketing Course|What do you teach in Digital Marketing|What do we learn in digital marketing|What do we learn in Digital media Marketing|What is Digital Marketing|What is Digital Media Marketing|Why study digital marketing?|What will I learn in digital marketing?|Is digital marketing good for career?|Digital marketing syllabus',
  `keywords` = 'digital marketing, digital media marketing, smm, online ads, meta ads, facebook ads, google ads, lead generation, campaigns, customer behaviour, what do you teach in digital marketing, what do we learn in digital marketing, what do we learn in digital media marketing, what is digital marketing, what is digital media marketing',
  `updated_at` = NOW()
WHERE `id` = 1 OR (`category` = 'Digital Marketing' AND `language` = 'en');

-- 2. Update Urdu Digital Marketing
UPDATE `knowledge_base`
SET
  `question_variations` = 'ڈیجیٹل مارکیٹنگ|ڈیجیٹل میڈیا مارکیٹنگ|ڈیجیٹل مارکیٹنگ کورس|ڈیجیٹل میڈیا مارکیٹنگ کورس|ڈیجیٹل مارکیٹنگ کیا ہے؟|ڈیجیٹل مارکیٹنگ کورس میں کیا سکھایا جاتا ہے؟|ڈیجیٹل مارکیٹنگ کے فوائد|ڈیجیٹل مارکیٹنگ میں کیا سیکھیں گے؟',
  `keywords` = 'ڈیجیٹل مارکیٹنگ, ڈیجیٹل میڈیا مارکیٹنگ, سوشل میڈیا, اشتہارات, فیس بک ایڈز, گوگل ایڈز, بزنس مارکیٹنگ, کیا سکھایا جاتا ہے, ڈیجیٹل مارکیٹنگ کورس',
  `updated_at` = NOW()
WHERE `id` = 2 OR (`category` = 'Digital Marketing' AND `language` = 'ur');

-- 3. Update Roman Urdu Digital Marketing
UPDATE `knowledge_base`
SET
  `question_variations` = 'Ap Digital Marketing k course my kia sikhaty hy|ap Digital Media Marketing k course my kia sikhaty hy|Digital Marketing|Digital Media Marketing|Digital Marketing Course|Digital Media Marketing Course|Digital marketing kya hai?|Digital marketing mein kya sikhaya jata hai?|Mujhe digital marketing karni chahiye?|Social media marketing course|Digital media marketing kya hai?|Digital marketing me kya sikhate hain|Digital marketing me kia sikhate hein',
  `keywords` = 'ap digital marketing k course my kia sikhaty hy, ap digital media marketing k course my kia sikhaty hy, digital media marketing, digital marketing, digital marketing course, digital media marketing course, online marketing, social media ads, meta ads, google ads, marketing seekhna, customer growth, smm',
  `updated_at` = NOW()
WHERE `id` = 3 OR (`category` = 'Digital Marketing' AND `language` = 'roman_ur');

