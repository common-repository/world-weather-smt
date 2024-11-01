<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function shortcode_generator()
{
  global $shortcode;
  $worldweather = new worldweather;
  $htmlout='<h3>Générateur de Shortcode</h3>';
  $htmlout.='<p>Pour afficher des données météo dans une page ou un article, vous pouvez utiliser le générateur de shortcodes qui va vous préparer un bout de code que vous n\'aurez plus qu\'a coller dans votre contenu.</p>';

  $worldweather_services = get_option("worldweather_services");
  if (!is_array($worldweather_services))
    {
      $worldweather_services=array();
    } else {
      $firstservice=$worldweather->get_service_default();
      //  die(var_dump($firstservice['provider']['apis']));
    }

  if (count($worldweather_services)==0)
  {
    $htmlout.="<p><b>Aucun service activé !</b><br>Vous devez commencer par activer au moins un service (en haut à droite de cette page).";
  } else {
    $htmlout.='<div class="worldweather_shortcode_generator">';
    $htmlout.='<div class="worldweather_shortcode_generator_form">';
    $htmlout.='<p>';
    $htmlout.='Fournisseur : <select id="worldweather_shortcode_generator_service" onchange="service_load_apis();">';
    foreach ($worldweather_services as $key => $service)
    {
      $thisprovider = $worldweather->get_provider($key);
      $htmlout.='<option value="'.$key.'">'.$thisprovider['name'].'</option>';
    }
    $htmlout.='</select>&nbsp;&nbsp;&nbsp;';

    $htmlout.='Service : <select id="worldweather_shortcode_generator_api">';
    foreach ($firstservice['provider']['apis'] as $key => $api)
    {
      $htmlout.='<option value="'.$key.'">'.$api['name'].'</option>';
    }
    $htmlout.='</select>&nbsp;&nbsp;&nbsp;';

    $htmlout.='</p>';
    $htmlout.='<p><button type="button" class="button button-success" onclick="shortcode_generator();">Générer</button></p>';
    $htmlout.='</div>';
    $htmlout.='<div class="worldweather_shortcode_generator_result">[worldweather]</div>';
    $htmlout.='</div>';
  }
  return $htmlout;
}

?>
