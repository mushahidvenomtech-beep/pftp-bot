-- ===================================================================
-- PFTP Website AI Chatbot - Admission & Registration Steps Knowledge Base
-- Added 30 new entries (Steps 1 through 9 + Direct Links & Follow-ups)
-- ===================================================================

USE `pftp_chatbot`;

INSERT INTO `knowledge_base`
(`category`, `language`, `question`, `question_variations`, `answer`, `keywords`, `possible_careers`, `status`)
VALUES

-- ===================================================================
-- STEP 1: WHERE TO APPLY FOR ADMISSION
-- ===================================================================
(
  'Admission Process',
  'en',
  'Where should I go to apply for admission?',
  'How to apply for admission?|Where to apply?|PFTP admission website|How to register online?|Where do I apply for PFTP courses?',
  'Open your browser (Chrome, for example) and type pftpedu.org in the address bar. Once the PFTP homepage opens, look for the Apply Now, Start Your Application, or Apply for Training button and click it. This will take you to the online registration form.\n\nDirect Registration Link: https://pftpedu.org/registration',
  'apply for admission, where to apply, pftpedu.org, apply now button, start your application, apply for training, registration form, website',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'ur',
  'داخلے کے لیے مجھے ویب سائٹ پر کہاں جانا ہوگا؟',
  'داخلہ کیسے لیں؟|آن لائن داخلہ کیسے بھیجیں؟|پی ایف ٹی پی ویب سائٹ داخلہ|داخلے کے لیے کہاں جائیں؟',
  'سب سے پہلے اپنے موبائل یا کمپیوٹر میں Chrome کھولیں اور pftpedu.org لکھ کر ویب سائٹ کھولیں۔ ہوم پیج پر Apply Now، Start Your Application یا Apply for Training کا بٹن تلاش کریں اور اس پر کلک کریں۔ اس کے بعد آپ داخلہ فارم تک پہنچ جائیں گے۔\n\nبراہِ راست لنک: https://pftpedu.org/registration',
  'داخلہ, ویب سائٹ, اپلائی, رجسٹریشن فارم, pftpedu.org, داخلے کے لیے کہاں جائیں',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'roman_ur',
  'Admission ke liye mujhe website par kahan jana hoga?',
  'Admission kaise lein?|Online apply kaise karein?|PFTP website par admission kahan hai?|Website par kahan jana hoga?',
  'Sab se pehle apne mobile ya computer mein Chrome open karein aur pftpedu.org type karke website kholein. Homepage par Apply Now, Start Your Application ya Apply for Training ka button dhoondein aur us par click karein. Is ke baad aap admission form par pohanch jayenge.\n\nDirect Link: https://pftpedu.org/registration',
  'admission kahan jana hoga, apply now, registration form, online admission, pftpedu.org, website par kahan jayein',
  'PFTP Admissions Desk',
  'active'
),

-- ===================================================================
-- STEP 2: OPEN THE ADMISSION FORM
-- ===================================================================
(
  'Admission Process',
  'en',
  'Which page should I open for registration?',
  'What is the direct registration link?|Where is the admission form?|Start Your Admission page|Registration URL',
  'Click the admission or registration button. You should reach the page titled Start Your Admission. You can also open it directly by visiting:\nhttps://pftpedu.org/registration\n\nThe form is divided into sections, which you complete one by one.',
  'registration page, admission form link, pftpedu.org/registration, start your admission, direct admission link',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'ur',
  'رجسٹریشن کے لیے کون سا صفحہ کھولنا ہے؟',
  'داخلہ فارم کا صفحہ|رجسٹریشن لنک کیا ہے؟|آن لائن فارم کا لنک',
  'Admission یا Registration کے بٹن پر کلک کریں۔ آپ کے سامنے Start Your Admission کے عنوان سے داخلہ فارم کھل جائے گا۔ آپ براہِ راست بھی اس لنک سے فارم کھول سکتے ہیں:\nhttps://pftpedu.org/registration\n\nفارم مختلف حصوں میں تقسیم ہے جنہیں آپ ایک ایک کر کے مکمل کرتے ہیں۔',
  'رجسٹریشن صفحہ, داخلہ فارم لنک, pftpedu.org/registration, کون سا صفحہ کھولنا ہے',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'roman_ur',
  'Registration ke liye konsa page open karna hai?',
  'Admission form ka link kya hai?|Direct registration link|Form kahan se open karein?|Konsa page open karein?',
  'Admission ya Registration ke button par click karein. Aapke samne Start Your Admission ke naam se form open ho jayega. Aap direct is link se bhi form open kar sakte hain:\nhttps://pftpedu.org/registration\n\nForm mukhtalif sections mein divided hai jinhe aap step by step complete karte hain.',
  'registration page, admission form link, direct link, pftpedu.org/registration, konsa page',
  'PFTP Admissions Desk',
  'active'
),

