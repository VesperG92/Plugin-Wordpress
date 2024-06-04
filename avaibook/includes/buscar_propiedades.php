<?php
require_once dirname(PLUGIN_FILE) . '/includes/buscar_propiedades.php';

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once 'classes/class_post.php';
require_once 'classes/class_conect_api.php';


/**
 * Esta función realiza el ajuste de fechas si las temporadas tienen restriccion de checkInDay.
 */

function ajustarFechas($fecha_inicio, $fecha_fin, $checkin_day, $departure_day, $minimumStay, $ajustar_fecha_ini, $ajustar_fecha_fin)
{
    $dias_semana = ['SUNDAY' => 'sunday', 'MONDAY' => 'monday', 'TUESDAY' => 'tuesday', 'WEDNESDAY' => 'wednesday', 'THURSDAY' => 'thursday', 'FRIDAY' => 'friday', 'SATURDAY' => 'saturday'];

    if (array_key_exists($checkin_day, $dias_semana)) {
        $fecha_inicio_dt = new DateTime($fecha_inicio);
        $fecha_fin_dt = new DateTime($fecha_fin);

        // Si el día de inicio es igual al día de checkIn, se mantiene sin cambios
        if ($fecha_inicio_dt->format('l') !== ucfirst(strtolower($checkin_day))) {
            $fecha_inicio_dt->modify("last " . $dias_semana[$checkin_day]);
            $ajustar_fecha_ini = true;
        }

        if ($fecha_fin_dt->format('l') !== ucfirst(strtolower($departure_day))) {
            $fecha_fin_dt = clone $fecha_inicio_dt;
            $fecha_fin_dt->modify("+" . ($minimumStay) . " days");
            $ajustar_fecha_fin = true;
        }
        if ($ajustar_fecha_ini && $ajustar_fecha_fin) {
            return array('inicio' => $fecha_inicio_dt->format('Y-m-d'), 'fin' => $fecha_fin_dt->format('Y-m-d'), 'ajustar_fecha_ini' => $ajustar_fecha_ini, 'ajustar_fecha_fin' => $ajustar_fecha_fin);
        } elseif (!$ajustar_fecha_ini && $ajustar_fecha_fin) {
            return array('inicio' => $fecha_inicio, 'fin' => $fecha_fin_dt->format('Y-m-d'), 'ajustar_fecha_ini' => $ajustar_fecha_ini, 'ajustar_fecha_fin' => $ajustar_fecha_fin);
        } elseif ($ajustar_fecha_ini && !$ajustar_fecha_fin) {
            return array('inicio' => $fecha_inicio_dt->format('Y-m-d'), 'fin' => $fecha_fin, 'ajustar_fecha_ini' => $ajustar_fecha_ini, 'ajustar_fecha_fin' => $ajustar_fecha_fin);
        }
    }

    return array('inicio' => $fecha_inicio, 'fin' => $fecha_fin, 'ajustar_fecha_ini' => $ajustar_fecha_ini = false, 'ajustar_fecha_fin' => $ajustar_fecha_ini = false);
}

function ajustarFechaMinima($fecha_inicio, $minimumStay)
{
    $fecha_inicio_dt = new DateTime($fecha_inicio);

    $fecha_fin_dt = clone $fecha_inicio_dt;
    $fecha_fin_dt->modify("+" . ($minimumStay) . " days");
    return array('inicio' => $fecha_inicio_dt->format('Y-m-d'), 'fin' => $fecha_fin_dt->format('Y-m-d'));
}



/**
 * Funcion para procesar la búsqueda del formulario, se lanza desde el shortcode
 */
