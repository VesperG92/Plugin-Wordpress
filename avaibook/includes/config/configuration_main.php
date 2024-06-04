<?php

function avaibook_add_api()
{
    $valor_api_avaibook = get_option('avaibook_api');
    //**********************
    $valor_api2_avaibook = get_option('avaibook_api2');
    //**********************
    $entorno_produccion = get_option('avaibook_entorno_produccion'); 


    if(isset($_POST['guardar_api_avaibook']))
    {
        $api_avaibook = sanitize_text_field($_POST['api_avaibook']);
        update_option('avaibook_api', $api_avaibook);

        //**********************
        $api_avaibook2 = sanitize_text_field($_POST['avaibook_api2']);
        update_option('avaibook_api2', $api_avaibook2);

        $entorno_produccion = ($_POST['entorno'] == 'produccion') ? true : false;
        update_option('avaibook_entorno_produccion', $entorno_produccion);
    
    }

    ?>
    <div class="wrap formulario-style">
        <h1>Configuracion API Avaibook</h1>
        <form method="post">
            <label for="api_avaibook"></label>
            <br>
            <input type="text" name="api_avaibook" id="api_avaibook" value="<?php echo esc_attr($valor_api_avaibook); ?>" class="regular-text" style="width: 90%;">
            <br><br>
            <!--*********-->
            <label for="avaibook_api2"></label>
            <br>
            <input type="text" name="avaibook_api2" id="avaibook_api2" value="<?php echo esc_attr($valor_api2_avaibook); ?>" class="regular-text" style="width: 90%;">
            <br><br>
            <!--*********-->
            <input type="radio" name="entorno" id="produccion" value="produccion" <?php checked($entorno_produccion, true); ?>>
            <label for="produccion">Producción</label><br>

            <input type="radio" name="entorno" id="desarrollo" value="desarrollo" <?php checked($entorno_produccion, false); ?>>
            <label for="desarrollo">Desarrollo</label><br>
            
            <p class="submit"><input type="submit" name="guardar_api_avaibook" class="button-primary" value="Guardar cambios"></p>
        </form>
    </div>
    <?php

    ?>
<style>
    .formulario-configuracion {
      background-color: #FFFFFF;
      padding: 10px;
      border-radius: 15px;
      width: 95%;
    }
</style>
<?php
}