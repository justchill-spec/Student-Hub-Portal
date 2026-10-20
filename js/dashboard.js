/**
 * Dashboard Page Script (dashboard.js)
 */

document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("page-ready");
  setupNav();
  setupThemeToggle();
  setupNotificationDismiss();
  setupSlideshow();
  setupFaq();
  setupModal();
});

function setupNotificationDismiss() {
  document.querySelectorAll(".dismiss-notice").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.currentTarget.closest(".notification-banner")?.remove();
    });
  });
}

function setupSlideshow() {
  const slides = [
    ["Focus this week", "Review your Java project checklist before the submission deadline."],
    ["Friday quiz", "Use the Course page to revisit the DSA topics included in this week's quiz."],
    ["Library hours", "The library stays open until 9:00 PM during assessment week."],
  ];
  let index = 0;

  const render = () => {
    const titleEl = document.querySelector("#slide-title");
    const copyEl = document.querySelector("#slide-copy");
    const statusEl = document.querySelector(".slide-status");
    if (titleEl) titleEl.textContent = slides[index][0];
    if (copyEl) copyEl.textContent = slides[index][1];
    if (statusEl) statusEl.textContent = `${index + 1} / ${slides.length}`;
  };

  document.querySelector(".slide-prev")?.addEventListener("click", () => {
    index = (index + slides.length - 1) % slides.length;
    render();
  });
  document.querySelector(".slide-next")?.addEventListener("click", () => {
    index = (index + 1) % slides.length;
    render();
  });
}

function setupFaq() {
  document.querySelectorAll(".faq-question").forEach((question) => {
    question.addEventListener("click", () => {
      const answer = question.nextElementSibling;
      const expanded = question.getAttribute("aria-expanded") === "true";
      question.setAttribute("aria-expanded", String(!expanded));
      if (answer) answer.hidden = expanded;
      const icon = question.querySelector("span");
      if (icon) icon.textContent = expanded ? "+" : "-";
    });
  });
}

function setupModal() {
  const modal = document.querySelector(".modal");
  if (!modal) return;

  const close = () => { modal.hidden = true; };

  document.querySelector(".modal-open")?.addEventListener("click", () => {
    modal.hidden = false;
    document.querySelector(".modal-close")?.focus();
  });
  document.querySelector(".modal-close")?.addEventListener("click", close);
  modal.addEventListener("click", (e) => { if (e.target === modal) close(); });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !modal.hidden) close();
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
