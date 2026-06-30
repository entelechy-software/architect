/**
 * Entelechy Architect — package JS entry point.
 *
 * Exported function — host apps call this inside their own livewire:init.
 * Alpine is provided by Livewire auto-injection; never bundled here.
 *
 * Usage (Vite-bundled host app):
 *   import { registerArchitectComponents } from '../../packages/architect/resources/js/architect.js';
 *   document.addEventListener('livewire:init', () => {
 *       registerArchitectComponents(window.Alpine);
 *   });
 */
import flatpickr                        from 'flatpickr';
import { registerArchitectToolbar }     from './components/architectToolbar.js';
import { registerArchitectSupersearch } from './components/architectSupersearch.js';
import { registerModuleTabs }           from './components/moduleTabs.js';
import { registerModuleTable }          from './components/moduleTable.js';
import { registerArchitectChart }       from './components/architectChart.js';
import { registerStepperGuard }         from './components/stepperGuard.js';
import { registerToastStore }           from './components/toastStore.js';
import { registerArchitectForms }       from './components/architectForms.js';
import { registerDashboardEdit }        from './components/dashboardEdit.js';

export function registerArchitectComponents(Alpine) {
    registerArchitectToolbar(Alpine);
    registerArchitectSupersearch(Alpine);
    registerModuleTabs(Alpine);
    registerModuleTable(Alpine);
    registerArchitectChart(Alpine);
    registerStepperGuard(Alpine);
    registerToastStore(Alpine);
    registerArchitectForms(Alpine);
    registerDashboardEdit(Alpine);
}

// Expose flatpickr globally so blade views can call flatpickr(...) directly.
if (typeof window !== 'undefined') {
    window.flatpickr = flatpickr;
}

// IIFE auto-boot: when @architectScripts loads this as a plain <script>,
// ensure components are registered regardless of whether livewire:init has
// already fired (deferred script loaded after Livewire) or not yet.
if (typeof window !== 'undefined') {
    let registered = false;
    const boot = () => {
        if (!registered && window.Alpine) {
            registered = true;
            registerArchitectComponents(window.Alpine);
        }
    };
    document.addEventListener('livewire:init', boot);
    // Immediate fallback: if livewire:init already fired before this script ran,
    // Alpine is already available — register components now so the next
    // Livewire-driven Alpine initialisation picks them up.
    boot();
}
