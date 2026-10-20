document.addEventListener("DOMContentLoaded", () => {
  renderSimpleEventsPage();
});

let allEvents = [];

async function renderSimpleEventsPage() {
  const container = document.getElementById("dynamicDataContainer");
  const searchInput = document.getElementById("eventSearch");
  const sortSelect = document.getElementById("eventSort");
  if (!container) return;

  try {
    const data = typeof window.fetchPortalData === "function"
      ? await window.fetchPortalData()
      : await fetchJsonFallback();

    allEvents = Array.isArray(data.events) ? data.events : [];

    const applyFilters = () => {
      const query = (searchInput?.value || "").trim().toLowerCase();
      const sortBy = sortSelect?.value || "date";

      let filteredEvents = !query
        ? [...allEvents]
        : allEvents.filter((event) => {
            const searchable = [
              event.title,
              event.category,
              event.description,
              event.organizer,
              event.venue
            ].join(" ").toLowerCase();
            return searchable.includes(query);
          });

      filteredEvents = filteredEvents.sort((a, b) => {
        if (sortBy === "title") {
          return (a.title || "").localeCompare(b.title || "");
        }
        return new Date(a.date || 0) - new Date(b.date || 0);
      });

      renderEvents(filteredEvents, container);
    };

    searchInput?.addEventListener("input", applyFilters);
    sortSelect?.addEventListener("change", applyFilters);
    applyFilters();
  } catch (error) {
    console.error("Failed to load events:", error);
    container.innerHTML = `
      <div class="empty-state">
        <h3>Unable to load events</h3>
        <p>Please try again later.</p>
      </div>
    `;
  }
}

function renderEvents(events, container) {
  if (!events.length) {
    container.innerHTML = `
      <div class="empty-state">
        <h3>No events found</h3>
        <p>Try a different search term.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = events.map((event) => `
    <article class="data-card event-card">
      ${event.poster ? `<img class="event-poster" src="${escapeHtml(event.poster)}" alt="Poster for ${escapeHtml(event.title || "event")}" loading="lazy">` : ""}
      <div class="card-header-row">
        <span class="event-badge">${escapeHtml(event.category || "Event")}</span>
        <span class="event-date">${escapeHtml(formatDate(event.date))}</span>
      </div>
      <h3 class="card-title">${escapeHtml(event.title || "Untitled Event")}</h3>
      <p class="card-description">${escapeHtml(event.description || "No description available.")}</p>
      <div class="event-meta">
        <span><strong>Time:</strong> ${escapeHtml(event.time || "TBA")}</span>
        <span><strong>Venue:</strong> ${escapeHtml(event.venue || "TBA")}</span>
        <span><strong>Organizer:</strong> ${escapeHtml(event.organizer || "N/A")}</span>
        <span><strong>Seats:</strong> ${escapeHtml(event.seats || "N/A")}</span>
      </div>
    </article>
  `).join("");
}

async function fetchJsonFallback() {
  const response = await fetch("../data/events.json");
  if (!response.ok) {
    throw new Error(`Could not load events: ${response.status}`);
  }
  return { events: await response.json() };
}

function formatDate(dateString) {
  if (!dateString) return "Date TBD";
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return dateString;
  return new Intl.DateTimeFormat("en-US", { month: "short", day: "numeric", year: "numeric" }).format(date);
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
