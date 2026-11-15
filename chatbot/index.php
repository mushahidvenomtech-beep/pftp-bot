<?php
/**
 * PFTP Website AI Chatbot - Embedded Widget View
 * Can be directly included in any PHP page or loaded via embed.js
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$botName    = htmlspecialchars(getChatbotSetting('bot_name', 'PFTP Course Advisor'), ENT_QUOTES, 'UTF-8');
$botTitle   = htmlspecialchars(getChatbotSetting('bot_title', 'Student Counselling & Admissions'), ENT_QUOTES, 'UTF-8');
$botStatus  = htmlspecialchars(getChatbotSetting('bot_status', 'online'), ENT_QUOTES, 'UTF-8');
$themeColor = htmlspecialchars(getChatbotSetting('theme_color', '#07613F'), ENT_QUOTES, 'UTF-8');

// Determine asset base path relative to the current script execution or server
$chatbotAssetsPath = (function() {
    $scriptDir = str_replace('\\', '/', __DIR__);
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    if ($docRoot !== '' && strpos($scriptDir, $docRoot) === 0) {
        return substr($scriptDir, strlen($docRoot));
    }
    return '/chatbot';
})();
?>
<!-- PFTP Chatbot Fonts: Oswald for headings, Asap Condensed for content -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">

<!-- PFTP Chatbot Stylesheet -->
<link rel="stylesheet" href="<?= $chatbotAssetsPath ?>/assets/css/chatbot.css">

<!-- PFTP Chatbot Widget Root Container -->
<div id="chatbot-root" class="chatbot-root">

  <!-- Floating Launcher Button -->
  <button id="chatbot-launcher" class="chatbot-launcher" type="button" aria-label="Open PFTP Student Advisor Chat">
    <div class="chatbot-launcher-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
      </svg>
    </div>
    <span id="chatbot-badge" class="chatbot-badge" style="display: none;">1</span>
  </button>

  <!-- Main Chatbot Window -->
  <div id="chatbot-window" class="chatbot-window" role="dialog" aria-labelledby="chatbot-header-title">

    <!-- Header -->
    <div class="chatbot-header">
      <div class="chatbot-header-left">
        <div class="chatbot-avatar">
          <img src="<?= $chatbotAssetsPath ?>/assets/images/pftp-logo.png" alt="PFTP Logo">
        </div>
        <div class="chatbot-header-info">
          <div id="chatbot-header-title" class="chatbot-header-title"><?= $botName ?></div>
          <div id="chatbot-header-subtitle" class="chatbot-header-subtitle">
            <span class="chatbot-online-indicator"></span>
            <?= $botTitle ?>
          </div>
        </div>
      </div>
      <div class="chatbot-header-actions">
        <button id="chatbot-btn-expand" class="chatbot-btn-icon" type="button" title="Expand chat" aria-label="Expand chat">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 3 21 3 21 9"></polyline>
            <polyline points="9 21 3 21 3 15"></polyline>
            <line x1="21" y1="3" x2="14" y2="10"></line>
            <line x1="3" y1="21" x2="10" y2="14"></line>
          </svg>
        </button>
        <button id="chatbot-btn-minimize" class="chatbot-btn-icon" type="button" title="Minimize chat" aria-label="Minimize chat">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
        <button id="chatbot-btn-close" class="chatbot-btn-icon" type="button" title="Close chat" aria-label="Close chat">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>

    <!-- View 1: Welcome & Visitor Registration -->
    <div id="chatbot-intro-view" class="chatbot-intro-view">
      <div class="chatbot-intro-badge">✨ Live Course Counselling</div>
      <h3 class="chatbot-intro-title">Welcome to PFTP</h3>
      <p class="chatbot-intro-desc">
        Connect directly with our AI Student Advisor to explore our 16 practical courses, check career paths, and plan your enrollment.
      </p>

      <form id="chatbot-reg-form" novalidate>
        <div class="chatbot-form-group">
          <label for="chatbot-name-input" class="chatbot-form-label">Full Name <span>*</span></label>
          <input
            type="text"
            id="chatbot-name-input"
            class="chatbot-form-input"
            placeholder="e.g. Ali Ahmed"
            required
            autocomplete="name"
          >
          <div id="chatbot-name-error" class="chatbot-form-error-text"></div>
        </div>

        <div class="chatbot-form-group">
          <label for="chatbot-email-input" class="chatbot-form-label">Email Address <span>*</span></label>
          <input
            type="email"
            id="chatbot-email-input"
            class="chatbot-form-input"
            placeholder="e.g. ali@example.com"
            required
            autocomplete="email"
          >
          <div id="chatbot-email-error" class="chatbot-form-error-text"></div>
        </div>

        <button type="submit" id="chatbot-btn-reg-submit" class="chatbot-btn-submit">
          <span>Start Conversation</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="12" x2="19" y2="12"></line>
            <polyline points="12 5 19 12 12 19"></polyline>
          </svg>
        </button>
      </form>
    </div>

    <!-- View 2: Active Conversation Area -->
    <div id="chatbot-messages-view" class="chatbot-messages-view" style="display: none;">
      <!-- Message Bubbles Scroll Area -->
      <div id="chatbot-messages-list" class="chatbot-messages-list" role="log" aria-live="polite"></div>

      <!-- Typing Indicator -->
      <div id="chatbot-typing" class="chatbot-typing">
        <div class="chatbot-typing-dot"></div>
        <div class="chatbot-typing-dot"></div>
        <div class="chatbot-typing-dot"></div>
        <span class="chatbot-typing-text">Advisor is typing...</span>
      </div>

      <!-- Quick Reply Action Chips Carousel -->
      <div id="chatbot-quick-replies" class="chatbot-quick-replies"></div>

      <!-- Input Bar -->
      <div class="chatbot-input-bar">
        <input
          type="text"
          id="chatbot-msg-input"
          class="chatbot-input-field"
          placeholder="Ask anything about courses or counselling..."
          aria-label="Ask your question"
          autocomplete="off"
        >
        <button id="chatbot-btn-send" class="chatbot-btn-send" type="button" title="Send Message" aria-label="Send Message" disabled>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
        </button>
      </div>

      <!-- Tiny Policy Disclaimer -->
      <div class="chatbot-disclaimer">
        Course outcomes, jobs and earnings are skill-based and not guaranteed.
      </div>
    </div>

  </div>

</div>

<!-- PFTP Chatbot JavaScript -->
<script src="<?= $chatbotAssetsPath ?>/assets/js/chatbot.js" defer></script>

