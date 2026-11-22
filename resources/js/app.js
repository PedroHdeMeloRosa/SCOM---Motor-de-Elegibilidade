import 'bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Sincroniza troca de conta e logout entre abas do mesmo navegador
const metaUser = document.querySelector('meta[name="scom-user-id"]');
if (metaUser) {
    const usuarioAtual = metaUser.content || '';
    localStorage.setItem('scom_active_user', usuarioAtual);

    window.addEventListener('storage', (event) => {
        if (event.key === 'scom_active_user' && event.newValue !== usuarioAtual) {
            window.location.reload();
        }
    });
}