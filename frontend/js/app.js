const API_BASE = '../api';
const FALLBACK_IMAGE = 'https://images.unsplash.com/photo-1560942485-b2a11cc13456?q=80&w=400';
const FALLBACK_AVATAR = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150';
const DEFAULT_PAGE_LIMIT = 6;

// Shared client-side state used across pages because this project loads one app.js everywhere.
const state = {
    user: null,
    sessionVerified: false,
    events: [],
    registeredEventIds: new Set(),
    posts: [],
    likedPosts: [],
    myPosts: [],
    pagination: {
        events: { page: 1, limit: DEFAULT_PAGE_LIMIT },
        registrations: { page: 1, limit: DEFAULT_PAGE_LIMIT },
        communityPosts: { page: 1, limit: DEFAULT_PAGE_LIMIT },
        postLikedPosts: { page: 1, limit: DEFAULT_PAGE_LIMIT },
        myPosts: { page: 1, limit: DEFAULT_PAGE_LIMIT },
        profileLikedPosts: { page: 1, limit: DEFAULT_PAGE_LIMIT },
    },
};

// Send a JSON API request with session cookies and normalize error responses.
async function apiRequest(path, options = {}) {
    const response = await fetch(`${API_BASE}${path}`, {
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
        ...options,
    });

    const text = await response.text();
    let data = {};

    try {
        data = text ? JSON.parse(text) : {};
    } catch {
        data = {
            success: false,
            message: text || `Request failed with status ${response.status}`,
        };
    }

    if (!response.ok || data.success === false) {
        throw new Error(data.message || `Request failed with status ${response.status}`);
    }

    return data;
}

// Upload one image file to the PHP upload API and return the Cloudinary image URL.
async function uploadImage(type, file) {
    const formData = new FormData();
    formData.append('image', file);

    const response = await fetch(`${API_BASE}/uploads/image.php?type=${encodeURIComponent(type)}`, {
        method: 'POST',
        credentials: 'include',
        body: formData,
    });

    const text = await response.text();
    let data = {};

    try {
        data = text ? JSON.parse(text) : {};
    } catch {
        data = {
            success: false,
            message: text || `Upload failed with status ${response.status}`,
        };
    }

    if (!response.ok || data.success === false) {
        throw new Error(data.message || `Upload failed with status ${response.status}`);
    }

    return data.image_url || '';
}

// Small DOM helper for finding an element by id.
function byId(id) {
    return document.getElementById(id);
}

// Escape user-controlled text before putting it into an HTML string.
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Display a fallback value when data is missing or empty.
function fallback(value, fallbackValue = '-') {
    return value === null || value === undefined || value === '' ? fallbackValue : value;
}

// Programmatically switch the radio-button based page/view state.
function setChecked(id) {
    const input = byId(id);
    if (input) {
        input.checked = true;
    }
}

// Return the current HTML file name so page-specific initialization can run.
function currentPage() {
    return window.location.pathname.split('/').pop().toLowerCase();
}

// Show a form message, or fall back to alert when no message container exists.
function showMessage(container, message, type = 'info') {
    if (!container) {
        alert(message);
        return;
    }

    container.textContent = message;
    container.dataset.type = type;
}

// Format a date-like value for display while preserving unknown formats.
function formatDate(value) {
    if (!value) {
        return 'TBA';
    }

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString();
}

// Format a date-time value for display while preserving unknown formats.
function formatDateTime(value) {
    if (!value) {
        return 'TBA';
    }

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString();
}

// Save the current user in memory and localStorage for quick navigation rendering.
function storeUser(user) {
    state.user = user || null;

    if (user) {
        localStorage.setItem('currentUser', JSON.stringify(user));
    }
}

// Read the cached user from localStorage, returning null if the cache is invalid.
function getStoredUser() {
    try {
        return JSON.parse(localStorage.getItem('currentUser') || 'null');
    } catch {
        return null;
    }
}

// Replace an image only after the target URL loads, reducing avatar flicker.
function setImageWhenReady(image, src, fallbackSrc = FALLBACK_AVATAR) {
    if (!src) {
        image.src = fallbackSrc;
        return;
    }

    if (image.src === src) {
        return;
    }

    const loader = new Image();

    loader.onload = () => {
        image.src = src;
    };

    loader.onerror = () => {
        image.src = fallbackSrc;
    };

    loader.src = src;
}

// Update repeated sidebar profile text and avatar images after loading a user profile.
function syncProfileText(profile) {
    const user = profile || state.user || getStoredUser();

    if (!user) {
        return;
    }

    document.querySelectorAll('.profile-name').forEach((element) => {
        element.textContent = user.username || 'User';
    });

    document.querySelectorAll('.profile-bio').forEach((element) => {
        if (element.closest('.sidebar-profile-box')) {
            element.textContent = `UID: ${user.user_id || '-'}`;
        } else {
            element.textContent = user.anime_interest || 'Anime fan';
        }
    });

    document.querySelectorAll('.avatar-wrapper img, .profile-avatar-wrapper img').forEach((image) => {
        setImageWhenReady(image, user.avatar_url || '', FALLBACK_AVATAR);
    });
}

// Admins land on the event management page after login; regular users land on home.
function redirectTargetForUser(user) {
    return user?.role === 'admin' ? 'Event.html' : 'HomePage.html';
}

// Adjust navigation links based on the current user's role.
function applyRoleNavigation(user = state.user) {
    if (user?.role !== 'admin') {
        return;
    }

    document.querySelectorAll('.nav-links a[href="HomePage.html"]').forEach((link) => {
        link.remove();
    });

    document.querySelectorAll('.navbar-brand[href="HomePage.html"], .navbar-brand[href="#"]').forEach((link) => {
        link.href = 'Event.html';
    });

    byId('label-my-events')?.remove();
}

// Add the "My Registered Event" menu item for non-admin event pages.
function ensureUserEventMenu() {
    if (isAdmin() || byId('label-my-events')) {
        return;
    }

    const allEventsLabel = byId('label-all-events');

    allEventsLabel?.insertAdjacentHTML(
        'afterend',
        '<label class="menu-label" id="label-my-events" for="tab-my-events"><i class="fa-solid fa-paper-plane"></i> My Registered Event</label>'
    );
}

// Load the current session user from the API, with optional silent fallback to localStorage.
async function loadCurrentUser({ silent = true } = {}) {
    try {
        const data = await apiRequest('/profile/me.php');
        state.sessionVerified = true;
        storeUser(data.profile);
        syncProfileText(data.profile);
        applyRoleNavigation();
        return data.profile;
    } catch (error) {
        const storedUser = getStoredUser();
        state.sessionVerified = false;
        state.user = storedUser;
        syncProfileText(storedUser);
        applyRoleNavigation(storedUser);

        if (!silent) {
            throw error;
        }

        return storedUser;
    }
}

