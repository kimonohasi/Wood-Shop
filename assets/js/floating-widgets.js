/**
 * WoodCon - floating-widgets.js
 * Pill button + Chat AI + Liên hệ hỗ trợ interactions.
 * Generate a session_id once per browser, then POST /ajax/chatbot.php
 */
(function () {
  'use strict';

  var BASE = (window.WOODCON_BASE_URL || '').replace(/\/$/, '');
  var CHATBOT_URL = BASE + '/ajax/chatbot.php';

  function spriteIcon(id, cls) {
    var iconCls = 'icon' + (cls ? ' ' + cls : '');
    return '<svg class="' + iconCls + '" aria-hidden="true"><use href="' + BASE + '/assets/icons/sprite.svg#' + id + '"></use></svg>';
  }

  /* ---------- Session ID (persist trong localStorage) ---------- */
  function getSessionId() {
    var key = 'wc_chat_session';
    var sid = localStorage.getItem(key);
    if (!sid) {
      sid = 'cs_' + Date.now().toString(36) + '_' + Math.random().toString(36).substring(2, 10);
      localStorage.setItem(key, sid);
    }
    return sid;
  }
  var SESSION_ID = getSessionId();

  /* ---------- DOM refs ---------- */
  var pillAssistant = document.getElementById('pillAssistant');
  var pillContact   = document.getElementById('pillContact');
  var chatPanel     = document.getElementById('chatPanel');
  var contactPanel  = document.getElementById('contactPanel');
  var chatClose     = document.getElementById('chatClose');
  var contactClose  = document.getElementById('contactClose');
  var chatMessages  = document.getElementById('chatMessages');
  var chatForm      = document.getElementById('chatForm');
  var chatInput     = document.getElementById('chatInput');
  var chatSend      = document.getElementById('chatSend');
  var chatSuggestions = document.getElementById('chatSuggestions');

  var isChatOpen = false;
  var isContactOpen = false;
  var hasInteracted = false;

  /* ---------- Chat panel ---------- */
  function openChat() {
    isChatOpen = true;
    chatPanel.classList.remove('d-none');
    if (!isContactOpen) {
      // nothing to sync
    }
    chatInput.focus();
    scrollBottom();
  }
  function closeChat() {
    isChatOpen = false;
    chatPanel.classList.add('d-none');
  }

  /* ---------- Contact panel ---------- */
  function openContact() {
    isContactOpen = true;
    contactPanel.classList.remove('d-none');
  }
  function closeContact() {
    isContactOpen = false;
    contactPanel.classList.add('d-none');
  }

  /* ---------- Pill buttons ---------- */
  if (pillAssistant) {
    pillAssistant.addEventListener('click', function () {
      if (isChatOpen) {
        closeChat();
      } else {
        openChat();
      }
    });
  }

  if (pillContact) {
    pillContact.addEventListener('click', function () {
      if (isContactOpen) {
        closeContact();
      } else {
        openContact();
      }
    });
  }

  if (chatClose) {
    chatClose.addEventListener('click', closeChat);
  }
  if (contactClose) {
    contactClose.addEventListener('click', closeContact);
  }

  /* ---------- Gui tin nhan ---------- */
  function sendMessage(text) {
    if (!text) return;
    appendMessage('user', text);
    chatInput.value = '';

    if (!hasInteracted && chatSuggestions) {
      hasInteracted = true;
      chatSuggestions.style.display = 'none';
    }

    chatInput.disabled = true;
    chatSend.disabled = true;
    showTyping();

    fetch(CHATBOT_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text, session_id: SESSION_ID, _token: window.WOODCON_CSRF || '' })
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        removeTyping();
        appendMessage('bot', data.reply || 'Xin loi, he thong dang loi. Vui long thu lai.');
        if (data.products && data.products.length > 0) {
          appendProductCards(data.products);
        }
      })
      .catch(function () {
        removeTyping();
        appendMessage('bot', 'Khong ket noi duoc voi may chu. Vui long thu lai.');
      })
      .finally(function () {
        chatInput.disabled = false;
        chatSend.disabled = false;
        chatInput.focus();
      });
  }

  chatForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var text = chatInput.value.trim();
    if (!text) return;
    sendMessage(text);
  });

  /* ---------- Quick reply chip ---------- */
  if (chatSuggestions) {
    chatSuggestions.addEventListener('click', function (e) {
      var chip = e.target.closest('.wc-chip');
      if (!chip) return;
      var topic = chip.getAttribute('data-topic');
      if (topic) sendMessage(topic);
    });
  }

  /* ---------- Append message ---------- */
  function appendMessage(role, text) {
    var row = document.createElement('div');
    row.className = 'wc-chat-msg wc-chat-msg-' + role;

    if (role === 'bot') {
      row.innerHTML =
        '<div class="wc-chat-avatar-bot">' + spriteIcon('ms-auto_awesome') + '</div>' +
        '<div class="wc-chat-bubble">' + formatReply(text) + '</div>';
    } else {
      row.innerHTML = '<div class="wc-chat-bubble">' + escapeHtml(text) + '</div>';
    }

    chatMessages.appendChild(row);
    scrollBottom();
  }

  /* ---------- Format reply (ammo dau *, co the co link web) ---------- */
  function formatReply(text) {
    var html = escapeHtml(text);
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\n/g, '<br>');
    return html;
  }

  /* ---------- Append product cards ---------- */
  function appendProductCards(products) {
    var wrap = document.createElement('div');
    wrap.className = 'wc-chat-msg wc-chat-msg-bot';

    var avatar = '<div class="wc-chat-avatar-bot">' + spriteIcon('ms-auto_awesome') + '</div>';

    var cardsHtml = '<div class="wc-product-list">';
    for (var i = 0; i < products.length; i++) {
      var p = products[i];
      var priceHtml = '<span class="wc-product-card-price">' + escapeHtml(p.price) + '</span>';
      if (p.has_sale && p.original_price) {
        priceHtml = '<span class="wc-product-card-old">' + escapeHtml(p.original_price) + '</span> ' + priceHtml;
      }
      cardsHtml += '' +
        '<a href="' + escapeHtml(p.url) + '" target="_blank" class="wc-product-card">' +
          '<img class="wc-product-card-img" src="' + escapeHtml(p.image) + '" alt="' + escapeHtml(p.name) + '" loading="lazy" onerror="this.src=\''+BASE+'/assets/images/placeholder-product.svg\'">' +
          '<div class="wc-product-card-info">' +
            '<span class="wc-product-card-name">' + escapeHtml(p.name) + '</span>' +
            priceHtml +
            '<span class="wc-product-card-btn">Xem ngay ' + spriteIcon('ms-arrow_forward') + '</span>' +
          '</div>' +
        '</a>';
    }
    cardsHtml += '</div>';

    wrap.innerHTML = avatar + cardsHtml;
    chatMessages.appendChild(wrap);
    scrollBottom();
  }

  /* ---------- Typing indicator ---------- */
  function showTyping() {
    var el = document.createElement('div');
    el.className = 'wc-chat-msg wc-chat-msg-bot';
    el.id = 'chatTyping';
    el.innerHTML =
      '<div class="wc-chat-avatar-bot">' + spriteIcon('ms-auto_awesome') + '</div>' +
      '<div class="wc-chat-bubble"><div class="wc-chat-typing"><span></span><span></span><span></span></div></div>';
    chatMessages.appendChild(el);
    scrollBottom();
  }
  function removeTyping() {
    var el = document.getElementById('chatTyping');
    if (el) el.remove();
  }

  /* ---------- Helpers ---------- */
  function scrollBottom() {
    requestAnimationFrame(function () {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    });
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
  }
})();
