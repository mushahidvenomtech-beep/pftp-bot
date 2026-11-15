# PFTP Dynamic Website AI Chatbot System

A lightweight, high-performance, responsive floating chatbot widget built with **Core PHP**, **Vanilla JavaScript**, and **MySQL**. Designed to be embedded into any website or tech stack (WordPress, Laravel, React, Vue, Python/Django, plain HTML/PHP) with zero external frameworks or paid AI dependencies.

---

## 🌟 Key Features

1. **Floating Chat Launcher**: WhatsApp / Messenger style circular widget at bottom-right corner with smooth attention ripple animation and unread badge.
2. **Two-Stage User Flow**:
   - **Stage 1 (Registration)**: Collects visitor's Full Name and Email Address with client & server-side validation. Generates a secure session token (`bin2hex(random_bytes(32))`) stored in `localStorage` so visitors do not have to re-register on page refresh.
   - **Stage 2 (Main Chat)**: Interactive Messenger-style interface with real-time response rendering, typing indicator, auto-scroll, Enter-to-send, and customizable quick-reply pills.
3. **Dynamic Multilingual Matching Engine**:
   - 16 Complete Courses extracted from official PFTP Student Counselling Guide plus 16 comprehensive qualification-specific counselling pathways, Alternative Course Recommendations with contextual memory, Course Execution & Completion Process, Learning Freelancing & Online Earning guidance, physical classes FAQ, and policy disclaimers (185 total knowledge base records across 27 categories).
   - Trilingual support: **English**, **Urdu (اردو)** with automatic RTL typography, and **Roman Urdu**.
   - Intelligent multi-tiered matching: Exact match &rarr; Question variations &rarr; Weighted TF-IDF keyword overlap &rarr; Fuzzy Levenshtein typo-tolerance.
   - Comprehensive Qualification Counselling: Recommends specialized course roadmaps tailored for Matric Science, Matric Arts, FSc Pre-Medical, FSc Pre-Engineering, FA Arts, ICS, I.Com, B.Com, BBA, BSCS, BS English, BS Psychology, BS Mathematics, BS Media Studies, and Postgraduate degrees.
   - Institutional Policy Enforcement: Enforces Matric minimum eligibility criteria (polite notice for Class 8/9 underage inquiries), handles unavailable course inquiries (e.g. Flutter/React Native), and reinforces skill-based freelancing guidelines without fixed salary/job guarantees.
   - Safe Fallback: Returns polite fallback response when no accurate answer is found and logs the query to `unanswered_queries` for administrator review. Never fabricates answers.
4. **Secure Administration Suite**:
   - Protected by `password_hash()` and session authentication (`/chatbot/admin/`).
   - Visitor & conversation history viewer with full transcript inspection.
   - Knowledge Base CRUD (Create, Read, Update, Delete) with custom question variations, keywords, and possible career paths.
   - Unanswered questions review queue with 1-click "Add to Knowledge Base" feature.
   - Live Settings Manager: Bot identity, availability status (Online / Offline), theme color, welcome message, fallback text, and rate limiter.
5. **Universal Embeddability**:
   - Can be embedded in any stack via a single `<script src=".../embed.js" async defer></script>` tag.
   - All CSS selectors are strictly namespaced with the `chatbot-` prefix and wrapped in isolated containers to prevent host CSS conflicts.
   - Zero external paid AI APIs. Completely self-hosted on your own PHP & MySQL server.

---

## 📁 Project File & Directory Structure

