/**
 * Dropdown do Menu de Usuário
 * Controla abertura/fechamento do menu dropdown
 */

(function() {
    'use strict';

    // Inicializar quando DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        const dropdown = document.querySelector('.user-dropdown');
        const toggle = document.querySelector('.user-dropdown-toggle');

        if (!dropdown || !toggle) return;

        // Toggle dropdown ao clicar
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
        });

        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Fechar ao pressionar ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && dropdown.classList.contains('active')) {
                dropdown.classList.remove('active');
            }
        });
    });
})();