// Check whether the loaded session belongs to an admin.
function isAdmin() {
    return state.sessionVerified && state.user?.role === 'admin';
}

// Render a standard empty-state card for list panels.
function renderEmpty(message, className = 'feed-item-card') {
    return `<div class="${className}">${escapeHtml(message)}</div>`;
}

// Render the pixel-style loading placeholder used while API calls are pending.
function renderLoading(message = 'Connecting to database...') {
    return `
        <div class="loading-card" aria-live="polite">
            <div class="pixel-stage" aria-hidden="true">
                <div class="pixel-runner">
                    <span class="runner-head"></span>
                    <span class="runner-body"></span>
                    <span class="runner-tail"></span>
                    <span class="runner-leg runner-leg-a"></span>
                    <span class="runner-leg runner-leg-b"></span>
                </div>
                <div class="pixel-ground"></div>
            </div>
            <div class="loading-copy">${escapeHtml(message)}</div>
        </div>
    `;
}

// Convert stored pagination state into a query string for paginated APIs.
function paginationParams(key) {
    const current = state.pagination[key] || { page: 1, limit: DEFAULT_PAGE_LIMIT };
    return `page=${encodeURIComponent(current.page || 1)}&limit=${encodeURIComponent(current.limit || DEFAULT_PAGE_LIMIT)}`;
}

// Merge pagination metadata from an API response into client-side state.
function setPagination(key, pagination) {
    if (!pagination) {
        return;
    }

    const existing = state.pagination[key] || { page: 1, limit: DEFAULT_PAGE_LIMIT, total: 0 };
    const merged = {
        ...existing,
        ...pagination,
    };
    const limit = Number(merged.limit || DEFAULT_PAGE_LIMIT);
    const total = Number(merged.total || 0);
    const totalPages = Math.max(1, Math.ceil(total / limit));
    const page = Math.min(Number(merged.page || 1), totalPages);

    state.pagination[key] = {
        ...merged,
        page,
        limit,
        total,
        total_pages: totalPages,
        has_previous: page > 1,
        has_next: total > page * limit,
    };
}

// Choose a compact set of page numbers with first/last/current neighbors.
function pageNumbers(currentPage, totalPages) {
    const pages = new Set([1, totalPages, currentPage - 1, currentPage, currentPage + 1]);

    return [...pages]
        .filter((page) => page >= 1 && page <= totalPages)
        .sort((left, right) => left - right);
}

// Render Previous/Next and numbered page controls for any paginated list.
function renderPagination(key, pagination) {
    if (!pagination || (pagination.total || 0) <= (pagination.limit || DEFAULT_PAGE_LIMIT) || (pagination.total_pages || 1) <= 1) {
        return '';
    }

    const currentPage = pagination.page || 1;
    const totalPages = pagination.total_pages || 1;
    const pages = pageNumbers(currentPage, totalPages);
    let lastPage = 0;

    const pageButtons = pages.map((page) => {
        const gap = page - lastPage > 1 ? '<span class="pagination-ellipsis">...</span>' : '';
        lastPage = page;

        return `${gap}<button type="button" class="pagination-btn ${page === currentPage ? 'is-active' : ''}" data-pagination-key="${escapeHtml(key)}" data-pagination-page="${page}" ${page === currentPage ? 'aria-current="page"' : ''}>${page}</button>`;
    }).join('');

    return `
        <nav class="pagination-bar" aria-label="Pagination">
            <div class="pagination-summary">Page ${escapeHtml(currentPage)} of ${escapeHtml(totalPages)} - ${escapeHtml(pagination.total || 0)} total</div>
            <div class="pagination-actions">
                <button type="button" class="pagination-btn" data-pagination-key="${escapeHtml(key)}" data-pagination-page="${currentPage - 1}" ${pagination.has_previous ? '' : 'disabled'}>Previous</button>
                ${pageButtons}
                <button type="button" class="pagination-btn" data-pagination-key="${escapeHtml(key)}" data-pagination-page="${currentPage + 1}" ${pagination.has_next ? '' : 'disabled'}>Next</button>
            </div>
        </nav>
    `;
}

// Handle clicks on pagination buttons and refresh only the affected panel.
async function handlePaginationClick(event) {
    const button = event.target.closest('[data-pagination-key][data-pagination-page]');

    if (!button || button.disabled) {
        return false;
    }

    const key = button.dataset.paginationKey;
    const page = Number(button.dataset.paginationPage);

    if (!Number.isInteger(page) || page < 1) {
        return true;
    }

    setPagination(key, { page });

    if (key === 'events') {
        await renderEvents();
    } else if (key === 'registrations') {
        renderMyEvents(await loadMyRegistrations());
    } else if (key === 'communityPosts') {
        await renderCommunityPosts();
    } else if (key === 'postLikedPosts') {
        await renderLikedPosts();
    } else if (key === 'myPosts') {
        await renderMyPosts();
    } else if (key === 'profileLikedPosts') {
        await renderProfileLikedPosts();
    }

    return true;
}

// Put loading cards into panels before their page-specific data loads.
function setInitialLoading() {
    const loadingTargets = [
        ['panel-user-list', 'Loading events...'],
        ['panel-my-events', 'Loading registered events...'],
        ['panel-event-detail', 'Loading event details...'],
        ['panel-admin-list', 'Loading admin events...'],
        ['panel-admin-detail', 'Loading participants...'],
        ['panel-community', 'Loading community posts...'],
        ['panel-liked-posts-flow', 'Loading liked posts...'],
        ['panel-sent-posts', 'Loading sent posts...'],
        ['panel-like-posts', 'Loading liked posts...'],
        ['panel-detail-sent', 'Loading post details...'],
        ['panel-detail-liked', 'Loading post details...'],
    ];

    loadingTargets.forEach(([id, message]) => {
        const element = byId(id);

        if (element) {
            element.innerHTML = renderLoading(message);
        }
    });
}

// Create or reuse the shared HTML dialog used for details and forms.
function ensureDialog() {
    let dialog = byId('apiDialog');

    if (dialog) {
        return dialog;
    }

    dialog = document.createElement('dialog');
    dialog.id = 'apiDialog';
    dialog.className = 'api-dialog';
    document.body.appendChild(dialog);
    return dialog;
}

// Open a read-only dialog with a title and HTML body.
function openInfoDialog(title, bodyHtml) {
    const dialog = ensureDialog();
    dialog.innerHTML = `
        <div class="dialog-title">${title}</div>
        <div class="dialog-body">${bodyHtml}</div>
        <div class="dialog-buttons">
            <button type="button" class="btn-dialog btn-no" data-dialog-close>Close</button>
        </div>
    `;
    dialog.showModal();
}

