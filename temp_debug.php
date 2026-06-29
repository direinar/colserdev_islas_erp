<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$turno = App\Models\Turno::where('fecha','2026-06-06')->where('numero_turno',1)->first();
if (!$turno) {
    die("turno not found\n");
}
echo "Turno id {$turno->id}\n";
$relations = [
    'ventas' => App\Models\TurnoVenta::where('turno_id',$turno->id)->count(),
    'surtidores' => App\Models\TurnoSurtidor::where('turno_id',$turno->id)->count(),
    'lubricantes' => App\Models\TurnoLubricante::where('turno_id',$turno->id)->count(),
    'mediosPago' => App\Models\TurnoMedioPago::where('turno_id',$turno->id)->count(),
    'qrPagos' => App\Models\TurnoQrPago::where('turno_id',$turno->id)->count(),
    'recaudos' => App\Models\TurnoRecaudo::where('turno_id',$turno->id)->count(),
    'transferencias' => App\Models\TurnoTransferencia::where('turno_id',$turno->id)->count(),
    'gasolinaEds' => App\Models\TurnoGasolinaEds::where('turno_id',$turno->id)->count(),
    'varios' => App\Models\TurnoVarios::where('turno_id',$turno->id)->count(),
    'recaudosAdmin' => App\Models\TurnoRecaudoAdmin::where('turno_id',$turno->id)->count(),
];
foreach ($relations as $name => $count) {
    echo "$name: $count\n";
}
