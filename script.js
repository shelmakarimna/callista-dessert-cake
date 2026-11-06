(function () {
  'use strict';
  var yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();
  var burgerBtn = document.getElementById('burgerBtn');
  var navMenu = document.getElementById('navMenu');

  function closeMenu() {
    navMenu.classList.remove('is-open');
    burgerBtn.setAttribute('aria-expanded', 'false');
  }

  function toggleMenu() {
    var isOpen = navMenu.classList.toggle('is-open');
    burgerBtn.setAttribute('aria-expanded', String(isOpen));
  }

  if (burgerBtn && navMenu) {
    burgerBtn.addEventListener('click', toggleMenu);
    navMenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', closeMenu);
    });

    document.addEventListener('click', function (event) {
      var clickedInsideNav = navMenu.contains(event.target) || burgerBtn.contains(event.target);
      if (!clickedInsideNav && navMenu.classList.contains('is-open')) {
        closeMenu();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && navMenu.classList.contains('is-open')) {
        closeMenu();
        burgerBtn.focus();
      }
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 720) closeMenu();
    });
  }

  var sections = Array.prototype.slice.call(
    document.querySelectorAll('section[id], header[id]')
  );
  var navLinks = Array.prototype.slice.call(
    document.querySelectorAll('.navbar__group a')
  );

  function setActiveLink() {
    var scrollPos = window.scrollY + window.innerHeight * 0.35;
    var current = sections[0];

    sections.forEach(function (section) {
      if (section.offsetTop <= scrollPos) current = section;
    });

    if (!current) return;
    var currentId = current.getAttribute('id');

    navLinks.forEach(function (link) {
      var href = link.getAttribute('href') || '';
      var isMatch = href === '#' + currentId;
      link.classList.toggle('is-active', isMatch);
    });
  }

  var scrollTicking = false;
  window.addEventListener('scroll', function () {
    if (!scrollTicking) {
      window.requestAnimationFrame(function () {
        setActiveLink();
        scrollTicking = false;
      });
      scrollTicking = true;
    }
  });
  setActiveLink();
})();

(function () {
  const tabs = document.querySelectorAll('.branches__tab');
  const panels = document.querySelectorAll('.branches__panel');
  if (!tabs.length || !panels.length) return;

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.target;

      tabs.forEach((t) => {
        const active = t === tab;
        t.classList.toggle('is-active', active);
        t.setAttribute('aria-selected', String(active));
      });

      panels.forEach((panel) => {
        panel.classList.toggle('is-hidden', panel.dataset.panel !== target);
      });
    });
  });
})();

const chatbotToggle = document.getElementById("chatbotToggle");
const chatbot = document.getElementById("chatbot");
const chatInput = document.getElementById("chatInput");
const sendButton = document.getElementById("sendButton");
const chatbotBody = document.getElementById("chatbotBody");

chatbotToggle.addEventListener("click", function () {
  chatbot.classList.toggle("active");
  chatbotToggle.classList.toggle("active");

});

// Balasan kontak kalau pengunjung mau pesan (tanpa lewat AI).
// Kontak diambil dari database (diubah admin); kalau gagal pakai data bawaan.
async function balasKontak() {
  let k = { whatsapp: "+62 812-1555-5886", email: "aqilajanniecallista@gmail.com", jam_operasional: "08.00-21.00" };
  try {
    const r = await fetch("admin.php?aksi=publik", { cache: "no-store" });
    const d = await r.json();
    if (d && d.kontak) k = Object.assign(k, d.kontak);
  } catch (e) {}

  let digit = String(k.whatsapp).replace(/\D/g, "");
  if (digit.charAt(0) === "0") digit = "62" + digit.slice(1);

  const box = document.createElement("div");
  box.classList.add("message", "bot-message");
  box.appendChild(document.createTextNode("Mau pesan? Boleh banget! Hubungi Callista Dessert & Cake ya 💗"));

  const baris = (label, isi, href) => {
    box.appendChild(document.createElement("br"));
    box.appendChild(document.createTextNode(label + ": "));
    if (href) {
      const a = document.createElement("a");
      a.href = href;
      a.target = "_blank";
      a.rel = "noopener noreferrer";
      a.textContent = isi;
      box.appendChild(a);
    } else {
      box.appendChild(document.createTextNode(isi));
    }
  };
  baris("WhatsApp", k.whatsapp, "https://wa.me/" + digit);
  baris("Email", k.email, "https://mail.google.com/mail/?view=cm&fs=1&to=" + encodeURIComponent(k.email));
  if (k.jam_operasional) baris("Jam buka", k.jam_operasional);

  chatbotBody.appendChild(box);
  chatbotBody.scrollTop = chatbotBody.scrollHeight;
}

async function sendMessage() {
  const message = chatInput.value.trim();
  if (message === "") {
    return;
  }
  const userMessage = document.createElement("div");
  userMessage.classList.add(
    "message",
    "user-message"
  );

  userMessage.textContent = message;
  chatbotBody.appendChild(userMessage);
  chatInput.value = "";
  chatbotBody.scrollTop =
    chatbotBody.scrollHeight;

  // kalau mau pesan -> tampilkan kontak
  if (/\b(pesan|pesen|memesan|mesen|order|beli|booking|preorder|pre-order)\b/i.test(message)) {
    balasKontak();
    return;
  }

  // indikator "sedang mengetik" selama menunggu balasan
  const typingMessage = document.createElement("div");
  typingMessage.classList.add("message", "bot-message");
  typingMessage.textContent = "Callista's AI sedang mengetik... 💭";
  chatbotBody.appendChild(typingMessage);
  chatbotBody.scrollTop = chatbotBody.scrollHeight;

  try {
    const response = await fetch("chat.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ message: message })
    });

    const data = await response.json();
    typingMessage.remove();

    const botMessage = document.createElement("div");
    botMessage.classList.add("message", "bot-message");
    botMessage.textContent =
      data.reply || data.error || "Maaf, ada yang salah. Coba lagi ya! 💗";

    chatbotBody.appendChild(botMessage);

  } catch (error) {
    console.error(error);
    typingMessage.remove();

    const botMessage = document.createElement("div");
    botMessage.classList.add("message", "bot-message");
    botMessage.textContent =
      "Waduh, Callista's AI lagi gangguan koneksi nih. Coba tanya lagi sebentar lagi ya! 💗";

    chatbotBody.appendChild(botMessage);
  }

  chatbotBody.scrollTop = chatbotBody.scrollHeight;
}

sendButton.addEventListener(
  "click",
  sendMessage
);

chatInput.addEventListener(
  "keydown",
  function (event) {

    if (event.key === "Enter") {
      sendMessage();

    }
  }
);