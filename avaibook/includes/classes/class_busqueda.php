<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'class_post.php';
require_once 'class_conect_api.php';

class Busqueda
{
    /**
     * Función para obtener los datos de las temporadas en función de las fechas seleccionadas
     * @param array $array_temporadas todos los datos de temporadas de las propiedades
     * @param date $fecha_ini 
     * @param date $fecha_fin
     * @return array $temporadas_fechas_seleccionadas
     */
    function GetPropiedadesDisponiblesFlexibles($array_temporadas, $fecha_ini)
    {
        $temporadas_fechas_seleccionadas = [];
    
        foreach ($array_temporadas as $temporadas) {
            foreach ($temporadas as $temporada) {
                if (is_array($temporada)) {
                    if ($fecha_ini >= $temporada['dateIni'] && $fecha_ini <= $temporada['dateEnd']) {
                        $temporadas_fechas_seleccionadas[] = $temporada;
                    }
                }
            }
        }
    
        $temporadas_fechas_seleccionadas_unicas = [];
    
        foreach ($temporadas_fechas_seleccionadas as $temporada) {
            $existente = false;
            foreach ($temporadas_fechas_seleccionadas_unicas as &$temporada_unica) {
    
                if (
                    $temporada_unica['minimumStay'] == $temporada['minimumStay'] &&
                    $temporada_unica['checkInDay'] == $temporada['checkInDay'] &&
                    $temporada_unica['departureDay'] == $temporada['departureDay']
                ) {
                    $temporada_unica['num_repeticiones'] = ($temporada_unica['num_repeticiones'] ?? 1) + 1;
                    $existente = true;
                    break;
                }
            }
    
            if (!$existente) {
                $temporada['num_repeticiones'] = 1;
                $temporadas_fechas_seleccionadas_unicas[] = array(
                    'minimumStay' => $temporada['minimumStay'],
                    'checkInDay' => $temporada['checkInDay'],
                    'departureDay' => $temporada['departureDay'],
                    'num_repeticiones' => $temporada['num_repeticiones']
                );
            }
        }
    
        return $temporadas_fechas_seleccionadas_unicas;
    }
    
    /**
     * Función para recuperar todos los datos de temporadas de todas las propiedades 
     * @param array $response_all_properties array con los datos básicos de las propiedades obtenido de get_all() necesario para pasar a la llamada de la API el id de la propiedad
     * @return array $array_temporadas
     */

    function get_temporadas($response_all_properties)
    {


        $array_temporadas = [];

        foreach ($response_all_properties as $property) {
            $property_id = $property['id'];

            $datos = $this->obtener_info_adicional($property_id);

            if (!isset($array_temporadas[$property_id])) {
                $array_temporadas[$property_id] = [];
            }

            // Agregar el subarray 'SMALL' de $datos['images'] a $array_temporadas[$property_id]['image']
            if (isset($datos['images'][0]['SMALL'])) {
                $array_temporadas[$property_id]['image'] = $datos['images'][0]['SMALL'];
            }

            if (isset($datos['units'][0]['unitSeasons'])) {
                foreach ($datos['units'][0]['unitSeasons'] as $season) {
                    $season['property_id'] = $property_id;
                    $array_temporadas[$property_id][] = $season;
                }
            }
        }

        
        return $array_temporadas;
    }
    /**
     * Función que recupera los datos básicos de todas las propiedades
     * @return array datos de todas las propiedades divididos por id
     */

    function get_all()
    {
        $api = new ConnectAPI();

        $url = "api/owner/accommodations/";

        $api->call_API($url, 'get_all');

        $response_all_properties = $api->get_api_response('get_all');

        return $response_all_properties;
    }