function formulario_de_busqueda()
{

    ob_start();
    $error_message = '';
?>

    <div class="container">
        <?php if (!empty($error_message)) : ?>
            <div class="error-message" style="color: red;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <form id="searchForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validateForm();">
            <div class="row">
                <div class="col">
                    <label for="fecha_inicio"><?= _e('Entrada', 'avaibook_test') ?>:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= isset($_POST['fecha_inicio']) ? htmlspecialchars($_POST['fecha_inicio']) : '' ?>" onchange="updateFechaFinMin()">
                </div>
                <div class="col">
                    <label for="fecha_fin"><?= _e('Salida', 'avaibook_test') ?>:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?= isset($_POST['fecha_fin']) ? htmlspecialchars($_POST['fecha_fin']) : '' ?>">
                </div>
                <div class="col">
                    <label for="num_viajeros"><?= _e('Personas', 'avaibook_test') ?>:</label>
                    <input type="number" id="num_viajeros" name="num_viajeros" value="<?= isset($_POST['num_viajeros']) ? htmlspecialchars($_POST['num_viajeros']) : '' ?>">
                </div>
                <div class="col">
                    <input type="submit" value=<?= _e('Vamos!', 'avaibook_test') ?> class="search-btn">
                    <input type="hidden" name="busqueda-simple" value="1">
                </div>
            </div>
        </form>
    </div>

    <div id="loader" class="modal" style="display: none;">
        <div class="modal-content" id="modal-content">
            <p> <?php _e('Estamos buscando entre todas nuestras propiedades.', 'avaibook_test') ?></p>
            <div id="animacion" style="color: #fff; font-family: monospace; font-weight: bold; font-size: 5rem; opacity: 0.8;">
                <span style="display: inline-block; animation: pulse 1.2s alternate infinite ease-in-out;">.</span>
                <span style="display: inline-block; animation: pulse 1.2s alternate infinite ease-in-out; animation-delay: 0.5s;">.</span>
                <span style="display: inline-block; animation: pulse 1.2s alternate infinite ease-in-out; animation-delay: 1.0s;">.</span>
                <style>
                    @keyframes pulse {
                        to {
                            transform: scale(0.8);
                            opacity: 0.5;
                        }
                    }
                </style>
            </div>
        </div>
    </div>

<?php

    $content = ob_get_contents();
    ob_end_clean();

    echo $content;

    $busqueda = new Busqueda();
    $todas_propiedades_datos = $busqueda->get_all();
    $datos_temporadas = $busqueda->get_temporadas($todas_propiedades_datos);

    if (isset($_POST['busqueda-simple'])) {
        $objeto_post = new ClasePOST();

        if ($objeto_post->procesar_datos_POST()) {      
            $fecha_inicio = $objeto_post->fecha_inicio;
            $fecha_fin = $objeto_post->fecha_fin;

            $temporadas = $busqueda->GetPropiedadesDisponiblesFlexibles($datos_temporadas, $objeto_post->fecha_inicio);
            //echo '<pre>';
            //print_r($temporadas);
            //echo '</pre>';

            $ajustar_fecha_ini = false;
            $ajustar_fecha_fin = false;
            $varias_temporadas = count($temporadas) > 1;
            $diff = date_diff(date_create($fecha_inicio), date_create($fecha_fin))->days;


            foreach ($temporadas as $temporada) {
                if (isset($temporada['checkInDay']) && $temporada['checkInDay'] !== 'NONE') {
                    $fechas_ajustadas = ajustarFechas($fecha_inicio, $fecha_fin, $temporada['checkInDay'], $temporada['departureDay'], $temporada['minimumStay'], $ajustar_fecha_ini, $ajustar_fecha_fin);
                    $fecha_inicio = $fechas_ajustadas['inicio'];
                    $fecha_fin = $fechas_ajustadas['fin'];
                    $ajustar_fecha_ini = $fechas_ajustadas['ajustar_fecha_ini'];
                    $ajustar_fecha_fin = $fechas_ajustadas['ajustar_fecha_fin'];
                    break;
                } elseif (!$varias_temporadas && $temporada['checkInDay'] === 'NONE' && $temporada['minimumStay'] > 1) {
                    $fechas_ajustadas = ajustarFechaMinima($fecha_inicio, $temporada['minimumStay']);
                    $fecha_inicio = $fechas_ajustadas['inicio'];
                    $fecha_fin = $fechas_ajustadas['fin'];
                    $ajustar_fecha_ini = false;
                    $ajustar_fecha_fin = true;
                    break;
                } elseif ($temporada['checkInDay'] === 'NONE' && $temporada['minimumStay'] < 1) {

                    break;
                }
            }
    ?>

            <?php if ($ajustar_fecha_ini === false && $ajustar_fecha_fin === false) : ?>
                <?php
                $propiedades_disponibilidad_datos = $busqueda->buscar_propiedades_disponibles($objeto_post->num_viajeros, $fecha_inicio, $fecha_fin, $todas_propiedades_datos);
                $propiedades_mostradas = $busqueda->mostrar_propiedades_disponibles($objeto_post, $fecha_inicio, $fecha_fin, $propiedades_disponibilidad_datos, $datos_temporadas);
                ?>
            <?php endif; ?>
            <?php if ($ajustar_fecha_ini === true && $ajustar_fecha_fin === true) : ?>
                <div class="container-propiedades">
                    <p style="color:red">
                        <?php echo _e('Durante el periodo seleccionado solo se permiten estancias de' , 'avaibook_test') ?> <?php echo $temporadas[0]['minimumStay']; ?> <?php _e('días con entrada el', 'avaibook_test') ?> <?php echo $temporadas[0]['checkInDay']; ?> <?php _e('y salida el', 'avaibook_test') ?> <?php echo $temporadas[0]['departureDay']; ?></p>
                </div>
                <?php
                $propiedades_disponibilidad_datos = $busqueda->buscar_propiedades_disponibles($objeto_post->num_viajeros, $fecha_inicio, $fecha_fin, $todas_propiedades_datos);
                $propiedades_mostradas = $busqueda->mostrar_propiedades_flexibles($objeto_post, $fecha_inicio, $fecha_fin, $propiedades_disponibilidad_datos, $datos_temporadas);
                ?>
            <?php endif; ?>
            <?php if ($ajustar_fecha_ini === false && $ajustar_fecha_fin === true) : ?>
                <div class="container-propiedades">
                    <p style="color:red">
                        <? echo __('Durante el periodo seleccionado solo se permiten estancias de') ?> <?php echo $temporadas[0]['minimumStay']; ?><? echo __(' días') ?></p>
                </div>
                <?php
                $propiedades_disponibilidad_datos = $busqueda->buscar_propiedades_disponibles($objeto_post->num_viajeros, $fecha_inicio, $fecha_fin, $todas_propiedades_datos);
                $propiedades_mostradas = $busqueda->mostrar_propiedades_flexibles($objeto_post, $fecha_inicio, $fecha_fin, $propiedades_disponibilidad_datos, $datos_temporadas);
                ?>
            <?php endif; ?>

            <?php
            // Mostrar sugerencias si hay varias temporadas con restricciones
            if ($varias_temporadas) {
                $max_min_stay = 0;
                $suggestionsFound = false;
                foreach ($temporadas as $temporada) {
                    if ($temporada['minimumStay'] > $diff) {
                        if ($temporada['minimumStay'] > $max_min_stay) {
                            $max_min_stay = $temporada['minimumStay'];
                            $ajustar_fecha_fin = ajustarFechaMinima($fecha_inicio, $temporada['minimumStay']);
                            $fecha_inicio = $ajustar_fecha_fin['inicio'];
                            $fecha_fin = $ajustar_fecha_fin['fin'];
            ?>
                            <div class="container-propiedades">
                                <p><? echo _e('Puede haber propiedades disponibles con las siguientes restricciones de estancia:') ?></p>
                                <ul>
                                    <?php foreach ($temporadas as $temporada) : ?>
                                        <?php if ($temporada['minimumStay'] > $diff) : ?>
                                            <?php $temporada_restriccion = $temporada['minimumStay']; ?>
                                            <li><? $temporada['num_repeticiones'] ?> <?php echo _e('propiedades con una estancia mínima de', 'avaibook_test') ?> <?= $temporada['minimumStay'] ?> <?php echo _e('días', 'avaibook_test') ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                                <button id="showSuggestionButton" onclick="showSuggestion()" class="details-btn"><?php echo _e('Ver disponibilidad', 'avaibook_test'); ?></button>
                            </div>
                            <script>
                                function showSuggestion() {
                                    document.getElementById('suggestionModal').style.display = 'block';
                                }
                            </script>
                            <div id="suggestionModal" class="modal" style="display: none;">
                                <div class="container_propiedades">
                                    <span class="close" onclick="document.getElementById('suggestionModal').style.display='none'">&times;</span>
                            <?php
                            $propiedades_disponibilidad_datos = $busqueda->buscar_propiedades_disponibles($objeto_post->num_viajeros, $fecha_inicio, $fecha_fin, $todas_propiedades_datos);
                            $propiedades_mostradas = $busqueda->mostrar_propiedades_disponibles_restringidas($objeto_post, $fecha_inicio, $fecha_fin, $propiedades_disponibilidad_datos, $datos_temporadas);
                            $suggestionsFound = true;
                        }
                    }
                }
                if (!$suggestionsFound) {
                            ?>
                            <div class="container-propiedades">
                                <p><?= _e('No hay más propiedades disponibles.') ?></p>
                            </div>
                        <?php
                    }
                        ?>
                        


            <?php



            }
        }
    }


            ?>
                                </div>
                            </div>
                        <?php
                    }

                        ?>
                        <?php
