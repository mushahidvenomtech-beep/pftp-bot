/**
 * PFTP Website AI Chatbot - Client JavaScript
 * Isolated Vanilla JS with zero external dependencies.
 */

(function () {
  'use strict';

  // Prevent multiple initializations
  if (window.PFTPChatbotInitialized) return;
  window.PFTPChatbotInitialized = true;

  // Auto-detect base API path dynamically
  let scriptTag = document.currentScript;
  if (!scriptTag) {
    const scripts = document.getElementsByTagName('script');
    for (let i = scripts.length - 1; i >= 0; i--) {
      if (scripts[i].src && scripts[i].src.indexOf('chatbot.js') !== -1) {
        scriptTag = scripts[i];
        break;
      }
    }
  }

  let basePath = '';
  if (scriptTag && scriptTag.src) {
    const url = new URL(scriptTag.src);
    // Find the /chatbot/ root
    const idx = url.pathname.indexOf('/assets/js/');
    if (idx !== -1) {
      basePath = url.origin + url.pathname.substring(0, idx);
    } else {
      basePath = url.origin + (url.pathname.indexOf('/pftp-bot') !== -1 ? '/pftp-bot/chatbot' : '/chatbot');
    }
  } else {
    basePath = window.PFTP_CHATBOT_BASE_URL || (window.location.pathname.indexOf('/pftp-bot') !== -1 ? window.location.origin + '/pftp-bot/chatbot' : window.location.origin + '/chatbot');
  }

  const STORAGE_KEY = 'pftp_chatbot_session_token';
  const STORAGE_VISITOR = 'pftp_chatbot_visitor_data';

  // State
  const state = {
    isOpen: false,
    sessionToken: null,
    visitor: null,
    isSubmitting: false,
    settings: {
      bot_name: 'PFTP Course Advisor',
      bot_title: 'Student Counselling & Admissions',
      theme_color: '#0f766e'
    }
  };

  // DOM Elements cache
  let dom = {};

  function initChatbot() {
    cacheDom();
    bindEvents();
    // Default on each window load: clear conversation and show intro form (Name & Email)
    resetToDefaultView();
    // Fetch bot settings and quick reply definitions
    loadChatbotConfig();
  }

  function cacheDom() {
    dom = {
      root: document.getElementById('chatbot-root'),
      launcher: document.getElementById('chatbot-launcher'),
      badge: document.getElementById('chatbot-badge'),
      window: document.getElementById('chatbot-window'),
      btnExpand: document.getElementById('chatbot-btn-expand'),
      btnMinimize: document.getElementById('chatbot-btn-minimize'),
      btnClose: document.getElementById('chatbot-btn-close'),
      introView: document.getElementById('chatbot-intro-view'),
      messagesView: document.getElementById('chatbot-messages-view'),
      regForm: document.getElementById('chatbot-reg-form'),
      nameInput: document.getElementById('chatbot-name-input'),
      emailInput: document.getElementById('chatbot-email-input'),
      btnRegSubmit: document.getElementById('chatbot-btn-reg-submit'),
      nameError: document.getElementById('chatbot-name-error'),
      emailError: document.getElementById('chatbot-email-error'),
      messagesList: document.getElementById('chatbot-messages-list'),
      quickReplies: document.getElementById('chatbot-quick-replies'),
      typingIndicator: document.getElementById('chatbot-typing'),
      msgInput: document.getElementById('chatbot-msg-input'),
      btnSend: document.getElementById('chatbot-btn-send'),
      headerTitle: document.getElementById('chatbot-header-title'),
      headerSubtitle: document.getElementById('chatbot-header-subtitle')
    };
  }

  function bindEvents() {
    if (dom.launcher) {
      dom.launcher.addEventListener('click', toggleChat);
    }
    if (dom.btnExpand) {
      dom.btnExpand.addEventListener('click', toggleExpand);
    }
    if (dom.btnMinimize) {
      dom.btnMinimize.addEventListener('click', closeChat);
    }
    if (dom.btnClose) {
      dom.btnClose.addEventListener('click', closeChat);
    }

    // Registration submission
    if (dom.regForm) {
      dom.regForm.addEventListener('submit', handleRegistration);
    }

    // Chat message send
    if (dom.btnSend) {
      dom.btnSend.addEventListener('click', handleSendMessage);
    }

    if (dom.msgInput) {
      dom.msgInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          handleSendMessage();
        }
      });
      dom.msgInput.addEventListener('input', function () {
        if (dom.btnSend) {
          dom.btnSend.disabled = dom.msgInput.value.trim().length === 0;
        }
      });
    }

    // Quick replies delegation
    if (dom.quickReplies) {
      dom.quickReplies.addEventListener('click', function (e) {
        const pill = e.target.closest('.chatbot-pill');
        if (pill) {
          const query = pill.getAttribute('data-query');
          if (query) {
            sendUserQuery(query);
          }
        }
      });
    }
  }

  function toggleChat() {
    if (state.isOpen) {
      closeChat();
    } else {
      openChat();
    }
  }

  function openChat() {
    state.isOpen = true;
    if (dom.window) dom.window.classList.add('chatbot-active');
    if (dom.badge) dom.badge.style.display = 'none';

    // Lock host page background scroll on mobile devices
    if (window.innerWidth <= 640) {
      document.body.classList.add('chatbot-body-locked');
    }

    // Focus appropriate input
    setTimeout(() => {
      if (state.sessionToken && dom.messagesView && dom.messagesView.style.display !== 'none' && dom.msgInput) {
        dom.msgInput.focus();
        scrollToBottom();
      } else if (dom.nameInput) {
        dom.nameInput.focus();
      }
    }, 250);
  }

  function closeChat() {
    state.isOpen = false;
    if (dom.window) {
      dom.window.classList.remove('chatbot-active');
      dom.window.classList.remove('chatbot-expanded');
    }
    document.body.classList.remove('chatbot-body-locked');
  }

  function toggleExpand() {
    if (!dom.window) return;
    const isExpanded = dom.window.classList.toggle('chatbot-expanded');
    if (dom.btnExpand) {
      dom.btnExpand.title = isExpanded ? 'Collapse chat' : 'Expand chat';
      dom.btnExpand.setAttribute('aria-label', isExpanded ? 'Collapse chat' : 'Expand chat');
      dom.btnExpand.innerHTML = isExpanded
        ? `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="4 14 10 14 10 20"></polyline>
            <polyline points="20 10 14 10 14 4"></polyline>
            <line x1="14" y1="10" x2="21" y2="3"></line>
            <line x1="3" y1="21" x2="10" y2="14"></line>
           </svg>`
        : `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 3 21 3 21 9"></polyline>
            <polyline points="9 21 3 21 3 15"></polyline>
            <line x1="21" y1="3" x2="14" y2="10"></line>
            <line x1="3" y1="21" x2="10" y2="14"></line>
           </svg>`;
    }
    setTimeout(scrollToBottom, 200);
  }

  // Handle window resize / orientation change
  window.addEventListener('resize', function () {
    if (state.isOpen) {
      if (window.innerWidth <= 640) {
        document.body.classList.add('chatbot-body-locked');
      } else {
        document.body.classList.remove('chatbot-body-locked');
      }
    }
  });

  /**
   * Reset conversation state on window load to ensure default view asks for Name & Email
   */
  function resetToDefaultView() {
    state.sessionToken = null;
    state.visitor = null;
    try {
      localStorage.removeItem(STORAGE_KEY);
      localStorage.removeItem(STORAGE_VISITOR);
    } catch (e) {}

    if (dom.messagesList) {
      dom.messagesList.innerHTML = '';
    }
    if (dom.nameInput) {
      dom.nameInput.value = '';
      dom.nameInput.classList.remove('chatbot-input-error');
    }
    if (dom.emailInput) {
      dom.emailInput.value = '';
      dom.emailInput.classList.remove('chatbot-input-error');
    }
    if (dom.nameError) {
      dom.nameError.textContent = '';
    }
    if (dom.emailError) {
      dom.emailError.textContent = '';
    }

    showIntroView();
  }

  /**
   * Load general chatbot configuration & quick replies from server
   */
  async function loadChatbotConfig() {
    try {
      const url = basePath + '/api/check-session.php';
      const res = await fetch(url);
      const data = await res.json();

      if (data.success && data.data) {
        if (data.data.settings) {
          applySettings(data.data.settings);
        }
        if (data.data.quick_replies) {
          renderQuickReplies(data.data.quick_replies);
        }
      }
    } catch (err) {
      console.warn('[Chatbot Config Load]', err);
    }
  }

  function applySettings(settings) {
    state.settings = Object.assign(state.settings, settings);
    if (dom.headerTitle && settings.bot_name) {
      dom.headerTitle.textContent = settings.bot_name;
    }
    if (dom.headerSubtitle && settings.bot_title) {
      dom.headerSubtitle.innerHTML = `<span class="chatbot-online-indicator"></span> ${escapeHtml(settings.bot_title)}`;
    }
  }

  function showIntroView() {
    if (dom.introView) dom.introView.style.display = 'flex';
    if (dom.messagesView) dom.messagesView.style.display = 'none';
  }

  function showMessagesView() {
    if (dom.introView) dom.introView.style.display = 'none';
    if (dom.messagesView) dom.messagesView.style.display = 'flex';
    scrollToBottom();
  }

  /**
   * Handle Registration Form
   */
  async function handleRegistration(e) {
    e.preventDefault();

    const name = dom.nameInput.value.trim();
    const email = dom.emailInput.value.trim();

    let hasError = false;

    // Reset errors
    dom.nameInput.classList.remove('chatbot-input-error');
    dom.emailInput.classList.remove('chatbot-input-error');

    if (!name || name.length < 2) {
      dom.nameInput.classList.add('chatbot-input-error');
      dom.nameError.textContent = 'Please enter your full name (minimum 2 characters).';
      hasError = true;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email || !emailRegex.test(email)) {
      dom.emailInput.classList.add('chatbot-input-error');
      dom.emailError.textContent = 'Please provide a valid email address.';
      hasError = true;
    }

    if (hasError) return;

    // Loading state
    dom.btnRegSubmit.disabled = true;
    const originalText = dom.btnRegSubmit.innerHTML;
    dom.btnRegSubmit.innerHTML = `<span class="chatbot-spinner"></span> Connecting...`;

    try {
      const res = await fetch(basePath + '/api/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ full_name: name, email: email })
      });
      const data = await res.json();

      if (data.success && data.data) {
        state.sessionToken = data.data.session_token;
        state.visitor = data.data.visitor;
        try {
          localStorage.setItem(STORAGE_KEY, state.sessionToken);
          localStorage.setItem(STORAGE_VISITOR, JSON.stringify(state.visitor));
        } catch (e) {}

        if (data.data.quick_replies) {
          renderQuickReplies(data.data.quick_replies);
        }

        // Switch to the chat messages view
        showMessagesView();

        // Clear existing DOM messages list first
        if (dom.messagesList) {
          dom.messagesList.innerHTML = '';
        }

        // Render conversation: old conversation if existing visitor, or new blank window if fresh visitor
        if (data.data.messages && data.data.messages.length > 0) {
          renderMessageHistory(data.data.messages);
        } else {
          fetchMessages();
        }

        if (dom.msgInput) {
          dom.msgInput.focus();
        }
      } else {
        alert(data.error || 'Registration failed. Please try again.');
      }
    } catch (err) {
      console.error('[Registration Error]', err);
      alert('Unable to connect to server. Please check your internet connection.');
    } finally {
      dom.btnRegSubmit.disabled = false;
      dom.btnRegSubmit.innerHTML = originalText;
    }
  }

  /**
   * Fetch message history
   */
  async function fetchMessages() {
    if (!state.sessionToken) return;

    try {
      const res = await fetch(basePath + '/api/get-messages.php?session_token=' + encodeURIComponent(state.sessionToken), {
        headers: { 'X-Session-Token': state.sessionToken }
      });
      const data = await res.json();

      if (data.success && data.data && data.data.messages) {
        renderMessageHistory(data.data.messages);
      }
    } catch (err) {
      console.error('[Fetch Messages Error]', err);
    }
  }

  function renderMessageHistory(messages) {
    if (!dom.messagesList) return;
    dom.messagesList.innerHTML = '';

    messages.forEach(msg => {
      appendMessageToDOM(msg.sender_type, msg.message, {
        time: formatTime(msg.created_at),
        careers: msg.possible_careers,
        isFallback: !!msg.is_fallback
      });
    });

    scrollToBottom();
  }

  /**
   * Handle user sending a message via input
   */
  function handleSendMessage() {
    if (!dom.msgInput) return;
    const text = dom.msgInput.value.trim();
    if (!text || state.isSubmitting) return;

    sendUserQuery(text);
    dom.msgInput.value = '';
    if (dom.btnSend) dom.btnSend.disabled = true;
  }

  /**
   * Core function to send user message to PHP backend
   */
  async function sendUserQuery(text) {
    if (!state.sessionToken) {
      openChat();
      showIntroView();
      return;
    }

    // Append user message immediately
    appendMessageToDOM('visitor', text, { time: 'Just now' });
    scrollToBottom();

    // Show typing indicator
    showTyping(true);
    state.isSubmitting = true;
    if (dom.btnSend) dom.btnSend.disabled = true;

    try {
      const res = await fetch(basePath + '/api/send-message.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-Token': state.sessionToken
        },
        body: JSON.stringify({
          session_token: state.sessionToken,
          message: text
        })
      });
      const data = await res.json();

      // Realistic typing delay (350ms - 500ms)
      await new Promise(r => setTimeout(r, 400));

      showTyping(false);

      if (data && data.success && data.data) {
        const bot = data.data.bot_response || data.data.bot_message || data.data;
        if (bot && bot.message) {
          appendMessageToDOM('bot', bot.message, {
            time: formatTime(bot.created_at) || 'Just now',
            careers: bot.possible_careers,
            isFallback: !!bot.is_fallback
          });
        } else {
          const fallback = getClientSideFallback(text);
          appendMessageToDOM('bot', fallback.message, {
            time: 'Just now',
            careers: fallback.careers
          });
        }
      } else if (res.status === 401) {
        // Session expired
        state.sessionToken = null;
        localStorage.removeItem(STORAGE_KEY);
        showIntroView();
        alert('Your conversation session has expired. Please enter your name to start a new chat.');
      } else {
        const fallback = getClientSideFallback(text);
        appendMessageToDOM('bot', fallback.message, {
          time: 'Just now',
          careers: fallback.careers
        });
      }
    } catch (err) {
      showTyping(false);
      console.warn('[Send Message Network Fallback]', err);
      const fallback = getClientSideFallback(text);
      appendMessageToDOM('bot', fallback.message, {
        time: 'Just now',
        careers: fallback.careers
      });
    } finally {
      state.isSubmitting = false;
      if (dom.msgInput) {
        dom.msgInput.disabled = false;
        dom.msgInput.focus();
      }
      if (dom.btnSend) {
        dom.btnSend.disabled = dom.msgInput ? dom.msgInput.value.trim().length === 0 : true;
      }
      scrollToBottom();
    }
  }

  /**
   * Client-side fallback responder to guarantee zero-freeze conversation
   */
  function getClientSideFallback(query) {
    const q = (query || '').toLowerCase().trim();

    if (q.includes('apply') || q.includes('admission') || q.includes('register') || q.includes('داخلہ') || q.includes('registration') || q.includes('enroll')) {
      return {
        message: "To apply for admission at PFTP, visit our official admissions portal at https://pftpedu.org. Click on **Apply Now**, choose your course from the 16 available tracks, fill in your details, and submit your application.",
        careers: "Full Stack Developer; AI Specialist; Digital Marketer; Freelancer"
      };
    }
    if (q.includes('fee') || q.includes('pay') || q.includes('cost') || q.includes('فیس') || q.includes('payment') || q.includes('challan')) {
      return {
        message: "PFTP maintains subsidized, affordable admission processing fees. You can pay securely via Bank Transfer, JazzCash, or Easypaisa after submitting your application on https://pftpedu.org.",
        careers: null
      };
    }
    if (q.includes('python')) {
      return {
        message: "Python is widely used in software development, automation, data science, and Artificial Intelligence. Learning Python provides an excellent foundation to build careers in programming and data engineering.",
        careers: "Python Developer; Junior Software Developer; Automation Engineer; Data Analyst"
      };
    }
    if (q.includes('web') || q.includes('full stack') || q.includes('html') || q.includes('frontend') || q.includes('backend')) {
      return {
        message: "The Full Stack Web Development course covers HTML5, CSS3, JavaScript, PHP, MySQL, and modern front-end & back-end tools for building responsive commercial websites and web apps.",
        careers: "Full Stack Web Developer; Frontend Developer; Backend Developer; Freelance Web Master"
      };
    }
    if (q.includes('ai') || q.includes('artificial intelligence') || q.includes('machine learning')) {
      return {
        message: "The Artificial Intelligence (AI) course introduces modern neural architectures, machine learning models, computer vision, and prompt engineering for real-world automation.",
        careers: "AI Specialist; Prompt Engineer; Machine Learning Associate"
      };
    }
    if (q.includes('marketing') || q.includes('digital') || q.includes('seo') || q.includes('ads')) {
      return {
        message: "The Digital Marketing course teaches social media marketing (Meta, Instagram), Google Ads, SEO, and lead generation funnels for client acquisition.",
        careers: "Digital Marketing Specialist; Social Media Manager; Performance Marketer"
      };
    }
    if (q.includes('graphic') || q.includes('design') || q.includes('photoshop') || q.includes('illustrator')) {
      return {
        message: "Graphic Design covers visual identity, branding, Adobe Photoshop, Illustrator, and typography to create high-impact marketing assets and freelancing portfolios.",
        careers: "Graphic Designer; Visual Identity Artist; UI Designer"
      };
    }
    if (q.includes('course') || q.includes('program') || q.includes('تمام کورسز') || q.includes('offer')) {
      return {
        message: "PFTP offers 16 specialized courses including Full Stack Web Development, Artificial Intelligence (AI), Python Programming, Graphic Design, Digital Marketing, Freelancing & Upwork Mastery, Cyber Security, Mobile App Development, UI/UX Design, and English Language Communication.",
        careers: "Web Development; AI & Data Science; Digital Marketing; Freelancing"
      };
    }
    if (q.includes('guarantee') || q.includes('job') || q.includes('نوکری') || q.includes('placement')) {
      return {
        message: "PFTP focuses on practical hands-on skill mastery, freelancing profile optimization, and portfolio building. While no institution can legally guarantee jobs, our alumni work successfully on Upwork, Fiverr, and in top tech agencies.",
        careers: null
      };
    }

    return {
      message: "Thank you for reaching out! PFTP admissions are currently open across 16 domains. You can apply directly at https://pftpedu.org or connect with our student counselling desk on WhatsApp at **+92 300 1234567**.",
      careers: "Full Stack Development; AI; Digital Marketing; Freelancing"
    };
  }

  /**
   * Append message bubble into DOM
   */
  function appendMessageToDOM(sender, text, meta = {}) {
    if (!dom.messagesList) return;

    const row = document.createElement('div');
    const isUser = sender === 'visitor';
    row.className = `chatbot-msg-row ${isUser ? 'chatbot-msg-user' : 'chatbot-msg-bot'}`;

    // Detect Urdu script for RTL styling
    const isUrdu = /[\u0600-\u06FF]/.test(text);

    let html = `
      <div class="chatbot-msg-bubble ${isUrdu ? 'chatbot-rtl' : ''}">
        ${formatMessageBody(text)}
      </div>
    `;

    // Append career tags if available
    if (meta.careers && !isUser) {
      const tags = meta.careers.split(';').map(c => `<span class="chatbot-career-tag">${escapeHtml(c.trim())}</span>`).join('');
      html += `
        <div class="chatbot-career-box">
          <div class="chatbot-career-title">🚀 Possible Career Directions:</div>
          <div class="chatbot-career-tags">${tags}</div>
        </div>
      `;
    }

    if (meta.isFallback && !isUser) {
      html += `
        <div class="chatbot-fallback-box">
          💡 Our admissions desk is available on WhatsApp at <strong>+92 300 1234567</strong> for instant personalized counselling.
        </div>
      `;
    }

    if (meta.time) {
      html += `<span class="chatbot-msg-time">${escapeHtml(meta.time)}</span>`;
    }

    row.innerHTML = html;
    dom.messagesList.appendChild(row);
  }

  function formatMessageBody(text) {
    let safe = escapeHtml(text);
    // Line breaks
    safe = safe.replace(/\n/g, '<br>');
    // Bold **text**
    safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Auto-link URLs
    safe = safe.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #0d9488; text-decoration: underline; font-weight: 600; word-break: break-all;">$1</a>');
    // Auto-link domain pftpedu.org if bare
    safe = safe.replace(/(^|[\s(])(pftpedu\.org(?:\/[^\s<)]*)?)/gi, '$1<a href="https://$2" target="_blank" rel="noopener noreferrer" style="color: #0d9488; text-decoration: underline; font-weight: 600; word-break: break-all;">$2</a>');
    return safe;
  }

  function renderQuickReplies(replies) {
    if (!dom.quickReplies) return;
    dom.quickReplies.innerHTML = '';
    replies.forEach(rep => {
      const pill = document.createElement('button');
      pill.type = 'button';
      pill.className = 'chatbot-pill';
      pill.setAttribute('data-query', rep.query);
      pill.textContent = rep.label;
      dom.quickReplies.appendChild(pill);
    });
  }

  function showTyping(show) {
    if (dom.typingIndicator) {
      if (show) {
        dom.typingIndicator.classList.add('chatbot-typing-active');
      } else {
        dom.typingIndicator.classList.remove('chatbot-typing-active');
      }
      scrollToBottom();
    }
  }

  function scrollToBottom() {
    if (dom.messagesList) {
      setTimeout(() => {
        dom.messagesList.scrollTop = dom.messagesList.scrollHeight;
      }, 50);
    }
  }

  function formatTime(timestamp) {
    if (!timestamp) return '';
    try {
      const date = new Date(timestamp.replace(/-/g, '/'));
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch {
      return '';
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Public API
  window.PFTPChatbot = {
    open: openChat,
    close: closeChat,
    toggle: toggleChat,
    send: sendUserQuery,
    resetSession: function () {
      localStorage.removeItem(STORAGE_KEY);
      localStorage.removeItem(STORAGE_VISITOR);
      location.reload();
    }
  };

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatbot);
  } else {
    initChatbot();
  }
})();