-- ===================================================================
-- STEP 3: FILL IN PERSONAL INFORMATION
-- ===================================================================
(
  'Admission Process',
  'en',
  'What information do I need to enter in the first section?',
  'Personal information needed for admission|What documents or details are needed for registration?|CNIC B-form requirement in registration',
  'In the Personal Information section, enter:\n• Full name\n• Father''s name\n• CNIC or B-Form number (13 digits without dashes)\n• Email address\n• Mobile number\n• Date of birth\n• Gender\n• Highest educational qualification\n\nMake sure your details are correct because your certificate and student records will use this information.\nApply Online: https://pftpedu.org/registration',
  'personal information, first section, cnic, b-form, email, mobile number, qualification, student record details, registration requirements',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'ur',
  'پہلے حصے میں کون سی معلومات درج کرنی ہوں گی؟',
  'ذاتی معلومات کیا درج کرنی ہیں؟|شناختی کارڈ اور تعلیمی تفصیلات فارم میں|رجسٹریشن میں کیا معلومات چاہیئں؟',
  'Personal Information کے حصے میں درج ذیل معلومات درج کریں:\n• اپنا مکمل نام\n• والد کا نام\n• شناختی کارڈ یا ب فارم نمبر (بغیر ڈیش کے 13 ہندسے)\n• ای میل ایڈریس\n• موبائل نمبر\n• تاریخ پیدائش\n• جنس\n• آخری تعلیمی قابلیت\n\nتمام معلومات درست درج کریں کیونکہ یہی معلومات آپ کے اسٹوڈنٹ ریکارڈ اور سرٹیفکیٹ میں استعمال ہوں گی۔\nآن لائن اپلائی: https://pftpedu.org/registration',
  'ذاتی معلومات, پہلا حصہ, شناختی کارڈ, ب فارم, موبائل نمبر, تعلیمی قابلیت',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'roman_ur',
  'Pehle section mein konsi information enter karni hogi?',
  'Personal details mein kya likhna hai?|Registration form mein pehla step|CNIC aur details|Pehle hissay mein kya enter karein?',
  'Personal Information ke section mein ye details enter karein:\n• Apna full name\n• Father ka name\n• CNIC ya B-Form number (13 digits, baghair dash ke)\n• Email address\n• Mobile number\n• Date of birth\n• Gender\n• Highest educational qualification\n\nApni tamam details sahi enter karein kyun ke ye aapke student record aur certificate mein use hongi.\nApply Online: https://pftpedu.org/registration',
  'personal information, pehle section, cnic, b-form number, mobile number, qualification, pehli information',
  'PFTP Admissions Desk',
  'active'
),

-- ===================================================================
-- STEP 4: SELECT YOUR COURSE
-- ===================================================================
(
  'Admission Process',
  'en',
  'Where do I select my desired course?',
  'How to select course in form?|How many courses can I select?|Promo code field in registration|Course selection step|Can I choose multiple courses?',
  'Scroll down to the Course Selection section. Click the Program dropdown and select the course you want to study.\n• Select your preferred course.\n• You can add more courses using Add Another Course.\n• The form allows you to select up to five programs.\n• If you have a valid promo code, enter it in the Promo Code field.\n• After selecting your courses, review the displayed fee details before proceeding.\nApply Online: https://pftpedu.org/registration',
  'select course, desired course, program dropdown, add another course, five programs, promo code, course selection section',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'ur',
  'اپنی پسند کا کورس کہاں سے منتخب کرنا ہے؟',
  'کورس کا انتخاب کیسے کریں؟|ایک وقت میں کتنے کورسز منتخب ہو سکتے ہیں؟|پرومو کوڈ کہاں درج کریں؟|کورس سلیکشن کہاں ہے؟',
  'فارم میں نیچے جائیں اور Course Selection کا سیکشن تلاش کریں۔ Program کے ڈراپ ڈاؤن پر کلک کریں اور اپنی پسند کا کورس منتخب کریں۔\n• اپنا پسندیدہ کورس منتخب کریں۔\n• مزید کورسز کے لیے Add Another Course پر کلک کریں۔\n• ایک فارم میں زیادہ سے زیادہ پانچ کورسز منتخب کیے جا سکتے ہیں۔\n• اگر آپ کے پاس درست Promo Code ہے تو اسے متعلقہ خانے میں درج کریں۔\n• کورس منتخب کرنے کے بعد فیس کی تفصیلات ضرور چیک کریں۔',
  'کورس منتخب, اپنی پسند کا کورس, پروگرام ڈراپ ڈاؤن, پانچ کورسز, پرومو کوڈ',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'roman_ur',
  'Apna pasandeeda course kahan se select karna hai?',
  'Course selection kaise karein?|Kitne course select kar sakte hain?|Promo code kahan enter karein?|Course kahan se select hoga?',
  'Form mein neeche ja kar Course Selection section dhoondein. Program dropdown par click karein aur apna pasandeeda course select karein.\n• Apna preferred course choose karein.\n• Mazeed courses ke liye Add Another Course par click karein.\n• Ek form mein maximum five programs select kar sakte hain.\n• Agar aapke paas valid Promo Code hai to usay enter karein.\n• Course selection ke baad fee details zaroor check karein.',
  'pasandeeda course, course selection, program dropdown, add another course, 5 courses, promo code',
  'PFTP Admissions Desk',
  'active'
),

