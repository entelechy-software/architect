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
import { registerArchitectToolbar }     from './components/architectToolbar.js';
import { registerArchitectSupersearch } from './components/architectSupersearch.js';
import { registerModuleTabs }           from './components/moduleTabs.js';
import { registerModuleTable }          from './components/moduleTable.js';
import { registerArchitectChart }       from './components/architectChart.js';
import { registerStepperGuard }         from './components/stepperGuard.js';
import { registerToastStore }           from './components/toastStore.js';
import { registerArchitectForms }       from './components/architectForms.js';

export function registerArchitectComponents(Alpine) {
    registerArchitectToolbar(Alpine);
    registerArchitectSupersearch(Alpine);
    registerModuleTabs(Alpine);
    registerModuleTable(Alpine);
    registerArchitectChart(Alpine);
    registerStepperGuard(Alpine);
    registerToastStore(Alpine);
    registerArchitectForms(Alpine);
}

// IIFE auto-boot: when @architectScripts loads this without a bundler,
// Livewire has already fired livewire:init — use the late-init event.
if (typeof window !== 'undefined') {
    document.addEventListener('livewire:init', () => {
        if (window.Alpine) registerArchitectComponents(window.Alpine);
    });
}