```text
pftp-bot/
│
├── .htaccess                 # Apache rewrite rules for clean URLs (/login, /dashboard, /logout)
├── login.php                 # Dedicated administrator login portal (/pftp-bot/login)
├── dashboard.php             # Dedicated administrator dashboard portal (/pftp-bot/dashboard)
├── logout.php                # Dedicated logout handler (/pftp-bot/logout)
├── index.php                 # Live demo website showcasing chatbot & courses
├── embed.js                  # Universal 1-line embedding script for any stack
│
└── chatbot/
    ├── index.php             # Native PHP widget template (direct include)
    │
    ├── ai/                   # Modular Intelligent Query Understanding & Analysis Engine
    │   ├── context-manager.php     # MySQL-backed conversation state & pronoun resolution
    │   ├── intent-detector.php     # Intent classifier, entity extractor & jailbreak defense
    │   ├── query-analyzer.php      # Pipeline orchestrator & validated structured analyzer
    │   ├── knowledge-retriever.php # Grounded multi-tier KB search & fact retriever
    │   ├── response-generator.php  # Decision engine (8 response types) & fluent synthesizer
    │   └── ai-client.php           # AI gateway with instant native fallback
    │
    ├── config/
    │   └── database.php      # PDO database connection & settings loader
    │
    ├── api/
    │   ├── register.php          # Visitor registration & session generation
    │   ├── send-message.php      # Message receiver, NLP matcher & bot response
    │   ├── get-messages.php      # Conversation message history loader
    │   ├── check-session.php     # Session validation & initial data provider
    │   └── knowledge-search.php  # Knowledge base search & category list API
    │
    ├── includes/
    │   ├── session.php           # Session token & visitor management
    │   ├── validation.php        # Input validation, sanitization & rate limiter
    │   ├── response.php          # JSON response & CORS handler
    │   ├── matching_engine.php   # NLP, fuzzy & keyword matching algorithm
    │   └── auth.php              # Admin session authentication & guards
    │
    ├── assets/
    │   ├── css/
    │   │   └── chatbot.css       # Scoped widget styling & mobile responsiveness
    │   ├── js/
    │   │   └── chatbot.js        # Vanilla JS widget controller & state manager
    │   └── images/
    │       ├── chatbot-icon.svg  # Floating launcher SVG icon
    │       └── bot-avatar.svg    # Advisor robot avatar SVG
    │
    ├── database/
    │   ├── schema.sql            # Complete MySQL tables & default admin/settings
    │   └── knowledge-base.sql    # 16 PFTP courses in English, Urdu & Roman Urdu
    │
    ├── admin/
    │   ├── index.php             # Legacy redirector to /pftp-bot/dashboard
    │   ├── login.php             # Legacy redirector to /pftp-bot/login
    │   ├── logout.php            # Secure logout handler (redirects to /pftp-bot/login)
    │   ├── sidebar.php           # Shared admin navigation sidebar
    │   ├── conversations.php     # Visitor conversations list & chat inspector
    │   ├── knowledge-base.php    # Complete Query & Q&A Management System (CRUD, Search, Filters, Bulk, Export, Import)
    │   ├── qa-service.php        # Service layer: OpenXML .xlsx/.docx generator, SQL backup, import parser & CRUD logic
    │   ├── unanswered.php        # Unanswered questions queue & 1-click KB add
    │   ├── settings.php          # Bot settings, online status & theme manager
    │   └── assets/
    │       ├── css/admin.css     # Clean responsive admin dashboard styles & toast notifications
    │       └── js/admin.js       # Admin UI interactions
    │
    └── README.md                 # System documentation & deployment guide
```

---

## 🗄️ Database Architecture

The system utilizes a UTF-8 compatible (`utf8mb4_unicode_ci`) MySQL database named `pftp_chatbot` comprising 8 relational tables:

1. **`visitors`**: Stores visitor full names, email addresses, phone numbers, IP addresses, user agents, and registration timestamps.
2. **`conversations`**: Associates visitors with a unique 64-character cryptographically secure `session_token`, tracking conversation status (`active`, `closed`).
3. **`messages`**: Stores conversation transcripts with `sender_type` (`visitor`, `bot`, `admin`), message content, timestamp, `matched_kb_id`, and `is_fallback` indicator.
4. **`conversation_context`**: Relational memory tracking `current_topic`, `last_mentioned_course`, `user_education_status`, `last_intent`, `pending_clarification`, and JSON state (`discussed_courses`, `recent_entities`) per conversation.
5. **`knowledge_base`**: Central Q&A repository with fields for `category`, `language` (`en`, `ur`, `roman_ur`), `question`, `question_variations` (pipe-separated), `answer`, `keywords` (comma-separated), `possible_careers` (semicolon-separated), `status` (`active`, `inactive`), and `views_count`.
6. **`chatbot_settings`**: Key-value settings table controlling bot name, bot title, online status, theme color, welcome message, fallback text, rate limits, contact details, and disclaimers.
7. **`admins`**: Administrator accounts protected with Bcrypt password hashes (`password_hash()`), email, role, and last login timestamps.
8. **`unanswered_queries`**: Automatic log table recording queries where no match was found, enabling administrators to review and expand the knowledge base.