-- ===================================================================
-- STEP 5: ENTER YOUR LOCATION
-- ===================================================================
(
  'Admission Process',
  'en',
  'What should I enter in the Location section?',
  'Address in admission form|Location details required|City and province in registration|Where to add home address?',
  'Click Next: Location and provide:\n• Province\n• City\n• Street address\n\nEnter your correct residential location and then click Next: Account.\nApply Online: https://pftpedu.org/registration',
  'location section, province, city, street address, residential location, address details',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'ur',
  'Location والے حصے میں کیا درج کرنا ہے؟',
  'رہائشی پتہ کیا لکھنا ہے؟|شہر اور صوبہ داخلہ فارم|پتہ کیسے درج کریں؟',
  'Next: Location پر کلک کریں اور درج ذیل معلومات درج کریں:\n• صوبہ\n• شہر\n• گلی اور گھر کا مکمل پتہ\n\nاپنا درست رہائشی پتہ درج کرنے کے بعد Next: Account پر کلک کریں۔',
  'لوکیشن, رہائشی پتہ, صوبہ, شہر, گھر کا پتہ',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'roman_ur',
  'Location wale section mein kya enter karna hai?',
  'Address details kya likhein?|City province address form|Address kahan enter karna hai?',
  'Next: Location par click karein aur ye information enter karein:\n• Province\n• City\n• Street address\n\nApna correct residential address enter karne ke baad Next: Account par click karein.',
  'location section, province, city, street address, ghar ka pata, residential address',
  'PFTP Admissions Desk',
  'active'
),

-- ===================================================================
-- STEP 6: CREATE YOUR STUDENT ACCOUNT
-- ===================================================================
(
  'Admission Process',
  'en',
  'How do I create my student account?',
  'Account security section|Password requirement for student portal|Terms and refund policy agreement|Student account password setup',
  'In the Account Security section:\n• Create a password of at least eight characters.\n• Enter the same password again in Confirm Password.\n• Read the declaration and program terms.\n• Tick the agreement checkbox to confirm that your information is correct and that you accept the terms, refund policy, and WhatsApp SOPs.\n• Keep your password safe because you will need it to access your student account.\nApply Online: https://pftpedu.org/registration',
  'student account, account security, password 8 characters, confirm password, refund policy, terms agreement, whatsapp sops',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'ur',
  'اپنا اسٹوڈنٹ اکاؤنٹ کیسے بنانا ہے؟',
  'اکاؤنٹ پاس ورڈ کیسے بنائیں؟|پاس ورڈ کتنا بڑا ہونا چاہیے؟|شرائط اور ریفنڈ پالیسی|اسٹوڈنٹ لاگ ان کیسے بنے گا؟',
  'Account Security کے حصے میں:\n• کم از کم آٹھ حروف پر مشتمل پاس ورڈ بنائیں۔\n• وہی پاس ورڈ دوبارہ Confirm Password میں درج کریں۔\n• دی گئی شرائط اور اقرار نامہ پڑھیں۔\n• چیک باکس پر نشان لگائیں تاکہ آپ اپنی معلومات کی درستگی اور پروگرام کی شرائط، ریفنڈ پالیسی اور WhatsApp SOPs سے اتفاق کی تصدیق کر سکیں۔\n• اپنا پاس ورڈ محفوظ رکھیں کیونکہ آپ کو اسٹوڈنٹ اکاؤنٹ میں لاگ اِن کرنے کے لیے اس کی ضرورت ہوگی۔',
  'اسٹوڈنٹ اکاؤنٹ, پاس ورڈ, کنفرم پاس ورڈ, ریفنڈ پالیسی, شرائط, اکاؤنٹ سیکیورٹی',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'roman_ur',
  'Apna student account kaise create karna hai?',
  'Student account password kaise banayein?|Password kitne digits ka ho?|Terms agreement|Account kaise banega?',
  'Account Security section mein:\n• Kam az kam 8 characters ka password create karein.\n• Wohi password Confirm Password mein dobara enter karein.\n• Declaration aur program terms ko parhein.\n• Agreement checkbox ko tick karein aur confirm karein ke aapki information correct hai aur aap program terms, refund policy aur WhatsApp SOPs se agree karte hain.\n• Apna password safe rakhein kyun ke student account login karne ke liye iski zaroorat hogi.',
  'student account, password create, confirm password, 8 characters, terms agreement, refund policy, whatsapp sops',
  'PFTP Admissions Desk',
  'active'
),

