/**
 * Profile Page Script (profile.js)
 */

document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("page-ready");
  setupNav();
  setupThemeToggle();
  setupProfileTabs();
  setupProfileData();
  setupPhotoUpload();
});

function setupProfileTabs() {
  const tabs = document.querySelectorAll(".tab-btn");
  tabs.forEach((tab) =>
    tab.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
    })
  );
}

function setupProfileData() {
  const profileContainer = document.querySelector(".tabbed-profile");
  if (!profileContainer) return;

  try {
    const currentUser = JSON.parse(localStorage.getItem("currentUser") || "null");
    if (!currentUser) return;

    const nameHeader = profileContainer.querySelector("h3");
    if (nameHeader && currentUser.fullName) nameHeader.textContent = currentUser.fullName;

    const idTag = profileContainer.querySelector(".id-tag");
    if (idTag && currentUser.studentId) idTag.textContent = `ID: ${currentUser.studentId}`;

    profileContainer.querySelectorAll(".info-list li").forEach((li) => {
      const label = li.querySelector(".label")?.textContent.trim();
      const valEl = li.querySelector(".val");
      if (!valEl) return;

      if (label === "Full Name:" && currentUser.fullName) valEl.textContent = currentUser.fullName;
      if (label === "Email:" && currentUser.email) valEl.textContent = currentUser.email;
      if (label === "Phone:" && currentUser.mobile) valEl.textContent = `+91 ${currentUser.mobile}`;
      if (label === "Department:" && currentUser.course) valEl.textContent = currentUser.course;
      if (label === "Current Semester:" && currentUser.year) valEl.textContent = `Year ${currentUser.year}`;
    });
  } catch (e) {
    // Ignore JSON parse errors
  }
}

function setupPhotoUpload() {
  const uploadInput = document.getElementById("photo-upload");
  const avatarImg = document.querySelector(".avatar-box img");

  uploadInput?.addEventListener("change", (e) => {
    const file = e.target.files?.[0];
    if (file && avatarImg) {
      const reader = new FileReader();
      reader.onload = (event) => {
        avatarImg.src = event.target?.result;
      };
      reader.readAsDataURL(file);
    }
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
