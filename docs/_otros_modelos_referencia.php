<?php
// VentaSurtidor.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class VentaSurtidor extends Model {
    public $timestamps = false;
    protected $table = 'ventas_surtidor';
    protected $fillable = ['turno_id','surtidor','tipo_combustible','galones','valor'];
}

// LecturaSurtidor.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LecturaSurtidor extends Model {
    public $timestamps = false;
    protected $table = 'lecturas_surtidor';
    protected $fillable = ['turno_id','manguera','tipo_combustible','lectura_inicial','lectura_final'];
}

// Consignacion.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Consignacion extends Model {
    public $timestamps = false;
    protected $table = 'consignaciones';
    protected $fillable = ['turno_id','numero','valor','descuento'];
}

// CarteraCredito.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CarteraCredito extends Model {
    public $timestamps = false;
    protected $table = 'cartera_credito';
    protected $fillable = ['turno_id','numero_factura','cliente','valor'];
}

// MediosPagoElectronico.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MediosPagoElectronico extends Model {
    public $timestamps = false;
    protected $table = 'medios_pago_electronicos';
    protected $fillable = ['turno_id','tc_datafono_1','tc_datafono_2','tc_datafono_3','transferencias_bancolombia','gasolina_eds','puntos_redimidos'];
}

// VentaUreaLubricante.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class VentaUreaLubricante extends Model {
    public $timestamps = false;
    protected $table = 'ventas_urea_lubricantes';
    protected $fillable = ['turno_id','cantidad','producto','valor_sin_iva','iva'];
}

// RecaudoAnticipo.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RecaudoAnticipo extends Model {
    public $timestamps = false;
    protected $table = 'recaudos_anticipos';
    protected $fillable = ['turno_id','cliente','valor'];
}

// Vario.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Vario extends Model {
    public $timestamps = false;
    protected $table = 'varios';
    protected $fillable = ['turno_id','concepto','valor'];
}

// RecaudoAdministracion.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RecaudoAdministracion extends Model {
    public $timestamps = false;
    protected $table = 'recaudos_administracion';
    protected $fillable = ['turno_id','banco_caja','cliente','valor'];
}
