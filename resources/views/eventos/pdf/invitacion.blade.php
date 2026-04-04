<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Invitación – {{ $evento->nombre }}</title>
    <style>
        body        { font-family: Arial, Helvetica, sans-serif; background: #f9f9f9; padding: 16px; font-size: 15px; }
        .card       { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; border: 2px solid #3382f7; box-shadow: 2px 6px 18px #0001; padding: 32px 24px;}
        h2          { text-align: center; color: #3382f7; margin-bottom: 8px;}
        .evento     { text-align: center; font-size: 19px; color: #0a2540; font-weight: bold; margin-bottom: 25px;}
        .dato       { margin-bottom: 5px;}
        .qr         { text-align: center; margin-top: 28px; }
        .cantidad   { font-size: 17px; font-weight: bold; color: #177c41; margin: 5px 0 15px 0; text-align: center;}
        .leyenda    { text-align: center; color: #660000; font-size: 12px; margin-top: 25px;}
        .footer     { color: #888; text-align: center; margin-top: 38px; font-size: 12px;}
    </style>
</head>
<body>
    <div class="card">
        <h2>INVITACIÓN AL EVENTO</h2>
        <div class="evento">{{ $evento->nombre }}</div>

        <div class="dato"><b>Invitado:</b> {{ $nombre }} {{ $apellido }}</div>
        <div class="dato"><b>Email:</b> {{ $invitado->email }}</div>
        @if(isset($invitado->dni))
            <div class="dato"><b>DNI:</b> {{ $invitado->dni }}</div>
        @endif
        <div class="dato"><b>Fecha del evento:</b> {{ \Carbon\Carbon::parse($evento->fecha)->format('d/m/Y') }}</div>
        <div class="cantidad">
            Válida para <b>{{ $invitado->cantidad ?? 1 }}</b> persona{{ ($invitado->cantidad ?? 1) > 1 ? 's' : '' }}
        </div>
        
        <div class="qr">
    @if(!empty($qrSvg))
        {!! $qrSvg !!}
    @else
        <div style="color: red;">SIN QR DISPONIBLE</div>
    @endif
</div>
</div>
<div style="font-size: 10px; color: red;">
    {!! isset($qrSvg) ? 'SVG PRESENTE' : 'SVG AUSENTE' !!}
</div>
        <div class="leyenda">
            Presentá esta invitación (impresa o digital) al ingresar.<br>
            <b>No se podrá ingresar sin este comprobante y QR.</b>
        </div>
        <div class="footer">
            Generado automáticamente • {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>