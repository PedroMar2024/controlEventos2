/**
 * Geocoder para eventos - Búsqueda de direcciones con Nominatim + Leaflet
 * @author Tu Nombre
 * @version 1.0
 */

(function() {
    'use strict';
    
    // ========================================
    // 1. ELEMENTOS DEL DOM
    // ========================================
    const searchInput = document.getElementById('ubicacion-search');
    const suggestionsBox = document.getElementById('ubicacion-suggestions');
    const loadingIndicator = document.getElementById('ubicacion-loading');
    const mapContainer = document.getElementById('map-container');
    const mapDiv = document.getElementById('map');
    const ubicacionInfo = document.getElementById('ubicacion-info');
    const ubicacionDisplay = document.getElementById('ubicacion-display');
    const coordenadasDisplay = document.getElementById('coordenadas-display');
    const cambiarBtn = document.getElementById('cambiar-ubicacion');
    
    // Campos ocultos que se envían al servidor
    const ubicacionInput = document.getElementById('ubicacion');
    const latitudInput = document.getElementById('latitud');
    const longitudInput = document.getElementById('longitud');
    const localidadInput = document.getElementById('localidad');
    const provinciaInput = document.getElementById('provincia');
    
    let map = null;
    let marker = null;
    let searchTimeout = null;
    
    // ========================================
    // 2. FUNCIÓN: Buscar direcciones en Nominatim
    // ========================================
    async function buscarDirecciones(query) {
        if (query.length < 3) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.classList.add('hidden');
            return;
        }
        
        loadingIndicator.classList.remove('hidden');
        
        try {
            // ========================================
            // NUEVA: Llamada al backend (proxy seguro)
            // ========================================
            const response = await fetch(
                `/api/geocode?address=${encodeURIComponent(query + ', Santa Cruz, Argentina')}&components=country:AR|administrative_area:Santa Cruz`,
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );
            
            if (!response.ok) {
                throw new Error('Error en la búsqueda');
            }
            
            const data = await response.json();
            
            // Adaptamos el formato de Google Maps al formato que espera mostrarSugerencias()
            const resultados = data.results.map(lugar => ({
                lat: lugar.geometry.location.lat,
                lon: lugar.geometry.location.lng,
                display_name: lugar.formatted_address,
                address: {
                    city: lugar.locality || '',
                    town: lugar.locality || '',
                    state: lugar.administrative_area_level_1 || ''
                }
            }));
            
            mostrarSugerencias(resultados);
            
        } catch (error) {
            console.error('Error al buscar direcciones:', error);
            suggestionsBox.innerHTML = '<div class="p-3 text-sm text-red-600">Error al buscar. Intentá de nuevo.</div>';
            suggestionsBox.classList.remove('hidden');
        } finally {
            loadingIndicator.classList.add('hidden');
        }
    }
    
    // ========================================
    // 3. FUNCIÓN: Mostrar sugerencias en lista
    // ========================================
    function mostrarSugerencias(resultados) {
        if (resultados.length === 0) {
            suggestionsBox.innerHTML = '<div class="p-3 text-sm text-gray-500">No se encontraron resultados</div>';
            suggestionsBox.classList.remove('hidden');
            return;
        }
        
        suggestionsBox.innerHTML = resultados.map(lugar => `
            <div class="suggestion-item p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-200 last:border-b-0"
                 data-lat="${lugar.lat}"
                 data-lon="${lugar.lon}"
                 data-display="${lugar.display_name}"
                 data-localidad="${lugar.address?.city || lugar.address?.town || lugar.address?.village || ''}"
                 data-provincia="${lugar.address?.state || ''}">
                <p class="text-sm font-medium text-gray-900">${lugar.display_name}</p>
                <p class="text-xs text-gray-500">📍 ${lugar.lat}, ${lugar.lon}</p>
            </div>
        `).join('');
        
        suggestionsBox.classList.remove('hidden');
        
        // Event listeners para cada sugerencia
        document.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => seleccionarUbicacion(item));
        });
    }
    
    // ========================================
    // 4. FUNCIÓN: Cuando se selecciona una dirección
    // ========================================
    function seleccionarUbicacion(item) {
        const lat = parseFloat(item.dataset.lat);
        const lon = parseFloat(item.dataset.lon);
        const displayName = item.dataset.display;
        const localidad = item.dataset.localidad;
        const provincia = item.dataset.provincia;
        
        // Rellenar campos ocultos
        ubicacionInput.value = displayName;
        latitudInput.value = lat;
        longitudInput.value = lon;
        localidadInput.value = localidad;
        provinciaInput.value = provincia;
        
        // Mostrar información de confirmación
        ubicacionDisplay.textContent = displayName;
        coordenadasDisplay.textContent = `Coordenadas: ${lat}, ${lon}`;
        ubicacionInfo.classList.remove('hidden');
        
        // Ocultar búsqueda y sugerencias
        searchInput.value = '';
        suggestionsBox.classList.add('hidden');
        searchInput.classList.add('hidden');
        
        // Mostrar mapa
        mostrarMapa(lat, lon, displayName);
    }
    
    // ========================================
    // 5. FUNCIÓN: Mostrar mapa con Leaflet
    // ========================================
    function mostrarMapa(lat, lon, nombre) {
        mapContainer.classList.remove('hidden');
        
        // Si ya existe un mapa, destruirlo
        if (map) {
            map.remove();
        }
        
        // Crear mapa nuevo
        map = L.map('map').setView([lat, lon], 15);
        
        // Agregar capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Agregar marcador rojo
        marker = L.marker([lat, lon]).addTo(map)
            .bindPopup(`<b>${nombre}</b><br>📍 ${lat}, ${lon}`)
            .openPopup();
    }
    
    // ========================================
    // 6. FUNCIÓN: Cambiar ubicación
    // ========================================
    cambiarBtn.addEventListener('click', () => {
        searchInput.classList.remove('hidden');
        searchInput.value = '';
        searchInput.focus();
        ubicacionInfo.classList.add('hidden');
        mapContainer.classList.add('hidden');
    });
    
    // ========================================
    // 7. EVENT LISTENERS
    // ========================================
    
    // Búsqueda con debounce
    // ========================================