---

## 🤖 Intelligent Query Understanding & Self-Analysis Engine (`chatbot/ai/`)

Every incoming message is passed through a 5-stage intelligent processing pipeline:

```text
Visitor Query ──► [Stage 1: ContextManager] (Resolves pronouns "its", "uski" from MySQL memory)
                       │
                       ▼
                  [Stage 2: QueryAnalyzer & IntentDetector]
                  (Detects intent, entities, language, detects manipulation/prompt injection)
                       │
                       ▼
                  [Stage 3: KnowledgeRetriever] (Multi-tier grounded search: exact, TF-IDF, typo)
                       │
                       ▼
                  [Stage 4: ResponseGenerator] (Decision Engine: 8 response types, conversational tone)
                       │
                       ▼
                  [Stage 5: AiClient Gateway] (External LLM support with instant native self-hosted fallback)
```

### 1. Decision-Making Engine (8 Response Types)
- **`direct_answer`**: Grounded, concise response from verified knowledge base.
- **`detailed_explanation`**: Extended syllabus, overview, and possible career opportunities.
- **`recommendation`**: Tailored course recommendations based on student's education level or interests.
- **`clarification`**: Triggered when a pronoun or follow-up query is ambiguous (e.g. asking "What is its duration?" before any course is mentioned).
- **`follow_up`**: Proactive counseling follow-ups (e.g. asking for semester, degree, or background).
- **`unavailable_info`**: Honest, polite refusal when verified data is missing. Never hallucinates facts.
- **`human_assistance`**: Direct contact information and office hours for live admissions staff.
- **`manipulation_defense`**: Firm, polite defense that deflects prompt injection or jailbreak attempts.

### 2. Strict Knowledge Grounding Standards
- Verified knowledge entries from institutional documents are the sole source of truth.
- Fees, durations, course names, and policies are never hallucinated.
- Low-confidence or ungrounded queries are logged to `unanswered_queries` for administrator review.

---

## 🧠 Knowledge Base: 16 PFTP Courses, Admission Flow, Company Overview, Education Status Discovery, Course Inquiries & Generic Greetings (118 Total Entries)

The knowledge base is fully populated with the 16 courses from the **PFTP Student Counselling Guide**, the complete **9-Step Official Admission & Registration Guide**, and **Generic Greetings with Dynamic Personalization**:

### A. 16 Course Counselling Modules
| # | Course Title | Category | Languages Included | Possible Career Directions |
|---|---|---|---|---|
| 1 | Digital Marketing | Marketing | EN, UR, Roman Urdu | Digital Marketing Executive, SMM, Meta/Google Ads Specialist |
| 2 | Freelancing | Career | EN, UR, Roman Urdu | Freelance Service Provider, Virtual Assistant, Upwork/Fiverr |
| 3 | Graphic Design | Creative | EN, UR, Roman Urdu | Graphic Designer, Logo Designer, Brand Identity Designer |
| 4 | Video Editing | Media | EN, UR, Roman Urdu | Video Editor, YouTube Video Editor, Reels/Shorts Editor |
| 5 | YouTube Automation | Media | EN, UR, Roman Urdu | Faceless Channel Creator, Scriptwriter, Thumbnail Designer |
| 6 | Content Writing | Writing | EN, UR, Roman Urdu | Content Writer, Blog Writer, Copywriter, SEO Writer |
| 7 | SEO | Marketing | EN, UR, Roman Urdu | SEO Specialist, Technical SEO Assistant, Content Strategist |
| 8 | Full Stack Web Dev | Tech | EN, UR, Roman Urdu | Frontend/Backend Developer, Full Stack, React, Node.js |
| 9 | Artificial Intelligence | Tech | EN, UR, Roman Urdu | Prompt Designer, AI Tools Specialist, AI Workflow Builder |
| 10 | Cyber Security | Security | EN, UR, Roman Urdu | Cyber Security Analyst, SOC Analyst, Network Technician |
| 11 | Data Science | Tech | EN, UR, Roman Urdu | Data Analyst, Junior Data Scientist, BI Analyst, Reporting |
| 12 | Python Programming | Tech | EN, UR, Roman Urdu | Python Developer, Automation Developer, Backend Developer |
| 13 | E-Commerce | Business | EN, UR, Roman Urdu | Shopify Store Manager, Product Listing, Marketplace Manager |
| 14 | QuickBooks | Finance | EN, UR, Roman Urdu | Bookkeeping Assistant, QuickBooks Operator, Accounts Assistant |
| 15 | English Language | Communication | EN, UR, Roman Urdu | Customer Support, Workplace Communication, Interview Skills |
| 16 | IELTS Preparation | Test Prep | EN, UR, Roman Urdu | Study Abroad Planning, Immigration, Academic English |