-- ===================================================================
-- STEP 7: SUBMIT ADMISSION APPLICATION
-- ===================================================================
(
  'Admission Process',
  'en',
  'How do I finally submit my admission form?',
  'Submitting admission application|Submit button on form|What happens after clicking submit application?|Final application submission',
  'Once you have completed all sections:\n• Review your personal information.\n• Check your selected course or courses.\n• Verify your location and account details.\n• Click Submit Application.\n\nAfter successful submission, you will be taken to your student portal, where your Documentation Processing Fee challan and PSID will be available.\nRegistration Link: https://pftpedu.org/registration',
  'submit admission form, submit application button, final submission, review information, challan psid',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'ur',
  'آخر میں داخلہ فارم کیسے جمع کروانا ہے؟',
  'فارم جمع کیسے کروائیں؟|سبمٹ ایپلیکیشن پر کلک کرنے کے بعد کیا ہوتا ہے؟|فارم فائنل کیسے ہوگا؟',
  'تمام حصے مکمل کرنے کے بعد:\n• اپنی ذاتی معلومات دوبارہ چیک کریں۔\n• منتخب کردہ کورس یا کورسز کی تصدیق کریں۔\n• اپنا پتہ اور اکاؤنٹ کی تفصیلات چیک کریں۔\n• Submit Application پر کلک کریں۔\n\nدرخواست کامیابی سے جمع ہونے کے بعد آپ کو اسٹوڈنٹ پورٹل پر بھیج دیا جائے گا، جہاں Documentation Processing Fee کا چالان اور PSID دستیاب ہوگا۔',
  'داخلہ فارم جمع, سبمٹ ایپلیکیشن, فائنل جمع, چالان, پی ایس آئی ڈی, اسٹوڈنٹ پورٹل',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'roman_ur',
  'Aakhir mein admission form kaise submit karna hai?',
  'Form kaise submit karein?|Submit application button|Form submit karne ke baad kya hota hai?|Final form submission',
  'Tamam sections complete karne ke baad:\n• Apni personal information dobara check karein.\n• Selected course ya courses verify karein.\n• Apna address aur account details check karein.\n• Submit Application par click karein.\n\nApplication successfully submit hone ke baad aap student portal par chale jayenge. Wahan Documentation Processing Fee ka challan aur PSID available hoga.',
  'admission form submit, submit application, final submit, challan psid, student portal',
  'PFTP Admissions Desk',
  'active'
),

