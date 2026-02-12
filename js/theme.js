/**
 * Sistema de Temas - TSI
 * Sincronização global de tema claro/escuro
 */

(function() {
    'use strict';

    // Carregar tema salvo imediatamente (antes do DOM estar pronto)
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }

    // Função para alternar tema
    window.toggleTheme = function() {
        const html = document.documentElement;
        const isDark = html.getAttribute('data-theme') === 'dark';

        if (isDark) {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        }

        // Atualizar ícones se existirem
        updateThemeIcons();

        // Disparar evento customizado para outros componentes
        window.dispatchEvent(new CustomEvent('themechange', {
            detail: { theme: isDark ? 'light' : 'dark' }
        }));

        return !isDark;
    };

    // Atualizar ícones do botão de tema
    function updateThemeIcons() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const lightIcon = document.getElementById('theme-icon-light');
        const darkIcon = document.getElementById('theme-icon-dark');

        if (lightIcon && darkIcon) {
            if (isDark) {
                lightIcon.style.display = 'block';
                darkIcon.style.display = 'none';
            } else {
                lightIcon.style.display = 'none';
                darkIcon.style.display = 'block';
            }
        }
    }

    // Inicializar quando DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        updateThemeIcons();
    });

    // Detectar mudança de preferência do sistema
    if (window.matchMedia) {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');

        darkModeQuery.addEventListener('change', function(e) {
            // Só aplicar se o usuário não tiver uma preferência salva
            if (!localStorage.getItem('theme')) {
                if (e.matches) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
                updateThemeIcons();
            }
        });
    }
})();
