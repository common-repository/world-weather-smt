<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function worldweather_options_page()
{
  global $providers,$cachedelays,$license,$worldweatherpro,$pluginpath,$pluginwebpath,$credits;
  $worldweather = new worldweather;
  ?>
  <?php screen_icon(); ?>
  <h1>World Weather</h1>
  <div id="worldweather_options_overlay">
      <div id="worldweather_waiting_text"><? print(__( 'Veuillez patienter', 'world-weather-smt' )); ?>Veuillez patienter</div>
  </div>
  <div class="content">


      <table width="100%" style="margin-right:10px;">
        <tr>
          <td width="40%" valign="top">

            <div class="worldweather_options_block">
            <h2><? print(__( 'Paramètres', 'world-weather-smt' )); ?></h2>

            <form method="post" action="options.php">
            <?php settings_fields( 'worldweather_options_group' );
                  $cache = get_option('worldweather_cache');
                  $mesure = get_option('worldweather_mesure');
                  $icons = get_option('worldweather_icons');
            ?>
            <table width="100%">
            <tr valign="top">
            <th scope="row" width="20%"><label for="worldweather_cache"><? print(__( 'Cache', 'world-weather-smt' )); ?></label></th>
            <td width="30%"><select id="worldweather_cache_select" name="worldweather_cache"><?

                foreach ($cachedelays as $delay)
                {
                  ?><option value="<? print($delay);  ?>"<? if ($cache==$delay) {  ?> selected<?  } ?>><? print($delay);  ?></option><?
                }
            ?></select> <? print(__( 'minutes', 'world-weather-smt' )); ?>.</td>
            <th scope="row" width="25%"><label for="worldweather_mesure"><? print(__( 'Système de mesure', 'world-weather-smt' )); ?></label></th>
            <td width="25%"><select id="worldweather_mesure" name="worldweather_mesure">
              <option value="m"<? if ($mesure=="m") {  ?> selected<?  } ?>><? print(__( 'Métrique', 'world-weather-smt' )); ?></option>
              <option value="i"<? if ($mesure=="i") {  ?> selected<?  } ?>><? print(__( 'Impérial', 'world-weather-smt' )); ?></option>
            </select></td>
            </tr>
            <tr>
              <th width="20%"><label for="worldweather_icons"><? print(__( 'Icones', 'world-weather-smt' )); ?></label></th>
              <td width="30%"><select id="worldweather_icons" name="worldweather_icons">
                <option value="0"<? if ($icons=="0") {  ?> selected<?  } ?>>[<? print(__( 'Service', 'world-weather-smt' )); ?>]</option>
                <?
                    $iconsset = $worldweather->get_icons_sets();
                    //  die(var_dump($iconsset));
                    if (!isset($iconsset['errors']))
                    {
                        if (isset($iconsset['iconssets']))
                        {
                          foreach ($iconsset['iconssets'] as $iconset)
                          {
                            ?><option value="<? print($iconset);  ?>"<? if ($icons==$iconset) {  ?> selected<?  } ?>><? print($iconset);  ?></option><?
                          }
                        }
                    }
                ?>

              </select><?
              if (isset($iconsset['errors']))
              {
                  ?><p><? print(__( 'Erreur(s)', 'world-weather-smt' )); ?> : <?
                    implode('<br>',$iconsset['errors']);
                  ?></p><?
              }
              ?></td>
            </tr>
            </table>
            <?php  submit_button(); ?>
            </form>
            </div>

            <div class="worldweather_options_block">
            <?
            $basegeo = get_option('worldweather_basegeo');
              ?><h2><? print(__( 'Localisation', 'world-weather-smt' )); ?></h2>
              <p><? print(__( 'Vous devez définir votre localisation par défaut pour afficher des données.', 'world-weather-smt' )); ?></p><?

              if ($basegeo=="")
              {
                ?><p><? print(__( 'Nous n\'avez pas encore défini votre localisation', 'world-weather-smt' )); ?>.</p><?
              } else {

                ?><p><? print(__( 'Votre localisation a été enregistrée le', 'world-weather-smt' )); ?> <b><? print(date("d.m.Y",strtotime($basegeo['dt']))); ?></b><br><b><? print($basegeo['query']); ?></b> (<?  print($basegeo['lat']);  ?>/<?  print($basegeo['lng']);  ?>).</p><?
              }


            ?>
            <p><? print(__( 'Nouvelle Localisation', 'world-weather-smt' )); ?> : <input type="text" size="50" id="worldweather_geo_query" name="worldweather_geo_query">&nbsp;<button type="button" class="button" onclick="geobase_new_geocode();">Chercher</button></p>
            </div>


          </td>
          <td width="40%" valign="top">
            <div class="worldweather_options_block">
            <h2><? print(__( 'Services', 'world-weather-smt' )); ?></h2>
            <p><?
                $worldweather_services = get_option("worldweather_services");
                if (!is_array($worldweather_services))
                {
                  $worldweather_services_count = 0;
                } else {
                  $worldweather_services_count = count($worldweather_services);
                }

                if ($worldweather_services_count==0)
                {
                  ?><? print(__( 'Vous n\'avez pas configuré de service et aucune information ne va s\'afficher.', 'world-weather-smt' )); ?><?
                } else {
                  if ($worldweather_services_count==1)
                  {
                    ?><? print(__( 'Vous avez configuré un seul service', 'world-weather-smt' )); ?> :<ul><?
                  } else {
                    ?><? print(__( 'Vous avez configuré', 'world-weather-smt' )." ".$worldweather_services_count." ".__( 'services', 'world-weather-smt' )); ?> :<ul><?
                  }
                  ?><table class="wp-list-table widefat fixed striped">
                    <thead>
                      <th><? print(__( 'Service', 'world-weather-smt' )); ?></th>
                      <th><? print(__( 'Type', 'world-weather-smt' )); ?></th>
                      <th><? print(__( 'Date', 'world-weather-smt' )); ?></th>
                      <th>&nbsp;</th>
                    </thead>
                    <tbody><?
                  foreach ($worldweather_services as $key => $theservice)
                  {
                    $thisprovider=$worldweather->get_provider($key);
                    ?><tr>
                        <td><?  print($thisprovider['name']);  ?></td>
                        <td><?  print(strtoupper($thisprovider['type']));  ?></td>
                        <td><?  print(date('d.m.Y',strtotime($theservice['added'])));  ?></td>
                        <td align="right"><button class="button button-small" onclick="remove_service('<? print($key); ?>','<?  print($thisprovider['name']);  ?>')">Retirer</button></td>
                      </tr><?
                  }
                  ?></tbody>
                </table><?
                }
            ?>
            <p><? print(__( 'Ajouter un service', 'world-weather-smt' )); ?> : <select id="worldweather_provider_add_id">
              <?
              foreach ($providers as $provider)
              {
                ?><option value="<? print($provider['code']); ?>"><? print($provider['name']); ?></option><?
              }
              ?>
            </select> <button type="button" class="button button-success" onclick="show_add_provider();"><? print(__( 'Continuer', 'world-weather-smt' )); ?></button>

            <?

            foreach ($providers as $provider)
            {
              ?><div class="worldweather_provider_add" id="worldweather_provider_add_<? print($provider['code']); ?>" style="display:none;">
                <?
                if ($provider['type']=="api")
                {
                  ?><h3><? print($provider['name']); ?></h3><p><? print(__( 'Clé API', 'world-weather-smt' )); ?> <input type="text" name="provider_<? print($provider['code']); ?>_apikey" id="provider_<? print($provider['code']); ?>_apikey"><button type="button" class="button button-info button-small" onclick="add_service('<? print($provider['code']); ?>');"><? print(__( 'Enregistrer', 'world-weather-smt' )); ?></button><button type="button" class="button button-info" onclick="add_service_cancel('worldweather_provider_add_<? print($provider['code']); ?>');"><? print(__( 'Annuler', 'world-weather-smt' )); ?></button></p><?
                }
                ?>
              </div><?
            }

            ?>
</div>

          <div class="worldweather_options_block">
          <?
              print(shortcode_generator());
          ?>
          </div>
          </td>
          <td width="20%" valign="top" class="worldweather_options_rightcol">
            <img src="<?  print($pluginwebpath);  ?>images/worldweather_about_header.jpg" style="max-width:100%;width:100%;">
            <p><a href="https://swissmediatools.ch/web/extensions-wordpress/world-weather/" target="_blank">World Weather SMT</A> <? print(__( 'est une extension pour', 'world-weather-smt' )); ?> <a href="https://wordpress.org" target="_blank">Wordpress</a> <? print(__( 'développée par ', 'world-weather-smt' )); ?> <a href="https://swissmediatools.ch/" target="_blank">Swiss Media Tools</a> <? print(__( 'à Genève (Suisse)', 'world-weather-smt' )); ?>.</p>
            <p><? print(__( 'Vous utilisez la version', 'world-weather-smt' )." <b>".world_weather_smt_get_current_version()."</b>");  ?></p>

            <h3><? print(__( 'Cache', 'world-weather-smt' )); ?></h3>
            <?
                $cachefolder=$pluginpath."cache/";
                //  die(var_dump($cachefolder));
                $cptfile=0;
                $totalsize=0;
                if (is_dir($cachefolder)){
                  if ($dh = opendir($cachefolder)){
                    while (($file = readdir($dh)) !== false){
                      $cptfile++;
                      $totalsize=$totalsize+filesize($cachefolder.$file);
                    }
                    closedir($dh);
                  }
                }
                ?><p><b><? print($cptfile); ?></b> <? print(__( 'fichier(s)', 'world-weather-smt' )); ?> :
                  <b><? print(floor($totalsize/1000));  ?></b> Ko &nbsp; <a href="#" onclick="clearcache();" class="button button-small button-success"><? print(__( 'Vider le cache', 'world-weather-smt' )); ?></a></p>

                <?
            ?>

            <p><br><b><? print(__( 'Crédits', 'world-weather-smt' )); ?></b><br><?php

                $creditshtml=array();
                foreach ($credits as $credit)
                {
                    $creditshtml[]='<a href="'.$credit['url'].'" target="_blank">'.$credit['name'].'</a>';
                }
                print(implode(", ",$creditshtml));
            ?>
              </ul>
            </p>
          </td>
        </tr>
      </table>

  </div>

  <?
        $urljsapi=plugin_dir_url( __FILE__) . 'worldweatherapi.php';
  ?><script type="text/javascript">
        var worldWeatherApiService = '<?  print($urljsapi);  ?>';
  </script>

  <?php
}

?>
