{{--
    Toast notification container.

    Listens for 'architect:toast:show' browser events dispatched by ToastManager.
    Alpine.js handles the fade-in/out animation and auto-dismiss timer.
--}}
<div
    class="arch-toast-container"
    data-position="{{ config('architect.toast.position', 'bottom-right') }}"
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now();
            this.toasts.push({ ...toast, id, visible: false });
            this.$nextTick(() => {
                const t = this.toasts.find(t => t.id === id);
                if (t) t.visible = true;
            });
            if (toast.dismissAfter && toast.dismissAfter > 0) {
                setTimeout(() => this.remove(id), toast.dismissAfter);
            }
        },
        remove(id) {
            const t = this.toasts.find(t => t.id === id);
            if (t) t.visible = false;
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
        }
    }"
    @architect:toast:show.window="add($event.detail.toast ?? $event.detail)"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="arch-toast"
            :class="'arch-toast--' + toast.severity"
            x-show="toast.visible"
            x-transition:enter="arch-toast-enter"
            x-transition:leave="arch-toast-leave"
            role="alert"
        >
            <span class="arch-toast__message" x-text="toast.message"></span>
            <template x-if="toast.dismissible !== false">
                <button type="button" class="arch-toast__close" @click="remove(toast.id)" aria-label="{{ __('Dismiss') }}">
                    &times;
                </button>
            </template>
        </div>
    </template>
</div>