// Render one dynamic field used by openFormDialog.
function renderDialogField(field) {
    if (field.type === 'hidden') {
        return `<input type="hidden" name="${escapeHtml(field.name)}" value="${escapeHtml(field.value || '')}">`;
    }

    const label = field.label ? `<span>${escapeHtml(field.label)}</span>` : '';
    let control = '';

    if (field.type === 'textarea') {
        control = `<textarea name="${escapeHtml(field.name)}" class="dialog-input" ${field.required ? 'required' : ''}>${escapeHtml(field.value || '')}</textarea>`;
    } else if (field.type === 'file') {
        control = `<input type="file" name="${escapeHtml(field.name)}" class="dialog-input" ${field.accept ? `accept="${escapeHtml(field.accept)}"` : ''} ${field.required ? 'required' : ''}>`;
    } else {
        control = `<input type="${escapeHtml(field.type || 'text')}" name="${escapeHtml(field.name)}" class="dialog-input" value="${escapeHtml(field.value || '')}" ${field.required ? 'required' : ''}>`;
    }

    return `
        <label class="dialog-field">
            ${label}
            ${control}
        </label>
    `;
}

// Open a modal form and call onSubmit with its collected fields.
function openFormDialog({ title, fields, confirmText = 'Submit', pendingText = 'Submitting...', onSubmit }) {
    const dialog = ensureDialog();
    dialog.innerHTML = `
        <form method="dialog" class="api-dialog-form">
            <div class="dialog-title">${escapeHtml(title)}</div>
            <div class="dialog-body">
                ${fields.map(renderDialogField).join('')}
                <div class="dialog-inline-message" aria-live="polite"></div>
            </div>
            <div class="dialog-buttons">
                <button type="button" class="btn-dialog btn-no" data-dialog-close>Cancel</button>
                <button type="submit" class="btn-dialog btn-yes">${escapeHtml(confirmText)}</button>
            </div>
        </form>
    `;

    dialog.querySelector('form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const message = dialog.querySelector('.dialog-inline-message');
        const submitButton = form.querySelector('button[type="submit"]');
        const controls = form.querySelectorAll('button, input, textarea');
        const originalText = submitButton?.textContent || confirmText;
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        message.textContent = pendingText;
        controls.forEach((control) => {
            control.disabled = true;
        });

        if (submitButton) {
            submitButton.textContent = pendingText;
        }

        try {
            await onSubmit(payload);
            dialog.close();
        } catch (error) {
            message.textContent = error.message;
            controls.forEach((control) => {
                control.disabled = false;
            });

            if (submitButton) {
                submitButton.textContent = originalText;
            }
        }
    });

    dialog.showModal();
}

// Close any open dialog when a dialog-close control is clicked.
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-dialog-close]')) {
        event.target.closest('dialog')?.close();
    }
});

// Initialize the login page form and redirect after a successful login.
function initLogin() {
    const form = document.querySelector('form');
    const message = byId('form-message');

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(message, 'Logging in...');

        try {
            const data = await apiRequest('/auth/login.php', {
                method: 'POST',
                body: JSON.stringify({
                    email: byId('username')?.value.trim(),
                    password: byId('password')?.value,
                }),
            });
            storeUser(data.user);
            window.location.href = redirectTargetForUser(data.user);
        } catch (error) {
            showMessage(message, error.message, 'error');
        }
    });
}

// Initialize the register page form and log the new user in after registration.
function initRegister() {
    const form = document.querySelector('form');
    const message = byId('form-message');

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (byId('password')?.value !== byId('confirm_password')?.value) {
            showMessage(message, 'Passwords do not match', 'error');
            return;
        }

        showMessage(message, 'Creating account...');

        try {
            const data = await apiRequest('/auth/register.php', {
                method: 'POST',
                body: JSON.stringify({
                    username: byId('username')?.value.trim(),
                    email: byId('email')?.value.trim(),
                    password: byId('password')?.value,
                    telephone: byId('phone')?.value.trim(),
                    anime_interest: '',
                }),
            });
            storeUser(data.user);
            window.location.href = 'HomePage.html';
        } catch (error) {
            showMessage(message, error.message, 'error');
        }
    });
}

// Initialize the home page by loading the current user's profile text.
async function initHome() {
    await loadCurrentUser();

    if (isAdmin()) {
        window.location.href = 'Event.html';
    }
}

// Determine whether an event has no available slots.
function isEventFull(event) {
    const availableSlots = Number(event.available_slots);
    return Number.isFinite(availableSlots) && availableSlots <= 0;
}

// Render a small text indicator showing event capacity status.
function slotStatusHtml(event) {
    if (Number.isFinite(Number(event.available_slots))) {
        return `${escapeHtml(event.available_slots)} slots left`;
    }

    return escapeHtml(fallback(event.status, 'Upcoming'));
}

// Render the joined/full badge shown on event cards.
function eventStatusBadgeHtml(event, registered) {
    if (registered) {
        return '<span class="event-status-badge joined-status"><i class="fa-solid fa-circle-check"></i> Joined</span>';
    }

    if (isEventFull(event)) {
        return '<span class="event-status-badge full-status"><i class="fa-solid fa-circle-xmark"></i> Full</span>';
    }

    return '';
}

// Render one event card for user lists, admin lists, or registered-event lists.
function eventCard(event, { admin = false, registered = false, canCancel = false } = {}) {
    const image = event.image_url || FALLBACK_IMAGE;
    const statusClass = registered ? 'event-joined-card' : (isEventFull(event) ? 'event-full-card' : '');

    return `
        <div class="event-item-card ${statusClass}">
            <div class="event-item-inner-grid">
                <div class="event-left-img-box"><img src="${escapeHtml(image)}" alt="${escapeHtml(event.title)}"></div>
                <div class="event-right-info-box">
                    ${eventStatusBadgeHtml(event, registered)}
                    <div class="event-main-title">${escapeHtml(event.title || 'Untitled Event')}</div>
                    <div class="event-meta-row">
                        <span>Date: ${escapeHtml(formatDate(event.event_date))}</span>
                        <span>Time: ${escapeHtml(fallback(event.event_time, 'TBA'))}</span>
                        <span>${slotStatusHtml(event)}</span>
                    </div>
                    <div class="event-description-container">${escapeHtml(event.description || event.category || event.location || 'No description yet.')}</div>
                    <button type="button" class="${admin ? 'btn-know-more-box' : 'btn-know-more-action'}" data-event-detail="${escapeHtml(event.event_id)}" data-event-admin="${admin ? 'true' : 'false'}">know more</button>
                </div>
            </div>
            ${canCancel ? `
                <div class="admin-crud-bar">
                    <button class="crud-icon-btn" data-event-cancel="${escapeHtml(event.event_id)}"><i class="fa-regular fa-circle-xmark"></i> Cancel Registration</button>
                </div>
            ` : ''}
            ${admin ? `
                <div class="admin-crud-bar">
                    <button class="crud-icon-btn" data-event-edit="${escapeHtml(event.event_id)}"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                    <button class="crud-icon-btn" data-event-delete="${escapeHtml(event.event_id)}"><i class="fa-regular fa-trash-can"></i> Delete</button>
                </div>
            ` : ''}
        </div>
    `;
}

