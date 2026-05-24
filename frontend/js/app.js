const API_BASE = '../api';
const FALLBACK_IMAGE = 'https://images.unsplash.com/photo-1560942485-b2a11cc13456?q=80&w=400';
const FALLBACK_AVATAR = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150';

const state = {
    user: null,
    sessionVerified: false,
    events: [],
    registeredEventIds: new Set(),
    posts: [],
    likedPosts: [],
    myPosts: [],
};

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

function byId(id) {
    return document.getElementById(id);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function fallback(value, fallbackValue = '-') {
    return value === null || value === undefined || value === '' ? fallbackValue : value;
}

function setChecked(id) {
    const input = byId(id);
    if (input) {
        input.checked = true;
    }
}

function currentPage() {
    return window.location.pathname.split('/').pop().toLowerCase();
}

function showMessage(container, message, type = 'info') {
    if (!container) {
        alert(message);
        return;
    }

    container.textContent = message;
    container.dataset.type = type;
}

function formatDate(value) {
    if (!value) {
        return 'TBA';
    }

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString();
}

function formatDateTime(value) {
    if (!value) {
        return 'TBA';
    }

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString();
}

function storeUser(user) {
    state.user = user || null;

    if (user) {
        localStorage.setItem('currentUser', JSON.stringify(user));
    }
}

function getStoredUser() {
    try {
        return JSON.parse(localStorage.getItem('currentUser') || 'null');
    } catch {
        return null;
    }
}

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
}

function redirectTargetForUser(user) {
    return user?.role === 'admin' ? 'Event.html' : 'HomePage.html';
}

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

function isAdmin() {
    return state.sessionVerified && state.user?.role === 'admin';
}

function renderEmpty(message, className = 'feed-item-card') {
    return `<div class="${className}">${escapeHtml(message)}</div>`;
}

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

function openFormDialog({ title, fields, confirmText = 'Submit', pendingText = 'Submitting...', onSubmit }) {
    const dialog = ensureDialog();
    dialog.innerHTML = `
        <form method="dialog" class="api-dialog-form">
            <div class="dialog-title">${escapeHtml(title)}</div>
            <div class="dialog-body">
                ${fields.map((field) => `
                    <label class="dialog-field">
                        <span>${escapeHtml(field.label)}</span>
                        ${field.type === 'textarea'
                            ? `<textarea name="${escapeHtml(field.name)}" class="dialog-input" ${field.required ? 'required' : ''}>${escapeHtml(field.value || '')}</textarea>`
                            : `<input type="${escapeHtml(field.type || 'text')}" name="${escapeHtml(field.name)}" class="dialog-input" value="${escapeHtml(field.value || '')}" ${field.required ? 'required' : ''}>`}
                    </label>
                `).join('')}
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
        const payload = Object.fromEntries(new FormData(form).entries());

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

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-dialog-close]')) {
        event.target.closest('dialog')?.close();
    }
});

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

async function initHome() {
    await loadCurrentUser();

    if (isAdmin()) {
        window.location.href = 'Event.html';
    }
}

function isEventFull(event) {
    const availableSlots = Number(event.available_slots);
    return Number.isFinite(availableSlots) && availableSlots <= 0;
}

function slotStatusHtml(event) {
    if (Number.isFinite(Number(event.available_slots))) {
        return `${escapeHtml(event.available_slots)} slots left`;
    }

    return escapeHtml(fallback(event.status, 'Upcoming'));
}

function eventStatusBadgeHtml(event, registered) {
    if (registered) {
        return '<span class="event-status-badge joined-status"><i class="fa-solid fa-circle-check"></i> Joined</span>';
    }

    if (isEventFull(event)) {
        return '<span class="event-status-badge full-status"><i class="fa-solid fa-circle-xmark"></i> Full</span>';
    }

    return '';
}

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

    byId('admin-event-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const payload = Object.fromEntries(new FormData(form).entries());
        const endpoint = payload.event_id ? '/admin/events/update.php' : '/admin/events/create.php';

        try {
            await apiRequest(endpoint, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            resetEventForm();
            setChecked('tab-admin-list');
            await renderEvents();
        } catch (error) {
            alert(error.message);
        }
    });
}

async function refreshEventViews() {
    if (isAdmin()) {
        await renderEvents();
        return;
    }

    const registrations = await loadRegistrationState();
    await renderEvents();
    renderMyEvents(registrations);
}

async function loadMyRegistrations() {
    const data = await apiRequest('/registrations/my_registrations.php');
    const registrations = data.registrations || [];
    state.registeredEventIds = new Set(registrations.map((item) => item.event_id || item.event?.event_id).filter(Boolean));
    return registrations;
}

async function loadRegistrationState() {
    try {
        return await loadMyRegistrations();
    } catch {
        state.registeredEventIds = new Set();
        return [];
    }
}

async function renderEvents() {
    const data = await apiRequest('/events/list.php');
    const events = data.events || [];
    state.events = events;

    const userPanel = byId('panel-user-list');
    const adminPanel = byId('panel-admin-list');

    if (userPanel) {
        userPanel.innerHTML = `
            <div class="feed-header-title">Events</div>
            <div class="feed-header-subtitle">Explore upcoming anime activities on campus.</div>
            ${events.length ? events.map((event) => eventCard(event, { registered: state.registeredEventIds.has(event.event_id) })).join('') : renderEmpty('No events found.', 'event-item-card')}
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
        `;
    }
}

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
    `;
}

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

