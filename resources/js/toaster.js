const TOAST_CONTAINER_ID = 'app-toast-container';
const DEFAULT_DURATION_MS = 4500;

const TOAST_THEME = {
    success: {
        panel: 'border-emerald-200 bg-emerald-50/95 text-emerald-900',
        icon: 'fa-solid fa-circle-check text-emerald-600',
        progress: 'bg-emerald-500',
        action: 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-300',
        title: 'Success',
    },
    error: {
        panel: 'border-rose-200 bg-rose-50/95 text-rose-900',
        icon: 'fa-solid fa-circle-xmark text-rose-600',
        progress: 'bg-rose-500',
        action: 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-300',
        title: 'Action Failed',
    },
    warning: {
        panel: 'border-amber-200 bg-amber-50/95 text-amber-900',
        icon: 'fa-solid fa-triangle-exclamation text-amber-600',
        progress: 'bg-amber-500',
        action: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-300',
        title: 'Attention',
    },
    info: {
        panel: 'border-indigo-200 bg-indigo-50/95 text-indigo-900',
        icon: 'fa-solid fa-circle-info text-indigo-600',
        progress: 'bg-indigo-500',
        action: 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-300',
        title: 'Notice',
    },
};

function ensureContainer() {
    if (!document?.body) {
        return null;
    }

    let container = document.getElementById(TOAST_CONTAINER_ID);
    if (container) {
        return container;
    }

    container = document.createElement('div');
    container.id = TOAST_CONTAINER_ID;
    container.className = [
        'pointer-events-none fixed inset-x-4 top-4 z-[120] flex flex-col gap-3',
        'sm:right-4 sm:left-auto sm:w-full sm:max-w-sm',
    ].join(' ');

    document.body.appendChild(container);
    return container;
}

function createElement(tag, className = '', text = '') {
    const element = document.createElement(tag);
    if (className) {
        element.className = className;
    }
    if (text) {
        element.textContent = text;
    }
    return element;
}

function themeFor(type) {
    return TOAST_THEME[type] || TOAST_THEME.info;
}

function dismissToast(toast, callback = null) {
    if (!toast || toast.dataset.removing === 'true') {
        return;
    }

    toast.dataset.removing = 'true';
    toast.classList.add('opacity-0', 'translate-y-2', 'scale-[0.98]');

    window.setTimeout(() => {
        toast.remove();
        if (typeof callback === 'function') {
            callback();
        }
    }, 180);
}

function buildToast({
    type = 'info',
    message,
    title = '',
    duration = DEFAULT_DURATION_MS,
    closeable = true,
    actions = [],
    onClose = null,
}) {
    const theme = themeFor(type);

    const toast = createElement(
        'div',
        [
            'pointer-events-auto relative overflow-hidden rounded-xl border px-4 py-3 shadow-lg backdrop-blur',
            'transition duration-200 ease-out opacity-0 translate-y-2 scale-[0.98]',
            theme.panel,
        ].join(' '),
    );

    const content = createElement('div', 'flex items-start gap-3');
    const icon = createElement('i', `${theme.icon} mt-0.5 text-lg leading-none`);

    const body = createElement('div', 'min-w-0 flex-1');
    const heading = createElement('p', 'text-sm font-semibold', title || theme.title);
    const detail = createElement('p', 'mt-1 text-sm leading-5 opacity-90', String(message || ''));

    body.appendChild(heading);
    body.appendChild(detail);

    content.appendChild(icon);
    content.appendChild(body);

    if (closeable) {
        const closeButton = createElement(
            'button',
            'ml-2 inline-flex h-7 w-7 items-center justify-center rounded-md text-current/70 transition hover:bg-black/5 hover:text-current',
            '',
        );
        closeButton.type = 'button';
        closeButton.setAttribute('aria-label', 'Dismiss notification');
        closeButton.innerHTML = '<i class="fa-solid fa-xmark text-sm"></i>';
        closeButton.addEventListener('click', () => {
            dismissToast(toast);
            if (typeof onClose === 'function') {
                onClose();
            }
        });
        content.appendChild(closeButton);
    }

    toast.appendChild(content);

    if (actions.length > 0) {
        const actionsRow = createElement('div', 'mt-3 flex flex-wrap gap-2 pl-8');

        actions.forEach((action) => {
            const button = createElement(
                'button',
                [
                    'inline-flex items-center rounded-md px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition',
                    'focus:outline-none focus:ring-2 focus:ring-offset-1',
                    action.variant === 'secondary'
                        ? 'bg-slate-500 hover:bg-slate-600 focus:ring-slate-300'
                        : theme.action,
                ].join(' '),
                action.label || 'Action',
            );

            button.type = 'button';
            button.addEventListener('click', () => {
                if (typeof action.onClick === 'function') {
                    action.onClick();
                }
            });

            actionsRow.appendChild(button);
        });

        toast.appendChild(actionsRow);
    }

    if (duration > 0) {
        const progress = createElement(
            'div',
            `absolute bottom-0 left-0 h-1 ${theme.progress} transition-[width] ease-linear`,
        );
        progress.style.width = '100%';
        progress.style.transitionDuration = `${duration}ms`;

        toast.appendChild(progress);

        requestAnimationFrame(() => {
            progress.style.width = '0%';
        });
    }

    return toast;
}