// Initialize the Event page, including admin actions and user registration actions.
async function initEvents() {
    await loadCurrentUser();

    if (isAdmin()) {
        setChecked('tab-admin-list');
        await renderEvents();
    } else {
        ensureUserEventMenu();
        const registrations = await loadRegistrationState();
        await renderEvents();
        renderMyEvents(registrations);
    }

    document.body.addEventListener('click', async (event) => {
        if (await handlePaginationClick(event)) {
            return;
        }

        const detailButton = event.target.closest('[data-event-detail]');
        const editButton = event.target.closest('[data-event-edit]');
        const deleteButton = event.target.closest('[data-event-delete]');
        const cancelButton = event.target.closest('[data-event-cancel]');

        if (detailButton) {
            try {
                await renderEventDetail(detailButton.dataset.eventDetail, isAdmin() || detailButton.dataset.eventAdmin === 'true');
            } catch (error) {
                alert(error.message);
            }
        }

        if (editButton) {
            try {
                await fillEventForm(editButton.dataset.eventEdit);
            } catch (error) {
                alert(error.message);
            }
        }

        if (deleteButton && confirm('Delete this event?')) {
            try {
                await apiRequest('/admin/events/delete.php', {
                    method: 'POST',
                    body: JSON.stringify({ event_id: deleteButton.dataset.eventDelete }),
                });
                await renderEvents();
            } catch (error) {
                alert(error.message);
            }
        }

        if (cancelButton && confirm('Cancel this registration?')) {
            try {
                await apiRequest('/registrations/cancel.php', {
                    method: 'POST',
                    body: JSON.stringify({ event_id: cancelButton.dataset.eventCancel }),
                });
                await refreshEventViews();
            } catch (error) {
                alert(error.message);
            }
        }

        if (event.target.closest('.btn-top-add')) {
            resetEventForm();
        }
    });

    // Submit the join-event form for regular users.
    byId('join-event-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const eventId = event.currentTarget.dataset.eventId;

        try {
            await apiRequest('/registrations/join.php', {
                method: 'POST',
                body: JSON.stringify({ event_id: eventId }),
            });
            alert('Registration successful.');
            await refreshEventViews();
            setChecked('tab-user-list');
        } catch (error) {
            alert(error.message);
        }
    });

    // Submit the admin create/update event form, uploading a selected image first.
    byId('admin-event-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const imageFile = form.elements.image?.files?.[0] || null;
        const payload = {
            event_id: form.elements.event_id.value,
            title: form.elements.title.value,
            category: form.elements.category.value,
            description: form.elements.description.value,
            event_date: form.elements.event_date.value,
            event_time: form.elements.event_time.value,
            location: form.elements.location.value,
            capacity: form.elements.capacity.value,
            image_url: form.elements.image_url.value || '',
            status: form.elements.status.value,
        };
        const endpoint = payload.event_id ? '/admin/events/update.php' : '/admin/events/create.php';

        try {
            if (imageFile) {
                payload.image_url = await uploadImage('event', imageFile);
            }

            await apiRequest(endpoint, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            if (!payload.event_id) {
                setPagination('events', { page: 1 });
            }
            resetEventForm();
            setChecked('tab-admin-list');
            await renderEvents();
        } catch (error) {
            alert(error.message);
        }
    });
}

// Refresh the event list and registered-event list after join/cancel/delete changes.
async function refreshEventViews() {
    if (isAdmin()) {
        await renderEvents();
        return;
    }

    const registrations = await loadRegistrationState();
    await renderEvents();
    renderMyEvents(registrations);
}

// Load the current user's paginated active registrations.
async function loadMyRegistrations() {
    const data = await apiRequest(`/registrations/my_registrations.php?${paginationParams('registrations')}`);
    const registrations = data.registrations || [];
    setPagination('registrations', data.pagination);
    return registrations;
}

// Load registration state for the Event page and keep joined badges accurate.
async function loadRegistrationState() {
    try {
        const registrations = await loadMyRegistrations();
        await loadRegisteredEventIds();
        return registrations;
    } catch {
        state.registeredEventIds = new Set();
        return [];
    }
}

// Load every registered event id so event cards can show Joined across all pages.
async function loadRegisteredEventIds() {
    const ids = [];
    let page = 1;
    let totalPages = 1;

    do {
        const data = await apiRequest(`/registrations/my_registrations.php?page=${page}&limit=50`);
        const registrations = data.registrations || [];
        ids.push(...registrations.map((item) => item.event_id || item.event?.event_id).filter(Boolean));
        totalPages = data.pagination?.total_pages || 1;
        page += 1;
    } while (page <= totalPages);

    state.registeredEventIds = new Set(ids);
}

// Render the All Events or admin Events Console list.
async function renderEvents() {
    const data = await apiRequest(`/events/list.php?${paginationParams('events')}`);
    const events = data.events || [];
    setPagination('events', data.pagination);
    state.events = events;

    const userPanel = byId('panel-user-list');
    const adminPanel = byId('panel-admin-list');

    if (userPanel) {
        userPanel.innerHTML = `
            <div class="feed-header-title">Events</div>
            <div class="feed-header-subtitle">Explore upcoming anime activities on campus.</div>
            ${events.length ? events.map((event) => eventCard(event, { registered: state.registeredEventIds.has(event.event_id) })).join('') : renderEmpty('No events found.', 'event-item-card')}
            ${renderPagination('events', state.pagination.events)}
        `;
    }

    if (adminPanel) {
        adminPanel.innerHTML = `
            <div class="header-action-row">
                <div>
                    <div class="feed-header-title">Events Console</div>
                    <div class="feed-header-subtitle">Admin privileged backend list operations.</div>
                </div>
                <label class="btn-top-add" for="tab-admin-form"><i class="fa-solid fa-plus"></i> Add Events</label>
            </div>
            ${isAdmin()
                ? (events.length ? events.map((event) => eventCard(event, { admin: true })).join('') : renderEmpty('No events found.', 'event-item-card'))
                : renderEmpty('Admin access required.', 'event-item-card')}
            ${isAdmin() ? renderPagination('events', state.pagination.events) : ''}
        `;
    }
}