    /**
     * API: Funcion que permite buscar las propiedades disponibles dentro de un rando de fechas y el numero de huespedes, creando un objeto de la clase ConnectAPI para solicitar datos.
     * 
     * @param int $num_viajeros número de personas que se van a alojar
     * @param date $fecha_inicio fecha en la que se va a comenzar la estancia
     * @param date $fecha_fin fecha en la que finaliza la estancia
     * @return array $propiedades_disponibilidad_datos ids de las propiedades disponibles
     */
    function buscar_propiedades_disponibles($num_viajeros, $fecha_inicio, $fecha_fin, $todas_propiedades_datos)
    {

        $api = new ConnectAPI();

        $params = array(
            'checkinDate' => $fecha_inicio,
            'checkoutDate' => $fecha_fin,
            'travelers' => $num_viajeros
        );

        $api->call_API("api/owner/accommodations/booking-price/", 'properties_available', $params);

        $response_properties_available = $api->get_api_response('properties_available');
        //echo '<pre>';
        //print_r($response_properties_available);
        //echo '</pre>';

        $propiedades_disponibles = [];
        $propiedades_disponibilidad_datos = [];

        foreach ($response_properties_available as $property_available) {
            $propiedades_disponibles[] = array(
                'unit_id' => $property_available['unit']['id'],
                'total' => $property_available['total'],
                'name' => $property_available['unit']['name']['es'],
                'status' => $property_available['status']
            );
        }

        // Filtrar propiedades por disponibilidad
        foreach ($todas_propiedades_datos as $property) {
            foreach ($property['units'] as $property2) {
                foreach ($propiedades_disponibles as $propiedad_disponible) {
                    if ($property2['id'] === $propiedad_disponible['unit_id']) {
                        $propiedades_disponibilidad_datos[] = array(
                            'propiedad_id' => $property['id'],
                            'unidad_id' => $propiedad_disponible['unit_id'],
                            'name' => $property['name'],
                            'tipo' => $property['rentalType'],
                            'total' => $propiedad_disponible['total'],
                            'status' => $propiedad_disponible['status']
                        );
                    }
                }
            }
        }

        //echo '<pre>';
        //print_r('respuesta de la API calendario');
        //print_r($propiedades_disponibilidad_datos);
        //echo '</pre>';


        return $propiedades_disponibilidad_datos;
    }

    /**
     * API: Funcion para obtener los datos de temporada de una propiedad,imagenes, etc
     * @param string $propiedad_id id de la propiedad
     * @return array $response_property_specific_info
     */

    public function obtener_info_adicional($propiedad_id)
    {
        $api = new ConnectAPI();

        $url = "api/owner/accommodations/$propiedad_id/";

        $api->call_API($url, 'property_specific_info');

        $response_property_specific_info = $api->get_api_response('property_specific_info');

        //echo '<pre>';
        //print_r('respuesta de la datos propiedades');
        //print_r($response_property_specific_info);
        //echo '</pre>';

        return $response_property_specific_info;
    }

    /**
     * Funcion para mostrar las propiedades disponibles entre los rangos de fechas introducidos por el usuario
     * @param object $objeto_post
     * @param array $propiedades_disponibilidad_datos
     * @return array $propiedades mostradas
     */
    function mostrar_propiedades_disponibles($objeto_post,$fecha_inicio, $fecha_fin, $propiedades_disponibilidad_datos, $array_temporadas)
    {
        $propiedades_mostradas = array();
        $hay_propiedades_disponibles = false;

        // Comprobar si hay propiedades disponibles
        foreach ($propiedades_disponibilidad_datos as $propiedad) {
            if ($propiedad['status'] === 'AVAILABLE') {
                $hay_propiedades_disponibles = true;
                break;
            }
        }
?>
        <div class="container-propiedades">
            <?php if ($hay_propiedades_disponibles) : ?>
                <strong class="texto-flex"><?php echo _e('Estas son las propiedades disponibles en tus fechas', 'avaibook_test'); ?></strong>
                <div class="view-buttons">
                    <button id="gridView" onclick="setGridView()"><?php echo '<span class="dashicons dashicons-grid-view"></span>' ?></button>
                    <button id="listView" onclick="setListView()"><?php echo '<span class="dashicons dashicons-list-view"></span>' ?></button>
                </div>
            <?php else : ?>
                <strong><?php echo _e('Ups!, parece que no hay propiedades disponibles en estas fechas.', 'avaibook_test'); ?></strong>
            <?php endif; ?>
            <div class="properties-container grid-view" id="propertiesContainer">
                <ul class="properties-list">
                    <?php foreach ($propiedades_disponibilidad_datos as $propiedad) :
                        if ($propiedad['status'] === 'AVAILABLE') {
                            $propiedad_id = $propiedad['propiedad_id'];
                            $unidad_id = $propiedad['unidad_id'];
                            $propiedades_mostradas[] = $propiedad_id;
                            $propiedad_datos_adicionales = $array_temporadas[$propiedad_id];
                    ?>
                            <li class="item propiedad-disponible">
                                <div class="property-details">
                                    <?php if (!empty($propiedad_datos_adicionales['image'])) : ?>
                                        <div class="image-container">
                                            <img src="<?= $propiedad_datos_adicionales['image'] ?>" alt="Imagen de propiedad" class="property-image">
                                        </div>
                                    <?php endif; ?>
                                    <div class="details-container">
                                        <strong><?php echo _e($propiedad['name']); ?></strong>
                                        <p><strong><?php echo _e('Precio total de la estancia:', 'avaibook_test'); ?><br></strong> <?php echo $propiedad['total']; ?> €</p>
                                        <a href="https://www.avaibook.com/reservas/nueva_reserva.php?cod_alojamiento=<?php echo $propiedad_id; ?>&cod_unidad_alojativa=<?php echo $unidad_id; ?>&f_ini=<?php echo $fecha_inicio; ?>&f_fin=<?php echo $fecha_fin; ?>&capacidad=<?php echo $objeto_post->num_viajeros; ?>s&adults=1&lang=es#!" class="details-btn" target="_blank"><?php echo _e('Reservar', 'avaibook_test'); ?></a>
                                    </div>
                                </div>
                            </li>
                    <?php }
                    endforeach; ?>
                </ul>
            </div>
        </div>
<?php
        return $propiedades_mostradas;
    }

