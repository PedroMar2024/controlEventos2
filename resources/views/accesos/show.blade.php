@extends('layouts.app')

@section('title', 'Control de Acceso - ' . $evento->nombre)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header con Reloj -->
    <div class="mb-6">
        <a href="{{ route('accesos.index') }}" class="text-indigo-600 hover:underline mb-2 inline-block">
            ← Volver a lista de eventos
        </a>
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold">Control de Acceso: {{ $evento->nombre }}</h1>
                <p class="text-gray-600">{{ $evento->fecha_evento ? $evento->fecha_evento->format('d/m/Y') : 'Sin fecha' }}</p>
            </div>
            <!-- RELOJ EN TIEMPO REAL -->
            <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg">
                <p class="text-xs uppercase tracking-wide mb-1 text-gray-300">Hora Actual</p>
                <p id="reloj" class="text-3xl font-mono font-bold">--:--:--</p>
            </div>
        </div>
    </div>

    <!-- Mensajes flash -->
    @if(session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-6 rounded">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mb-6 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Estadísticas MEJORADAS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Invitados -->
        <div class="bg-blue-100 border-2 border-blue-300 rounded-lg p-6 text-center shadow-md">
            <p class="text-blue-600 text-sm font-semibold uppercase mb-2">👥 Total Invitados</p>
            <p class="text-5xl font-bold text-blue-800">{{ $totalInvitados }}</p>
            <p class="text-xs text-blue-600 mt-1">Confirmados para el evento</p>
        </div>

        <!-- Ingresados (Adentro AHORA) -->
        <div class="bg-green-100 border-2 border-green-300 rounded-lg p-6 text-center shadow-md">
            <p class="text-green-600 text-sm font-semibold uppercase mb-2">✅ Ingresados</p>
            <p class="text-5xl font-bold text-green-800" id="contador-dentro">{{ $dentroAhora }}</p>
            <p class="text-xs text-green-600 mt-1">Personas adentro ahora</p>
        </div>

        <!-- Faltantes -->
        @php
            $faltantes = $totalInvitados - $dentroAhora;
        @endphp
        <div class="bg-orange-100 border-2 border-orange-300 rounded-lg p-6 text-center shadow-md">
            <p class="text-orange-600 text-sm font-semibold uppercase mb-2">⏳ Faltantes</p>
            <p class="text-5xl font-bold text-orange-800" id="contador-faltantes">{{ $faltantes }}</p>
            <p class="text-xs text-orange-600 mt-1">Aún no ingresaron</p>
        </div>
    </div>

    <!-- Botones principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Escanear QR -->
        <div class="bg-white border-2 border-indigo-300 rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold mb-4 text-indigo-700">📷 Escanear QR</h2>
            <p class="text-gray-600 mb-4">Escaneá el código QR de la invitación para registrar entrada/salida.</p>
            
            <!-- Mensaje de estado del escáner -->
            <div id="scanner-status" class="hidden mb-3 p-2 rounded text-sm"></div>
            
            <button id="btn-abrir-scanner" type="button" onclick="abrirEscanerQR()" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold">
                📷 Abrir Escáner
            </button>
            
            <!-- Área de escaneo (inicialmente oculta) -->
            <div id="area-escaner" class="hidden mt-4">
                <div id="reader" class="w-full border-2 border-indigo-400 rounded"></div>
                <button type="button" onclick="cerrarEscanerQR()" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded mt-2">
                    ❌ Cerrar Cámara
                </button>
            </div>
        </div>

        <!-- Ingreso Manual -->
        <div class="bg-white border-2 border-emerald-300 rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold mb-4 text-emerald-700">✍️ Ingreso Manual</h2>
            <p class="text-gray-600 mb-4">Si el QR no funciona, ingresá el DNI manualmente.</p>
            <form id="form-ingreso-manual" method="POST" action="{{ route('accesos.ingreso-manual', $evento->id) }}">
    @csrf
    <label for="dni" class="block text-sm font-semibold mb-2">DNI del invitado:</label>
    <input type="text" 
           name="dni" 
           id="dni"
           placeholder="Ej: 12345678" 
           required
           class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 mb-3 focus:border-emerald-500 focus:outline-none">
    <button type="submit" 
            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-semibold">
        ✅ Registrar Acceso
    </button>
</form>
        </div>
    </div>

    <!-- Botón ver historial -->
    <div class="text-center">
        <a href="{{ route('accesos.historial', $evento->id) }}" 
           class="inline-block bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold">
            📋 Ver Historial de Accesos
        </a>
    </div>
</div>

<!-- Librerías para escaneo QR -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
// ========================================
// RELOJ EN TIEMPO REAL
// ========================================
function actualizarReloj() {
    const ahora = new Date();
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');
    
    document.getElementById('reloj').textContent = `${horas}:${minutos}:${segundos}`;
}

// Actualizar el reloj cada segundo
setInterval(actualizarReloj, 1000);
// Llamar inmediatamente para no esperar 1 segundo
actualizarReloj();

// ========================================
// ESCÁNER QR
// ========================================
let html5QrCodeScanner = null;
let scannerActivo = false;

function mostrarEstado(mensaje, tipo) {
    const statusDiv = document.getElementById('scanner-status');
    statusDiv.classList.remove('hidden', 'bg-red-100', 'bg-green-100', 'bg-yellow-100', 'text-red-700', 'text-green-700', 'text-yellow-700');
    
    if (tipo === 'error') {
        statusDiv.classList.add('bg-red-100', 'text-red-700');
    } else if (tipo === 'success') {
        statusDiv.classList.add('bg-green-100', 'text-green-700');
    } else {
        statusDiv.classList.add('bg-yellow-100', 'text-yellow-700');
    }
    
    statusDiv.textContent = mensaje;
    statusDiv.classList.remove('hidden');
}

function abrirEscanerQR() {
    if (scannerActivo) return;
    
    document.getElementById('area-escaner').classList.remove('hidden');
    document.getElementById('btn-abrir-scanner').disabled = true;
    
    mostrarEstado('⏳ Iniciando cámara...', 'info');
    
    html5QrCodeScanner = new Html5Qrcode("reader");
    
    // Configuración del escáner
    const config = { 
        fps: 10, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };
    
    html5QrCodeScanner.start(
        { facingMode: "environment" }, // Cámara trasera en móviles
        config,
        onScanSuccess,
        onScanError
    ).then(() => {
        scannerActivo = true;
        mostrarEstado('✅ Cámara activa. Enfocá el código QR.', 'success');
    }).catch(err => {
        console.error('Error al iniciar cámara:', err);
        mostrarEstado('❌ Error: ' + err, 'error');
        document.getElementById('btn-abrir-scanner').disabled = false;
    });
}

function cerrarEscanerQR() {
    if (html5QrCodeScanner && scannerActivo) {
        html5QrCodeScanner.stop().then(() => {
            scannerActivo = false;
            document.getElementById('area-escaner').classList.add('hidden');
            document.getElementById('btn-abrir-scanner').disabled = false;
            document.getElementById('scanner-status').classList.add('hidden');
        }).catch(err => {
            console.error('Error al detener cámara:', err);
        });
    }
}

function onScanSuccess(decodedText, decodedResult) {
    // Detener el escáner temporalmente para evitar múltiples lecturas
    cerrarEscanerQR();
    
    mostrarEstado('⏳ Procesando código QR...', 'info');
    
    // Extraer el token de la URL escaneada
    let token = '';
    
    // Caso 1: Si es una URL completa (http://dominio.com/evento/ingreso?token=XXX)
    if (decodedText.includes('?token=')) {
        token = decodedText.split('?token=')[1];
    } 
    // Caso 2: Si es solo el token (XXX)
    else if (!decodedText.includes('/') && !decodedText.includes('http')) {
        token = decodedText;
    }
    // Caso 3: Cualquier otro formato, intentar extraer
    else {
        token = decodedText.split('/').pop().split('?token=').pop();
    }
    
    console.log('QR escaneado:', decodedText);
    console.log('Token extraído:', token);
    
    // Enviar al servidor
    fetch("{{ route('accesos.escanear-qr', $evento->id) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ token: token })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tipoTexto = data.tipo === 'entrada' ? 'ENTRADA' : 'SALIDA';
            const emoji = data.tipo === 'entrada' ? '✅' : '🔽';
            
            alert(`${emoji} ${tipoTexto} registrada\n\nPersona: ${data.persona.nombre} ${data.persona.apellido}\nEmail: ${data.persona.email}`);
            
            // Actualizar contadores
            document.getElementById('contador-dentro').textContent = data.dentro_ahora;
            const faltantes = {{ $totalInvitados }} - data.dentro_ahora;
            document.getElementById('contador-faltantes').textContent = faltantes;
            
            mostrarEstado(`✅ ${tipoTexto} registrada correctamente`, 'success');
            
            // Reabrir el escáner después de 2 segundos
            setTimeout(() => {
                abrirEscanerQR();
            }, 2000);
        } else {
            alert('❌ Error: ' + data.message);
            mostrarEstado('❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error al procesar el código QR');
        mostrarEstado('❌ Error de conexión', 'error');
    });
}

function onScanError(errorMessage) {
    // Ignorar errores menores de escaneo (es normal mientras enfoca)
}
// ========================================
// INGRESO MANUAL CON AJAX
// ========================================
document.getElementById('form-ingreso-manual').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const dniInput = document.getElementById('dni');
    
    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tipoTexto = data.tipo === 'entrada' ? 'ENTRADA' : 'SALIDA';
            const emoji = data.tipo === 'entrada' ? '✅' : '🔽';
            
            alert(`${emoji} ${tipoTexto} registrada\n\nPersona: ${data.persona.nombre} ${data.persona.apellido}\nDNI: ${data.persona.dni}`);
            
            // Actualizar contadores
            document.getElementById('contador-dentro').textContent = data.dentro_ahora;
            document.getElementById('contador-faltantes').textContent = data.faltantes;
            
            // Limpiar el input
            dniInput.value = '';
            dniInput.focus();
        } else {
            alert('❌ Error: ' + (data.message || 'No se pudo registrar el acceso'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error al procesar el ingreso manual');
    });
});
</script>
@endsection