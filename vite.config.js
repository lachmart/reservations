import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Vite config aligned with DDEV manual for Laravel
export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
  server: {
    // Respond to all network requests
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    // Defines the origin of the generated asset URLs during development. Use DDEV primary URL
    // and strip a possible custom port before appending Vite's port.
    origin: `${process.env.DDEV_PRIMARY_URL.replace(/:\d+$/, '')}:5173`,
    // Configure CORS securely for *.ddev.site domains. Adjust if you use a different project_tld.
    cors: {
      origin: /https?:\/\/([A-Za-z0-9\-\.]+)?(\.ddev\.site)(?::\d+)?$/,
    },
  },
});
