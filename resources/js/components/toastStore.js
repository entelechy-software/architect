/**
 * toastStore — Alpine store for architect toast notifications.
 *
 * Extracted from architectToast.js and generalised for the package.
 *
 * Usage (JS):
 *   window.architectToast.success('Member saved.')
 *   window.architectToast.error('Something went wrong.')
 *   window.architectToast.warning('Session expires soon.')
 *   window.architectToast.info('Audit trail is not yet available.', 'Audit Trail')
 *
 * The Alpine store ('toasts') is registered in architect.js inside livewire:init.
 * Livewire Engine dispatches the 'architect:toast' browser event,
 * which this store listens for.
 */

let _nextId = 1;

function dispatch(type, message, title = '', opts = {}) {
    const timeout = opts.timeOut ?? 4000;
    const id = _nextId++;

    if (window.Alpine && window.Alpine.store('toasts')) {
        window.Alpine.store('toasts').add({ id, type, message, title, timeout });
    } else {
        console.warn(`[architectToast] ${type}: ${message}`);
    }
}

const architectToast = {
    success: (message, title = '', opts = {}) => dispatch('success', message, title, opts),
    error:   (message, title = '', opts = {}) => dispatch('error',   message, title, opts),
    warning: (message, title = '', opts = {}) => dispatch('warning', message, title, opts),
    info:    (message, title = '', opts = {}) => dispatch('info',    message, title, opts),
};

window.architectToast = architectToast;

export function registerToastStore(Alpine) {
    Alpine.store('toasts', {
        items: [],

        add({ id, type, message, title = '', timeout = 4000 }) {
            this.items.push({ id, type, message, title, timeout });

            if (timeout > 0) {
                setTimeout(() => this.remove(id), timeout);
            }
        },

        remove(id) {
            this.items = this.items.filter(t => t.id !== id);
        },
    });

    // Listen for the 'architect:toast' browser event dispatched by Livewire Engine
    document.addEventListener('architect:toast', (e) => {
        if (!e.detail) return;
        const { message, type = 'info', timeout } = e.detail;
        dispatch(type, message, '', timeout !== undefined ? { timeOut: timeout } : {});
    });
}

export default architectToast;
