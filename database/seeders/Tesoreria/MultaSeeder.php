<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\Multa;

class MultaSeeder extends Seeder
{
    public function run()
    {
        $multas = [
            // [articulo, literal, descripcion, monto_ur, monto_pesos, inciso_legal, articulo_completo]
            ['2', null, 'Desobediencia a las disposiciones (d.539)', 1.5, null, null, null],
            ['3', null, 'Desobediencia a señalización (d.540)', 1.5, null, null, null],
            ['4', 'B', 'Vehículo circulando por la acera (d.541)', 4.0, null, null, null],
            ['4', '4/2', 'Conduce usando celular', 3.0, null, 'Decreto Nº 81/014', null],
            ['4', '9/1', 'Menor en asiento delantero', 3.0, null, 'Decreto Nº 81/014', null],
            ['4', '9/2', 'No utiliza sistema de retención infantil', 2.0, null, 'Decreto Nº 81/014', null],
            ['5', 'A', 'Vehículos: uso indebido de las aceras, no dar preferencia al peatón (d.542)', 4.0, null, null, null],
            ['5', 'B', 'Entrada y salida del inmueble cuando el cordón no está dispuesto a esos efectos (d.542)', 4.0, null, null, null],
            ['5', 'C', 'Prohibición de estacionar sobre la acera (d.542)', 4.0, null, null, null],
            ['5', 'D', 'Circula por ciclovía o bicisenda', 4.0, null, null, null],
            ['6', null, 'Peatón uso indebido de la calzada (d.543)', 0.1, null, null, null],
            ['7', null, 'No obedecer limitaciones de circulación temporaria (d.544)', 1.0, null, null, null],
            ['8', '1', 'Conduce sin licencia o con licencia suspendida (d.545)', 8.0, null, null, null],
            ['8', '2', 'Licencia no habilitada para el vehículo que conduce (d.545)', 2.5, null, null, null],
            ['9', '1', 'Conduce sin poseer cualidades físicas o psíquicas que lo habiliten (d.546)', 2.5, null, null, null],
            ['9', '2', 'Persona con discapacidad conduciendo vehículo sin autorización (d.546)', 2.5, null, null, null],
            ['11', '1', 'Aspirante a conductor no autorizado (d.548)', 1.0, null, null, null],
            ['11', '2', 'Instructor no habilitado (d.548)', 1.0, null, null, null],
            ['12', '15A', 'Alcoholemia', 15.0, null, null, null],
            ['12', '15B', 'Presencia de drogas psicotrópicas', 15.0, null, null, null],
            ['14', null, 'Conduce vehículos especiales sin permiso (d.551)', 2.5, null, null, null],
            ['15', null, 'Conduce con licencia sin reválida (d.552)', 2.5, null, null, null],
            ['16', null, 'Omisión de actualización de domicilio (d.553)', 0.3, null, null, null],
            ['17', null, 'Posee más de una licencia de conducir (d.554)', 2.5, null, null, null],
            ['19', null, 'Conduce con licencia vencida (d.556)', 1.0, null, null, null],
            ['21', null, 'Licencia condicional vencida (d.558)', 2.5, null, null, null],
            ['21', '7', 'Niños y adolescentes no alcanzan los posapies en motos', 1.0, null, 'Decreto Nº 81/014', null],
            ['22', null, 'Circulación de vehículo sin empadronar (d.559)', 2.5, null, null, null],
            ['28', null, 'Circula con vehículo no inspeccionado (d.565)', 1.0, null, null, null],
            ['30', null, 'Vehículo con exceso de peso sobre el pavimento (d.567)', 5.0, null, null, null],
            ['34', null, 'Circula sin placas de matrícula (d.571)', 1.0, null, null, null],
            ['103', '2A', 'Exceso de velocidad. Hasta 20 km/h por encima de la velocidad permitida', 5.0, null, null, null],
            ['103', '2B', 'Exceso de velocidad. Entre 21 km/h y 30 km/h por encima de la velocidad permitida', 8.0, null, null, null],
            ['103', '2C', 'Exceso de velocidad. Más de 30 km/h por encima de la velocidad permitida', 12.0, null, null, null],
            ['106', null, 'No detener la marcha cuando lo indiquen agentes de tránsito. Cruzar o girar con luz roja', 5.0, null, null, null],
            ['140', '2E', 'Escape libre ruidoso en motos', 10.0, null, null, null],
            ['148', '3', 'No utiliza cinturón de seguridad', 1.0, null, null, null],
            ['170', null, 'Circular sin casco', 2.0, null, null, null],
            ['184', '1', 'Carecer de SOA: Motos', 0, 5154.00, null, null],
            ['184', '2', 'Carecer de SOA: Automóviles y Camionetas', 0, 13066.00, null, null],
            ['184', '3', 'Carecer de SOA: Camiones', 0, 22498.00, null, null],
            ['184', '4', 'Carecer de SOA: Ómnibus', 0, 26328.00, null, null],
            ['184', '5', 'Carecer de SOA: Taxis', 0, 15402.00, null, null],
            ['184', '6', 'Carecer de SOA: Remises', 0, 14074.00, null, null],
            ['184', '7', 'Carecer de SOA: Vehículos de alquiler sin chofer', 0, 14846.00, null, null],
            ['184', '8', 'Carecer de SOA: Ambulancias', 0, 14166.00, null, null],
            ['184', '9', 'Carecer de SOA: Coche Escuela', 0, 13600.00, null, null],
            ['184', '10', 'Carecer de SOA: Trailers', 0, 10290.00, null, null],
        ];

        foreach ($multas as $m) {
            Multa::create([
                'articulo' => $m[0],
                'literal' => $m[1],
                'descripcion' => $m[2],
                'monto_ur' => $m[3] ?? 0,
                'monto_pesos' => $m[4] ?? 0,
                'inciso_legal' => $m[5],
                'articulo_completo' => $m[6],
            ]);
        }
    }
}
