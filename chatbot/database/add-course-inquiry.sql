-- ===================================================================
-- PFTP Website AI Chatbot - Course Inquiries & 16 Courses Directory
-- ===================================================================

USE `pftp_chatbot`;

INSERT INTO `knowledge_base`
(`category`, `language`, `question`, `question_variations`, `answer`, `keywords`, `possible_careers`, `status`)
VALUES
(
  'Courses Overview',
  'roman_ur',
  'Mujhy ap k courses k bary my janna hy?',
  'Mujhe aap ke courses ke baare mein janna hai?|Aap kon sy courses karwaty hy?|Kon sy courses karwaty hy?|Courses k baray me batayein|Mujhy courses ki detail chahiye|PFTP k courses kon kon say hain?|Aapke paas konse courses hain?|Courses list Roman Urdu',
  'PFTP 16 mukhtalif specialized practical skill-building courses offer karta hai:\n\n1. Digital Marketing\n2. Freelancing\n3. Graphic Design\n4. Video Editing\n5. YouTube Automation\n6. Content Writing\n7. Search Engine Optimization (SEO)\n8. Full Stack Web Development\n9. Artificial Intelligence (AI)\n10. Cyber Security\n11. Data Science\n12. Python Programming\n13. E-Commerce\n14. QuickBooks\n15. English Language\n16. IELTS Preparation\n\nAap kis field mein interest rakhte hain? Aap apni education level batayein, aur main aapko behtareen course guide karunga!',
  'mujhy ap k courses k bary my janna hy, kon sy courses karwaty hy, courses k baray me batayein, courses list, courses details, konse courses',
  'Tech, Creative, Marketing & Business Programs',
  'active'
),
(
  'Courses Overview',
  'en',
  'I need to know about your courses?',
  'Which courses do you offer?|What courses do you offer?|Whatcourses?|I want to know about your courses|Tell me about your courses|Show me the courses list|What can I study at PFTP?|Available courses list',
  'PFTP offers 16 specialized, practical skill-building courses:\n\n1. Digital Marketing\n2. Freelancing\n3. Graphic Design\n4. Video Editing\n5. YouTube Automation\n6. Content Writing\n7. Search Engine Optimization (SEO)\n8. Full Stack Web Development\n9. Artificial Intelligence (AI)\n10. Cyber Security\n11. Data Science\n12. Python Programming\n13. E-Commerce\n14. QuickBooks\n15. English Language\n16. IELTS Preparation\n\nWhich field matches your background or interest? Tell me your education level, and I will guide you!',
  'i need you know about your courses, i need to know about your courses, which courses do you offer, what courses, whatcourses, course offerings, all courses',
  'Tech, Creative, Marketing & Business Programs',
  'active'
),
(
  'Courses Overview',
  'ur',
  'مجھے آپ کے کورسز کے بارے میں جاننا ہے؟',
  'آپ کون سے کورسز کرواتے ہیں؟|پی ایف ٹی پی میں کون سے کورسز ہوتے ہیں؟|تمام کورسز کی تفصیلات|کورسز کی فہرست دکھائیں|کون کون سے کورسز کرواتے ہیں؟',
  'پی ایف ٹی پی (PFTP) 16 اہم اور عملی اسکل بلڈنگ کورسز کرواتا ہے:\n\n1. ڈیجیٹل مارکیٹنگ (Digital Marketing)\n2. فری لانسنگ (Freelancing)\n3. گرافک ڈیزائننگ (Graphic Design)\n4. ویڈیو ایڈیٹنگ (Video Editing)\n5. یوٹیوب آٹومیشن (YouTube Automation)\n6. کانٹینٹ رائٹنگ (Content Writing)\n7. سرچ انجن آپٹیمائزیشن (SEO)\n8. فل اسٹیک ویب ڈویلپمنٹ (Full Stack Web Development)\n9. آرٹیفیشل انٹیلیجنس (AI)\n10. سائبر سیکیورٹی (Cyber Security)\n11. ڈیٹا سائنس (Data Science)\n12. پائتھن پروگرامنگ (Python Programming)\n13. ای کامرس (E-Commerce)\n14. کوئیک بکس (QuickBooks)\n15. انگلش لینگویج (English Language)\n16. آئلٹس تیاری (IELTS Preparation)\n\nکون سا شعبہ آپ کی دلچسپی اور تعلیمی قابلیت سے میل کھاتا ہے؟ مجھے اپنی تعلیمی قابلیت بتائیں، اور میں آپ کی مکمل رہنمائی کروں گا!',
  'مجھے آپ کے کورسز کے بارے میں جاننا ہے, آپ کون سے کورسز کرواتے ہیں, تمام کورسز, کورسز لسٹ, پروگرامز',
  'Tech, Creative, Marketing & Business Programs',
  'active'
);

