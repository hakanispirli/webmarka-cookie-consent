import * as esbuild from 'esbuild';

const watch = process.argv.includes('--watch');
const target = 'es2020';
const notice = '/*! Webmarka Cookie Consent | (c) 2026 Webmarka | MIT License | https://github.com/hakanispirli/webmarka-cookie-consent */';
const banner = { js: notice, css: notice };

/** @type {esbuild.BuildOptions[]} */
const builds = [
    // Auto-booting browser build used by @cookieConsentScripts.
    { entryPoints: ['resources/js/browser.ts'], outfile: 'dist/cookie-consent.js', format: 'iife' },
    // ESM build for bundlers (Vite, etc.).
    { entryPoints: ['resources/js/index.ts'], outfile: 'dist/cookie-consent.esm.js', format: 'esm' },
    // Alpine.js store.
    { entryPoints: ['resources/js/adapters/alpine-browser.ts'], outfile: 'dist/cookie-consent-alpine.js', format: 'iife' },
    { entryPoints: ['resources/js/adapters/alpine.ts'], outfile: 'dist/cookie-consent-alpine.esm.js', format: 'esm' },
    // Styles.
    { entryPoints: ['resources/css/cookie-consent.css'], outfile: 'dist/cookie-consent.css' },
].map((options) => ({
    bundle: true,
    minify: true,
    sourcemap: false,
    legalComments: 'none',
    target,
    banner,
    logLevel: 'info',
    ...options,
}));

if (watch) {
    await Promise.all(builds.map(async (options) => (await esbuild.context(options)).watch()));
} else {
    await Promise.all(builds.map((options) => esbuild.build(options)));
}
