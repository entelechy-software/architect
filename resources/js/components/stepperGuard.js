/**
 * stepperGuard — Alpine.js component for stepper navigation guards.
 *
 * Intercepts forward-step clicks and runs configured validation gates
 * before allowing navigation. Backward navigation (to prior steps)
 * is never blocked at the JS layer.
 *
 * Gates (run in order, each optional):
 *   1. HTML5 form validity  — checkValidity() / reportValidity()
 *   2. Livewire method call — await $wire.methodName() → bool
 *
 * Usage (emitted by stepper.blade.php when guards are configured):
 *   x-data="stepperGuard({ formId: 'my-form', wireMethod: 'validateStep' })"
 *
 * @param {Object}      options
 * @param {string|null} options.formId     — HTML id of the <form> to validate.
 * @param {string|null} options.wireMethod — Livewire component method name.
 */
export function registerStepperGuard(Alpine) {
    Alpine.data('stepperGuard', ({ formId = null, wireMethod = null } = {}) => ({
        /**
         * Run all configured validation gates, then navigate if all pass.
         *
         * Called by guarded step anchor clicks via:
         *   x-on:click.prevent="guardNavigate('{{ $url }}')"
         *
         * @param {string} href  Destination URL.
         */
        async guardNavigate(href) {
            // Gate 1: HTML5 form validity
            if (formId) {
                const form = document.getElementById(formId);
                if (form && !form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
            }

            // Gate 2: Livewire method (must return bool; false = block)
            if (wireMethod && this.$wire) {
                try {
                    const valid = await this.$wire[wireMethod]();
                    if (!valid) {
                        return;
                    }
                } catch {
                    // Method threw (e.g. ValidationException bubbled) —
                    // block navigation; let Livewire render its own errors.
                    return;
                }
            }

            // All gates passed — navigate
            window.location.href = href;
        },
    }));
}