// Render the current user's registered events list.
function renderMyEvents(registrations = []) {
    const panel = byId('panel-my-events');

    if (!panel) {
        return;
    }

    panel.innerHTML = `
        <div class="feed-header-title">My Registered Events</div>
        <div class="feed-header-subtitle">Review the anime activities you have signed up for.</div>
        ${registrations.length
            ? registrations.map((item) => eventCard({ ...item.event, status: item.status }, { registered: true, canCancel: true })).join('')
            : renderEmpty('You have not joined any events yet.', 'event-item-card')}
        ${renderPagination('registrations', state.pagination.registrations)}
    `;
}

// Render the event detail panel for either user view or admin view.
async function renderEventDetail(eventId, admin = false) {
    const endpoint = admin
        ? `/admin/events/detail.php?event_id=${encodeURIComponent(eventId)}`
        : `/events/detail.php?event_id=${encodeURIComponent(eventId)}`;
    const data = await apiRequest(endpoint);
    const event = data.event;
    const panel = byId(admin ? 'panel-admin-detail' : 'panel-event-detail');
    const image = event.image_url || FALLBACK_IMAGE;

    panel.innerHTML = `
        <div class="event-card-box detail-view-container">
            <div class="feed-header-title" style="text-align: center; font-size: 28px;">${escapeHtml(event.title)}</div>
            <div class="detail-flex-hero">
                <img class="detail-big-cover" src="${escapeHtml(image)}" alt="${escapeHtml(event.title)}">
                <div class="detail-info-sheet">
                    <div class="event-main-title">${escapeHtml(event.category || event.title)}</div>
                    <div class="event-description-container" style="max-width: 100%;">${escapeHtml(event.description || 'No description yet.')}</div>
                    <div class="event-meta-row">
                        <span><strong>Date:</strong> ${escapeHtml(formatDate(event.event_date))}</span><br>
                        <span><strong>Time:</strong> ${escapeHtml(fallback(event.event_time, 'TBA'))}</span><br>
                        <span><strong>Location:</strong> ${escapeHtml(fallback(event.location, 'TBA'))}</span><br>
                        <span><strong>Capacity:</strong> ${escapeHtml(event.joined_count ?? 0)} / ${escapeHtml(event.capacity ?? 0)}</span><br>
                        <span><strong>Status:</strong> ${escapeHtml(fallback(event.status, 'Upcoming'))}</span>
                    </div>
                </div>
            </div>
            ${admin ? renderParticipants(event.participants || []) : ''}
            <div class="bottom-control-bar">
                <label class="btn-footer btn-cancel" for="${admin ? 'tab-admin-list' : 'tab-user-list'}">Back</label>
                ${admin ? '' : renderJoinControl(event)}
            </div>
        </div>
    `;

    const joinForm = byId('join-event-form');
    if (joinForm) {
        joinForm.dataset.eventId = event.event_id;
        byId('join-date').value = String(event.event_date || '').slice(0, 10);
        byId('join-time').value = String(event.event_time || '00:00').slice(0, 5);
    }

    setChecked(admin ? 'tab-admin-detail' : 'tab-event-detail');
}

// Render the join button state for an event detail view.
function renderJoinControl(event) {
    if (event.is_joined) {
        return '<span class="btn-footer btn-joined"><i class="fa-solid fa-circle-check"></i> Joined</span>';
    }

    if (isEventFull(event)) {
        return '<span class="btn-footer btn-full"><i class="fa-solid fa-circle-exclamation"></i> Full</span>';
    }

    return '<label class="btn-footer btn-confirm" for="tab-join-form">Join Event</label>';
}

// Render the admin participant list inside an event detail view.
function renderParticipants(participants) {
    return `
        <div class="participants-box">
            <h3><i class="fa-solid fa-list-ol"></i> List of participants</h3>
            <ul class="participants-list">
                ${participants.length ? participants.map((participant) => `
                    <li>${escapeHtml(participant.username || participant.email || participant.user_id)} - Email: ${escapeHtml(fallback(participant.email))} | Phone: ${escapeHtml(fallback(participant.telephone))} | Joined: ${escapeHtml(formatDateTime(participant.registration_date))}</li>
                `).join('') : '<li>No participants yet.</li>'}
            </ul>
        </div>
    `;
}

// Reset the admin event form to create mode.
function resetEventForm() {
    const form = byId('admin-event-form');
    form?.reset();

    if (form?.elements.event_id) {
        form.elements.event_id.value = '';
    }

    if (form?.elements.image_url) {
        form.elements.image_url.value = '';
    }

    if (form?.elements.image) {
        form.elements.image.value = '';
    }

    if (byId('admin-form-title')) {
        byId('admin-form-title').textContent = 'Plan Your Event';
    }
}

// Load an event into the admin form for editing.
async function fillEventForm(eventId) {
    const data = await apiRequest(`/admin/events/detail.php?event_id=${encodeURIComponent(eventId)}`);
    const event = data.event;
    const form = byId('admin-event-form');

    byId('admin-form-title').textContent = 'Edit Event';
    form.elements.event_id.value = event.event_id || '';
    form.elements.title.value = event.title || '';
    form.elements.category.value = event.category || '';
    form.elements.description.value = event.description || '';
    form.elements.event_date.value = String(event.event_date || '').slice(0, 10);
    form.elements.event_time.value = String(event.event_time || '').slice(0, 5);
    form.elements.location.value = event.location || '';
    form.elements.capacity.value = event.capacity || '';
    form.elements.image_url.value = event.image_url || '';
    if (form.elements.image) {
        form.elements.image.value = '';
    }
    form.elements.status.value = event.status || 'Upcoming';
    setChecked('tab-admin-form');
}

// Initialize the Post page, including create-post form and action buttons.
async function initPosts() {
    await loadCurrentUser();
    await Promise.allSettled([renderCommunityPosts(), renderLikedPosts()]);

    // Create a new post, uploading the selected image to Cloudinary first when present.
    byId('new-post-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const imageFile = form.elements.image?.files?.[0] || null;
        const payload = {
            title: form.elements.title.value,
            content: form.elements.content.value,
            image_url: form.elements.image_url.value || '',
        };

        try {
            if (imageFile) {
                payload.image_url = await uploadImage('post', imageFile);
            }

            await apiRequest('/posts/create.php', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            setPagination('communityPosts', { page: 1 });
            form.reset();
            setChecked('tab-community');
            await Promise.allSettled([renderCommunityPosts(), renderLikedPosts()]);
        } catch (error) {
            alert(error.message);
        }
    });

    document.body.addEventListener('click', async (event) => {
        if (await handlePaginationClick(event)) {
            return;
        }

        await handlePostActionClick(event);
    });
}

