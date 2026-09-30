/**
 * Contact Page Script (contact.js)
 */

document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("page-ready");
  setupNav();
  setupThemeToggle();
  setupContactForm();
  setupFaqSection();
});

function setupContactForm() {
  const form = document.getElementById("contactForm");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const name = document.getElementById("contactName")?.value.trim();
    const email = document.getElementById("contactEmail")?.value.trim();
    const message = document.getElementById("message")?.value.trim();

    if (!name || !email || !message) {
      alert("Please fill in all required fields (Name, Email, Message) before submitting.");
      return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      alert("Please enter a valid email address.");
      return;
    }

    alert(`Thank you, ${name}! Your message has been successfully submitted to student services.`);
    form.reset();
  });
}

function setupNav() {
  const nav = document.querySelector("nav");
  if (!nav || nav.querySelector(".menu-toggle")) return;

  const links = document.createElement("div");
  links.id = "portal-links";
  links.className = "nav-links";
  links.append(...nav.querySelectorAll(":scope > a"));

  const menu = document.createElement("button");
  menu.className = "menu-toggle";
  menu.type = "button";
  menu.textContent = "\u2630";
  menu.setAttribute("aria-label", "Open navigation");
  menu.setAttribute("aria-expanded", "false");
  menu.setAttribute("aria-controls", "portal-links");

  const theme = document.createElement("button");
  theme.className = "theme-toggle";
  theme.type = "button";
  theme.textContent = "LIGHT THEME";
  theme.setAttribute("aria-label", "Switch to light theme");

  nav.prepend(menu);
  nav.append(links, theme);

  menu.addEventListener("click", () => {
    const expanded = menu.getAttribute("aria-expanded") === "true";
    menu.setAttribute("aria-expanded", String(!expanded));
    links.classList.toggle("is-open", !expanded);
  });
}

function setupThemeToggle() {
  const themeToggle = document.querySelector(".theme-toggle");
  if (!themeToggle) return;

  const body = document.body;
  const isLight = () => body.classList.contains("light-theme");

  const updateLabel = () => {
    themeToggle.textContent = isLight() ? "DARK THEME" : "LIGHT THEME";
    themeToggle.setAttribute("aria-label", isLight() ? "Switch to dark theme" : "Switch to light theme");
  };

  if (localStorage.getItem("student-hub-theme") === "light") body.classList.add("light-theme");
  updateLabel();

  themeToggle.addEventListener("click", () => {
    body.classList.toggle("light-theme");
    localStorage.setItem("student-hub-theme", isLight() ? "light" : "dark");
    updateLabel();
  });
}

async function setupFaqSection() {
  const faqContainer = document.getElementById("contactFaqs");
  if (!faqContainer) return;

  const faqs = await loadFaqs();
  const visibleFaqs = faqs.slice(0, 5);

  faqContainer.innerHTML = visibleFaqs.map((faq, index) => `
    <div class="faq-item">
      <button class="faq-question" type="button" aria-expanded="${index === 0}">
        <span class="faq-question-text">${faq.question}</span>
        <span class="faq-toggle-icon">${index === 0 ? "−" : "+"}</span>
      </button>
      <div class="faq-answer" ${index === 0 ? "" : "hidden"}>${faq.answer}</div>
    </div>
  `).join("");

  faqContainer.querySelectorAll(".faq-question").forEach((question) => {
    question.addEventListener("click", () => {
      const answer = question.nextElementSibling;
      const icon = question.querySelector(".faq-toggle-icon");
      const expanded = question.getAttribute("aria-expanded") === "true";
      question.setAttribute("aria-expanded", String(!expanded));
      answer.hidden = expanded;
      icon.textContent = expanded ? "+" : "−";
    });
  });
}

async function loadFaqs() {
  const fallbackFaqs = [
    {
      question: "How can I view my timetable?",
      answer: "Open the Timetable page from the portal menu and select the relevant week or exam schedule."
    },
    {
      question: "Where do I check attendance?",
      answer: "Visit the Attendance page to review your monthly percentage and the latest class entries."
    },
    {
      question: "How do I contact the student services team?",
      answer: "Use the form on this page to send a message and the support team will get back to you."
    },
    {
      question: "When are results published?",
      answer: "Results are published after faculty evaluation and university approval for each term."
    },
    {
      question: "How do I update my profile details?",
      answer: "Go to the Profile page, edit the information, and save the changes before leaving the screen."
    }
  ];

  try {
    const response = await fetch("../data/faqs.json");
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();
    if (Array.isArray(data) && data.length) return data;
  } catch (error) {
    console.warn("FAQ data could not be loaded; using fallback data instead.", error);
  }

  return fallbackFaqs;
}
