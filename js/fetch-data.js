const portalDataFiles = {
  events: "../data/events.json",
  students: "../data/students.json",
  faqs: "../data/faqs.json"
};

async function fetchJson(url) {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Could not load ${url}: ${response.status}`);
  }
  return response.json();
}

async function fetchPortalData() {
  const entries = await Promise.all(
    Object.entries(portalDataFiles).map(async ([name, url]) => [name, await fetchJson(url)])
  );
  return Object.fromEntries(entries);
}

window.fetchPortalData = fetchPortalData;
