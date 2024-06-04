<?php

class ClasePOST
{
    public $num_viajeros;
    public $fecha_inicio;
    public $fecha_fin;
    public $error_message;

    public function __construct()
    {
        // Inicializar propiedades
        $this->num_viajeros = 0;
        $this->fecha_inicio = '';
        $this->fecha_fin = '';
        $this->error_message = '';
    }

    public function procesar_datos_POST()
    {
        if (isset($_POST['num_viajeros']) && isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin'])) {

            $this->num_viajeros = $this->validar_numero($_POST['num_viajeros']);
            $this->fecha_inicio = $this->validar_fecha($_POST['fecha_inicio']);
            $this->fecha_fin = $this->validar_fecha($_POST['fecha_fin']);

            if ($this->num_viajeros && $this->fecha_inicio && $this->fecha_fin) {
                if (strtotime($this->fecha_fin) >= strtotime($this->fecha_inicio)) {
                    return true;
                } else {
                    $this->error_message = 'La fecha de fin no puede ser menor que la fecha de inicio.';
                    return false;
                }
            } else {
                $this->error_message = 'Datos inválidos. Por favor, verifica los valores ingresados.';
            }
        } else {
            $this->error_message = 'Por favor, completa todos los campos.';
        }

        return false;
    }

    private function validar_numero($numero)
    {
        // Verificar si es un número entero positivo
        if (filter_var($numero, FILTER_VALIDATE_INT) && $numero > 0) {
            return $numero;
        } else {
            return false;
        }
    }

    private function validar_fecha($fecha)
    {
        // Verificar si la fecha tiene el formato correcto
        $fecha_valida = date_create_from_format('Y-m-d', $fecha);
        if ($fecha_valida) {
            return $fecha_valida->format('Y-m-d');
        } else {
            return false;
        }
    }
}
