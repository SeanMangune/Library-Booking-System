function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildPendingApprovalItem(item, approvalsUrl) {
    const roomName = escapeHtml(item?.room_name || 'Room');
    const userName = escapeHtml(item?.user_name || 'User');
    const createdAtHuman = escapeHtml(item?.created_at_human || 'Just now');

    return `
        <a href="${approvalsUrl}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition-colors">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                    <i class="w-5 h-5 text-amber-600 fa-icon fa-solid fa-clock text-xl leading-none"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">${roomName}</p>
                    <p class="text-xs text-gray-500">${userName} requested booking</p>
                    <p class="text-xs text-gray-400 mt-1">${createdAtHuman}</p>
                </div>
            </div>
        </a>
    `;
}

function normalizeUserUrl(url, isStaff) {
    const value = String(url || '#');
    if (value === '/logout' || value === window.LaravelLogoutUrl) {
        return '#';
    }
    if (isStaff) {
        return value;
    }
    const blocked = ['/rooms/approvals', '/rooms/manage', '/reports', '/settings', '/api/users/search', '/logout'];
    return blocked.some((fragment) => value.includes(fragment)) ? '/rooms' : value;
}
// Expose the logout route to JS for defensive checks
window.LaravelLogoutUrl = (typeof window.LaravelLogoutUrl !== 'undefined') ? window.LaravelLogoutUrl : (document.querySelector('form[action][method="POST"]')?.action.includes('/logout') ? document.querySelector('form[action][method="POST"]')?.action : '/logout');

function buildUnreadNotificationItem(item, isStaff) {
    const url = escapeHtml(normalizeUserUrl(item?.url || '#', isStaff));
    const rawId = item?.id != null ? String(item.id) : '';
    const idAttr = escapeHtml(rawId);
    const title = escapeHtml(item?.title || 'Notification');
    const message = escapeHtml(item?.message || '');
    const createdAtHuman = escapeHtml(item?.created_at_human || 'Just now');

    return `
        <a href="${url}" data-notification-id="${idAttr}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition-colors">
            <p class="text-sm font-medium text-gray-900">${title}</p>
            <p class="text-xs text-gray-600 mt-1">${message}</p>
            <p class="text-xs text-gray-400 mt-1">${createdAtHuman}</p>
        </a>
    `;
}

function showNotificationToast(payload) {
    const data = payload?.data && typeof payload.data === 'object' ? payload.data : payload;
    const message = data?.message ?? payload?.message;
    const title = data?.title ?? payload?.title ?? 'Notification';

    if (!message) {
        return;
    }

    window.dispatchEvent(
        new CustomEvent('show-notification', {
            detail: {
                type: 'info',
                title,
                message,
            },
        }),
    );
}