// Route clicks for post like/comment/share/detail/edit/delete/profile actions.
async function handlePostActionClick(event) {
    const likeButton = event.target.closest('[data-like-post]');
    const commentButton = event.target.closest('[data-comment-post]');
    const shareButton = event.target.closest('[data-share-post]');
    const detailButton = event.target.closest('[data-detail-post]');
    const editButton = event.target.closest('[data-edit-post]');
    const deleteButton = event.target.closest('[data-delete-post]');
    const profileButton = event.target.closest('[data-view-profile]');

    try {
        if (likeButton) {
            await togglePostLike(likeButton.dataset.likePost, likeButton.dataset.liked === 'true');
        } else if (commentButton) {
            openCommentDialog(commentButton.dataset.commentPost);
        } else if (shareButton) {
            await sharePost(shareButton.dataset.sharePost);
        } else if (detailButton) {
            await showPostDetail(detailButton.dataset.detailPost);
        } else if (editButton) {
            openEditPostDialog(editButton.dataset.editPost);
        } else if (deleteButton && confirm('Delete this post?')) {
            await deletePost(deleteButton.dataset.deletePost);
        } else if (profileButton) {
            await showPublicProfile(profileButton.dataset.viewProfile);
        }
    } catch (error) {
        alert(error.message);
    }
}

// Refresh post-related panels depending on the current page.
async function refreshPostViews() {
    const page = currentPage();

    if (page === 'post.html') {
        await Promise.allSettled([renderCommunityPosts(), renderLikedPosts()]);
    } else if (page === 'profile.html') {
        await Promise.allSettled([renderMyPosts(), renderProfileLikedPosts()]);
    }
}

// Like or unlike a post, then refresh the visible post lists.
async function togglePostLike(postId, isLikedNow) {
    await apiRequest(isLikedNow ? '/posts/unlike.php' : '/posts/like.php', {
        method: 'POST',
        body: JSON.stringify({ post_id: postId }),
    });
    await refreshPostViews();
}

// Open the comment form dialog for a selected post.
function openCommentDialog(postId) {
    openFormDialog({
        title: 'Add Comment',
        confirmText: 'Comment',
        pendingText: 'Sending...',
        fields: [
            {
                name: 'content',
                label: 'Comment',
                type: 'textarea',
                required: true,
            },
        ],
        onSubmit: async (payload) => {
            await apiRequest('/posts/comment.php', {
                method: 'POST',
                body: JSON.stringify({
                    post_id: postId,
                    content: payload.content,
                }),
            });
            await returnToPostPageAfterComment();
        },
    });
}

// After adding a comment, return users to the Post page community feed.
async function returnToPostPageAfterComment() {
    if (currentPage() !== 'post.html') {
        window.location.href = 'Post.html';
        return;
    }

    await refreshPostViews();
    setChecked('tab-community');
}

// Record a share action for a post and refresh counters.
async function sharePost(postId) {
    await apiRequest('/posts/share.php', {
        method: 'POST',
        body: JSON.stringify({ post_id: postId }),
    });
    await refreshPostViews();
    alert('Post shared successfully.');
}

// Show the full post detail, either in a dialog or Profile page detail panel.
async function showPostDetail(postId, backTarget = null) {
    const data = await apiRequest(`/posts/detail.php?post_id=${encodeURIComponent(postId)}`);
    const post = data.post;
    const comments = post.comments || [];
    const image = post.image_url || FALLBACK_IMAGE;

    if (currentPage() === 'profile.html' && backTarget) {
        const panel = byId(backTarget === 'liked' ? 'panel-detail-liked' : 'panel-detail-sent');
        panel.innerHTML = `
            <div class="profile-card post-detail-view-box">
                ${postDetailHtml(post, comments, image)}
                <div style="border-top:1px solid #f1f5f9; padding-top:20px;">
                    <label class="btn-profile-action btn-cancel" style="cursor: pointer;" for="${backTarget === 'liked' ? 'state-like-posts' : 'state-sent-posts'}">Back</label>
                </div>
            </div>
        `;
        setChecked(backTarget === 'liked' ? 'state-detail-liked' : 'state-detail-sent');
        return;
    }

    openInfoDialog('Post Information', postDetailHtml(post, comments, image));
}

// Build the reusable post detail HTML including image, metadata, and comments.
function postDetailHtml(post, comments, image) {
    return `
        <div class="post-detail-hero-flex">
            <img class="post-detail-big-cover" src="${escapeHtml(image)}" alt="${escapeHtml(post.title)}">
            <div class="post-detail-info-sheet">
                <h2 style="font-size: 22px; color: var(--dark-bg);">${escapeHtml(post.title || 'Untitled Post')}</h2>
                <p style="font-size: 13px; color: var(--text-light); margin-top: 4px;">Published by: <strong>${escapeHtml(post.author?.username || 'Unknown')}</strong></p>
                <div class="feed-post-description-box" style="margin-top: 15px; min-height: 110px;">${escapeHtml(post.content || 'No content yet.')}</div>
                <div class="feed-post-meta">
                    <span>${escapeHtml(post.like_count ?? 0)} Likes</span>
                    <span>${escapeHtml(post.comment_count ?? comments.length)} Comments</span>
                    <span>${escapeHtml(post.share_count ?? 0)} Shares</span>
                </div>
            </div>
        </div>
        <div class="comments-box">
            <h3><i class="fa-regular fa-comment"></i> Comments</h3>
            ${comments.length ? comments.map((comment) => `
                <div class="comment-row">
                    <strong>${escapeHtml(comment.author?.username || 'Unknown')}</strong>
                    <span>${escapeHtml(comment.content)}</span>
                </div>
            `).join('') : '<div class="feed-header-subtitle">No comments yet.</div>'}
        </div>
    `;
}

// Open the edit-post dialog and upload a replacement image if one is selected.
function openEditPostDialog(postId) {
    const post = state.myPosts.find((item) => item.post_id === postId) || state.posts.find((item) => item.post_id === postId);

    if (!post) {
        alert('Post data is not loaded yet.');
        return;
    }

    openFormDialog({
        title: 'Edit Post',
        confirmText: 'Save',
        fields: [
            { name: 'title', label: 'Title', value: post.title || '', required: true },
            { name: 'content', label: 'Content', type: 'textarea', value: post.content || '', required: true },
            { name: 'image', label: 'Post Image', type: 'file', accept: 'image/*' },
            { name: 'image_url', type: 'hidden', value: post.image_url || '' },
        ],
        onSubmit: async (payload) => {
            if (payload.image instanceof File && payload.image.size > 0) {
                payload.image_url = await uploadImage('post', payload.image);
            }

            delete payload.image;

            await apiRequest('/posts/update.php', {
                method: 'POST',
                body: JSON.stringify({
                    post_id: postId,
                    ...payload,
                }),
            });
            await refreshPostViews();
        },
    });
}