-- ===================================================================
-- STEP 8: PAY THE DOCUMENTATION PROCESSING FEE
-- ===================================================================
(
  'Fees & Payments',
  'en',
  'How do I pay the admission processing fee?',
  'How to pay documentation fee?|PayPro PSID payment instructions|Is there a tuition fee?|How much is documentation fee?|Rs 2900 fee challan|Banking app payment steps',
  'To pay the Documentation Processing Fee:\n1. Open your student portal.\n2. Find your Documentation Processing Fee challan.\n3. Note down your PSID.\n4. Open your banking or mobile wallet application (JazzCash, Easypaisa, Nayapay, SadaPay, or Bank App).\n5. Select Bills & Payments.\n6. Choose PayPro.\n7. Enter your PSID.\n8. Verify the displayed amount and confirm payment.\n9. Return to your PFTP portal and refresh it to check payment confirmation.\n\nFee Note: The currently displayed one-time Documentation Processing Fee is Rs. 2,900. There is no tuition fee for the listed free courses. The processing fee is non-refundable, subject to the published transfer or deferment policy.\nOfficial Payment Guide: https://pftpedu.org/how-to-pay',
  'pay processing fee, documentation processing fee, 2900, paypro, psid, bills payments, jazzcash, easypaisa, banking app, tuition fee free',
  'PFTP Accounts & Admissions',
  'active'
),
(
  'Fees & Payments',
  'ur',
  'داخلے کی پروسیسنگ فیس کیسے ادا کرنی ہے؟',
  'پروسیسنگ فیس کیسے جمع کروائیں؟|PayPro اور PSID کے ذریعے ادائیگی|کیا کورسز کی ٹیوشن فیس ہے؟|2900 فیس کیسے ادا کریں؟|فیس ادائیگی کا طریقہ',
  'داخلہ پروسیسنگ فیس کی ادائیگی کا طریقہ:\n1. اپنا اسٹوڈنٹ پورٹل کھولیں۔\n2. Documentation Processing Fee کا چالان تلاش کریں۔\n3. اپنا PSID نوٹ کریں۔\n4. اپنی بینکنگ یا موبائل والٹ ایپ (Easypaisa, JazzCash وغیرہ) کھولیں۔\n5. Bills & Payments منتخب کریں۔\n6. PayPro منتخب کریں۔\n7. اپنا PSID درج کریں۔\n8. ظاہر ہونے والی رقم چیک کریں اور ادائیگی کی تصدیق کریں۔\n9. دوبارہ PFTP پورٹل پر آئیں اور اسے Refresh کر کے ادائیگی کی تصدیق دیکھیں۔\n\nوضاحت: اس وقت ویب سائٹ پر ایک مرتبہ کی Documentation Processing Fee 2,900 روپے درج ہے۔ مفت کورسز کی ٹیوشن فیس نہیں ہے۔ یہ پروسیسنگ فیس ناقابلِ واپسی ہے، البتہ شائع شدہ پالیسی کے مطابق کورس تبدیل یا اگلے بیچ میں منتقل کرنے کی درخواست دی جا سکتی ہے۔\nادائیگی کی رہنمائی: https://pftpedu.org/how-to-pay',
  'پروسیسنگ فیس, 2900, پے پرو, پی ایس آئی ڈی, ایزی پیسہ, جیز کیش, بلز اینڈ پیمنٹس, فیس ادائیگی',
  'PFTP Accounts & Admissions',
  'active'
),
(
  'Fees & Payments',
  'roman_ur',
  'Admission processing fee kaise pay karni hai?',
  'Processing fee kaise jama karein?|PayPro PSID se payment kaise karein?|Kya tuition fee hai?|2900 fee kaise pay karein?|Payment ka tareeqa kya hai?',
  'Admission processing fee pay karne ka tareeqa:\n1. Apna student portal open karein.\n2. Documentation Processing Fee ka challan dhoondein.\n3. Apna PSID note karein.\n4. Apni banking ya mobile wallet app open karein.\n5. Bills & Payments select karein.\n6. PayPro choose karein.\n7. Apna PSID enter karein.\n8. Display hone wali amount verify karein aur payment confirm karein.\n9. PFTP portal par wapas aakar refresh karein aur payment confirmation check karein.\n\nNote: Website par currently one-time Documentation Processing Fee Rs. 2,900 show ho rahi hai. Free courses ki tuition fee nahi hai. Processing fee non-refundable hai, lekin published policy ke mutabiq course transfer ya next batch mein deferment ki request ki ja sakti hai.\nOfficial Payment Guide: https://pftpedu.org/how-to-pay',
  'admission processing fee, 2900, paypro, psid, bills payments, jazzcash, easypaisa, tuition fee free, how to pay',
  'PFTP Accounts & Admissions',
  'active'
),

