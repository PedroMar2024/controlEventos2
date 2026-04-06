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
<div<!-- ========================================
     MODAL FLOTANTE PARA CONSULTA Y CONFIRMACIÓN
     ======================================== -->
<div id="modal-resultado" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <!-- PASO 1: Mostrar información y elegir tipo -->
        <div id="paso-consulta">
            <!-- Header del modal -->
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white text-center">
                <h3 class="text-2xl font-bold">📋 INFORMACIÓN DE ACCESO</h3>
            </div>
            
            <!-- Contenido del modal -->
            <div class="px-6 py-6">
                <!-- Nombre del evento -->
                <div class="mb-6 text-center bg-indigo-50 rounded-lg py-3 px-4">
                    <p class="text-sm text-gray-600 mb-1">Evento</p>
                    <p class="text-xl font-bold text-indigo-900" id="modal-evento"></p>
                </div>
                
                <!-- Información de la persona -->
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">
                        <span class="text-gray-600">👤</span> <span id="modal-nombre"></span>
                    </p>
                    <p class="text-md text-gray-700">
                        <span class="text-gray-600">🎫 DNI:</span> <span id="modal-dni"></span>
                    </p>
                </div>
                
                <hr class="my-4 border-gray-300">
                
                <!-- Información de cupos -->
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-3">
                        <span class="text-gray-700 font-medium">Válido por:</span>
                        <span id="modal-cupos-total" class="text-2xl font-bold text-gray-800"></span>
                    </div>
                    
                    <div class="flex justify-between items-center bg-emerald-50 rounded-lg px-4 py-3">
                        <span class="text-emerald-700 font-medium">Ingresados:</span>
                        <span id="modal-cupos-ingresados" class="text-2xl font-bold text-emerald-600"></span>
                    </div>
                    
                    <div class="flex justify-between items-center bg-orange-50 rounded-lg px-4 py-3">
                        <span class="text-orange-700 font-medium">Disponibles:</span>
                        <span id="modal-cupos-disponibles" class="text-2xl font-bold text-orange-600"></span>
                    </div>
                </div>
                
                <hr class="my-4 border-gray-300">
                
                <!-- Selección de tipo (ENTRADA o SALIDA) -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-gray-700">Tipo de movimiento:</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" 
                                id="btn-tipo-entrada" 
                                onclick="seleccionarTipo('entrada')"
                                class="px-4 py-3 rounded-lg font-semibold text-white bg-green-600 hover:bg-green-700">
                            ✅ ENTRADA
                        </button>
                        <button type="button" 
                                id="btn-tipo-salida" 
                                onclick="seleccionarTipo('salida')"
                                class="px-4 py-3 rounded-lg font-semibold border-2 border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                            🔽 SALIDA
                        </button>
                    </div>
                </div>
                
                <!-- Cantidad de personas -->
                <div class="mb-4">
                    <label for="modal-cantidad" class="block text-sm font-semibold mb-2 text-gray-700">¿Cuántas personas?</label>
                    <input type="number" 
                           id="modal-cantidad" 
                           value="1" 
                           min="1" 
                           max="10"
                           class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 text-center text-2xl font-bold focus:border-indigo-500 focus:outline-none">
                </div>
            </div>
            
            <!-- Footer del modal -->
            <div class="px-6 py-4 bg-gray-50 flex gap-3">
                <button onclick="cerrarModal()" 
                        class="flex-1 bg-gray-400 hover:bg-gray-500 text-white px-6 py-3 rounded-lg font-semibold text-lg">
                    CANCELAR
                </button>
                <button onclick="confirmarAcceso()" 
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold text-lg">
                    CONFIRMAR
                </button>
            </div>
        </div>
        
        <!-- PASO 2: Mostrar mensaje de confirmación (GRACIAS) -->
        <div id="paso-gracias" class="hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white text-center">
                <h3 class="text-2xl font-bold">✅ REGISTRADO</h3>
            </div>
            
            <div class="px-6 py-8 text-center">
                <p class="text-4xl font-bold text-green-600 mb-4">GRACIAS</p>
                
                <!-- Información actualizada de cupos -->
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-3">
                        <span class="text-gray-700 font-medium">Válido por:</span>
                        <span id="gracias-cupos-total" class="text-xl font-bold text-gray-800"></span>
                    </div>
                    
                    <div class="flex justify-between items-center bg-emerald-50 rounded-lg px-4 py-3">
                        <span class="text-emerald-700 font-medium">Ingresados:</span>
                        <span id="gracias-cupos-ingresados" class="text-xl font-bold text-emerald-600"></span>
                    </div>
                    
                    <div class="flex justify-between items-center bg-orange-50 rounded-lg px-4 py-3">
                        <span class="text-orange-700 font-medium">Disponibles:</span>
                        <span id="gracias-cupos-disponibles" class="text-xl font-bold text-orange-600"></span>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 text-center">
                <button onclick="cerrarModal()" 
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg font-semibold text-lg">
                    ACEPTAR
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Librerías para escaneo QR -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
// ========================================
// VARIABLES GLOBALES
// ========================================
let invitacionActual = null;
let tipoSeleccionado = 'entrada'; // Por defecto entrada

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
function mostrarModalConsulta(data) {
    invitacionActual = data;
    
    // Llenar datos del evento
    document.getElementById('modal-evento').textContent = data.evento.nombre;
    
    // Llenar datos de la persona
    document.getElementById('modal-nombre').textContent = data.persona.nombre + ' ' + data.persona.apellido;
    document.getElementById('modal-dni').textContent = data.persona.dni || 'Sin DNI';
    
    // Llenar cupos
    document.getElementById('modal-cupos-total').textContent = data.cupos.total + ' personas';
    document.getElementById('modal-cupos-ingresados').textContent = data.cupos.ingresados + ' personas';
    document.getElementById('modal-cupos-disponibles').textContent = data.cupos.disponibles + ' personas';
    
    // Seleccionar tipo sugerido
    seleccionarTipo(data.tipo_sugerido);
    
    // Resetear cantidad
    document.getElementById('modal-cantidad').value = 1;
    
    // Mostrar paso 1 (consulta)
    document.getElementById('paso-consulta').classList.remove('hidden');
    document.getElementById('paso-gracias').classList.add('hidden');
    
    // Mostrar modal
    document.getElementById('modal-resultado').classList.remove('hidden');
}

