function redirecttohome() {
  window.location.href = "dashboard.html";
}

function formsub() {
  alert("Hello, your form has been successfully submitted!");
}

// User Authentication and Data Persistence Storage
function getRegisteredUsers() {
  const defaultUsers = [
    {
      fullName: "Mukund Borole",
      studentId: "25DCE012",
      email: "mukund@gmail.com",
      mobile: "9898989898",
      password: "Password@123",
      course: "BCA",
      year: "2",
      gender: "Male"
    }
  ];

  const stored = localStorage.getItem("registeredUsers");
  if (!stored) {
    localStorage.setItem("registeredUsers", JSON.stringify(defaultUsers));
    return defaultUsers;
  }

  try {
    const parsed = JSON.parse(stored);
    return Array.isArray(parsed) && parsed.length > 0 ? parsed : defaultUsers;
  } catch (e) {
    return defaultUsers;
  }
}

function saveRegisteredUser(student) {
  const users = getRegisteredUsers();
  const existingIdx = users.findIndex(
    (u) =>
      u.email.toLowerCase() === student.email.toLowerCase() ||
      (u.studentId && student.studentId && u.studentId.toLowerCase() === student.studentId.toLowerCase())
  );

  if (existingIdx >= 0) {
    users[existingIdx] = { ...users[existingIdx], ...student };
  } else {
    users.push(student);
  }

  localStorage.setItem("registeredUsers", JSON.stringify(users));
  localStorage.setItem("currentUser", JSON.stringify(student));
}

document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("page-ready");

  setupRowSelection();
  setupProfileTabs();
  setupNav();
  setupThemeToggle();
  setupNotificationDismiss();
  setupFaq();
  setupSlideshow();
  setupModal();
  setupLoginForm();
  setupRegistrationForm();
  setupDashboard();
  setupProfileData();
});

/**
 * LOGIN FORM VALIDATION & MATCHING
 * Checks whether all 4 parameters are filled and match registered credentials.
 * If all match, immediately redirects to dashboard.html.
 */
function setupLoginForm() {
  const form = document.getElementById("loginForm");
  if (!form) return;

  const nameInput = document.getElementById("name");
  const studentIdInput = document.getElementById("studentId");
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("regPassword");
  const generalError = document.getElementById("loginGeneralError");

  const setError = (fieldId, message) => {
    const field = document.getElementById(fieldId);
    const errorEl = document.getElementById(`${fieldId}Error`);
    if (field) {
      field.setAttribute("aria-invalid", message ? "true" : "false");
      field.classList.toggle("input-error", Boolean(message));
    }
    if (errorEl) errorEl.textContent = message;
    return !message;
  };

  const clearErrors = () => {
    ["name", "studentId", "email", "regPassword"].forEach((id) => setError(id, ""));
    if (generalError) generalError.textContent = "";
  };

  // Real-time clearance of errors on typing
  [nameInput, studentIdInput, emailInput, passwordInput].forEach((input) => {
    input?.addEventListener("input", () => {
      setError(input.id, "");
      if (generalError) generalError.textContent = "";
    });
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    clearErrors();

    const nameVal = nameInput?.value.trim() || "";
    const studentIdVal = studentIdInput?.value.trim() || "";
    const emailVal = emailInput?.value.trim() || "";
    const passwordVal = passwordInput?.value || "";

    let hasError = false;

    // 1. Verify required field presence and formats
    if (!nameVal) {
      setError("name", "Full Name is required.");
      hasError = true;
    } else if (nameVal.length < 2) {
      setError("name", "Full Name must be at least 2 characters.");
      hasError = true;
    }

    if (!studentIdVal) {
      setError("studentId", "Student ID is required.");
      hasError = true;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailVal) {
      setError("email", "Email address is required.");
      hasError = true;
    } else if (!emailRegex.test(emailVal)) {
      setError("email", "Please enter a valid email address.");
      hasError = true;
    }

    if (!passwordVal) {
      setError("regPassword", "Password is required.");
      hasError = true;
    }

    if (hasError) return;

    // 2. Match entered parameters with registered records
    const registeredUsers = getRegisteredUsers();

    // Check if an account matches Student ID or Email
    const matchedAccount = registeredUsers.find(
      (u) =>
        u.email.toLowerCase() === emailVal.toLowerCase() ||
        (u.studentId && u.studentId.toLowerCase() === studentIdVal.toLowerCase())
    );

    if (!matchedAccount) {
      if (generalError) {
        generalError.textContent = "No registered account found matching this Student ID or Email. Please register first.";
      }
      return;
    }

    // Check if Password matches
    if (matchedAccount.password !== passwordVal) {
      setError("regPassword", "Incorrect password. Please try again.");
      if (generalError) {
        generalError.textContent = "Entered password does not match registered account.";
      }
      return;
    }

    // Check if Student ID matches
    if (matchedAccount.studentId && matchedAccount.studentId.toLowerCase() !== studentIdVal.toLowerCase()) {
      setError("studentId", `Student ID does not match this email account.`);
      return;
    }

    // Check if Email matches
    if (matchedAccount.email.toLowerCase() !== emailVal.toLowerCase()) {
      setError("email", `Email does not match this Student ID.`);
      return;
    }

    // 3. All filled parameters matched successfully!
    localStorage.setItem("currentUser", JSON.stringify(matchedAccount));
    alert(`Login successful! Welcome, ${matchedAccount.fullName}. Redirecting to Dashboard...`);
    redirecttohome();
  });
}

