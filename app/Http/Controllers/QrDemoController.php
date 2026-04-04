<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrDemoController extends Controller
{
    // Muestra un QR de prueba en PNG embebido
    public function show()
    {
        $svg = QrCode::format('svg')->size(200)->generate('¡Hola, control de eventos!');
return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}