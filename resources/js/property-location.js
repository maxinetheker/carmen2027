import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '../css/location-admin.css';

document.querySelectorAll('[data-location-picker]').forEach((picker) => {
    const mapElement = picker.querySelector('[data-location-map]');
    const latitudeInput = picker.querySelector('[data-location-latitude]');
    const longitudeInput = picker.querySelector('[data-location-longitude]');
    const status = picker.querySelector('[data-location-status]');
    const selectedLatitude = Number(picker.dataset.latitude);
    const selectedLongitude = Number(picker.dataset.longitude);
    const hasSelection = Number.isFinite(selectedLatitude)
        && Number.isFinite(selectedLongitude) && picker.dataset.latitude !== '';
    const center = hasSelection
        ? [selectedLatitude, selectedLongitude]
        : [Number(picker.dataset.defaultLatitude), Number(picker.dataset.defaultLongitude)];
    const map = L.map(mapElement, { scrollWheelZoom: false }).setView(center, hasSelection ? 16 : 12);
    const icon = L.divIcon({
        className: 'location-map-pin',
        html: '<span class="material-symbols-rounded">location_on</span>',
        iconAnchor: [20, 40], iconSize: [40, 40],
    });
    let marker;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    const update = (latitude, longitude, move = false, notify = true) => {
        const point = [latitude, longitude];
        if (! marker) {
            marker = L.marker(point, { draggable: true, icon }).addTo(map);
            marker.on('dragend', () => {
                const position = marker.getLatLng();
                update(position.lat, position.lng);
            });
        } else {
            marker.setLatLng(point);
        }
        latitudeInput.value = Number(latitude).toFixed(7);
        longitudeInput.value = Number(longitude).toFixed(7);
        status.textContent = `Ubicación: ${latitudeInput.value}, ${longitudeInput.value}`;
        if (notify) latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
        if (move) map.setView(point, 16);
    };

    if (hasSelection) update(selectedLatitude, selectedLongitude, false, false);
    map.on('click', (event) => update(event.latlng.lat, event.latlng.lng));
    picker.querySelector('[data-location-current]')?.addEventListener('click', () => {
        if (! navigator.geolocation) {
            status.textContent = 'Tu navegador no permite obtener la ubicación.';
            return;
        }
        status.textContent = 'Obteniendo ubicación…';
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => update(coords.latitude, coords.longitude, true),
            () => { status.textContent = 'No se pudo obtener tu ubicación.'; },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
    picker.querySelector('[data-location-clear]')?.addEventListener('click', () => {
        marker?.remove();
        marker = null;
        latitudeInput.value = '';
        longitudeInput.value = '';
        latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
        status.textContent = 'Selecciona un punto en el mapa';
        map.setView(center, 12);
    });
    picker.closest('form')?.dispatchEvent(new Event('formbaselinechange'));
    window.setTimeout(() => map.invalidateSize(), 100);
});