// 3. DEBOUNCE (Esperar antes de buscar)
// ========================================
let debounceTimer = null;

searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    // Limpiar búsqueda anterior si estaba pendiente
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    
    // Si el campo está vacío, ocultar sugerencias
    if (query.length === 0) {
        suggestionsBox.classList.add('hidden');
        loadingIndicator.classList.add('hidden');
        return;
    }
    
    // Si hay menos de 3 caracteres, no buscar todavía
    if (query.length < 3) {
        suggestionsBox.innerHTML = '<div class="p-3 text-sm text-gray-500">Escribí al menos 3 caracteres</div>';
        suggestionsBox.classList.remove('hidden');
        loadingIndicator.classList.add('hidden');
        return;
    }
    
    // Mostrar indicador de carga
    loadingIndicator.classList.remove('hidden');
    suggestionsBox.classList.add('hidden');
    
    // DEBOUNCE: Esperar 500ms antes de buscar
    debounceTimer = setTimeout(() => {
        buscarDirecciones(query);
    }, 500);
});
    
    // Ocultar sugerencias si hacés click fuera
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.classList.add('hidden');
        }
    });
    
    // ========================================
    // 8. CARGAR VALORES ANTIGUOS (old() en Laravel)
    // ========================================
    window.addEventListener('DOMContentLoaded', () => {
        const ubicacionAntigua = ubicacionInput.value;
        const latAntigua = latitudInput.value;
        const lonAntigua = longitudInput.value;
        
        if (ubicacionAntigua && latAntigua && lonAntigua) {
            ubicacionDisplay.textContent = ubicacionAntigua;
            coordenadasDisplay.textContent = `Coordenadas: ${latAntigua}, ${lonAntigua}`;
            ubicacionInfo.classList.remove('hidden');
            searchInput.classList.add('hidden');
            mostrarMapa(parseFloat(latAntigua), parseFloat(lonAntigua), ubicacionAntigua);
        }
    });
})();