       /**
     * Funcion para mostrar las propiedades disponibles entre los rangos de fechas introducidos por el usuario
     * @param object $objeto_post
     * @param array $propiedades_disponibilidad_datos
     * @return array $propiedades mostradas
     */
    function mostrar_propiedades_disponibles_restringidas($objeto_post,$fecha_inicio, $fecha_fin, $propiedades_disponibilidad_datos, $array_temporadas)
    {
        $propiedades_mostradas = array();
        $hay_propiedades_disponibles = false;

        // Comprobar si hay propiedades disponibles
        foreach ($propiedades_disponibilidad_datos as $propiedad) {
            if ($propiedad['status'] === 'AVAILABLE') {
                $hay_propiedades_disponibles = true;
                break;
            }
        }
?>
        <div class="container-propiedades">
            <?php if ($hay_propiedades_disponibles) : ?>
                <strong class="texto-flex"><?php echo _e('Estas serían las propiedades disponibles con restricción. Entrada ', 'avaibook_test');?><br><?php echo __($fecha_inicio); echo _e(' y salida ', 'avaibook_test'); echo __($fecha_fin); ?></strong>
                <div class="view-buttons">
                    <button id="gridView" onclick="setGridView()"><?php echo '<span class="dashicons dashicons-grid-view"></span>' ?></button>
                    <button id="listView" onclick="setListView()"><?php echo '<span class="dashicons dashicons-list-view"></span>' ?></button>
                </div>
            <?php else : ?>
                <strong><?php echo _e('Ups!, parece que ninguna de las propiedades con restriciones está disponible.', 'avaibook_test'); ?></strong>
            <?php endif; ?>
            <div class="properties-container grid-view" id="propertiesContainer">
                <ul class="properties-list">
                    <?php foreach ($propiedades_disponibilidad_datos as $propiedad) :
                        if ($propiedad['status'] === 'AVAILABLE') {
                            $propiedad_id = $propiedad['propiedad_id'];
                            $unidad_id = $propiedad['unidad_id'];
                            $propiedades_mostradas[] = $propiedad_id;
                            $propiedad_datos_adicionales = $array_temporadas[$propiedad_id];
                    ?>
                            <li class="item propiedad-disponible">
                                <div class="property-details">
                                    <?php if (!empty($propiedad_datos_adicionales['image'])) : ?>
                                        <div class="image-container">
                                            <img src="<?= $propiedad_datos_adicionales['image'] ?>" alt="Imagen de propiedad" class="property-image">
                                        </div>
                                    <?php endif; ?>
                                    <div class="details-container">
                                        <strong><?php echo _e($propiedad['name']); ?></strong>
                                        <p><strong><?php echo _e('Precio total de la estancia:', 'avaibook_test'); ?><br></strong> <?php echo $propiedad['total']; ?> €</p>
                                        <a href="https://www.avaibook.com/reservas/nueva_reserva.php?cod_alojamiento=<?php echo $propiedad_id; ?>&cod_unidad_alojativa=<?php echo $unidad_id; ?>&f_ini=<?php echo $fecha_inicio; ?>&f_fin=<?php echo $fecha_fin; ?>&capacidad=<?php echo $objeto_post->num_viajeros; ?>s&adults=1&lang=es#!" class="details-btn" target="_blank"><?php echo _e('Reservar', 'avaibook_test'); ?></a>
                                    </div>
                                </div>
                            </li>
                    <?php }
                    endforeach; ?>
                </ul>
            </div>
        </div>
<?php
        return $propiedades_mostradas;
    }