function initializeRealtimeNotifications() {
    const root = document.getElementById('header-notification-root');

    if (!root) {
        return;
    }

    const userId = Number(root.dataset.userId || 0);
    const isStaffUser = root.dataset.isStaff === '1';
    const unreadUrl = root.dataset.unreadUrl || '';
    const approvalsUrl = root.dataset.approvalsUrl || '#';

    if (!userId || !unreadUrl) {
        return;
    }

    const badge = root.querySelector('[data-role="header-notification-badge"]');
    const unreadChip = root.querySelector('[data-role="header-unread-chip"]');
    const pendingSection = root.querySelector('[data-role="pending-approvals-section"]');
    const pendingList = root.querySelector('[data-role="pending-approvals-list"]');
    const unreadList = root.querySelector('[data-role="user-unread-list"]');
    const markAllReadContainer = root.querySelector('[data-role="mark-all-read-container"]');
    const markAllReadForm = root.querySelector('[data-role="mark-all-read-form"]');

    const POLL_INTERVAL_MS = 8000;
    let pollTimer = null;
    let isRefreshing = false;
    let queuedRefresh = false;

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const renderState = (state) => {
        const totalCount = Number(state?.header_notification_count || 0);
        const unreadCount = Number(state?.user_unread_count || 0);
        const isStaff = Boolean(state?.is_staff);
        const pendingApprovals = Array.isArray(state?.recent_pending_approvals)
            ? state.recent_pending_approvals
            : [];
        const unreadNotifications = Array.isArray(state?.user_unread_notifications)
            ? state.user_unread_notifications
            : [];

        if (badge) {
            badge.textContent = String(totalCount);
            badge.classList.toggle('hidden', totalCount <= 0);
        }

        if (unreadChip) {
            unreadChip.textContent = `${totalCount} unread`;
            unreadChip.classList.toggle('hidden', totalCount <= 0);
        }

        if (pendingSection) {
            pendingSection.classList.toggle('hidden', !isStaff);
        }

        if (pendingList && isStaff) {
            if (pendingApprovals.length > 0) {
                pendingList.innerHTML = pendingApprovals
                    .map((item) => buildPendingApprovalItem(item, approvalsUrl))
                    .join('');
            } else {
                pendingList.innerHTML = `
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm text-gray-500">No pending approvals right now.</p>
                    </div>
                `;
            }
        }

        if (unreadList) {
            if (unreadNotifications.length > 0) {
                unreadList.innerHTML = unreadNotifications
                    .map((item) => buildUnreadNotificationItem(item, isStaffUser))
                    .join('');
            } else {
                unreadList.innerHTML = `
                    <div class="px-4 py-8 text-center">
                        <i class="w-12 h-12 text-gray-300 mx-auto mb-2 fa-icon fa-solid fa-inbox text-5xl leading-none"></i>
                        <p class="text-sm text-gray-500">No unread notifications</p>
                    </div>
                `;
            }
        }

        if (markAllReadContainer) {
            markAllReadContainer.classList.toggle('hidden', unreadCount <= 0);
        }
    };

    const refreshState = async () => {
        if (!window.axios) {
            return;
        }

        const response = await window.axios.get(unreadUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        renderState(response.data || {});
    };

    const refreshStateSafe = async () => {
        if (isRefreshing) {
            queuedRefresh = true;
            return;
        }

        isRefreshing = true;

        try {
            await refreshState();
        } finally {
            isRefreshing = false;

            if (queuedRefresh) {
                queuedRefresh = false;
                await refreshStateSafe();
            }
        }
    };

    const startPolling = () => {
        if (pollTimer) {
            window.clearInterval(pollTimer);
        }

        pollTimer = window.setInterval(() => {
            refreshStateSafe().catch(() => {
                // Keep the last rendered notification state when polling fails.
            });
        }, POLL_INTERVAL_MS);
    };

    const stopPolling = () => {
        if (!pollTimer) {
            return;
        }

        window.clearInterval(pollTimer);
        pollTimer = null;
    };

    if (markAllReadForm) {
        markAllReadForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!window.axios) {
                markAllReadForm.submit();
                return;
            }

            try {
                await window.axios.post(
                    markAllReadForm.action,
                    {},
                    {
                        headers: {
                            Accept: 'application/json',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                        },
                    },
                );
                await refreshStateSafe();
            } catch (error) {
                markAllReadForm.submit();
            }
        });
    }

    if (unreadList) {
        unreadList.addEventListener('click', async (event) => {
            const link = event.target.closest('a[data-notification-id]');
            if (!link) {
                return;
            }

            const notificationId = link.getAttribute('data-notification-id');
            if (!notificationId) {
                return;
            }

            event.preventDefault();

            const href = link.getAttribute('href') || '#';

            if (window.axios) {
                try {
                    await window.axios.post(
                        `/notifications/${encodeURIComponent(notificationId)}/read`,
                        {},
                        {
                            headers: {
                                Accept: 'application/json',
                                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                            },
                        },
                    );
                } catch {
                    // Still navigate; mark-read is best-effort.
                }
            }

            if (href && href !== '#') {
                window.location.assign(href);
            } else {
                await refreshStateSafe();
            }
        });
    }

    if (window.Echo) {
        window.Echo.private(`App.Models.User.${userId}`).notification((notification) => {
            showNotificationToast(notification || {});
            refreshStateSafe().catch(() => {
                // Ignore transient realtime refresh failures.
            });
        });
    }

    startPolling();

    refreshStateSafe().catch(() => {
        // Keep the server-rendered dropdown content when initial sync fails.
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            return;
        }

        refreshStateSafe().catch(() => {
            // Ignore transient focus refresh failures.
        });
    });

    window.addEventListener('focus', () => {
        refreshStateSafe().catch(() => {
            // Ignore transient focus refresh failures.
        });
    });

    window.addEventListener('app:notifications-refresh', () => {
        refreshStateSafe().catch(() => {
            // Ignore refresh failures triggered by custom events.
        });
    });

    window.addEventListener('beforeunload', stopPolling);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeRealtimeNotifications);
} else {
    initializeRealtimeNotifications();
}
