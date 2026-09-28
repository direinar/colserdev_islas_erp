// PWA: registra el service worker (public/sw.js) y maneja el botón
// "Instalar aplicación" del menú (data-pwa-install), que solo aparece
// cuando el navegador ofrece instalar el ERP.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Sin HTTPS (o en navegadores sin soporte) el ERP sigue funcionando como web normal.
        });
    });
}

let installPrompt = null;

function toggleInstallButtons(visible) {
    document.querySelectorAll('[data-pwa-install]').forEach(item => item.classList.toggle('d-none', !visible));
}

window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    toggleInstallButtons(true);
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    toggleInstallButtons(false);
});

document.addEventListener('click', async event => {
    const button = event.target.closest('[data-pwa-install-button]');

    if (!button || !installPrompt) {
        return;
    }

    installPrompt.prompt();
    await installPrompt.userChoice;
    installPrompt = null;
    toggleInstallButtons(false);
});