function show(message, options = {}) {
    if (!message) {
        return null;
    }

    const container = ensureContainer();
    if (!container) {
        return null;
    }

    const toast = buildToast({
        type: options.type || 'info',
        title: options.title || '',
        message,
        duration: Number.isFinite(options.duration) ? options.duration : DEFAULT_DURATION_MS,
        closeable: options.closeable !== false,
    });

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('opacity-0', 'translate-y-2', 'scale-[0.98]');
    });

    if ((Number.isFinite(options.duration) ? options.duration : DEFAULT_DURATION_MS) > 0) {
        window.setTimeout(() => {
            dismissToast(toast);
        }, Number.isFinite(options.duration) ? options.duration : DEFAULT_DURATION_MS);
    }

    return {
        dismiss: () => dismissToast(toast),
    };
}

function confirmToast(message, options = {}) {
    if (!message) {
        return Promise.resolve(false);
    }

    const container = ensureContainer();
    if (!container) {
        return Promise.resolve(false);
    }

    return new Promise((resolve) => {
        let done = false;

        const finish = (result) => {
            if (done) {
                return;
            }

            done = true;
            dismissToast(toast, () => resolve(result));
        };

        const toast = buildToast({
            type: options.type || 'warning',
            title: options.title || 'Please Confirm',
            message,
            duration: 0,
            closeable: true,
            onClose: () => finish(false),
            actions: [
                {
                    label: options.confirmText || 'Confirm',
                    onClick: () => finish(true),
                },
                {
                    label: options.cancelText || 'Cancel',
                    variant: 'secondary',
                    onClick: () => finish(false),
                },
            ],
        });

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0', 'translate-y-2', 'scale-[0.98]');
        });
    });
}

const appToaster = {
    show,
    success(message, options = {}) {
        return show(message, { ...options, type: 'success' });
    },
    error(message, options = {}) {
        return show(message, { ...options, type: 'error' });
    },
    warning(message, options = {}) {
        return show(message, { ...options, type: 'warning' });
    },
    info(message, options = {}) {
        return show(message, { ...options, type: 'info' });
    },
    confirm(message, options = {}) {
        return confirmToast(message, options);
    },
};

window.AppToaster = appToaster;

window.notifyApp = function notifyApp(type, message, options = {}) {
    if (typeof message === 'undefined') {
        return appToaster.info(type, options);
    }

    return appToaster.show(message, { ...options, type });
};

window.confirmApp = function confirmApp(message, options = {}) {
    return appToaster.confirm(message, options);
};

if (!window.__appToasterNotificationBridgeAttached) {
    window.__appToasterNotificationBridgeAttached = true;

    window.addEventListener('show-notification', (event) => {
        const detail = event?.detail || {};

        if (!detail.message) {
            return;
        }

        appToaster.show(detail.message, {
            type: detail.type || 'info',
            title: detail.title || '',
            duration: Number.isFinite(detail.duration) ? detail.duration : DEFAULT_DURATION_MS,
        });
    });
}

export default appToaster;