function renderJoinControl(event) {
    if (event.is_joined) {
        return '<span class="btn-footer btn-joined"><i class="fa-solid fa-circle-check"></i> Joined</span>';
    }

    if (isEventFull(event)) {
        return '<span class="btn-footer btn-full"><i class="fa-solid fa-circle-exclamation"></i> Full</span>';
    }

    return '<label class="btn-footer btn-confirm" for="tab-join-form">Join Event</label>';
}

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

function resetEventForm() {
    const form = byId('admin-event-form');
    form?.reset();

    if (form?.elements.event_id) {
        form.elements.event_id.value = '';
    }

    if (byId('admin-form-title')) {
        byId('admin-form-title').textContent = 'Plan Your Event';
    }
}

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
    form.elements.status.value = event.status || 'Upcoming';
    setChecked('tab-admin-form');
}

async function initPosts() {
    await loadCurrentUser();
    await Promise.allSettled([renderCommunityPosts(), renderLikedPosts()]);

    byId('new-post-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            await apiRequest('/posts/create.php', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            form.reset();
            setChecked('tab-community');
            await Promise.allSettled([renderCommunityPosts(), renderLikedPosts()]);
        } catch (error) {
            alert(error.message);
        }
    });

    document.body.addEventListener('click', handlePostActionClick);
}

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

async function refreshPostViews() {
    const page = currentPage();

    if (page === 'post.html') {
        await Promise.allSettled([renderCommunityPosts(), renderLikedPosts()]);
    } else if (page === 'profile.html') {
        await Promise.allSettled([renderMyPosts(), renderProfileLikedPosts()]);
    }
}

async function togglePostLike(postId, isLikedNow) {
    await apiRequest(isLikedNow ? '/posts/unlike.php' : '/posts/like.php', {
        method: 'POST',
        body: JSON.stringify({ post_id: postId }),
    });
    await refreshPostViews();
}

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

async function returnToPostPageAfterComment() {
    if (currentPage() !== 'post.html') {
        window.location.href = 'Post.html';
        return;
    }

    await refreshPostViews();
    setChecked('tab-community');
}

