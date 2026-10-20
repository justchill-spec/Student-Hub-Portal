# Practical 6: Rendering External JSON Data using Fetch API, Search and Filter

**Course / Lab**: Web Development Laboratory  
**Topic**: Asynchronous JavaScript, Fetch API, Array Operations, Dynamic Rendering & State Handling  
**Project**: Student Hub Portal Management System  

---

## 1. Problem Definition & Objectives
Display campus event lists, student profiles, and FAQs from external `.json` files (`events.json`, `students.json`, `faqs.json`) using the standard **Fetch API**. Implement dynamic DOM rendering, real-time debounced **Search**, multi-criteria **Filter**, dynamic **Sorting**, client-side **Pagination**, **Loading Indicators**, and robust **Error Handling**.

---

## 2. Key Questions, Technical Analysis & Interpretation

### Question 1: How is JSON fetched, parsed, and rendered?
1. **Fetching**: 
   The modern asynchronous JavaScript `fetch(url)` function sends an HTTP GET request to the specified resource path (e.g. `../data/events.json`).
   ```javascript
   const response = await fetch("../data/events.json");
   ```
2. **Parsing**:
   The returned `Response` object provides the `.json()` asynchronous method, which consumes the readable stream and parses the incoming JSON string into native JavaScript objects/arrays:
   ```javascript
   if (!response.ok) {
     throw new Error(`HTTP error! status: ${response.status}`);
   }
   const data = await response.json();
   ```
3. **Rendering**:
   Using template literals with `Array.prototype.map()` and `.join('')`, HTML strings are generated dynamically and inserted into the container via `.innerHTML`, or by creating DOM elements via `document.createElement()`.
   ```javascript
   container.innerHTML = paginatedItems.map(item => `
     <article class="data-card">
       <h3>${item.title}</h3>
       <p>${item.description}</p>
     </article>
   `).join('');
   ```

---

### Question 2: Which array methods are used for search, filter, sort, and pagination?

| Operation | Array Method(s) | Implementation Details |
| :--- | :--- | :--- |
| **Search** | `Array.prototype.filter()` & `String.prototype.includes()` | Filters items whose fields (e.g. `title`, `description`, `name`, `id`) contain the search query after normalizing both with `.toLowerCase()`. |
| **Category Filter** | `Array.prototype.filter()` & `Set` | `Set` is used on `rawItems.map(item => item.category)` to extract unique categories. `filter()` then matches items where `item.category === selectedCategory`. |
| **Sorting** | `Array.prototype.sort()` & `localeCompare()` | For strings: `(a, b) => a.title.localeCompare(b.title)`. For numbers/dates: `(a, b) => new Date(a.date) - new Date(b.date)` or `(b.cgpa - a.cgpa)`. |
| **Pagination** | `Array.prototype.slice()` | Calculates `startIndex = (page - 1) * pageSize` and `endIndex = startIndex + pageSize`. Extracts the current page subset using `items.slice(startIndex, endIndex)`. |
| **Rendering** | `Array.prototype.map()` & `join()` | Iterates through filtered/sliced items to generate modular card HTML markup. |

---

### Question 3: How are loading and error states handled?
1. **Loading State**:
   - Before `fetch()` is executed, `setLoading(true)` displays a CSS keyframe spinner (`#loadingIndicator`) and clears stale content.
   - When the fetch completes (inside the `finally` block), `setLoading(false)` hides the spinner.
2. **Error State**:
   - If network connection fails, file is missing (404), or JSON parsing fails, the `try...catch` block intercepts the error.
   - A descriptive error banner (`#errorBanner`) is rendered with an explanation and an interactive **Retry** button (`#retryBtn`) that allows the user to re-trigger the fetch without reloading the entire page.
3. **Empty Results State**:
   - If search or category filters match 0 records, an informative empty state with a "Reset All Filters" button is displayed.

---

### Question 4: How is modularity maintained in JavaScript files?
1. **Separation of Concerns**:
   - `js/script.js`: Handles core portal functionality, form validations (Register & Login parameter verification), session storage, navigation drawer, theme switching, and dashboard integration.
   - `js/data-renderer.js`: Modular ES6+ class (`DataPortalManager`) dedicated entirely to data management: Fetch API requests, reactive filter pipelines, DOM rendering engines for distinct datasets, and pagination controllers.
2. **Extensible Configuration**:
   - Each dataset (`events`, `students`, `faqs`) is configured via a metadata dictionary (`datasetConfigs`) specifying URLs, search keys, category keys, and custom sort options. Adding a new dataset requires only a new configuration entry.
3. **Encapsulation & Reusability**:
   - Helper methods (`highlightMatch`, `sortItems`, `renderPagination`, `attachFaqListeners`) are decoupled, single-responsibility functions.

---

## 3. Dataset Specifications

| Dataset File | Record Count | Attributes | Description |
| :--- | :---: | :--- | :--- |
| `data/events.json` | **16 records** | `id`, `title`, `category`, `date`, `time`, `venue`, `organizer`, `seats`, `description` | Campus hackathons, AWS workshops, RoboWars, seminars, and fests. |
| `data/students.json` | **16 records** | `id`, `name`, `department`, `semester`, `email`, `phone`, `cgpa`, `status`, `avatar` | Student directory records across Computer, IT, Civil, and Mechanical engineering. |
| `data/faqs.json` | **16 records** | `id`, `category`, `question`, `answer`, `tags` | Searchable knowledge base covering exams, attendance rules, library, and placements. |

---

## 4. Verification and Testing Checklist

- [x] **Fetch API Execution**: Successfully retrieves JSON data asynchronously without page refresh.
- [x] **Search**: Debounced real-time search queries across multiple attributes with keyword highlight.
- [x] **Filter**: Categorical filtering for events (Technical, Workshop, Sports, etc.), departments for students, and domains for FAQs.
- [x] **Sorting**: Multi-directional sorting (A-Z, Z-A, Date, CGPA, Capacity).
- [x] **Pagination**: Dynamic page calculation, items-per-page selector (4, 6, 8, 12), Prev/Next and numeric jump controls.
- [x] **Error Handling**: Graceful error UI with live retry button on network/file fetch issues.
- [x] **Register & Login Matching**:
  - Registration and login are now handled server-side by the Practical 9 and Practical 10 PHP flows.
  - User accounts are stored in MySQL; passwords are hashed with `password_hash()` and verified using `password_verify()`.
  - Authenticated users receive role-checked PHP dashboards protected by sessions; credentials are not stored in browser `localStorage`.
