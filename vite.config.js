import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    build: {
        lib: {
            entry:    resolve(__dirname, 'resources/js/architect.js'),
            name:     'ArchitectPackage',
            // Explicit .js extension — Vite lib mode otherwise appends the
            // format name (architect.iife.js), which doesn't match the
            // @architectScripts directive's expected /architect.js path.
            fileName: () => 'architect.js',
            formats:  ['iife'],
        },
        outDir:    'resources/dist',
        emptyOutDir: false,   // CSS is written by Tailwind CLI separately
        rollupOptions: {
            // Alpine is injected by Livewire at runtime — keep it external
            external: ['alpinejs'],
            output: {
                globals: { alpinejs: 'Alpine' },
            },
        },
    },
});