async function sharePost(postId) {
    await apiRequest('/posts/share.php', {
        method: 'POST',
        body: JSON.stringify({ post_id: postId }),
    });
    await refreshPostViews();
    alert('Post shared successfully.');
}

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
            { name: 'image_url', label: 'Image URL', type: 'url', value: post.image_url || '' },
        ],
        onSubmit: async (payload) => {
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

async function deletePost(postId) {
    await apiRequest('/posts/delete.php', {
        method: 'POST',
        body: JSON.stringify({ post_id: postId }),
    });
    await refreshPostViews();
}

async function showPublicProfile(userId) {
    const data = await apiRequest(`/profile/view.php?user_id=${encodeURIComponent(userId)}`);
    const profile = data.profile;
    const posts = data.posts || [];

    openInfoDialog('Profile', `
        <div class="info-display-group">
            <div class="info-display-label">Username</div>
            <div class="info-display-value">${escapeHtml(profile.username || 'User')}</div>
        </div>
        <div class="info-display-group">
            <div class="info-display-label">Anime Interest</div>
            <div class="info-display-value bio-text">${escapeHtml(profile.anime_interest || 'No anime interest added yet.')}</div>
        </div>
        <div class="feed-header-subtitle">Posts (${posts.length})</div>
        ${posts.length ? posts.map((post) => `
            <div class="comment-row">
                <strong>${escapeHtml(post.title || 'Untitled Post')}</strong>
                <span>${escapeHtml(post.content || '')}</span>
            </div>
        `).join('') : '<div class="feed-header-subtitle">No posts yet.</div>'}
    `);
}

async function renderCommunityPosts() {
    const panel = byId('panel-community');

    try {
        const data = await apiRequest('/posts/list.php');
        const posts = await withCommentPreview(data.posts || []);
        state.posts = posts;
        panel.innerHTML = `
            <div class="feed-header-title">Community Square</div>
            <div class="feed-header-subtitle">See what's happening around the exhibition.</div>
            ${posts.length ? posts.map((post) => renderPostCard(post, { showCommentPreview: true })).join('') : renderEmpty('No posts found.')}
        `;
    } catch (error) {
        panel.innerHTML = renderEmpty(error.message);
    }
}

async function renderLikedPosts() {
    const panel = byId('panel-liked-posts-flow');

    try {
        const data = await apiRequest('/profile/liked_posts.php');
        const posts = await withCommentPreview(data.posts || []);
        state.likedPosts = posts;
        panel.innerHTML = `
            <div class="feed-header-title">My Like Post</div>
            <div class="feed-header-subtitle">Liked posts (${posts.length})</div>
            ${posts.length ? posts.map((post) => renderPostCard(post, { showCommentPreview: true })).join('') : renderEmpty('You have not liked any posts yet.')}
        `;
    } catch (error) {
        panel.innerHTML = renderEmpty(error.message);
    }
}

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

async function initProfile() {
    const profile = await loadCurrentUser({ silent: false }).catch((error) => {
        byId('panel-view-mode').innerHTML = renderEmpty(error.message);
        return null;
    });

    if (profile) {
        renderProfile(profile);
    }

    await Promise.allSettled([renderMyPosts(), renderProfileLikedPosts()]);

    byId('profile-edit-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(event.currentTarget).entries());

        try {
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
    }
}

async function renderMyPosts() {
    const panel = byId('panel-sent-posts');

    try {
        const data = await apiRequest('/profile/my_posts.php');
        const posts = await withCommentPreview(data.posts || []);
        state.myPosts = posts;
        panel.innerHTML = `
            <div class="feed-header-title">My Sent Post</div>
            <div class="feed-header-subtitle">Review all posts you have shared with the exhibition community.</div>
            <div class="scrollable-feed-list">${posts.length ? posts.map((post) => renderPostCard(post, { ownerTools: true, profileDetailTarget: 'sent', showCommentPreview: true })).join('') : renderEmpty('You have not published any posts yet.')}</div>
        `;
    } catch (error) {
        panel.innerHTML = renderEmpty(error.message);
    }
}

async function renderProfileLikedPosts() {
    const panel = byId('panel-like-posts');

    try {
        const data = await apiRequest('/profile/liked_posts.php');
        const posts = await withCommentPreview(data.posts || []);
        state.likedPosts = posts;
        panel.innerHTML = `
            <div class="feed-header-title">My Like Post</div>
            <div class="feed-header-subtitle">Review all creative gallery updates you have liked.</div>
            <div class="scrollable-feed-list">${posts.length ? posts.map((post) => renderPostCard(post, { profileDetailTarget: 'liked', showCommentPreview: true })).join('') : renderEmpty('You have not liked any posts yet.')}</div>
        `;
    } catch (error) {
        panel.innerHTML = renderEmpty(error.message);
    }
}

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