function seleccionarTipo(tipo) {
    tipoSeleccionado = tipo;
    
    const btnEntrada = document.getElementById('btn-tipo-entrada');
    const btnSalida = document.getElementById('btn-tipo-salida');
    
    if (tipo === 'entrada') {
        btnEntrada.className = 'px-4 py-3 rounded-lg font-semibold text-white bg-green-600 hover:bg-green-700';
        btnSalida.className = 'px-4 py-3 rounded-lg font-semibold border-2 border-gray-300 text-gray-700 bg-white hover:bg-gray-50';
    } else {
        btnSalida.className = 'px-4 py-3 rounded-lg font-semibold text-white bg-blue-600 hover:bg-blue-700';
        btnEntrada.className = 'px-4 py-3 rounded-lg font-semibold border-2 border-gray-300 text-gray-700 bg-white hover:bg-gray-50';
    }
}

function confirmarAcceso() {
    const cantidad = parseInt(document.getElementById('modal-cantidad').value) || 1;
    
    if (!invitacionActual) {
        alert('❌ Error: No hay invitación seleccionada');
        return;
    }
    
    // Enviar confirmación al servidor
    fetch("{{ route('accesos.confirmar', $evento->id) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            invitacion_id: invitacionActual.invitacion_id,
            tipo: tipoSeleccionado,
            personas: cantidad
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar contadores globales
            document.getElementById('contador-dentro').textContent = data.dentro_ahora;
            const faltantes = {{ $totalInvitados }} - data.dentro_ahora;
            document.getElementById('contador-faltantes').textContent = faltantes;
            
            // Mostrar paso 2 (gracias)
            mostrarGracias(data);
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error al confirmar el acceso');
    });
}

function mostrarGracias(data) {
    // Llenar cupos actualizados
    document.getElementById('gracias-cupos-total').textContent = data.cupos.total + ' personas';
    document.getElementById('gracias-cupos-ingresados').textContent = data.cupos.ingresados + ' personas';
    document.getElementById('gracias-cupos-disponibles').textContent = data.cupos.disponibles + ' personas';
    
    // Ocultar paso 1, mostrar paso 2
    document.getElementById('paso-consulta').classList.add('hidden');
    document.getElementById('paso-gracias').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modal-resultado').classList.add('hidden');
    invitacionActual = null;
    tipoSeleccionado = 'entrada';
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
    mostrarEstado('⏳ Consultando información...', 'info');
    
    let token = '';
    if (decodedText.includes('?token=')) {
        token = decodedText.split('?token=')[1];
    } else if (!decodedText.includes('/') && !decodedText.includes('http')) {
        token = decodedText;
    } else {
        token = decodedText.split('/').pop().split('?token=').pop();
    }
    
    // Consultar información (sin registrar)
    fetch("{{ route('accesos.consultar', $evento->id) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ 
            token: token
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar modal con la información
            mostrarModalConsulta(data);
            mostrarEstado('✅ Información cargada', 'success');
        } else {
            alert('❌ Error: ' + data.message);
            mostrarEstado('❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error al consultar la invitación');
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
    const dni = dniInput.value.trim();
    
    if (!dni) {
        alert('❌ Por favor ingresá un DNI');
        return;
    }
    
    // Consultar información (sin registrar)
    fetch("{{ route('accesos.consultar', $evento->id) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ 
            dni: dni
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar modal con la información
            mostrarModalConsulta(data);
            
            // Limpiar input
            dniInput.value = '';
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error al consultar la invitación');
    });
});
</script>
@endsection