/**
 * REGISTRATION FORM VALIDATION & MATCHING
 * Checks whether all filled parameters match validation rules and passwords match.
 * If all match, saves student to localStorage and redirects to dashboard.html.
 */
function setupRegistrationForm() {
  const form = document.getElementById("studentRegistrationForm");
  if (!form) return;

  const validators = {
    fullName: {
      regex: /^[A-Za-z\s.'-]{2,50}$/,
      message: "Name must contain only letters and spaces with at least 2 characters."
    },
    studentId: {
      regex: /^[A-Za-z0-9\-_]{3,20}$/,
      message: "Student ID must be 3-20 alphanumeric characters (e.g. 25DCE012)."
    },
    email: {
      regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
      message: "Please enter a valid email address."
    },
    mobile: {
      regex: /^[6-9]\d{9}$/,
      message: "Mobile number must be 10 digits starting with 6-9."
    },
    password: {
      regex: /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&#^()_-])[A-Za-z\d@$!%*?&#^()_-]{8,}$/,
      message: "Password must be at least 8 characters and include a letter, number, and special character."
    },
    confirmPassword: {
      regex: /.+/,
      message: "Confirm password is required."
    },
    course: {
      regex: /.+/,
      message: "Please select a course."
    },
    year: {
      regex: /.+/,
      message: "Please select a year."
    },
    gender: {
      regex: /.+/,
      message: "Please select a gender."
    },
    terms: {
      regex: /true|checked/,
      message: "You must accept the terms and conditions."
    }
  };

  const setError = (fieldName, message) => {
    const field = document.getElementById(fieldName);
    const errorEl = document.getElementById(`${fieldName}Error`);
    if (field) {
      field.setAttribute("aria-invalid", message ? "true" : "false");
      field.classList.toggle("input-error", Boolean(message));
    }
    if (errorEl) errorEl.textContent = message;
    return !message;
  };

  const validateField = (fieldName, value) => {
    const config = validators[fieldName];
    if (!config) return true;
    const trimmed = typeof value === "string" ? value.trim() : "";

    if (fieldName === "terms") {
      const isChecked = document.getElementById("terms")?.checked;
      return setError(fieldName, isChecked ? "" : config.message);
    }

    if (fieldName === "gender") {
      const selected = document.querySelector('input[name="gender"]:checked');
      return setError(fieldName, selected ? "" : config.message);
    }

    if (fieldName === "confirmPassword") {
      const passwordVal = document.getElementById("password")?.value || "";
      if (trimmed === "") return setError(fieldName, "Please confirm your password.");
      if (trimmed !== passwordVal) return setError(fieldName, "Passwords do not match.");
      return setError(fieldName, "");
    }

    if (fieldName === "password") {
      const isValid = config.regex.test(trimmed);
      const res = setError(fieldName, isValid ? "" : config.message);
      // Re-validate confirm password if it already has input
      const confirmField = document.getElementById("confirmPassword");
      if (confirmField && confirmField.value) {
        validateField("confirmPassword", confirmField.value);
      }
      return res;
    }

    if (trimmed === "") return setError(fieldName, "This field is required.");
    return setError(fieldName, config.regex.test(trimmed) ? "" : config.message);
  };

  // Real-time validation listeners
  ["fullName", "studentId", "email", "mobile", "password", "confirmPassword", "course", "year"].forEach((fieldName) => {
    const field = document.getElementById(fieldName);
    if (!field) return;
    field.addEventListener("input", () => validateField(fieldName, field.value));
    field.addEventListener("blur", () => validateField(fieldName, field.value));
  });

  document.querySelectorAll('input[name="gender"]').forEach((radio) => {
    radio.addEventListener("change", () => validateField("gender", radio.value));
  });

  document.getElementById("terms")?.addEventListener("change", () => validateField("terms", "true"));

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const allFieldNames = ["fullName", "studentId", "email", "mobile", "password", "confirmPassword", "course", "year", "gender", "terms"];

    let isFormValid = true;

    allFieldNames.forEach((fieldName) => {
      const field = document.getElementById(fieldName) || document.querySelector(`input[name="${fieldName}"]`);
      let val = "";
      if (field) {
        val = field.type === "checkbox" ? (field.checked ? "true" : "") : field.value;
      }
      const valid = validateField(fieldName, val);
      if (!valid) {
        isFormValid = false;
      }
    });

    // If all filled parameters match validation criteria
    if (isFormValid) {
      const newStudent = {
        fullName: document.getElementById("fullName").value.trim(),
        studentId: document.getElementById("studentId").value.trim().toUpperCase(),
        email: document.getElementById("email").value.trim(),
        mobile: document.getElementById("mobile").value.trim(),
        password: document.getElementById("password").value,
        course: document.getElementById("course").value,
        year: document.getElementById("year").value,
        gender: document.querySelector('input[name="gender"]:checked')?.value || "Other"
      };

      saveRegisteredUser(newStudent);
      alert(`Registration successful! Account created for ${newStudent.fullName} (${newStudent.studentId}). Redirecting to Dashboard...`);
      redirecttohome();
    }
  });
}

function setupDashboard() {
  const welcomeHeading = document.querySelector(".dashboard h1");
  if (!welcomeHeading) return;

  try {
    const currentUser = JSON.parse(localStorage.getItem("currentUser") || "null");
    if (currentUser && currentUser.fullName) {
      welcomeHeading.textContent = `Welcome, ${currentUser.fullName}`;
    }
  } catch (e) {
    // Ignore JSON parse errors
  }
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

function setupProfileTabs() {
  const tabs = document.querySelectorAll(".tab-btn");
  tabs.forEach((tab) =>
    tab.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
    })
  );
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

function setupNotificationDismiss() {
  document.querySelector(".dismiss-notice")?.addEventListener("click", (e) => {
    e.currentTarget.closest(".notification-banner").remove();
  });
}

function setupFaq() {
  document.querySelectorAll(".faq-question").forEach((question) => {
    question.addEventListener("click", () => {
      const answer = question.nextElementSibling;
      const expanded = question.getAttribute("aria-expanded") === "true";
      question.setAttribute("aria-expanded", String(!expanded));
      answer.hidden = expanded;
      question.querySelector("span").textContent = expanded ? "+" : "-";
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