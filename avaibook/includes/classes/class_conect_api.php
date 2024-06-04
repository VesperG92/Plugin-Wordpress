<?php

require_once dirname(PLUGIN_FILE) . '/includes/classes/class_conect_api.php';

class ConnectAPI
{

    private $api_key;
    private $api_key2;
    private $api_responses = [];
    private $api2_responses = [];
    private $tiempo;
    private $entorno_produccion;
    private $url_entorno_desarrollo = "https://api.avaibook.biz/";
    private $url_entorno_produccion = "https://api.avaibook.com/";

    function log($txt)
    {
        echo "<font color=red>" . $txt . "</font>- " . (time()) . "</br>";
    }

    function __construct()
    {
        $this->api_key = get_option('avaibook_api');
        $this->api_key2 = get_option('avaibook_api2');
        $this->entorno_produccion = get_option('avaibook_entorno_produccion');

        $this->tiempo=time();
        //ob_flush();
    }

    /**
     * Funcion que realiza la llamada a la API y almacena la respuesta con un Id
     * 
     * @param string $end_point el endpoint de la API
     * @param string $id_solicitud un id único para la respuesta
     * @param array $params los parametros adicionales para solicitar otros datos a la API
     */
    function call_API($end_point, $id_solicitud, $params = [])
    {


        $url = $this->entorno_produccion ? $this->url_entorno_produccion : $this->url_entorno_desarrollo;
        //Costruir la url a la que enviar la solicitud
        $url .= $end_point;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $responses = [];
        $curl = curl_init();
        

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "Content-Type: application/json",
                "X-AUTH-TOKEN: $this->api_key"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        //echo '<pre>';
        //print_r($response);
        //echo '</pre>';
        if ($err) {
            echo 'CURL error #:' . $err;
        } else {
            //LA SIGUIENTE LINEA SE DESCOMENTA Y SE ELIMINAN LAS QUE ESTÁN ENTRE ******** PARA EL FUNCIONAMIENTO CON UNA API
            // $this->api_response[$id_solicitud] = json_decode($response, true);
            //************BORRAR**************
            $response_array = json_decode($response, true);
            $this->api_responses[$id_solicitud] = $response_array;
            //*************BORRAR*************
        }
        //***********BORRAR***************
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "Content-Type: application/json",
                "X-AUTH-TOKEN: $this->api_key2"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo 'CURL error #:' . $err;
        } else {
            $response_array = json_decode($response, true);
            // Combina las respuestas en el mismo array
            $this->api_responses[$id_solicitud] = array_merge($this->api_responses[$id_solicitud], $response_array);
        }
        //************BORRAR**************
    }

    /**
     * Funcion para recuperar a través de un identificador los datos de la api
     * @param string $id_solicitud Nombre para identificar los datos de salida de una solicitud
     * @return api_response
     */
    function get_api_response($id_solicitud)
    {
        return isset($this->api_responses[$id_solicitud]) ? $this->api_responses[$id_solicitud] : null;
    }

    
}
