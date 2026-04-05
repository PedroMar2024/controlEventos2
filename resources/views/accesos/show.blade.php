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
            <form id="form-ingreso-manual">
                @csrf
                
                <!-- DNI del invitado -->
                <label for="dni" class="block text-sm font-semibold mb-2">DNI del invitado:</label>
                <input type="text" 
                       name="dni" 
                       id="dni-input"
                       placeholder="Ej: 12345678" 
                       required
                       class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 mb-4 focus:border-emerald-500 focus:outline-none">
                
                <!-- Cantidad de personas -->
                <label for="personas-manual" class="block text-sm font-semibold mb-2">¿Cuántas personas?</label>
                <input type="number" 
                       name="personas" 
                       id="personas-manual"
                       value="1"
                       min="1"
                       max="10"
                       required
                       class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 mb-4 focus:border-emerald-500 focus:outline-none">
                
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

<!-- ========================================
     MODAL FLOTANTE PARA MOSTRAR RESULTADO
     ======================================== -->
<div id="modal-resultado" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <!-- Header del modal -->
        <div id="modal-header" class="px-6 py-4 text-white text-center">
            <h3 id="modal-titulo" class="text-2xl font-bold"></h3>
        </div>
        
        <!-- Contenido del modal -->
        <div class="px-6 py-6">
            <!-- Información de la persona -->
            <div class="mb-6">
                <p class="text-lg font-semibold text-gray-800 mb-2" id="modal-nombre"></p>
                <p class="text-sm text-gray-600" id="modal-email"></p>
                <p class="text-sm text-gray-600" id="modal-dni"></p>
            </div>
            
            <hr class="my-4 border-gray-300">
            
            <!-- Información del movimiento -->
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-medium">Personas en este movimiento:</span>
                    <span id="modal-personas-movimiento" class="text-xl font-bold text-indigo-600"></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-medium">Personas adentro (esta invitación):</span>
                    <span id="modal-personas-dentro" class="text-xl font-bold text-emerald-600"></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-medium">Total permitido:</span>
                    <span id="modal-total-permitido" class="text-xl font-bold text-gray-800"></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-medium">Lugares disponibles:</span>
                    <span id="modal-disponibles" class="text-xl font-bold text-orange-600"></span>
                </div>
            </div>
            
            <hr class="my-4 border-gray-300">
            
            <!-- Total en el evento -->
            <div class="bg-indigo-50 rounded-lg p-4 text-center">
                <p class="text-sm text-gray-600 mb-1">Total personas en el evento</p>
                <p id="modal-total-evento" class="text-3xl font-bold text-indigo-600"></p>
            </div>
        </div>
        
        <!-- Footer del modal -->
        <div class="px-6 py-4 bg-gray-50 text-center">
            <button onclick="cerrarModal()" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg font-semibold text-lg w-full">
                ACEPTAR
            </button>
        </div>
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

setInterval(actualizarReloj, 1000);
actualizarReloj();

// ========================================
// FUNCIONES DEL MODAL
// ========================================
function mostrarModal(data) {
    const modal = document.getElementById('modal-resultado');
    const header = document.getElementById('modal-header');
    const titulo = document.getElementById('modal-titulo');
    
    // Configurar color según tipo
    if (data.tipo === 'entrada') {
        header.className = 'px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white text-center';
        titulo.textContent = '✅ ENTRADA REGISTRADA';
    } else {
        header.className = 'px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-center';
        titulo.textContent = '🔽 SALIDA REGISTRADA';
    }
    
    // Llenar datos de la persona
    document.getElementById('modal-nombre').textContent = `👤 ${data.persona.nombre} ${data.persona.apellido}`;
    document.getElementById('modal-email').textContent = `📧 ${data.persona.email}`;
    document.getElementById('modal-dni').textContent = data.persona.dni ? `🎫 DNI: ${data.persona.dni}` : '';
    
    // Llenar datos del movimiento
    document.getElementById('modal-personas-movimiento').textContent = data.personas_movimiento || 1;
    document.getElementById('modal-personas-dentro').textContent = data.personas_dentro_invitacion || 0;
    document.getElementById('modal-total-permitido').textContent = data.total_permitido || 0;
    
    const disponibles = (data.total_permitido || 0) - (data.personas_dentro_invitacion || 0);
    document.getElementById('modal-disponibles').textContent = disponibles;
    
    document.getElementById('modal-total-evento').textContent = data.dentro_ahora || 0;
    
    // Mostrar modal
    modal.classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modal-resultado').classList.add('hidden');
}

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
    
    const config = { 
        fps: 10, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };
    
    html5QrCodeScanner.start(
        { facingMode: "environment" },
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
    cerrarEscanerQR();
    mostrarEstado('⏳ Procesando código QR...', 'info');
    
    let token = '';
    if (decodedText.includes('?token=')) {
        token = decodedText.split('?token=')[1];
    } else if (!decodedText.includes('/') && !decodedText.includes('http')) {
        token = decodedText;
    } else {
        token = decodedText.split('/').pop().split('?token=').pop();
    }
    
    fetch("{{ route('accesos.escanear-qr', $evento->id) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ 
            token: token,
            personas: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar contadores
            document.getElementById('contador-dentro').textContent = data.dentro_ahora;
            const faltantes = {{ $totalInvitados }} - data.dentro_ahora;
            document.getElementById('contador-faltantes').textContent = faltantes;
            
            // Mostrar modal
            mostrarModal(data);
            
            mostrarEstado(`✅ ${data.tipo.toUpperCase()} registrada correctamente`, 'success');
            
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
    // Ignorar errores menores
}

// ========================================
// INGRESO MANUAL
// ========================================
document.getElementById('form-ingreso-manual').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const dniInput = document.getElementById('dni-input');
    const personasInput = document.getElementById('personas-manual');
    const dni = dniInput.value.trim();
    const personas = parseInt(personasInput.value) || 1;
    
    if (!dni) {
        alert('❌ Por favor ingresá un DNI');
        return;
    }
    
    fetch("{{ route('accesos.ingreso-manual', $evento->id) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ 
            dni: dni,
            personas: personas
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar contadores
            document.getElementById('contador-dentro').textContent = data.dentro_ahora;
            const faltantes = {{ $totalInvitados }} - data.dentro_ahora;
            document.getElementById('contador-faltantes').textContent = faltantes;
            
            // Mostrar modal
            mostrarModal(data);
            
            // Limpiar inputs
            dniInput.value = '';
            personasInput.value = '1';
            dniInput.focus();
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error al procesar el ingreso manual');
    });
});
</script>
@endsection