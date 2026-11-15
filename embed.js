/**
 * PFTP Universal Embed Script
 * Embed this on ANY website or stack (WordPress, Laravel, React, Django, HTML, etc.)
 * Usage: <script src="http://your-domain/pftp-bot/embed.js" async defer></script>
 */

(function () {
  'use strict';

  if (document.getElementById('chatbot-root')) return;

  // Determine host path dynamically
  let currentScript = document.currentScript;
  if (!currentScript) {
    const scripts = document.getElementsByTagName('script');
    for (let i = scripts.length - 1; i >= 0; i--) {
      if (scripts[i].src && scripts[i].src.indexOf('embed.js') !== -1) {
        currentScript = scripts[i];
        break;
      }
    }
  }

  let origin = '';
  let basePath = '';

  if (currentScript && currentScript.src) {
    const url = new URL(currentScript.src);
    origin = url.origin;
    const path = url.pathname;
    basePath = origin + path.substring(0, path.lastIndexOf('/'));
  } else {
    origin = window.location.origin;
    basePath = window.location.pathname.indexOf('/pftp-bot') !== -1 ? origin + '/pftp-bot' : origin;
  }

  const chatbotPath = basePath + '/chatbot';
  window.PFTP_CHATBOT_BASE_URL = chatbotPath;

  // 1. Inject Google Fonts (Oswald for headings, Asap Condensed for content)
  const fontId = 'pftp-chatbot-fonts';
  if (!document.getElementById(fontId)) {
    const fontLink = document.createElement('link');
    fontLink.id = fontId;
    fontLink.rel = 'stylesheet';
    fontLink.type = 'text/css';
    fontLink.href = 'https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap';
    document.head.appendChild(fontLink);
  }

  // 2. Inject CSS
  const cssId = 'pftp-chatbot-css';
  if (!document.getElementById(cssId)) {
    const link = document.createElement('link');
    link.id = cssId;
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.href = chatbotPath + '/assets/css/chatbot.css';
    document.head.appendChild(link);
  }

  // 2. Inject HTML Widget
  const container = document.createElement('div');
  container.id = 'chatbot-root';
  container.className = 'chatbot-root';
  container.innerHTML = `
    <!-- Floating Launcher -->
    <button id="chatbot-launcher" class="chatbot-launcher" type="button" aria-label="Open PFTP Student Advisor Chat">
      <div class="chatbot-launcher-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
        </svg>
      </div>
      <span id="chatbot-badge" class="chatbot-badge" style="display: none;">1</span>
    </button>

    <!-- Main Chat Window -->
    <div id="chatbot-window" class="chatbot-window" role="dialog" aria-labelledby="chatbot-header-title">
      <!-- Header -->
      <div class="chatbot-header">
        <div class="chatbot-header-left">
          <div class="chatbot-avatar">
            <img src="${chatbotPath}/assets/images/pftp-logo.png" alt="PFTP Logo">
          </div>
          <div class="chatbot-header-info">
            <div id="chatbot-header-title" class="chatbot-header-title">PFTP Course Advisor</div>
            <div id="chatbot-header-subtitle" class="chatbot-header-subtitle">
              <span class="chatbot-online-indicator"></span> Student Counselling & Admissions
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

      <!-- Welcome Registration View -->
      <div id="chatbot-intro-view" class="chatbot-intro-view">
        <div class="chatbot-intro-badge">✨ Live Course Counselling</div>
        <h3 class="chatbot-intro-title">Welcome to PFTP</h3>
        <p class="chatbot-intro-desc">
          Connect directly with our AI Student Advisor to explore our 16 practical courses, check career paths, and plan your enrollment.
        </p>

        <form id="chatbot-reg-form" novalidate>
          <div class="chatbot-form-group">
            <label for="chatbot-name-input" class="chatbot-form-label">Full Name <span>*</span></label>
            <input type="text" id="chatbot-name-input" class="chatbot-form-input" placeholder="e.g. Ali Ahmed" required autocomplete="name">
            <div id="chatbot-name-error" class="chatbot-form-error-text"></div>
          </div>

          <div class="chatbot-form-group">
            <label for="chatbot-email-input" class="chatbot-form-label">Email Address <span>*</span></label>
            <input type="email" id="chatbot-email-input" class="chatbot-form-input" placeholder="e.g. ali@example.com" required autocomplete="email">
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

      <!-- Messages View -->
      <div id="chatbot-messages-view" class="chatbot-messages-view" style="display: none;">
        <div id="chatbot-messages-list" class="chatbot-messages-list" role="log" aria-live="polite"></div>
        <div id="chatbot-typing" class="chatbot-typing">
          <div class="chatbot-typing-dot"></div>
          <div class="chatbot-typing-dot"></div>
          <div class="chatbot-typing-dot"></div>
          <span class="chatbot-typing-text">Advisor is typing...</span>
        </div>
        <div id="chatbot-quick-replies" class="chatbot-quick-replies"></div>
        <div class="chatbot-input-bar">
          <input type="text" id="chatbot-msg-input" class="chatbot-input-field" placeholder="Ask anything about courses or counselling..." autocomplete="off">
          <button id="chatbot-btn-send" class="chatbot-btn-send" type="button" disabled>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
          </button>
        </div>
        <div class="chatbot-disclaimer">
          Course outcomes, jobs and earnings are skill-based and not guaranteed.
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(container);

  // 3. Inject JS
  const jsId = 'pftp-chatbot-js';
  if (!document.getElementById(jsId)) {
    const script = document.createElement('script');
    script.id = jsId;
    script.src = chatbotPath + '/assets/js/chatbot.js';
    script.async = true;
    document.body.appendChild(script);
  }
})();