    function mostrar_propiedades_flexibles($objeto_post,$fecha_inicio, $fecha_fin, $propiedades_disponibilidad_datos, $array_temporadas)
    {
        $propiedades_mostradas = array();
        $hay_propiedades_disponibles = false;

        // Comprobar si hay propiedades disponibles
        foreach ($propiedades_disponibilidad_datos as $propiedad) {
            if ($propiedad['status'] === 'AVAILABLE') {
                $hay_propiedades_disponibles = true;
                break;
            }
        }
        
?>
        <div class="container-propiedades">
            <?php if ($hay_propiedades_disponibles) : ?>
                <strong class="texto-flex"><?php echo _e('Ups!, parece que en tus fechas no hay propiedades disponibles.', 'avaibook_test'); ?></strong><br>
                <strong class="texto-flex"><?php echo _e('Pero si modificas tus fechas del ', 'avaibook_test'); echo _e((new DateTime($fecha_inicio))->format('d F'), 'avaibook_test'); echo _e(' al '); echo _e((new DateTime($fecha_fin))->format('d F'), 'avaibook_test'); echo _e(' tienes estas opciones.' , 'avaibook_test'); ?></strong>
                <div class="view-buttons">
                    <button id="gridView" onclick="setGridView()"><?php echo '<span class="dashicons dashicons-grid-view"></span>' ?></button>
                    <button id="listView" onclick="setListView()"><?php echo '<span class="dashicons dashicons-list-view"></span>' ?></button>
                </div>
            <?php else : ?>
                <strong><?php echo _e('Ups!, parece que no hay propiedades disponibles en estas fechas.', 'avaibook_test'); ?></strong>
            <?php endif; ?>
            <div class="properties-container grid-view" id="propertiesContainer">
                <ul class="properties-list">
                    <?php foreach ($propiedades_disponibilidad_datos as $propiedad) :
                        if ($propiedad['status'] === 'AVAILABLE') {
                            $propiedad_id = $propiedad['propiedad_id'];
                            $unidad_id = $propiedad['unidad_id'];
                            $propiedades_mostradas[] = $propiedad_id;
                            $propiedad_datos_adicionales = $array_temporadas[$propiedad_id];
                    ?>
                            <li class="item propiedad-disponible">
                                <div class="property-details">
                                    <?php if (!empty($propiedad_datos_adicionales['image'])) : ?>
                                        <div class="image-container">
                                            <img src="<?= $propiedad_datos_adicionales['image'] ?>" alt="Imagen de propiedad" class="property-image">
                                        </div>
                                    <?php endif; ?>
                                    <div class="details-container">
                                        <strong><?php echo _e($propiedad['name']); ?></strong>
                                        <p><strong><?php echo _e('Precio total de la estancia:', 'avaibook_test'); ?><br></strong> <?php echo $propiedad['total']; ?> €</p>
                                        <a href="https://www.avaibook.com/reservas/nueva_reserva.php?cod_alojamiento=<?php echo $propiedad_id; ?>&cod_unidad_alojativa=<?php echo $unidad_id; ?>&f_ini=<?php echo $fecha_inicio; ?>&f_fin=<?php echo $fecha_fin; ?>&capacidad=<?php echo $objeto_post->num_viajeros; ?>s&adults=1&lang=es#!" class="details-btn" target="_blank"><?php echo _e('Reservar', 'avaibook_test'); ?></a>
                                    </div>
                                </div>
                            </li>
                    <?php }
                    endforeach; ?>
                </ul>
            </div>
        </div>
<?php
        return $propiedades_mostradas;
    }


 
    /**
     * Funcion para formatear los datos de calendario
     */
    function format_date($date)
    {
        $date = new DateTime($date);
        return $date->format('d F Y');
    }
}