// Soft-delete a post through the API and refresh visible lists.
async function deletePost(postId) {
    await apiRequest('/posts/delete.php', {
        method: 'POST',
        body: JSON.stringify({ post_id: postId }),
    });
    await refreshPostViews();
}

// Show another user's public profile and their active posts.
async function showPublicProfile(userId) {
    const data = await apiRequest(`/profile/view.php?user_id=${encodeURIComponent(userId)}`);
    const profile = data.profile;
    const posts = data.posts || [];
    const avatar = profile.avatar_url || FALLBACK_AVATAR;

    openInfoDialog('Profile', `
        <div class="public-profile-header">
            <img class="public-profile-avatar" src="${escapeHtml(avatar)}" alt="${escapeHtml(profile.username || 'User')}">
        </div>
        <div class="public-profile-fields">
            <div class="public-profile-field">
                <span>Username</span>
                <strong>${escapeHtml(profile.username || 'User')}</strong>
            </div>
            <div class="public-profile-field">
                <span>Anime Interest</span>
                <strong>${escapeHtml(profile.anime_interest || 'No anime interest added yet.')}</strong>
            </div>
        </div>
        <div class="public-profile-post-count">Posts (${posts.length})</div>
        ${posts.length ? posts.map((post) => `
            <div class="comment-row">
                <strong>${escapeHtml(post.title || 'Untitled Post')}</strong>
                <span>${escapeHtml(post.content || '')}</span>
            </div>
        `).join('') : '<div class="feed-header-subtitle">No posts yet.</div>'}
    `);
}

// Render paginated community posts on the Post page.
async function renderCommunityPosts() {
    const panel = byId('panel-community');

    try {
        const data = await apiRequest(`/posts/list.php?${paginationParams('communityPosts')}`);
        const posts = await withCommentPreview(data.posts || []);
        setPagination('communityPosts', data.pagination);
        state.posts = posts;
        panel.innerHTML = `
            <div class="feed-header-title">Community Square</div>
            <div class="feed-header-subtitle">See what's happening around the exhibition.</div>
            ${posts.length ? posts.map((post) => renderPostCard(post, { showCommentPreview: true })).join('') : renderEmpty('No posts found.')}
            ${renderPagination('communityPosts', state.pagination.communityPosts)}
        `;
    } catch (error) {
        panel.innerHTML = renderEmpty(error.message);
    }
}

// Render paginated posts liked by the current user on the Post page.
async function renderLikedPosts() {
    const panel = byId('panel-liked-posts-flow');

    try {
        const data = await apiRequest(`/profile/liked_posts.php?${paginationParams('postLikedPosts')}`);
        const posts = await withCommentPreview(data.posts || []);
        setPagination('postLikedPosts', data.pagination);
        state.likedPosts = posts;
        panel.innerHTML = `
            <div class="feed-header-title">My Like Post</div>
            <div class="feed-header-subtitle">Liked posts (${posts.length})</div>
            ${posts.length ? posts.map((post) => renderPostCard(post, { showCommentPreview: true })).join('') : renderEmpty('You have not liked any posts yet.')}
            ${renderPagination('postLikedPosts', state.pagination.postLikedPosts)}
        `;
    } catch (error) {
        panel.innerHTML = renderEmpty(error.message);
    }
}

// Render the two most recent comments shown under each post card.
function previewCommentsHtml(comments = []) {
    const preview = comments.slice(0, 2);

    return `
        <div class="post-comments-preview">
            <div class="comments-preview-title"><i class="fa-regular fa-comment"></i> Recent Comments</div>
            ${preview.length ? preview.map((comment) => `
                <div class="comment-row">
                    <strong>${escapeHtml(comment.author?.username || 'Unknown')}</strong>
                    <span>${escapeHtml(comment.content)}</span>
                </div>
            `).join('') : '<div class="feed-header-subtitle">No comments yet.</div>'}
        </div>
    `;
}

// Fetch comment previews for the posts currently visible on a page.
async function withCommentPreview(posts) {
    return Promise.all(posts.map(async (post) => {
        try {
            const detail = await apiRequest(`/posts/detail.php?post_id=${encodeURIComponent(post.post_id)}`);
            const comments = [...(detail.post?.comments || [])].sort((left, right) => {
                return new Date(right.created_at || 0).getTime() - new Date(left.created_at || 0).getTime();
            });
            return {
                ...post,
                preview_comments: comments.slice(0, 2),
                comment_count: detail.post?.comment_count ?? post.comment_count,
            };
        } catch {
            return {
                ...post,
                preview_comments: [],
            };
        }
    }));
}

// Render one post card with optional owner controls and comment preview.
function renderPostCard(post, { ownerTools = false, profileDetailTarget = null, showCommentPreview = false } = {}) {
    const image = post.image_url || FALLBACK_IMAGE;
    const liked = post.is_liked ? 'true' : 'false';
    const authorName = post.user_id
        ? `<button type="button" class="inline-link" data-view-profile="${escapeHtml(post.user_id)}">${escapeHtml(post.author?.username || 'Unknown')}</button>`
        : escapeHtml(post.author?.username || 'Unknown');

    return `
        <div class="feed-item-card">
            <div class="feed-item-inner-grid">
                <div class="feed-left-img-box"><img src="${escapeHtml(image)}" alt="${escapeHtml(post.title)}"></div>
                <div class="feed-right-info-box">
                    <div class="feed-post-title">${escapeHtml(post.title || 'Untitled Post')}</div>
                    <div class="feed-post-meta">
                        <span>By ${authorName}</span>
                        <span>${escapeHtml(formatDateTime(post.created_at || post.liked_at))}</span>
                    </div>
                    <div class="feed-post-description-box">${escapeHtml(post.content || 'No content yet.')}</div>
                    <div class="feed-action-bar">
                        <button class="action-btn ${post.is_liked ? 'active-liked' : ''}" data-like-post="${escapeHtml(post.post_id)}" data-liked="${liked}">
                            <i class="${post.is_liked ? 'fa-solid' : 'fa-regular'} fa-heart"></i> ${escapeHtml(post.like_count ?? 0)}
                        </button>
                        <button class="action-btn" type="button" data-comment-post="${escapeHtml(post.post_id)}"><i class="fa-regular fa-comment"></i> ${escapeHtml(post.comment_count ?? 0)}</button>
                        <button class="action-btn" type="button" data-share-post="${escapeHtml(post.post_id)}"><i class="fa-regular fa-share-from-square"></i> ${escapeHtml(post.share_count ?? 0)}</button>
                        <button class="action-btn" type="button" data-detail-post="${escapeHtml(post.post_id)}" ${profileDetailTarget ? `data-profile-detail-target="${escapeHtml(profileDetailTarget)}"` : ''}>Detail</button>
                        ${ownerTools ? `
                            <button class="action-btn" type="button" data-edit-post="${escapeHtml(post.post_id)}"><i class="fa-regular fa-pen-to-square"></i></button>
                            <button class="action-btn" type="button" data-delete-post="${escapeHtml(post.post_id)}"><i class="fa-regular fa-trash-can"></i></button>
                        ` : ''}
                    </div>
                </div>
            </div>
            ${showCommentPreview ? previewCommentsHtml(post.preview_comments || []) : ''}
        </div>
    `;
}

