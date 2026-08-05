document.querySelectorAll('[data-catalog-filters]').forEach((form) => {
    const drawer = form.closest('[data-filter-drawer]');
    if (drawer && matchMedia('(max-width: 900px)').matches) {
        drawer.removeAttribute('open');
    }
    const button = form.querySelector('[data-nearby-search]');
    const latitude = form.querySelector('[data-nearby-latitude]');
    const longitude = form.querySelector('[data-nearby-longitude]');
    const status = form.querySelector('[data-nearby-status]');
    button?.addEventListener('click', () => {
        if (! navigator.geolocation) {
            status.textContent = 'Tu navegador no permite acceder a la ubicación.';
            return;
        }
        button.disabled = true;
        status.textContent = 'Obteniendo tu ubicación…';
        navigator.geolocation.getCurrentPosition(({ coords }) => {
            latitude.value = coords.latitude.toFixed(7);
            longitude.value = coords.longitude.toFixed(7);
            status.textContent = 'Ubicación encontrada. Buscando propiedades…';
            form.submit();
        }, () => {
            button.disabled = false;
            status.textContent = 'No pudimos obtener tu ubicación. Revisa el permiso del navegador.';
        }, { enableHighAccuracy: true, timeout: 10000 });
    });
});
