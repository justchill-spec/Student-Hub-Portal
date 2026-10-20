
document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("page-ready");
  setupRegistrationForm();
});

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
      regex: /^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)+$/,
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
      const confirmValue = document.getElementById("confirmPassword")?.value || "";
      const passwordVal = document.getElementById("password")?.value || "";
      if (confirmValue === "") return setError(fieldName, "Please confirm your password.");
      if (confirmValue !== passwordVal) return setError(fieldName, "Passwords do not match.");
      return setError(fieldName, "");
    }

    if (fieldName === "password") {
      const isValid = config.regex.test(value) && value.length <= 72;
      const res = setError(fieldName, isValid ? "" : config.message);
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

    if (!isFormValid) e.preventDefault();
  });
}
