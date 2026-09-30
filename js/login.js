

function redirecttohome() {
  window.location.href = "dashboard.html";
}

function getRegisteredUsers() {
  const defaultUsers = [
    {
      fullName: "Mukund Borole",
      studentId: "25DCE012",
      email: "mukund@gmail.com",
      mobile: "9898989898",
      password: "Password@123",
      course: "BE",
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

document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("page-ready");
  setupLoginForm();
});

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

    // 1. Verify presence and format of all filled parameters
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

    // 2. Match credentials against registered accounts
    const registeredUsers = getRegisteredUsers();

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

    // Verify Password match
    if (matchedAccount.password !== passwordVal) {
      setError("regPassword", "Incorrect password. Please try again.");
      if (generalError) {
        generalError.textContent = "Entered password does not match registered account.";
      }
      return;
    }

    // Verify Student ID match
    if (matchedAccount.studentId && matchedAccount.studentId.toLowerCase() !== studentIdVal.toLowerCase()) {
      setError("studentId", "Student ID does not match this email account.");
      return;
    }

    // Verify Email match
    if (matchedAccount.email.toLowerCase() !== emailVal.toLowerCase()) {
      setError("email", "Email does not match this Student ID.");
      return;
    }

    // 3. All filled parameters matched successfully!
    localStorage.setItem("currentUser", JSON.stringify(matchedAccount));
    alert(`Login successful! Welcome, ${matchedAccount.fullName}. Redirecting to Dashboard...`);
    redirecttohome();
  });
}
