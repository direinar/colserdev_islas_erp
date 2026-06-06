<?php

namespace App\Actions;

use App\DTOs\TurnoDTO;
use App\Models\Turno;

class GuardarTurnoAction
{
    public function execute(TurnoDTO $dto): Turno
    {
        $data = $dto->toArray();

        // Map only allowed fields using Turno model fillable
        $allowed = (new Turno())->getFillable();
        $toSave = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $toSave[$key] = $data[$key];
            }
        }

        // If ventas_surtidor/lecturas etc are present as arrays, ensure they are JSON-able
        return Turno::create($toSave);
    }
}