-- ===================================================================
-- STEP 9: ACCESS STUDENT PORTAL & CLASSES
-- ===================================================================
(
  'Admission Process',
  'en',
  'What happens after my payment is confirmed?',
  'After fee payment what next?|When will I get class access?|Student portal activation|What to do after payment?',
  'Once your payment is confirmed and your portal access is activated, log in to your student portal and follow the instructions provided there for accessing your learning materials and classes.\n\nRegistration Link: https://pftpedu.org/registration\nFAQs: https://pftpedu.org/faqs',
  'after payment confirmed, student portal access, learning materials, classes access, portal activation',
  'PFTP Student Support',
  'active'
),
(
  'Admission Process',
  'ur',
  'میری ادائیگی کی تصدیق کے بعد کیا ہوگا؟',
  'فیس جمع کروانے کے بعد کیا کرنا ہے؟|کلاسز کب شروع ہوں گی؟|پورٹل ایکٹیویشن کے بعد کیا ہوگا؟',
  'جب آپ کی ادائیگی کی تصدیق ہو جائے اور پورٹل فعال ہو جائے تو اپنے اسٹوڈنٹ پورٹل میں لاگ اِن کریں اور وہاں دی گئی ہدایات کے مطابق اپنی کلاسز اور تعلیمی مواد تک رسائی حاصل کریں۔\n\nرجسٹریشن لنک: https://pftpedu.org/registration\nاکثر پوچھے گئے سوالات (FAQs): https://pftpedu.org/faqs',
  'ادائیگی کی تصدیق کے بعد, اسٹوڈنٹ پورٹل, کلاسز تک رسائی, تعلیمی مواد, پورٹل لاگ ان',
  'PFTP Student Support',
  'active'
),
(
  'Admission Process',
  'roman_ur',
  'Meri payment confirm hone ke baad kya hoga?',
  'Fee pay karne ke baad kya karna hoga?|Classes access kab milega?|Portal activate hone ke baad kya karein?',
  'Jab aapki payment confirm ho jaye aur portal activate ho jaye to apne student portal mein login karein aur wahan di gayi instructions ke mutabiq classes aur learning material access karein.\n\nRegistration Link: https://pftpedu.org/registration\nFAQs: https://pftpedu.org/faqs',
  'payment confirm hone ke baad, student portal login, classes access, learning material, portal activate',
  'PFTP Student Support',
  'active'
),

-- ===================================================================
-- COUNSELLOR NEXT-STEP QUESTION & DIRECT ADMISSION LINKS
-- ===================================================================
(
  'Admission Process',
  'en',
  'Would you like me to share the direct admission form link so you can start your registration?',
  'Share admission link|Send registration link|Admission link please|How to register now?|Direct link for admission',
  'Here is the direct link to start your admission:\n👉 https://pftpedu.org/registration\n\nYou can also explore:\n• How to Pay Guide: https://pftpedu.org/how-to-pay\n• Frequently Asked Questions (FAQs): https://pftpedu.org/faqs\n\nLet me know if you need any assistance while filling out the form!',
  'direct admission form link, registration link, start registration, how to pay, faqs, pftpedu.org',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'ur',
  'کیا آپ چاہتے ہیں کہ میں آپ کو داخلہ فارم کا براہِ راست لنک بھیج دوں تاکہ آپ اپنی رجسٹریشن شروع کر سکیں؟',
  'داخلہ فارم کا لنک بھیج دیں|رجسٹریشن لنک چاہیے|ابھی داخلہ لینا ہے|براہ راست لنک',
  'داخلہ شروع کرنے کے لیے براہِ راست لنک درج ذیل ہے:\n👉 https://pftpedu.org/registration\n\nمزید معلومات کے لیے:\n• فیس ادائیگی کی رہنمائی: https://pftpedu.org/how-to-pay\n• اکثر پوچھے جانے والے سوالات (FAQs): https://pftpedu.org/faqs\n\nفارم بھرنے کے دوران اگر کسی رہنمائی کی ضرورت ہو تو ضرور پوچھیں!',
  'براہ راست داخلہ لنک, رجسٹریشن لنک, داخلہ فارم, فیس ادائیگی رہنمائی, اکثر پوچھے گئے سوالات',
  'PFTP Admissions Desk',
  'active'
),
(
  'Admission Process',
  'roman_ur',
  'Kya aap chahte hain ke main aapko admission form ka direct link bhej doon taake aap apni registration start kar sakein?',
  'Admission link bhej dein|Registration link chahiye|Direct admission link|Abhi apply karna hai|Form link please',
  'Admission start karne ke liye ye raha direct link:\n👉 https://pftpedu.org/registration\n\nMazeed official links:\n• How to Pay Guide: https://pftpedu.org/how-to-pay\n• FAQs: https://pftpedu.org/faqs\n\nAgar form fill karte waqt koi bhi confusion ho to zaroor poochein!',
  'direct admission link, registration start, form link, how to pay, faqs, pftpedu.org',
  'PFTP Admissions Desk',
  'active'
);

