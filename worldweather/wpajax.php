<?php

    if ( ! defined( 'ABSPATH' ) ) exit;

    if (!is_admin())  exit;

    if (isset($_GET['do']))			  {	   $apido=sanitize_title_for_query($_GET['do']);	    }
    if (isset($_POST['do']))		  {	   $apido=sanitize_title_for_query($_POST['do']);		  }

    $dataout = array();
    $queries = array();
    $errors = array();
    $parpage_def = 25;

    include($pluginpath.'common.php');

    //  $dataout['ajax']=true;

    //  die(var_dump($_POST));

    $nonce = $_POST['nonce'];
    if (!wp_verify_nonce($nonce,'worldweather-ajax-nonce')) { die ('WTF nonce ?!?'); }

    //	Récupération du GET
    if ($_SERVER['QUERY_STRING']<>'')
    {
      $fullquery = $_SERVER['QUERY_STRING'];
      $fullquerytab = explode("&",$fullquery);
      foreach ($fullquerytab as $querytab)
      {
        $querytabparts=explode("=",$querytab);
        $queries[sanitize_key($querytabparts[0])]=sanitize_text_field($querytabparts[1]);
      }
    }
    //	Récupération du POST
    if (isset($_POST))
    {
      foreach ($_POST as $post_key => $post_value )
      {
        $queries[$post_key]=sanitize_text_field($post_value);
      }
    }

    //	$dataout['queries']=$queries;
    //	$dataout['fullquery']=$fullquery;

    if (!isset($apido))		{	$errors[]="Commande DO manquante";	}	else	{	$dataout['apido']=$apido;	}

    if ($apido=="geobase_new_geocode")
    {
        $geonames = new worldweather_geonames;
        $geonames->apikey = 'worldweathersmt';
        $geobase = $geonames->geocode($queries['query']);
        $dataout['query']=$queries['query'];
        if (isset($geobase->address))
        {
          $dataout['lat']=floatval($geobase->address->lat);
          $dataout['lon']=floatval($geobase->address->lng);
          $newgeobase = array('lat' => floatval($geobase->address->lat), 'lng' => floatval($geobase->address->lng), 'cityid' => '312997', 'query' => $queries['query'], 'dt' => date('Y-m-d'));
          update_option('worldweather_basegeo', $newgeobase);
          $dataout['ok']=1;
        } else {
          $dataout['ok']=0;
        }
    }

    if ($apido=="add_service")
    {
        $services = get_option("worldweather_services");
        //  die(json_encode($services));
        if (!is_array($services))
        {
          $services=array();
        }
        $services[$queries['provider']]['key']=$queries['apikey'];
        $services[$queries['provider']]['added']=date("Y-m-d");
        update_option('worldweather_services', $services);
        $dataout['ok']=1;
    }

    if ($apido=="remove_service")
    {
      $services = get_option("worldweather_services");
      $newservices = array();
      $deleted = false;
      foreach ($services as $key => $service)
      {
        if ($key<>$queries['service']) {  $newservices[$key]=$service;   }  else {   $deleted=true;     }
      }
      if ($deleted==true)
      {
        $dataout['ok']=1;
        if (count($newservices)==0) {  $newservices='';  }
        update_option('worldweather_services', $newservices);
      } else {
        $dataout['ok']=0;
      }
    }

    if ($apido=="clear_cache")
    {
      //  $cachefolder=dirname(plugin_dir_path( __FILE__ ))."cache/";
      $cachefolder=$pluginpath.'cache/';
      //  $dataout['cachefolder']=$cachefolder;
      $cptfile=0;
      $totalsize=0;
      if (is_dir($cachefolder)){
        if ($dh = opendir($cachefolder)){
          while (($file = readdir($dh)) !== false){
            unlink($cachefolder.$file);
            $cptfile++;
          }
          closedir($dh);
          $dataout['ok']=1;
          $dataout['deleted']=$cptfile;
        } else {
          $dataout['ok']=0;
        }
      } else {
        $dataout['ok']=0;
      }
    }

    if ($apido=="service_load_api")
    {
        //  die(var_dump($providers));
        foreach ($providers as $provider)
        {
          if ($provider['code']==$queries['service']) {    $dataout['apis']=$provider['apis'];     }
        }
    }

    wp_send_json_success($dataout);

    wp_die();

?>
