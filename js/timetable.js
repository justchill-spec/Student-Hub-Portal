/**
 * Timetable Page Script (timetable.js)
 */

document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("page-ready");
  setupNav();
  setupThemeToggle();
  setupRowSelection();
});

function setupRowSelection() {
  document.querySelectorAll("table tbody tr, table tr:not(:first-child)").forEach((row) => {
    row.tabIndex = 0;
    const toggle = () => row.classList.toggle("row-selected");
    row.addEventListener("click", toggle);
    row.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        toggle();
      }
    });
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