### B. Complete 9-Step Admission & Registration Guide (Trilingual)
- **Step 1**: Where should I go to apply for admission? (Visit `pftpedu.org` & click Apply Now)
- **Step 2**: Open the admission form (`https://pftpedu.org/registration`)
- **Step 3**: Fill in personal information (Name, Father's Name, 13-digit CNIC/B-Form without dashes, Email, Phone, DOB, Gender, Qualification)
- **Step 4**: Select desired course (Program dropdown, Add Another Course up to 5 courses, Promo Code)
- **Step 5**: Enter residential location (Province, City, Street address)
- **Step 6**: Create student account (8+ char password, Confirm password, Terms & WhatsApp SOP agreement)
- **Step 7**: Submit admission application (Submit Application button & Challan/PSID generation)
- **Step 8**: Pay Documentation Processing Fee (Rs. 2,900 one-time fee via PayPro/PSID in JazzCash, Easypaisa, Banking Apps; Free courses have Rs. 0 tuition fee; `https://pftpedu.org/how-to-pay`)
- **Step 9**: Access student portal & classes after payment confirmation (`https://pftpedu.org/faqs`)
- **Direct Links & Counsellor Follow-up**: Proactively provides direct admission and guide links in English, Urdu, and Roman Urdu.

*Plus specialized counseling Q&As: Student Discovery (Education background), Full Course Directory, Course Fee & Duration guidance, PFTP Verified Certificates, Contact Helplines, and Policy Disclaimers on Job & Earning expectations.*

---

## 🔒 Security Implementations

- **SQL Injection Prevention**: All database interactions use PDO with prepared statements and parameter binding (`PDO::ATTR_EMULATE_PREPARES => false`).
- **Cross-Site Scripting (XSS)**: All user and database outputs are escaped using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. Client-side message rendering encodes characters before converting formatted linebreaks/bold markup.
- **Server-Side Validation**: All inputs (names, email addresses, message text lengths) are strictly validated on the PHP server before database storage.
- **Rate Limiting**: Built-in sliding rate-limiter prevents flooding or abuse (configurable in settings, default: max 12 queries/minute per session).
- **Session Protection**: Cryptographically secure 64-character hex tokens generated with `random_bytes(32)`. Administrator sessions use `session_regenerate_id(true)`, `HttpOnly` and `SameSite` cookie flags.
- **No Hardcoded Credentials**: Database credentials are read from environment variables or `config/database.php`. Passwords use standard PHP `password_hash()`.

---

## 📱 Multi-Screen Responsive Architecture

The chatbot widget is engineered to be globally embeddable across any device without host CSS or viewport collisions:

| Screen Type | Viewport Breakpoint | Behavior & Layout |
|:---|:---|:---|
| **Mobile Phones** | `width <= 640px` | Opens as a dedicated full-viewport web application using dynamic `100dvh`, iOS safe-area notch & home-indicator padding, `16px` form inputs (strictly prevents unwanted iOS Safari zoom), and background body scroll locking (`.chatbot-body-locked`). |
| **Tablets & Foldables** | `641px - 1024px` | Sits elegantly in the bottom-right corner with 360px adaptive width, touch gesture support, and boundary enforcement. |
| **Desktops & Ultrawide** | `> 1024px` | Floating launcher with circular attention pulse animation, 380px × 590px chat card, smooth translateY scale entrance, and `z-index: 999999` to sit cleanly above any host navbar, fixed banners, or footers. |

---

## 🔌 Integration Instructions (Any Website or Stack)

### Option A: Universal JavaScript Embed (Any Stack)
Paste this snippet immediately before the closing `</body>` tag of your website:

```html
<!-- PFTP Floating AI Chatbot Widget -->
<script src="http://your-domain.com/pftp-bot/embed.js" async defer></script>
```

### Option B: Direct PHP Include (Same Server / cPanel)
If your existing website is written in PHP and hosted on the same server:

```php
<!-- Include Chatbot Widget -->
<?php include_once __DIR__ . '/chatbot/index.php'; ?>
```

---

## 💻 Local Testing & Setup (XAMPP / Apache)

1. **Verify Location**:
   Place the project folder in your Apache document root:
   `c:\xampp\htdocs\pftp-bot\`
2. **Start Services**:
   Start **Apache** and **MySQL** in your XAMPP Control Panel.
3. **Database Setup**:
   Open a terminal or phpMyAdmin and import the two SQL files:
   ```powershell
   mysql -u root -e "source c:/xampp/htdocs/pftp-bot/chatbot/database/schema.sql"
   mysql -u root -e "source c:/xampp/htdocs/pftp-bot/chatbot/database/knowledge-base.sql"
   ```
4. **Access the Demo Website**:
   Open your browser to:
   [http://localhost/pftp-bot/index.php](http://localhost/pftp-bot/index.php)
5. **Access the Admin Portal**:
   [http://localhost/pftp-bot/chatbot/admin/login.php](http://localhost/pftp-bot/chatbot/admin/login.php)
   - **Default Username**: `admin`
   - **Default Password**: `AdminPassword123!`

---

## 🚀 cPanel / Live Server Deployment

1. **Upload Files**: Upload the entire `pftp-bot` directory (or its contents) to your cPanel `public_html` via FTP or cPanel File Manager.
2. **Create Database**:
   - In cPanel, go to **MySQL Databases**.
   - Create a new database (e.g. `youruser_pftp_chatbot`).
   - Create a MySQL user and grant **All Privileges** to the database.
3. **Import SQL**:
   - Open **phpMyAdmin** in cPanel.
   - Select your database and import `schema.sql`, then import `knowledge-base.sql`.
4. **Configure Database Connection**:
   - Open `chatbot/config/database.php` and set your credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'youruser_pftp_chatbot');
     define('DB_USER', 'youruser_dbuser');
     define('DB_PASS', 'your_secure_password');
     ```
5. **Change Default Admin Password**:
   - Log into `/chatbot/admin/login.php` with default credentials and update your password.

---

## 🧪 Automated Verification Suite (105 + 11 Tests)

An end-to-end regression and verification suite is included in `tests/test_e2e.php` and `tests/test_query_management.php` verifying:
- Visitor registration, session isolation, and token security
- Natural language query understanding and typo tolerance
- Trilingual query matching across English, Roman Urdu, and Urdu script (RTL)
- Context-aware multi-turn conversations and pronoun resolution
- 16 Qualification-specific counselling scenarios and career pathways
- Alternative Course Recommendations & dynamic contextual personalization
- Disinterest, unsuitability, and simpler explanation queries ("not interested", "explain in simple words", "samajh nahi aa raha")
- Course Execution, Training & Completion Process (11-Stage Student Journey)
- Learning Freelancing and Online Earning guidance (Intent: `freelancing_and_online_earning_guidance`)
- Underage enrollment rules, unavailable course inquiries, and earnings disclaimers
- Physical classes / 100% online policy inquiries
- Multi-visitor session isolation and SQL/prompt injection defenses
- Complete Query Management CRUD: Trilingual creation, editing, soft/permanent deletion, and instant bot synchronization
- Genuine OpenXML Excel (`.xlsx`) export with Unicode text and formula injection defense
- Genuine OpenXML Word (`.docx`) export with RTL Nastaliq Urdu formatting
- Bulk actions: Copy selected, bulk status updates, bulk category movement, and bulk deletion
- Import validation (`.xlsx` & `.csv`) and 1-click database SQL backup

Run the test suites from PowerShell:
```powershell
php tests/test_e2e.php
php tests/test_query_management.php
```

---

## 📄 External Dependencies and Licensing Notice

This software is **100% self-hosted, independent, and free of proprietary or paid third-party AI APIs** (no OpenAI, Anthropic, or external cloud API costs).

- **Google Fonts (Plus Jakarta Sans)**: Open Font License (OFL), loaded purely for the demo showcase page.
- **Core Technology**: 100% Native HTML5, CSS3, Vanilla ECMAScript, Core PHP (>= 7.4 / 8.x), and MySQL (>= 5.7 / MariaDB).
- **Licensing**: Free for commercial and non-commercial integration on PFTP platforms.
