<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/**
 * ========================================
 * RUTAS DE GEOCODIFICACIÓN (Google Maps)
 * ========================================
 * 
 * Proxy seguro para búsqueda de direcciones.
 * La API Key de Google Maps nunca se expone al frontend.
 */

 Route::get('/api/geocode', function (Request $request) {
    // Obtener parámetros de la petición
    $address = $request->query('address');
    $region = $request->query('region', 'ar'); // Por defecto Argentina
    
    // Validar que se envió una dirección
    if (!$address) {
        return response()->json([
            'error' => 'Dirección requerida'
        ], 400);
    }
    
    // Obtener la API Key desde .env (segura, nunca se expone)
    $apiKey = env('GOOGLE_MAPS_API_KEY');
    
    if (!$apiKey) {
        \Log::error('GOOGLE_MAPS_API_KEY no está configurada en .env');
        return response()->json([
            'error' => 'Servicio de geocodificación no disponible'
        ], 500);
    }
    
    // Construir URL de Google Maps Geocoding API
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
        'address' => $address,
        'region' => $region,
        'key' => $apiKey
    ]);
    
    try {
        // Llamar a Google Maps API
        $response = file_get_contents($url);
        
        if ($response === false) {
            throw new \Exception('No se pudo conectar con Google Maps API');
        }
        
        $data = json_decode($response, true);
        
        // Verificar estado de la respuesta de Google
        if ($data['status'] === 'REQUEST_DENIED') {
            \Log::error('Google Maps API - REQUEST_DENIED: ' . ($data['error_message'] ?? 'Sin mensaje'));
            return response()->json([
                'error' => 'Error de autenticación con Google Maps'
            ], 500);
        }
        
        if ($data['status'] === 'OVER_QUERY_LIMIT') {
            \Log::warning('Google Maps API - OVER_QUERY_LIMIT alcanzado');
            return response()->json([
                'error' => 'Límite de búsquedas alcanzado. Intentá más tarde.'
            ], 429);
        }
        
        if ($data['status'] !== 'OK' && $data['status'] !== 'ZERO_RESULTS') {
            \Log::error('Google Geocoding Error: ' . $data['status']);
            return response()->json([
                'error' => 'Error en la búsqueda'
            ], 500);
        }
        
        // Procesar resultados para formato simplificado
        $results = array_map(function($result) {
            // Extraer componentes útiles de dirección
            $components = $result['address_components'] ?? [];
            $locality = '';
            $admin_area = '';
            
            foreach ($components as $component) {
                if (in_array('locality', $component['types'])) {
                    $locality = $component['long_name'];
                }
                if (in_array('administrative_area_level_1', $component['types'])) {
                    $admin_area = $component['long_name'];
                }
            }
            
            return [
                'formatted_address' => $result['formatted_address'],
                'geometry' => $result['geometry'],
                'locality' => $locality,
                'administrative_area_level_1' => $admin_area,
                'place_id' => $result['place_id'] ?? null
            ];
        }, $data['results'] ?? []);
        
        return response()->json([
            'status' => $data['status'],
            'results' => $results
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Geocoding Exception: ' . $e->getMessage());
        return response()->json([
            'error' => 'Error al conectar con el servicio de mapas'
        ], 500);
    }
});