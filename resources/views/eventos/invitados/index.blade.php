@extends('layouts.app')

@section('title', 'Invitados de ' . $evento->nombre)

@section('content')
    <h1 class="text-2xl font-bold mb-6">Invitados para "{{ $evento->nombre }}"</h1>
    <a href="{{ route('eventos.index') }}" class="text-blue-700 hover:underline mb-4 inline-block">← Volver a eventos</a>

    <!-- BOTÓN PARA AGREGAR INVITADOS -->
    <a href="{{ route('eventos.invitados.gestion', ['evento' => $evento->id]) }}"
   class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-sm font-semibold mb-6">
   Agregar invitados
</a>

    <table class="min-w-full divide-y divide-gray-200 mt-8">
        <thead>
            <tr>
                <th class="px-4 py-2 text-left">Email</th>
                <th class="px-4 py-2 text-left">Estado</th>
                <th class="px-4 py-2">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invitados as $inv)
                <tr>
                    <td class="px-4 py-2">{{ $inv->email }}</td>
                    <td class="px-4 py-2">
                        @if($inv->datos_completados)
                            <span class="text-green-700">Confirmado</span>
                        @elseif($inv->enviada)
                            <span class="text-blue-700">Enviada</span>
                        @else
                            <span class="text-yellow-700">Pendiente</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        <button class="bg-red-500 text-white px-3 py-1 rounded text-xs">Eliminar</button>
                        <button class="bg-orange-500 text-white px-3 py-1 rounded text-xs">Reenviar</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-6 text-gray-600 text-center">No hay invitados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- ==========================
         MODAL: Agregar invitados 
         ========================== -->
    <div id="modalInvitar"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40"
        style="backdrop-filter: blur(2px);">
        <div class="bg-white shadow-xl rounded-2xl border border-gray-100 w-full max-w-md px-8 py-12 flex flex-col items-center relative overflow-y-auto min-h-[470px]">
            <!-- Titulo + Boton Cerrar -->
            <div class="w-full flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-purple-700 text-center flex-1">Agregar invitados</h3>
                <button type="button"
                    onclick="cerrarModalInvitar()"
                    class="ml-3 text-gray-400 hover:text-gray-900 text-2xl font-bold"
                    style="line-height: 1;margin-top:2px;">
                    ×
                </button>
            </div>
            <!-- Opciones modal -->
            <div id="modalOpciones" class="flex flex-col gap-3 items-center w-full mb-2">
                <button type="button"
                    onclick="elegirModoInvitacion('manual')"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-base font-semibold transition"
                    style="min-width:170px;">
                    Carga manual
                </button>
                <button type="button"
                    onclick="elegirModoInvitacion('excel')"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-base font-semibold transition"
                    style="min-width:170px;">
                    Importar desde Excel
                </button>
            </div>
            <div id="formularioCarga" class="w-full mt-3"></div>
        </div>
    </div>

    <script>
    // Variables globales
    var modoSeleccionado = null;
    var eventoEnModal = null;
    var listaManual = [];

    // Abrir el modal
    function abrirModalInvitar(eventoId) {
        eventoEnModal = eventoId;
        document.getElementById('modalInvitar').classList.remove('hidden');
        document.getElementById('modalInvitar').classList.add('flex');
        document.getElementById('formularioCarga').innerHTML = '';
        document.getElementById('modalOpciones').style.display = '';
        modoSeleccionado = null;
        listaManual = [];
    }

    // Cerrar el modal
    function cerrarModalInvitar() {
        document.getElementById('modalInvitar').classList.remove('flex');
        document.getElementById('modalInvitar').classList.add('hidden');
        document.getElementById('formularioCarga').innerHTML = '';
        document.getElementById('modalOpciones').style.display = '';
        eventoEnModal = null;
        listaManual = [];
    }

    // Opciones del modal
    function elegirModoInvitacion(modo) {
        modoSeleccionado = modo;
        document.getElementById('modalOpciones').style.display = 'none';
        if (modo === 'manual') {
            document.getElementById('formularioCarga').innerHTML = `
                <div class="w-full" style="max-width:480px;margin:0 auto;">
                    <label class="block text-lg font-semibold text-gray-700 mb-4 text-center">Email del invitado</label>
                    <div class="flex items-center gap-3 mb-4">
                        <input type="email" id="emailManual"
                            class="flex-1 h-12 px-3 rounded border border-gray-300 text-base shadow"
                            placeholder="ejemplo@email.com" autocomplete="off">
                        <button type="button"
                            class="h-12 px-6 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-semibold text-base shadow"
                            onclick="agregarEmailManual()"
                        >Agregar invitado</button>
                    </div>
                    <div id="erroresCargaManual" class="text-sm text-red-600 mb-2"></div>
                    <div id="listaInvitadosManual" class="mb-5"></div>
                    <div class="flex gap-4 justify-center mt-10">
                        <button type="button"
                            class="px-6 h-12 bg-violet-600 hover:bg-violet-700 text-white rounded font-semibold text-base"
                            onclick="finalizarCargaManual()"
                        >Finalizar carga</button>
                        <button type="button"
                            class="px-6 h-12 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded font-semibold text-base"
                            onclick="cerrarModalInvitar()"
                        >Cancelar</button>
                    </div>
                </div>
            `;
            renderListaInvitadosManual();
        } else if (modo === 'excel') {
            document.getElementById('formularioCarga').innerHTML = `
                <form id="formImportExcel" enctype="multipart/form-data">
                    <label class="block text-base font-medium text-gray-700 mb-3">Subí tu archivo Excel (.xlsx, debe tener una columna <b>email</b>):</label>
                    <input type="file" id="archivoExcel" name="archivoExcel" accept=".xlsx,.xls" class="mb-3">
                    <div class="flex gap-4 justify-center mt-4">
                        <button class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-base font-semibold" onclick="importarDesdeExcel(event)">Importar</button>
                        <button class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-base font-semibold" type="button" onclick="cerrarModalInvitar()">Cerrar</button>
                    </div>
                </form>
                <div id="listaPendientes" class="mt-5"></div>
            `;
        }
    }

    // Agregar email manualmente
    function agregarEmailManual() {
        const email = document.getElementById('emailManual').value.trim();
        const errores = [];

        if (!email) { errores.push('El email es obligatorio.'); }
        else if (!/^[\w\-\.]+@([\w-]+\.)+[\w-]{2,}$/.test(email)) { errores.push('El formato de email no es válido.'); }
        else if (listaManual.includes(email)) { errores.push('Ese email ya está cargado.'); }

        if (errores.length) {
            document.getElementById('erroresCargaManual').textContent = errores.join(' ');
            return;
        }

        listaManual.push(email);
        renderListaInvitadosManual();
        document.getElementById('emailManual').value = '';
        document.getElementById('erroresCargaManual').textContent = '';
    }

    // Mostrar lista de invitados cargados manualmente
    function renderListaInvitadosManual() {
        const listaDiv = document.getElementById('listaInvitadosManual');
        if (!listaDiv) return;
        if (listaManual.length === 0) {
            listaDiv.innerHTML = '<span class="text-gray-500 text-sm">Todavía no cargaste invitados.</span>';
            return;
        }
        listaDiv.innerHTML = `
            <ul class="mb-2">
                ${listaManual.map((email,i)=>`
                    <li class="flex justify-between items-center mb-1 px-2 py-1 bg-gray-50 rounded">
                        <span>${email}</span>
                        <button class="text-xs text-red-600 hover:underline" onclick="eliminarInvitadoManual(${i})">Eliminar</button>
                    </li>
                `).join('')}
            </ul>
            <span class="text-xs text-emerald-700">Total cargados: ${listaManual.length}</span>
        `;
    }

    // Eliminar invitado de la lista manual
    function eliminarInvitadoManual(indice) {
        listaManual.splice(indice,1);
        renderListaInvitadosManual();
    }

    // Finalizar carga manual
    function finalizarCargaManual() {
        if (listaManual.length === 0) {
            document.getElementById('erroresCargaManual').textContent = 'Agregá al menos un invitado.';
            return;
        }
        fetch(`/eventos/${eventoEnModal}/invitaciones-pendientes-masivo`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ emails: listaManual })
        })
        .then(r=>r.json())
        .then(resp=>{
            if(resp.success) {
                cerrarModalInvitar();
                alert(resp.mensaje);
                // Podés refrescar la lista principal aca si querés
            } else {
                document.getElementById('erroresCargaManual').textContent = (resp.mensaje || 'Error al cargar invitados.');
            }
        });
    }
    </script>
@endsection