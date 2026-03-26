<script>
(function() {
    // Esperar a que el DOM esté listo
    function initPickupModal() {
        console.log('🔥 Inicializando script pickup modal...');

        // Cargar el script completo si no está cargado
        if (typeof window.searchLocation === 'undefined') {
            fetch('/js/pickup-modal-complete.js')
                .then(r => r.text())
                .then(scriptText => {
                    eval(scriptText);
                    console.log('✅ Script pickup-modal-complete.js cargado y ejecutado');
                })
                .catch(e => console.error('Error cargando script:', e));
        }

        // Agregar listener al botón
        const btn = document.getElementById('openPickupModalBtn');
        if (btn && !btn.hasAttribute('data-listener-attached')) {
            btn.setAttribute('data-listener-attached', 'true');
            btn.onclick = function(e) {
                e.preventDefault();
                if (typeof window.openPickupModal === 'function') {
                    window.openPickupModal();
                } else {
                    console.error('openPickupModal no está definida');
                }
            };
            console.log('✅ Click handler agregado al botón');
        }
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPickupModal);
    } else {
        initPickupModal();
    }

    // Re-ejecutar en navegaciones de Livewire
    document.addEventListener('livewire:navigated', initPickupModal);
})();
</script>