// Initialize the Profile page, including profile edit and profile post lists.
async function initProfile() {
    const profile = await loadCurrentUser({ silent: false }).catch((error) => {
        byId('panel-view-mode').innerHTML = renderEmpty(error.message);
        return null;
    });

    if (profile) {
        renderProfile(profile);
    }

    await Promise.allSettled([renderMyPosts(), renderProfileLikedPosts()]);

    // Save profile changes, uploading a new avatar first when the user selected one.
    byId('profile-edit-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const avatarFile = form.elements.avatar?.files?.[0] || null;
        const payload = {
            username: form.elements.username.value,
            anime_interest: form.elements.anime_interest.value,
            email: form.elements.email.value,
            telephone: form.elements.telephone.value,
            avatar_url: form.elements.avatar_url.value || '',
        };

        try {
            if (avatarFile) {
                payload.avatar_url = await uploadImage('avatar', avatarFile);
            }

            const data = await apiRequest('/profile/update.php', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            storeUser(data.profile);
            renderProfile(data.profile);
            syncProfileText(data.profile);
            await Promise.allSettled([renderMyPosts(), renderProfileLikedPosts()]);
            setChecked('state-view');
        } catch (error) {
            alert(error.message);
        }
    });

    byId('logout-confirm')?.addEventListener('click', async (event) => {
        event.preventDefault();
        try {
            await apiRequest('/auth/logout.php', { method: 'POST', body: '{}' });
        } finally {
            localStorage.removeItem('currentUser');
            window.location.href = 'Login.html';
        }
    });

    document.body.addEventListener('click', async (event) => {
        if (await handlePaginationClick(event)) {
            return;
        }

        const detailButton = event.target.closest('[data-profile-detail-target]');

        if (detailButton) {
            event.preventDefault();
            try {
                await showPostDetail(detailButton.dataset.detailPost, detailButton.dataset.profileDetailTarget);
            } catch (error) {
                alert(error.message);
            }
            return;
        }

        await handlePostActionClick(event);
    });
}

// Render the main profile details panel and keep the edit form in sync.
function renderProfile(profile) {
    const bio = profile.anime_interest || 'No anime interest added yet.';
    const panel = byId('panel-view-mode');

    panel.innerHTML = `
        <div class="section-title"><i class="fa-regular fa-user"></i> My Profile</div>
        <div class="info-display-group">
            <div class="info-display-label">Username</div>
            <div class="info-display-value">${escapeHtml(profile.username)}</div>
        </div>
        <div class="info-display-group">
            <div class="info-display-label">Anime Interest</div>
            <div class="info-display-value bio-text">${escapeHtml(bio)}</div>
        </div>
        <div class="form-row-grid" style="margin-bottom: 0;">
            <div class="form-item-block info-display-group">
                <div class="info-display-label">Email Address</div>
                <div class="info-display-value">${escapeHtml(profile.email)}</div>
            </div>
            <div class="form-item-block info-display-group">
                <div class="info-display-label">Phone Number</div>
                <div class="info-display-value">${escapeHtml(fallback(profile.telephone))}</div>
            </div>
        </div>
        <div class="action-buttons-row" style="margin-top: 25px;">
            <label class="btn-profile-action btn-edit" for="state-edit"><i class="fa-regular fa-pen-to-square" style="margin-right: 8px;"></i> Edit</label>
        </div>
    `;

    const form = byId('profile-edit-form');

    if (form) {
        form.elements.username.value = profile.username || '';
        form.elements.anime_interest.value = profile.anime_interest || '';
        form.elements.email.value = profile.email || '';
        form.elements.telephone.value = profile.telephone || '';
        form.elements.avatar_url.value = profile.avatar_url || '';

        if (form.elements.avatar) {
            form.elements.avatar.value = '';
        }
    }
}

// Render paginated posts created by the current user.
async function renderMyPosts() {
    const panel = byId('panel-sent-posts');

    try {
        const data = await apiRequest(`/profile/my_posts.php?${paginationParams('myPosts')}`);
        const posts = await withCommentPreview(data.posts || []);
        setPagination('myPosts', data.pagination);
        state.myPosts = posts;
        panel.innerHTML = `
            <div class="feed-header-title">My Sent Post</div>
            <div class="feed-header-subtitle">Review all posts you have shared with the exhibition community.</div>
            <div class="scrollable-feed-list">${posts.length ? posts.map((post) => renderPostCard(post, { ownerTools: true, profileDetailTarget: 'sent', showCommentPreview: true })).join('') : renderEmpty('You have not published any posts yet.')}</div>
            ${renderPagination('myPosts', state.pagination.myPosts)}
        `;
    } catch (error) {
        panel.innerHTML = renderEmpty(error.message);
    }
}

// Render paginated posts liked by the current user on the Profile page.
async function renderProfileLikedPosts() {
    const panel = byId('panel-like-posts');

    try {
        const data = await apiRequest(`/profile/liked_posts.php?${paginationParams('profileLikedPosts')}`);
        const posts = await withCommentPreview(data.posts || []);
        setPagination('profileLikedPosts', data.pagination);
        state.likedPosts = posts;
        panel.innerHTML = `
            <div class="feed-header-title">My Like Post</div>
            <div class="feed-header-subtitle">Review all creative gallery updates you have liked.</div>
            <div class="scrollable-feed-list">${posts.length ? posts.map((post) => renderPostCard(post, { profileDetailTarget: 'liked', showCommentPreview: true })).join('') : renderEmpty('You have not liked any posts yet.')}</div>
            ${renderPagination('profileLikedPosts', state.pagination.profileLikedPosts)}
        `;
    } catch (error) {
        panel.innerHTML = renderEmpty(error.message);
    }
}

// Entry point: detect the current HTML page and initialize only the needed features.
document.addEventListener('DOMContentLoaded', () => {
    setInitialLoading();
    applyRoleNavigation(getStoredUser());

    const page = currentPage();

    if (page === 'login.html') {
        initLogin();
    } else if (page === 'register.html') {
        initRegister();
    } else if (page === 'homepage.html') {
        initHome();
    } else if (page === 'event.html') {
        initEvents();
    } else if (page === 'post.html') {
        initPosts();
    } else if (page === 'profile.html') {
        initProfile();
    }
});
