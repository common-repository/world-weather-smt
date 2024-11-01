<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class worldweather {

  function get_provider($code)
  {
    global $providers;
    $thisprovider = false;
    foreach ($providers as $provider)
    {
      if ($provider['code']==$code) {  $thisprovider=$provider; }
    }
    return $thisprovider;
  }

  function get_service($code)
  {
    $worldweather_services = get_option("worldweather_services");
    $thisservice = false;
    foreach ($worldweather_services as $key => $service)
    {
      if ($key==$code) {  $thisservice=$service; }
    }
    return $thisservice;
  }

  function get_service_default()
  {
    $worldweather_services = get_option("worldweather_services");
    $firstservice = false;
    foreach ($worldweather_services as $key => $service)
    {
      if ($firstservice==false)
      {
        $service['key']=$key;
        $service['provider']=$this->get_provider($key);
        $firstservice=$service;
      }
    }
    return $firstservice;
  }

  function worldweather_get_data($apiurl,$args=false)
  {
    global $pluginpath;
    $cache = get_option("worldweather_cache");
    $cachefile = md5($apiurl).".json";
    $cachefolder = $pluginpath."cache/";
    if (!file_exists($cachefolder.$cachefile))
    {
      $getnew = true;
    } else {
      if (filemtime($cachefolder.$cachefile)<(time()-($cache*60)))  {   $getnew = true;  }
    }
    $getnew = true;
    if ($getnew == true)
    {
      $response = wp_remote_post( $apiurl, array(
        "headers" => array(
          "Content-Type" => "application/json"
        ),
      )
    );

    $weather = json_decode($response['body']);
    $http_code = wp_remote_retrieve_response_code( $response );

    $result=array();
    if ($http_code==200)
    {
      $result['weatherdata']=$weather;
      file_put_contents($cachefolder.$cachefile,json_encode($weather));
      $result['cachefiledt']=0;
    } else {
      $result['errors'][]='L\'appel aux données (API) à retourné une erreur http '.$http_code;
    }
  } else {
    $result['weatherdata']=json_decode(file_get_contents($cachefolder.$cachefile));
    $result['cachefiledt']=filemtime($cachefolder.$cachefile);
  }
  //  die(var_dump($result));
  return $result;
}

function worldweather_wind_compass($degrees)
{
  $capes=array("N", "NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S", "SSW", "SW", "WSW", "W", "WNW", "NW", "NNW");
  return $capes[floor($degrees/22.5)];
}

function worldweather_data_convert($mainmode,$provider,$weatherdata)
{
  $dataout = array();
  if ($mainmode=="actual")
  {
    if ($provider=="owm")
    {
      // print('<pre>'.var_dump($weatherdata).'</pre>');
      $cptw=0;
      foreach ($weatherdata['weatherdata']->weather as $oneweather)
      {
        $dataout['actual']['weather'][$cptw]['owmid']=intval($oneweather->id);
        $dataout['actual']['weather'][$cptw]['owmicon']=strval($oneweather->icon);
        $dataout['actual']['weather'][$cptw]['main']=strval($oneweather->main);
        $dataout['actual']['weather'][$cptw]['description']=strval($oneweather->description);
        $cptw++;
      }
      $dataout['actual']['temp']['main']=floatval($weatherdata['weatherdata']->main->temp);
      $dataout['actual']['temp']['min']=floatval($weatherdata['weatherdata']->main->temp_min);
      $dataout['actual']['temp']['max']=floatval($weatherdata['weatherdata']->main->temp_max);
      $dataout['actual']['temp']['feel']=floatval($weatherdata['weatherdata']->main->feels_like);
      $dataout['actual']['pressure']=intval($weatherdata['weatherdata']->main->pressure);
      $dataout['actual']['wind']['speed']=floatval($weatherdata['weatherdata']->wind->speed);
      if (isset($weatherdata['weather']->wind->deg))
      {
        $dataout['actual']['wind']['deg']=intval($weatherdata['weather']->wind->deg);
      } else {
        $dataout['actual']['wind']['deg']=null;
      }
      $dataout['actual']['humidity']=intval($weatherdata['weatherdata']->main->humidity);
      $dataout['actual']['visibility']=intval($weatherdata['weatherdata']->visibility);
      $dataout['actual']['clouds']=intval($weatherdata['weatherdata']->clouds->all);
      $dataout['actual']['dt']=intval($weatherdata['weatherdata']->dt);
      $dataout['place']['city']=strval($weatherdata['weatherdata']->name);
      $dataout['place']['country']=strval($weatherdata['weatherdata']->sys->country);
      $dataout['place']['timezone']=intval($weatherdata['weatherdata']->timezone);
      $dataout['place']['sunrise']=intval($weatherdata['weatherdata']->sys->sunrise);
      $dataout['place']['sunset']=intval($weatherdata['weatherdata']->sys->sunset);
    }
    if ($provider=="accu")
    {
      //  var_dump($weatherdata['weather'][0]);
      $dataout['actual']['dt']=strtotime($weatherdata['weatherdata'][0]->LocalObservationDateTime);
      $dataout['actual']['weather'][0]['description']=strval($weatherdata['weatherdata'][0]->WeatherText);
      $dataout['actual']['weather'][0]['accuicon']=strval($weatherdata['weatherdata'][0]->WeatherIcon);
      $dataout['actual']['temp']['main']=floatval($weatherdata['weatherdata'][0]->Temperature->Metric->Value);
      $dataout['actual']['temp']['feel']=floatval($weatherdata['weatherdata'][0]->RealFeelTemperature->Metric->Value);
      $dataout['actual']['humidity']=intval($weatherdata['weatherdata'][0]->RelativeHumidity);
      $dataout['actual']['wind']['speed']=floatval($weatherdata['weatherdata'][0]->Wind->Speed->Metric->Value);
      $dataout['actual']['wind']['deg']=intval($weatherdata['weatherdata'][0]->Wind->Direction->Degrees);
      $dataout['actual']['visibility']=intval($weatherdata['weatherdata'][0]->Visibility->Metric->Value);
      $dataout['actual']['clouds']=intval($weatherdata['weatherdata'][0]->CloudCover);
      $dataout['actual']['pressure']=intval($weatherdata['weatherdata'][0]->Pressure->Metric->Value);
    }
  }
  if ($mainmode=="forecast")
  {
    if ($provider=="owm")
    {
      $cptforecast=0;
      foreach ($weatherdata['weatherdata']->list as $forecast)
      {
        $dataout['forecast'][$cptforecast]['dt']=intval($forecast->dt);
        $dataout['forecast'][$cptforecast]['owmicon']=strval($forecast->weather[0]->icon);
        $dataout['forecast'][$cptforecast]['description']=strval($forecast->weather[0]->description);
        $dataout['forecast'][$cptforecast]['temp']['main']=floatval($forecast->main->temp);
        $dataout['forecast'][$cptforecast]['temp']['min']=floatval($forecast->main->temp_min);
        $dataout['forecast'][$cptforecast]['temp']['max']=floatval($forecast->main->temp_max);
        $dataout['forecast'][$cptforecast]['temp']['feel']=floatval($forecast->main->feels_like);
        $dataout['forecast'][$cptforecast]['humidity']=intval($forecast->main->humidity);
        $dataout['forecast'][$cptforecast]['pressure']=intval($forecast->main->pressure);
        $dataout['forecast'][$cptforecast]['clouds']=intval($forecast->clouds->all);
        $dataout['forecast'][$cptforecast]['wind']['speed']=floatval($forecast->wind->speed);
        if (isset($forecast->wind->deg))
        {
          $dataout['forecast'][$cptforecast]['wind']['deg']=intval($forecast->wind->deg);
        } else {
          $dataout['forecast'][$cptforecast]['wind']['deg']=false;
        }
        $threehour='3h';
        if (isset($forecast->rain->$threehour))
        {
          $dataout['forecast'][$cptforecast]['rain']=floatval($forecast->rain->$threehour);
        } else {
          $dataout['forecast'][$cptforecast]['rain']=false;
        }
        if (isset($forecast->snow->$threehour))
        {
          $dataout['forecast'][$cptforecast]['snow']=floatval($forecast->snow->$threehour);
        } else {
          $dataout['forecast'][$cptforecast]['snow']=false;
        }
        $cptforecast++;
      }

      $dataout['place']['city']=strval($weatherdata['weather']->city->name);
      $dataout['place']['country']=strval($weatherdata['weather']->city->country);
      $dataout['place']['timezone']=intval($weatherdata['weather']->city->timezone);
      $dataout['place']['sunrise']=intval($weatherdata['weather']->city->sunrise);
      $dataout['place']['sunset']=intval($weatherdata['weather']->city->sunset);
    }
    if ($provider=="accu")
    {
      //  print('<pre>'.var_dump($weatherdata).'</pre>');
      $cptforecast=0;
      foreach ($weatherdata['weatherdata']->DailyForecasts as $forecast)
      {
        $dataout['forecast'][$cptforecast]['dt']=strtotime($forecast->Date);
        $dataout['forecast'][$cptforecast]['temp']['min']=floatval($forecast->Temperature->Minimum->Value);
        $dataout['forecast'][$cptforecast]['temp']['max']=floatval($forecast->Temperature->Maximum->Value);
        $dataout['forecast'][$cptforecast]['temp']['main']=number_format(($forecast->Temperature->Maximum->Value+$forecast->Temperature->Minimum->Value)/2,2);
        $dataout['forecast'][$cptforecast]['temp']['feel']=number_format(($forecast->RealFeelTemperature->Maximum->Value+$forecast->RealFeelTemperature->Minimum->Value)/2,2);
        $dataout['forecast'][$cptforecast]['day']['accuicon']=strval($forecast->Day->Icon);
        $dataout['forecast'][$cptforecast]['day']['description']=strval($forecast->Day->IconPhrase);
        $dataout['forecast'][$cptforecast]['day']['longtext']=strval($forecast->Day->LongPhrase);
        $dataout['forecast'][$cptforecast]['day']['probability']['prec']=intval($forecast->Day->PrecipitationProbability);
        $dataout['forecast'][$cptforecast]['day']['probability']['thunder']=intval($forecast->Day->ThunderstormProbability);
        $dataout['forecast'][$cptforecast]['day']['probability']['rain']=intval($forecast->Day->RainProbability);
        $dataout['forecast'][$cptforecast]['day']['probability']['snow']=intval($forecast->Day->SnowProbability);
        $dataout['forecast'][$cptforecast]['day']['wind']['speed']=floatval($forecast->Day->Wind->Speed->Value);
        $dataout['forecast'][$cptforecast]['day']['wind']['deg']=floatval($forecast->Day->Wind->Direction->Degrees);
        $dataout['forecast'][$cptforecast]['day']['clouds']=intval($forecast->Day->CloudCover);
        $dataout['forecast'][$cptforecast]['night']['accuicon']=strval($forecast->Night->Icon);
        $dataout['forecast'][$cptforecast]['night']['description']=strval($forecast->Night->IconPhrase);
        $dataout['forecast'][$cptforecast]['night']['longtext']=strval($forecast->Night->LongPhrase);
        $dataout['forecast'][$cptforecast]['night']['probability']['prec']=intval($forecast->Night->PrecipitationProbability);
        $dataout['forecast'][$cptforecast]['night']['probability']['thunder']=intval($forecast->Night->ThunderstormProbability);
        $dataout['forecast'][$cptforecast]['night']['probability']['rain']=intval($forecast->Night->RainProbability);
        $dataout['forecast'][$cptforecast]['night']['probability']['snow']=intval($forecast->Night->SnowProbability);
        $dataout['forecast'][$cptforecast]['night']['wind']['speed']=floatval($forecast->Night->Wind->Speed->Value);
        $dataout['forecast'][$cptforecast]['night']['wind']['deg']=floatval($forecast->Night->Wind->Direction->Degrees);
        $dataout['forecast'][$cptforecast]['night']['clouds']=intval($forecast->Night->CloudCover);
        $cptforecast++;
      }
    }
  }
  return $dataout;
}

function worldweather_api_url($params)
{
  global $providers;
  $errors=array();
  $result=array();
  $apiurl='';
  $basegeo = get_option('worldweather_basegeo');
  $basegeocoord = $basegeo['lat'].','.$basegeo['lng'];
  //  die(var_dump($basegeo['lng']));
  if (!isset($params['provider']))
  {
    $errors[]="No provider in params";
  } else {
    $worldweather = new worldweather;
    $provider=$this->get_provider(strval($params['provider']));
    $queryok=false;
    if (isset($params['mode']))
    {
      foreach ($provider['apis'] as $service => $value)
      {
        if ($value['mainmode']==$params['mode'])  {   $params['service']=$service;    }
      }
    }
    if ($provider['ssl']=="1")  { $apiurl="https://"; } else {  $apiurl="http://";  }
      if (!isset($provider['apis'][strval($params['service'])]))
      {
        $errors[]='API : service >'.$params['service'].'< is missing ???';
      } else {
        $apiurl.=$provider['apis'][$params['service']]['apiurl'];
      }
      $mainmode = $provider['apis'][$params['service']]['mainmode'];
      if ($provider['code']=="accu")  {   $params['query']=$basegeo['cityid'];$params['qtype']='cityid';   }
      if (!isset($params['qtype'])) {    $params['qtype']=$provider['qtype'];  }
      $apiquery = $provider['queries'][strval($params['qtype'])];

      if (!isset($params['query']))
      {
        $apiquery = str_replace('##LAT##',$basegeo['lat'],$apiquery);
        $apiquery = str_replace('##LNG##',$basegeo['lng'],$apiquery);
        $apiquery = str_replace('##QUERY##',$basegeo['query'],$apiquery);
        $apiquery = str_replace('##ZIP##',$basegeo['zip'],$apiquery);
        $apiquery = str_replace('##CID##',$basegeo['cityid'],$apiquery);
        $params['query']=$apiquery;
        $queryok=true;
      } else {
        if ($params['qtype']=="geo")
        {
          //  die(var_dump($params['query']));
          $queryparts=explode(",",$params['query']);
          $apiquery = str_replace('##LAT##',$queryparts[0],$apiquery);
          $apiquery = str_replace('##LNG##',$queryparts[1],$apiquery);
        }
        if ($params['qtype']=="city")
        {
          $apiquery = str_replace('##QUERY##',$params['query'],$apiquery);
        }
        if ($params['qtype']=="cityid")
        {
          $apiquery = str_replace('##CID##',$params['query'],$apiquery);
        }
        if ($params['qtype']=="zip")
        {
          $apiquery = str_replace('##ZIP##',$params['query'],$apiquery);
        }
      }
      $apiurl.=$apiquery;
      //  Ajout des paramêtres complémentaires
      $apiurl.= $provider['mesure'][get_option("worldweather_mesure")];
      if (isset($provider['langkeyname']))  {  $apiurl.='&'.$provider['langkeyname']."=".substr(get_option( 'WPLANG' ),0,2); }
      $service = $worldweather->get_service($params['provider']);
      if ($service==false)
      {
        $errors[]='API : service >'.$params['service'].'< is missing !!!';
      } else {
        $apiurl.='&'.$provider['apikeyname']."=".$service['key'];
      }
    }
    //  var_dump($apiurl);
    if (count($errors)>0) {   $result['errors']=$errors;    }
    if ($apiurl<>'')      {   $result['apiurl']=$apiurl;  }
    //  var_dump($mainmode);
    $result['mainmode']=$mainmode;
    $result['provider']=$provider;
    return $result;
  }

  function worldweather_shortcode_contents($weatherdata,$shortcode_cpt,$provider,$mode="actual",$display="default")
  {
    global $pluginwebpath;
    $iconsset = $this->get_icons_list_csv();
    $htmlout='<div class="worldweather_main" id="worldweather_'.$shortcode_cpt.'">';
    $mainmode = $provider['apis'][$mode]['mainmode'];
    $icons = get_option('worldweather_icons');
    if ($mainmode=="actual")
    {
      if ($display=="default")
      {
        $htmlout.="<p>";

        if ($icons=="0")
        {
          if (isset($weatherdata['actual']['weather'][0]['owmicon']))
          {
            $htmlout.='<img src="'.str_replace('##EXTKEY##',$weatherdata['actual']['weather'][0]['owmicon'],$provider['icondef']).'" align="right">';
          }
          if (isset($weatherdata['actual']['weather'][0]['accuicon']))
          {
            $htmlout.='<img src="'.str_replace('##EXTKEY##',$this->zerofirst($weatherdata['actual']['weather'][0]['accuicon']),$provider['icondef']).'" align="right">';
          }
        } else {
          if (isset($weatherdata['actual']['weather'][0]['owmicon']))  { $iconoriginal=$weatherdata['actual']['weather'][0]['owmicon']; }
          if (isset($weatherdata['actual']['weather'][0]['accuicon']))  { $iconoriginal=$weatherdata['actual']['weather'][0]['accuicon']; }
          $htmlout.='<img src="'.$pluginwebpath.'images/icons/'.$icons.'/'.$this->get_icon($provider,$iconoriginal).'.png" align="right">';
        }

        $htmlout.=__( 'Voici le temps observé à ', 'world-weather-smt' ).$weatherdata['place']['city'].__( ' à ', 'world-weather-smt' ).date("G:i",$weatherdata['actual']['dt'])." : <b>".esc_html($weatherdata['actual']['weather'][0]['description'])."</b><br>";

        if (isset($weatherdata['actual']['temp']))
        {
          $htmlout.=__( 'Température', 'world-weather-smt' ).' : <b>'.$weatherdata['actual']['temp']['main'].'</b>° ';
          $moretemp=array();
          if (isset($weatherdata['actual']['temp']['min']))   {     $moretemp[]=__( 'Min', 'world-weather-smt' ).": <b>".$weatherdata['actual']['temp']['min']."°</b>";        }
          if (isset($weatherdata['actual']['temp']['max']))   {     $moretemp[]=__( 'Max', 'world-weather-smt' ).": <b>".$weatherdata['actual']['temp']['max']."°</b>";        }
          if (isset($weatherdata['actual']['temp']['feel']))  {     $moretemp[]=__( 'Ressentie', 'world-weather-smt' ).": <b>".$weatherdata['actual']['temp']['feel']."°</b>";        }
          if (count($moretemp)>0)
          {
            $htmlout.="(".implode(", ",$moretemp).")";
          }
          $htmlout.="<br>";
        }
        if (isset($weatherdata['actual']['wind']))
        {
          $htmlout.=__( 'Vent', 'world-weather-smt' ).' : ';
          if ($provider['wind']=="ms")
          {
            $htmlout.='<b>'.$weatherdata['actual']['wind']['speed'].' m/s</b> = <b>'.floor($weatherdata['actual']['wind']['speed']*3.6).' km/h</b>';
          }
          if ($provider['wind']=="kmh")
          {
            $htmlout.='<b>'.round(($weatherdata['actual']['wind']['speed']/3.6),1).' m/s</b> = <b>'.$weatherdata['actual']['wind']['speed'].' km/h</b>';
          }
          //  $htmlout.='Vent : <b>'.$weatherdata['actual']['wind']['speed'].' m/s</b> = <b>'.floor($weatherdata['actual']['wind']['speed']*3.6).' km/h</b>';
          if (isset($weatherdata['actual']['wind']['deg'])) {   $htmlout.=", ".$weatherdata['actual']['wind']['deg']."° ".$this->worldweather_wind_compass($weatherdata['actual']['wind']['deg']);   }
          $htmlout.='<br>';
        }
        if (isset($weatherdata['actual']['pressure']))
        {
          $htmlout.=__( 'Pression', 'world-weather-smt' ).' : <b>'.$weatherdata['actual']['pressure'].' hpa</b> <br>';
        }
        if (isset($weatherdata['actual']['humidity']))
        {
          $htmlout.=__( 'Humidité', 'world-weather-smt' ).' : <b>'.$weatherdata['actual']['humidity'].'%</b> <br>';
        }
        if (isset($weatherdata['actual']['visibility']))
        {
          $htmlout.=__( 'Visibilité', 'world-weather-smt' ).' : <b>'.floor($weatherdata['actual']['visibility']/1000).' km</b> <br>';
        }
        if (isset($weatherdata['actual']['clouds']))
        {
          $htmlout.=__( 'Couverture nuageuse', 'world-weather-smt' ).' : <b>'.$weatherdata['actual']['clouds'].'%</b> <br>';
        }
        $htmlout.="</p>";
      } //  Fin mode defaut
    }

    if ($mainmode=="forecast")
    {
      if ($display=="default")
      {
        if (isset($weatherdata['place']['city']))
        {
          $htmlout.="<p>".__( 'Voici les prévisions à', 'world-weather-smt' )." <b>".$weatherdata['place']['city']."</b> : <br>";
        } else {
          $htmlout.="<p>".__( 'Voici les prévisions', 'world-weather-smt' )."<br>";
        }

        $htmlout.='<table class="worldweather_forecast_table">';
        /*
        $htmlout.='<thead>';
        $htmlout.='<tr>';
        $htmlout.='<th>Date/Heure</th>';
        $htmlout.='<th>Temps</th>';
        $htmlout.='<th>Température</th>';
        $htmlout.='<th>Autres</th>';
        $htmlout.='</tr>';
        $htmlout.='</thead>';
        */
        $htmlout.='<tbody>';
        $today=time();
        $actualday=date('Ymd',time());
        $forecastdate=$today;
        $mois=array('','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');
        $joursem=array('Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi');
        if ($provider['forecastmode']=="hourly")  {    $tablecol=4;     }
        if ($provider['forecastmode']=="daynight")  {    $tablecol=5;     }
        $htmlout.='<tr><td colspan="'.$tablecol.'" class="forecast_daterow">'.$joursem[date('w',$weatherdata['forecast'][0]['dt'])]." ".date("j",$weatherdata['forecast'][0]['dt'])." ".$mois[date("n",$weatherdata['forecast'][0]['dt'])]." ".date("Y",$weatherdata['forecast'][0]['dt']).' > <font color="red">Aujourd\'hui</font></td></tr>';
        $cptday=0;
        foreach ($weatherdata['forecast'] as $forecast)
        {
          if (date("Ymd",$forecast['dt'])<>$actualday)
          {
            $cptday++;
            if ($cptday==1)
            {
              $htmlout.='<tr><td colspan="'.$tablecol.'" class="forecast_daterow">'.$joursem[date('w',$forecast['dt'])]." ".date("j",$forecast['dt'])." ".$mois[date("n",$forecast['dt'])]." ".date("Y",$forecast['dt']).' > <font color="darkred">Demain</font></td></tr>';
            } else {
              $htmlout.='<tr><td colspan="'.$tablecol.'" class="forecast_daterow">'.$joursem[date('w',$forecast['dt'])]." ".date("j",$forecast['dt'])." ".$mois[date("n",$forecast['dt'])]." ".date("Y",$forecast['dt']).'</td></tr>';
            }

            $actualday=date("Ymd",$forecast['dt']);
          }
          $htmlout.='<tr>';
          if ($provider['apis'][strval($mode)]['forecastmode']=="hourly")
          {
            $htmlout.='<td class="worldweather_forecast_td" width="10%">'.date("G:i",$forecast['dt']).'</td>';
            $htmlout.='<td class="worldweather_forecast_td" width="30%">';

            if ($icons=="0")
            {
              if (isset($forecast['owmicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$forecast['owmicon'],$provider['icondef']).'" class="forecast_icon worldweather_hourly_icon">';
              }
              if (isset($forecast['accuicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$this->zerofirst($forecast['accuicon']),$provider['icondef']).'" class="forecast_icon worldweather_hourly_icon">';
              }
            } else {
              if (isset($forecast['owmicon']))  { $iconoriginal=$forecast['owmicon']; }
              if (isset($forecast['accuicon']))  { $iconoriginal=$forecast['accuicon']; }
              $htmlout.='<img src="'.$pluginwebpath.'images/icons/'.$icons.'/'.$this->get_icon($provider,$iconoriginal).'.png" class="forecast_icon worldweather_hourly_icon">';
            }
            $htmlout.=$forecast['description'].'</td>';
            $htmlout.='<td class="worldweather_forecast_td" width="30%"><span class="worldweather_forecast_bigtemp">'.number_format($forecast['temp']['main'],2).'°&nbsp;&nbsp;</span><span class="worldweather_forecast_small">('.$forecast['temp']['min'].'°>'.$forecast['temp']['max'].'°)<br>ressentie : '.$forecast['temp']['feel'].'°</span></td>';
            $htmlout.='<td class="worldweather_forecast_td worldweather_forecast_details" width="30%">';
            if ($forecast['rain']>0) {   $htmlout.=__( 'Pluie', 'world-weather-smt' )." : <b>".$forecast['rain']." mm</b><br>";    }
            if ($forecast['snow']>0) {   $htmlout.=__( 'Neige', 'world-weather-smt' )." : <b>".$forecast['snow']." mm</b><br>";    }
            if ($forecast['wind']['speed']>0.5)
            {
              $htmlout.=__( 'Vent', 'world-weather-smt' )." : <b>".floor($forecast['wind']['speed']*3.6)." km/h</b> (".$forecast['wind']['speed']." m/s)";
              if ($forecast['wind']['deg']<>false)  {   $htmlout.=" ".$forecast['wind']['deg']."° ".$this->worldweather_wind_compass($forecast['wind']['deg']);    }
              $htmlout.="<br>";
            }
            if (isset($forecast['pressure'])) {   $htmlout.=__( 'Pression', 'world-weather-smt' )." : <b>".$forecast['pressure']." hpa</b><br>";    }
            if (isset($forecast['humidity'])) {   $htmlout.=__( 'Humidité', 'world-weather-smt' )." : <b>".$forecast['humidity']."%</b><br>";    }
            if ($forecast['clouds']>0) {   $htmlout.=__( 'Couverture nuageuse', 'world-weather-smt' )." : <b>".$forecast['clouds']."%</b><br>";    }
            $htmlout.='</td>';
          }
          if ($provider['apis'][strval($mode)]['forecastmode']=="daynight")
          {
            $htmlout.='<td class="worldweather_forecast_td" width="10%" align="center">';

            if ($icons=="0")
            {
              if (isset($forecast['day']['owmicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$forecast['day']['owmicon'],$provider['icondef']).'" class="forecast_icon">';
              }
              if (isset($forecast['day']['accuicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$this->zerofirst($forecast['day']['accuicon']),$provider['icondef']).'" class="forecast_icon">';
              }
            } else {
              if (isset($forecast['day']['owmicon']))  { $iconoriginal=$forecast['day']['owmicon']; }
              if (isset($forecast['day']['accuicon']))  { $iconoriginal=$forecast['day']['accuicon']; }
              $htmlout.='<img src="'.$pluginwebpath.'images/icons/'.$icons.'/'.$this->get_icon($provider,$iconoriginal).'.png" class="forecast_icon">';
            }

            $htmlout.='<br><br><b>Jour</b>';
            $htmlout.='</td>';
            $htmlout.='<td class="worldweather_forecast_td worldweather_forecast_details" width="32%">';
            $htmlout.='<p>';
            $htmlout.='<span class="worldweather_forecast_lead">'.$forecast['day']['longtext'].'</span></p><p>';
            if ($forecast['day']['wind']['speed']>5)
            {
              $htmlout.="Vent : <b>".$forecast['day']['wind']['speed']." km/h</b> (".round(($forecast['day']['wind']['speed']/3.6),1)." m/s)";
              if ($forecast['day']['wind']['deg']<>false)  {   $htmlout.=" ".$forecast['day']['wind']['deg']."° ".$this->worldweather_wind_compass($forecast['day']['wind']['deg']);    }
              $htmlout.="<br>";
            }
            if ($forecast['day']['clouds']>0)
            {
              $htmlout.=__( 'Couverture nuageuse', 'world-weather-smt' )." : <b>".$forecast['day']['clouds']." %</b><br>";
            }
            if ($forecast['day']['probability']['prec']>0)
            {
              $htmlout.=__( 'Risque de précipitation', 'world-weather-smt' )." : <b>".$forecast['day']['probability']['prec']." %</b><br>";
            }
            if ($forecast['day']['probability']['thunder']>0)
            {
              $htmlout.=__( 'Risque d\'orage', 'world-weather-smt' )." : <b>".$forecast['day']['probability']['thunder']." %</b><br>";
            }
            if ($forecast['day']['probability']['rain']>0)
            {
              $htmlout.=__( 'Risque de pluie', 'world-weather-smt' )." : <b>".$forecast['day']['probability']['rain']." %</b><br>";
            }
            if ($forecast['day']['probability']['snow']>0)
            {
              $htmlout.=__( 'Risque de pluie', 'world-weather-smt' )." : <b>".$forecast['day']['probability']['snow']." %</b><br>";
            }
            $htmlout.='</p>';
            $htmlout.='</td>';
            $htmlout.='<td class="worldweather_forecast_td worldweather_forecast_temp" width="16%" align="center">';
            $htmlout.='<span class="worldweather_forecast_bigtemp">'.number_format($forecast['temp']['main'],1).'°</span>';
            $moretemp=array();
            if (isset($forecast['temp']['min']))   {     $moretemp[]=__( 'Min', 'world-weather-smt' )." : <b>".$forecast['temp']['min']."°</b>";        }
            if (isset($forecast['temp']['max']))   {     $moretemp[]=__( 'Max', 'world-weather-smt' )." : <b>".$forecast['temp']['max']."°</b>";        }
            if (isset($forecast['temp']['feel']))  {     $moretemp[]=__( 'Ressentie', 'world-weather-smt' )." : <b>".$forecast['temp']['feel']."°</b>";        }
            if (count($moretemp)>0)
            {
              $htmlout.='<br><span class="worldweather_forecast_details">'.implode("<br>",$moretemp).'</span>';
            }
            $htmlout.='</td>';
            $htmlout.='<td class="worldweather_forecast_td" width="10%" align="center">';

            if ($icons=="0")
            {
              if (isset($forecast['night']['owmicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$forecast['night']['owmicon'],$provider['icondef']).'" class="forecast_icon">';
              }
              if (isset($forecast['night']['accuicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$this->zerofirst($forecast['night']['accuicon']),$provider['icondef']).'" class="forecast_icon">';
              }
            } else {
              if (isset($forecast['night']['owmicon']))  { $iconoriginal=$forecast['night']['owmicon']; }
              if (isset($forecast['night']['accuicon']))  { $iconoriginal=$forecast['night']['accuicon']; }
              $htmlout.='<img src="'.$pluginwebpath.'images/icons/'.$icons.'/'.$this->get_icon($provider,$iconoriginal).'.png" class="forecast_icon">';
            }
            $htmlout.='<br><br><b>Nuit</b>';
            $htmlout.='</td>';
            $htmlout.='<td class="worldweather_forecast_td worldweather_forecast_details" width="32%">';
            $htmlout.='<span class="worldweather_forecast_lead">'.$forecast['night']['longtext'].'</span></p><p>';
            if ($forecast['night']['wind']['speed']>5)
            {
              $htmlout.="Vent : <b>".$forecast['night']['wind']['speed']." km/h</b> (".round(($forecast['night']['wind']['speed']/3.6),1)." m/s)";
              if ($forecast['night']['wind']['deg']<>false)  {   $htmlout.=" ".$forecast['night']['wind']['deg']."° ".$this->worldweather_wind_compass($forecast['night']['wind']['deg']);    }
              $htmlout.="<br>";
            }
            if ($forecast['night']['clouds']>0)
            {
              $htmlout.="Couverture nuageuse : <b>".$forecast['night']['clouds']." %</b><br>";
            }
            if ($forecast['night']['probability']['prec']>0)
            {
              $htmlout.="Risque de précipitation : <b>".$forecast['night']['probability']['prec']." %</b><br>";
            }
            if ($forecast['night']['probability']['thunder']>0)
            {
              $htmlout.="Risque d'orage : <b>".$forecast['night']['probability']['thunder']." %</b><br>";
            }
            if ($forecast['night']['probability']['rain']>0)
            {
              $htmlout.="Risque de pluie : <b>".$forecast['night']['probability']['rain']." %</b><br>";
            }
            if ($forecast['night']['probability']['snow']>0)
            {
              $htmlout.="Risque de neige : <b>".$forecast['night']['probability']['snow']." %</b><br>";
            }
            $htmlout.='</p></td>';
          }
          $htmlout.='</tr>';
        }
        $htmlout.='</tbody>';
        $htmlout.='</table>';
        $htmlout.="</p>";
      }
    } //  Fin mode défault
    $htmlout.='<p>Source : <a href="'.esc_url($provider['url']).'" target="_blank">'.esc_html($provider['name']).'</a></p>';
    $htmlout.='</div>';
    return $htmlout;
  }

  function worldweather_widget_contents($weatherdata,$provider,$mainmode="actual",$display="default")
  {
    global $pluginwebpath;
    //  die(var_dump($provider));
    $iconsset = $this->get_icons_list_csv();
    $htmlout='<div class="worldweather_widget_main">';
    $icons = get_option('worldweather_icons');
    if ($mainmode=="actual")
    {
      if ($display=="default")
      {
        $htmlout.="<p>";

        if ($icons=="0")
        {
          if (isset($weatherdata['actual']['weather'][0]['owmicon']))
          {
            $htmlout.='<img src="'.str_replace('##EXTKEY##',$weatherdata['actual']['weather'][0]['owmicon'],$provider['icondef']).'" class="worldweather_widget_icon">';
          }
          if (isset($weatherdata['actual']['weather'][0]['accuicon']))
          {
            $htmlout.='<img src="'.str_replace('##EXTKEY##',$this->zerofirst($weatherdata['actual']['weather'][0]['accuicon']),$provider['icondef']).'" align="right" class="worldweather_widget_icon">';
          }
        } else {
          if (isset($weatherdata['actual']['weather'][0]['owmicon']))  { $iconoriginal=$weatherdata['actual']['weather'][0]['owmicon']; }
          if (isset($weatherdata['actual']['weather'][0]['accuicon']))  { $iconoriginal=$weatherdata['actual']['weather'][0]['accuicon']; }
          $htmlout.='<img src="'.$pluginwebpath.'images/icons/'.$icons.'/'.$this->get_icon($provider,$iconoriginal).'.png" align="right" class="worldweather_widget_icon">';
        }

        if (isset($weatherdata['actual']['weather'][0]['description']))
        {
            $htmlout.="<b>".esc_html($weatherdata['actual']['weather'][0]['description'])."</b></p>";
        }
        $htmlout.='<div class="worldweather_widget_details">';
        if (isset($weatherdata['actual']['temp']))
        {
          $htmlout.=__( 'Température', 'world-weather-smt' ).' : <b>'.$weatherdata['actual']['temp']['main'].'</b>° ';
          $htmlout.="<br>";
        }

        if (isset($weatherdata['actual']['wind']))
        {
          $htmlout.=__( 'Vent', 'world-weather-smt' ).' : ';
          if ($provider['wind']=="ms")
          {
            $htmlout.='<b>'.$weatherdata['actual']['wind']['speed'].' m/s</b> = <b>'.floor($weatherdata['actual']['wind']['speed']*3.6).' km/h</b>';
          }
          if ($provider['wind']=="kmh")
          {
            $htmlout.='<b>'.round(($weatherdata['actual']['wind']['speed']/3.6),1).' m/s</b> = <b>'.$weatherdata['actual']['wind']['speed'].' km/h</b>';
          }
          //  $htmlout.='Vent : <b>'.$weatherdata['actual']['wind']['speed'].' m/s</b> = <b>'.floor($weatherdata['actual']['wind']['speed']*3.6).' km/h</b>';
          if (isset($weatherdata['actual']['wind']['deg'])) {   $htmlout.=", ".$weatherdata['actual']['wind']['deg']."° ".$this->worldweather_wind_compass($weatherdata['actual']['wind']['deg']);   }
          $htmlout.='<br>';
        }

        if (isset($weatherdata['actual']['visibility']))
        {
          $htmlout.=__( 'Visibilité', 'world-weather-smt' ).' : <b>'.floor($weatherdata['actual']['visibility']/1000).' km</b> <br>';
        }
        if (isset($weatherdata['actual']['clouds']))
        {
          $htmlout.=__( 'Couverture nuageuse', 'world-weather-smt' ).' : <b>'.$weatherdata['actual']['clouds'].'%</b> <br>';
        }

        $htmlout.="</div>";
      } //  Fin mode defaut
    }

    if ($mainmode=="forecast")
    {
      if ($display=="default")
      {

        if (date("G")>21)
        {
          $days=array(time()+86400,time()+86400+86400);
          $daystitle=array('Demain','Après-Demain');
        } else {
          $days=array(time(),time()+86400);
          $daystitle=array('Aujourd\'hui','Demain');
        }

        foreach ($days as $index => $day)
        {
            $htmlout.='<h6 class="worldweather_widget_forecast_daytitle"><b>'.$daystitle[$index].'</b> : '.date("d.m",$day).'</h6>';
            if ($provider['apis'][strval($mainmode)]['forecastmode']=="hourly")
            {
              $hours=array(9,12,15,18);
              $dayvalues=array();
              foreach ($weatherdata['forecast'] as $forecast)
              {
                  if (date("Ymd",$forecast['dt'])==date("Ymd",$day)&&in_array(date("G",$forecast['dt']),$hours))
                  {
                      $dayvalues[]=$forecast;
                  }
              }
              $colwidth=floor(100/count($dayvalues));
              //  var_dump($dayvalues);
              $htmlout.='<table width="100%" class="worldweather_widget_forecast_table" cellspacing="0" cellpadding="0"><tr>';
              foreach ($dayvalues as $value)
              {
                  $htmlout.='<td width="'.$colwidth.'%" align="center">';
                  $htmlout.='<div class="worldweather_widget_forecast_hour">'.date("G",$value['dt']).':00</div>';
                  if ($icons=="0")
                  {
                    if (isset($value['owmicon']))
                    {
                      $htmlout.='<img src="'.str_replace('##EXTKEY##',$value['owmicon'],$provider['icondef']).'" class="worldweather_widget_forecast_icon">';
                    }
                    if (isset($value['accuicon']))
                    {
                      $htmlout.='<img src="'.str_replace('##EXTKEY##',$this->zerofirst($value['accuicon']),$provider['icondef']).'" class="worldweather_widget_forecast_icon">';
                    }
                  } else {
                    if (isset($value['owmicon']))  { $iconoriginal=$value['owmicon']; }
                    if (isset($value['accuicon']))  { $iconoriginal=$value['accuicon']; }
                    $htmlout.='<img src="'.$pluginwebpath.'images/icons/'.$icons.'/'.$this->get_icon($provider,$iconoriginal).'.png" class="worldweather_widget_forecast_icon">';
                    $htmlout.='<div class="worldweather_widget_forecast_temperature">'.number_format($value['temp']['main'],2).'°</div>';
                  }

                  $htmlout.='</td>';
              }
              $htmlout.="</tr></table>";
            }
            if ($provider['apis'][strval($mode)]['forecastmode']=="daynight")
            {

            }

        }

        /*

        $htmlout.='<table class="worldweather_forecast_table">';

        $htmlout.='<thead>';
        $htmlout.='<tr>';
        $htmlout.='<th>Date/Heure</th>';
        $htmlout.='<th>Temps</th>';
        $htmlout.='<th>Température</th>';
        $htmlout.='<th>Autres</th>';
        $htmlout.='</tr>';
        $htmlout.='</thead>';

        $htmlout.='<tbody>';
        $today=time();
        $actualday=date('Ymd',time());
        $forecastdate=$today;
        $mois=array('','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');
        $joursem=array('Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi');
        if ($provider['forecastmode']=="hourly")  {    $tablecol=4;     }
        if ($provider['forecastmode']=="daynight")  {    $tablecol=5;     }
        $htmlout.='<tr><td colspan="'.$tablecol.'" class="forecast_daterow">'.$joursem[date('w',$weatherdata['forecast'][0]['dt'])]." ".date("j",$weatherdata['forecast'][0]['dt'])." ".$mois[date("n",$weatherdata['forecast'][0]['dt'])]." ".date("Y",$weatherdata['forecast'][0]['dt']).' > <font color="red">Aujourd\'hui</font></td></tr>';
        $cptday=0;
        foreach ($weatherdata['forecast'] as $forecast)
        {
          if (date("Ymd",$forecast['dt'])<>$actualday)
          {
            $cptday++;
            if ($cptday==1)
            {
              $htmlout.='<tr><td colspan="'.$tablecol.'" class="forecast_daterow">'.$joursem[date('w',$forecast['dt'])]." ".date("j",$forecast['dt'])." ".$mois[date("n",$forecast['dt'])]." ".date("Y",$forecast['dt']).' > <font color="darkred">Demain</font></td></tr>';
            } else {
              $htmlout.='<tr><td colspan="'.$tablecol.'" class="forecast_daterow">'.$joursem[date('w',$forecast['dt'])]." ".date("j",$forecast['dt'])." ".$mois[date("n",$forecast['dt'])]." ".date("Y",$forecast['dt']).'</td></tr>';
            }

            $actualday=date("Ymd",$forecast['dt']);
          }
          $htmlout.='<tr>';
          if ($provider['apis'][strval($mode)]['forecastmode']=="hourly")
          {
            $htmlout.='<td class="worldweather_forecast_td" width="10%">'.date("G:i",$forecast['dt']).'</td>';
            $htmlout.='<td class="worldweather_forecast_td" width="30%">';

            if ($icons=="0")
            {
              if (isset($forecast['owmicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$forecast['owmicon'],$provider['icondef']).'" class="forecast_icon worldweather_hourly_icon">';
              }
              if (isset($forecast['accuicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$this->zerofirst($forecast['accuicon']),$provider['icondef']).'" class="forecast_icon worldweather_hourly_icon">';
              }
            } else {
              if (isset($forecast['owmicon']))  { $iconoriginal=$forecast['owmicon']; }
              if (isset($forecast['accuicon']))  { $iconoriginal=$forecast['accuicon']; }
              $htmlout.='<img src="'.$pluginwebpath.'images/icons/'.$icons.'/'.$this->get_icon($provider,$iconoriginal).'.png" class="forecast_icon worldweather_hourly_icon">';
            }
            $htmlout.=$forecast['description'].'</td>';
            $htmlout.='<td class="worldweather_forecast_td" width="30%"><span class="worldweather_forecast_bigtemp">'.number_format($forecast['temp']['main'],2).'°&nbsp;&nbsp;</span><span class="worldweather_forecast_small">('.$forecast['temp']['min'].'°>'.$forecast['temp']['max'].'°)<br>ressentie : '.$forecast['temp']['feel'].'°</span></td>';
            $htmlout.='<td class="worldweather_forecast_td worldweather_forecast_details" width="30%">';
            if ($forecast['rain']>0) {   $htmlout.=__( 'Pluie', 'world-weather-smt' )." : <b>".$forecast['rain']." mm</b><br>";    }
            if ($forecast['snow']>0) {   $htmlout.=__( 'Neige', 'world-weather-smt' )." : <b>".$forecast['snow']." mm</b><br>";    }
            if ($forecast['wind']['speed']>0.5)
            {
              $htmlout.=__( 'Vent', 'world-weather-smt' )." : <b>".floor($forecast['wind']['speed']*3.6)." km/h</b> (".$forecast['wind']['speed']." m/s)";
              if ($forecast['wind']['deg']<>false)  {   $htmlout.=" ".$forecast['wind']['deg']."° ".$this->worldweather_wind_compass($forecast['wind']['deg']);    }
              $htmlout.="<br>";
            }
            if (isset($forecast['pressure'])) {   $htmlout.=__( 'Pression', 'world-weather-smt' )." : <b>".$forecast['pressure']." hpa</b><br>";    }
            if (isset($forecast['humidity'])) {   $htmlout.=__( 'Humidité', 'world-weather-smt' )." : <b>".$forecast['humidity']."%</b><br>";    }
            if ($forecast['clouds']>0) {   $htmlout.=__( 'Couverture nuageuse', 'world-weather-smt' )." : <b>".$forecast['clouds']."%</b><br>";    }
            $htmlout.='</td>';
          }
          if ($provider['apis'][strval($mode)]['forecastmode']=="daynight")
          {
            $htmlout.='<td class="worldweather_forecast_td" width="10%" align="center">';

            if ($icons=="0")
            {
              if (isset($forecast['day']['owmicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$forecast['day']['owmicon'],$provider['icondef']).'" class="forecast_icon">';
              }
              if (isset($forecast['day']['accuicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$this->zerofirst($forecast['day']['accuicon']),$provider['icondef']).'" class="forecast_icon">';
              }
            } else {
              if (isset($forecast['day']['owmicon']))  { $iconoriginal=$forecast['day']['owmicon']; }
              if (isset($forecast['day']['accuicon']))  { $iconoriginal=$forecast['day']['accuicon']; }
              $htmlout.='<img src="'.$pluginwebpath.'images/icons/'.$icons.'/'.$this->get_icon($provider,$iconoriginal).'.png" class="forecast_icon">';
            }

            $htmlout.='<br><br><b>Jour</b>';
            $htmlout.='</td>';
            $htmlout.='<td class="worldweather_forecast_td worldweather_forecast_details" width="32%">';
            $htmlout.='<p>';
            $htmlout.='<span class="worldweather_forecast_lead">'.$forecast['day']['longtext'].'</span></p><p>';
            if ($forecast['day']['wind']['speed']>5)
            {
              $htmlout.="Vent : <b>".$forecast['day']['wind']['speed']." km/h</b> (".round(($forecast['day']['wind']['speed']/3.6),1)." m/s)";
              if ($forecast['day']['wind']['deg']<>false)  {   $htmlout.=" ".$forecast['day']['wind']['deg']."° ".$this->worldweather_wind_compass($forecast['day']['wind']['deg']);    }
              $htmlout.="<br>";
            }
            if ($forecast['day']['clouds']>0)
            {
              $htmlout.=__( 'Couverture nuageuse', 'world-weather-smt' )." : <b>".$forecast['day']['clouds']." %</b><br>";
            }
            if ($forecast['day']['probability']['prec']>0)
            {
              $htmlout.=__( 'Risque de précipitation', 'world-weather-smt' )." : <b>".$forecast['day']['probability']['prec']." %</b><br>";
            }
            if ($forecast['day']['probability']['thunder']>0)
            {
              $htmlout.=__( 'Risque d\'orage', 'world-weather-smt' )." : <b>".$forecast['day']['probability']['thunder']." %</b><br>";
            }
            if ($forecast['day']['probability']['rain']>0)
            {
              $htmlout.=__( 'Risque de pluie', 'world-weather-smt' )." : <b>".$forecast['day']['probability']['rain']." %</b><br>";
            }
            if ($forecast['day']['probability']['snow']>0)
            {
              $htmlout.=__( 'Risque de pluie', 'world-weather-smt' )." : <b>".$forecast['day']['probability']['snow']." %</b><br>";
            }
            $htmlout.='</p>';
            $htmlout.='</td>';
            $htmlout.='<td class="worldweather_forecast_td worldweather_forecast_temp" width="16%" align="center">';
            $htmlout.='<span class="worldweather_forecast_bigtemp">'.number_format($forecast['temp']['main'],1).'°</span>';
            $moretemp=array();
            if (isset($forecast['temp']['min']))   {     $moretemp[]=__( 'Min', 'world-weather-smt' )." : <b>".$forecast['temp']['min']."°</b>";        }
            if (isset($forecast['temp']['max']))   {     $moretemp[]=__( 'Max', 'world-weather-smt' )." : <b>".$forecast['temp']['max']."°</b>";        }
            if (isset($forecast['temp']['feel']))  {     $moretemp[]=__( 'Ressentie', 'world-weather-smt' )." : <b>".$forecast['temp']['feel']."°</b>";        }
            if (count($moretemp)>0)
            {
              $htmlout.='<br><span class="worldweather_forecast_details">'.implode("<br>",$moretemp).'</span>';
            }
            $htmlout.='</td>';
            $htmlout.='<td class="worldweather_forecast_td" width="10%" align="center">';

            if ($icons=="0")
            {
              if (isset($forecast['night']['owmicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$forecast['night']['owmicon'],$provider['icondef']).'" class="forecast_icon">';
              }
              if (isset($forecast['night']['accuicon']))
              {
                $htmlout.='<img src="'.str_replace('##EXTKEY##',$this->zerofirst($forecast['night']['accuicon']),$provider['icondef']).'" class="forecast_icon">';
              }
            } else {
              if (isset($forecast['night']['owmicon']))  { $iconoriginal=$forecast['night']['owmicon']; }
              if (isset($forecast['night']['accuicon']))  { $iconoriginal=$forecast['night']['accuicon']; }
              $htmlout.='<img src="'.$pluginwebpath.'images/icons/'.$icons.'/'.$this->get_icon($provider,$iconoriginal).'.png" class="forecast_icon">';
            }
            $htmlout.='<br><br><b>Nuit</b>';
            $htmlout.='</td>';
            $htmlout.='<td class="worldweather_forecast_td worldweather_forecast_details" width="32%">';
            $htmlout.='<span class="worldweather_forecast_lead">'.$forecast['night']['longtext'].'</span></p><p>';
            if ($forecast['night']['wind']['speed']>5)
            {
              $htmlout.="Vent : <b>".$forecast['night']['wind']['speed']." km/h</b> (".round(($forecast['night']['wind']['speed']/3.6),1)." m/s)";
              if ($forecast['night']['wind']['deg']<>false)  {   $htmlout.=" ".$forecast['night']['wind']['deg']."° ".$this->worldweather_wind_compass($forecast['night']['wind']['deg']);    }
              $htmlout.="<br>";
            }
            if ($forecast['night']['clouds']>0)
            {
              $htmlout.="Couverture nuageuse : <b>".$forecast['night']['clouds']." %</b><br>";
            }
            if ($forecast['night']['probability']['prec']>0)
            {
              $htmlout.="Risque de précipitation : <b>".$forecast['night']['probability']['prec']." %</b><br>";
            }
            if ($forecast['night']['probability']['thunder']>0)
            {
              $htmlout.="Risque d'orage : <b>".$forecast['night']['probability']['thunder']." %</b><br>";
            }
            if ($forecast['night']['probability']['rain']>0)
            {
              $htmlout.="Risque de pluie : <b>".$forecast['night']['probability']['rain']." %</b><br>";
            }
            if ($forecast['night']['probability']['snow']>0)
            {
              $htmlout.="Risque de neige : <b>".$forecast['night']['probability']['snow']." %</b><br>";
            }
            $htmlout.='</p></td>';
          }
          $htmlout.='</tr>';
        }
        $htmlout.='</tbody>';
        $htmlout.='</table>';
        $htmlout.="</p>"
        */
      }

    } //  Fin mode défault
    $htmlout.='<p class="worldweather_widget_credit">Source : <a href="'.esc_url($provider['url']).'" target="_blank">'.esc_html($provider['name']).'</a></p>';
    $htmlout.='</div>';
    return $htmlout;
  }

  function zerofirst($value)
  {
    if (intval($value)<10)
    {
      return '0'.$value;
    } else {
      return $value;
    }
  }

  function get_icon($provider,$original)
  {
    $iconsset = $this->get_icons_list_csv();
    //  die(var_dump($provider['icons']));
    $original = str_replace('.png','',$original);
    $original = str_replace('.PNG','',$original);
    $original = str_replace('.gif','',$original);
    $original = str_replace('.GIF','',$original);
    $original = str_replace('.jpg','',$original);
    $original = str_replace('.jpeg','',$original);
    $original = str_replace('.JPG','',$original);
    $original = str_replace('.JPEG','',$original);
    //  die(var_dump($original));
    if (isset($provider['icons'][$original]))
    {
      return intval($provider['icons'][$original]);
    } else {
      return false;
    }
  }

  function get_icons_sets()
  {
    global $pluginpath;
    $result = array();
    $filenameignore = array('.','..','.DS_Store');
    $iconssetfolder = $pluginpath."images/icons/";
    if ($handle = opendir($iconssetfolder)) {
      while (false !== ($entry = readdir($handle))) {
        if (!in_array(strval($entry),$filenameignore))
        {
          $result['iconssets'][]=$entry;
        }
      }
    } else {
      $result['errors'][]="Unable to open dir ".$iconssetfolder;
    }
    return $result;
  }

  function get_icons_list_csv()
  {
    global $pluginpath;
    $iconescsvfile=$pluginpath."data/icones.csv";
    $fields=array('id','type','detail','owm','accu');
    $row = 1;
    $icones = array();
    $iconescsv = file_get_contents($iconescsvfile);
    $iconescsvlines=explode("\n",strval($iconescsv));
    foreach ($iconescsvlines as $iconescsvline)
    {
      if ($iconescsvline<>'')
      {
        $iconescsvlinedata=explode(";",$iconescsvline);
        $cptfield=0;
        $thisline=array();
        foreach ($iconescsvlinedata as $thedata)
        {
          if ($cptfield<count($fields))
          {
            $thisline[$fields[$cptfield]]=$thedata;
          }
          $cptfield++;
        }
        $icones[]=$thisline;
      }

    }
    return $icones;
  }

}

function worldweather_shortcode($atts) {

  global $shortcode_cpt,$providers;
  $errors=array();
  $weatherdata=false;
  $worldweather = new worldweather;
  $basegeo = get_option('worldweather_basegeo');
  $basegeocoord = $basegeo['lat'].','.$basegeo['lng'];
  $services = get_option('worldweather_services');
  if (!is_array($services))
  {
    $services=array();
    $errors[]="Pas de service configuré ! :-(";
  } else {
    $firstservice = $worldweather->get_service_default();
    //  die(var_dump($firstservice));
    $default = array(
      'service' => 'actual',
      'qtype' => 'geo',
      'query' => $basegeocoord,
      'provider' => $firstservice['key'],
      'display' => 'default'
    );
    if ($basegeo=="")
    {
      $errors[]='API : basegeo is empty';
    } else {
      $default['query']=$basegeo['lat'].",".$basegeo['lng'];
    }
    $params = shortcode_atts($default, $atts);

    $apiurlfull=$worldweather->worldweather_api_url($params);
    if (isset($apiurlfull['errors'])) {   $errors[]=$apiurlfull['errors'];     }
    $apiurl=$apiurlfull['apiurl'];
    //  var_dump($apiurl);
    $mainmode=$apiurlfull['mainmode'];
    $provider=$apiurlfull['provider'];
    if (isset($apiurlfull['errors'])) {    foreach ($apiurlfull['errors'] as $apierror) {   $errors[]=$apierror;   }    }
    //  var_dump($apiurl);
  }

  //  var_dump($apiurl);
  //  Execution de la requête
  if (count($errors)==0)
  {
    $weatherdata = $worldweather->worldweather_get_data($apiurl,false);
    //  die(var_dump($weatherdata));
    if (isset($weatherdata['errors']))
    {
      foreach ($weatherdata['errors'] as $error)
      {
        $errors[]=$error;
      }
    } else {
      $weatherdata = $worldweather->worldweather_data_convert($mainmode,$params['provider'],$weatherdata);
      if (isset($weatherdata['errors'])) {    foreach ($weatherdata['errors'] as $wderror) {   $errors[]=$wderror;    }    }
      if (count($errors)==0)
      {
        $htmlout = $worldweather->worldweather_shortcode_contents($weatherdata,$shortcode_cpt,$provider,$params['service'],$params['display']);
      }
    }
    //  die(var_dump($weatherdata));
    //  Conversion des données

  }

  if (count($errors)>0)
  {

    $htmlout.='<div class="worldweather_errors">';
    if (count($errors)==1)
    {
      $htmlout.='<h4>Erreur</h4><ul class="worldweather_errors_errorlist">';
    } else {
      $htmlout.='<h4>Erreurs</h4><ul class="worldweather_errors_errorlist">';
    }
    foreach ($errors as $error)
    {
      $htmlout.='<li class="worldweather_errors_error">'.$error.'</li>';
    }
    $htmlout.='</ul></div><br>';
  }

  return $htmlout;
  $shortcode_cpt++;
}


//  Widget

class worldweather_widget_class extends WP_Widget {

  // Main constructor
  public function __construct() {
    parent::__construct(
      'worldweather_widget',
      __( 'World Weather', 'world-weather-smt' ),
      array(
        'customize_selective_refresh' => true,
      )
    );
  }

  // The widget form (for the backend )
  public function form( $instance ) {

    $worldweather = new worldweather;
    $services = get_option('worldweather_services');

    $showform=true;

    if (!is_array($services)) {   $showform=false;  }

    // Set widget defaults
    $defaults = array(
      'title'    => '',
      'service'     => '',
      'mode'  => ''
    );

    if ($showform==false)
    {
      ?><h4>Pas de service configuré !</h4><p>Vous devez commencer par configurer un service avec un fournisseur de données dans les <a href="/wp-admin/options-general.php?page=worldweather">options</a> de World Weather.<?
    } else {

      // Parse current settings with defaults
      extract( wp_parse_args( ( array ) $instance, $defaults ) ); ?>

      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Titre du Widget', 'world-weather-smt' ); ?> : </label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
      </p>

      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'service' ) ); ?>"><?php _e( 'Service', 'world-weather-smt' ); ?> : </label>
        <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'service' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'service' ) ); ?>">
          <?
          foreach ($services as $key => $service)
          {
            $thisprovider = $worldweather->get_provider($key);
            if ($key<>'accu') {   ?><option value="<? print($key);  ?>" <? selected( $service, $key, false ); ?>><? print($thisprovider['name']); ?></option><? }
          }
          ?>
        </select>
      </p>

      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>"><?php _e( 'Mode', 'world-weather-smt' ); ?> :&nbsp;&nbsp;&nbsp;</label>
        <input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>_actual" name="<?php echo esc_attr( $this->get_field_name( 'mode' ) ); ?>" value="actual" <? checked( $mode, 'actual', true ); ?>> Temps Actuel&nbsp;&nbsp;&nbsp;
        <input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>_forecast" name="<?php echo esc_attr( $this->get_field_name( 'mode' ) ); ?>" value="forecast" <? checked( $mode, 'forecast', true ); ?>> Prévisions
      </p>

      <?php

    }
  }

  // Update widget settings
  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['title']    = isset( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
    $instance['service']     = isset( $new_instance['service'] ) ? wp_strip_all_tags( $new_instance['service'] ) : '';
    $instance['mode'] = isset( $new_instance['mode'] ) ? wp_kses_post( $new_instance['mode'] ) : '';
    return $instance;
  }

  // Display the widget
  public function widget( $args, $instance ) {
    $services = get_option('worldweather_services');
    $errors = array();

    extract( $args );

    // Check the widget options
    $title    = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
    $service     = isset( $instance['service'] ) ? $instance['service'] : '';
    $mode = isset( $instance['mode'] ) ? $instance['mode'] : '';

    // WordPress core before_widget hook (always include )
    echo $before_widget;

    // Display the widget
    echo '<div class="widget-text wp_widget_plugin_box">';

    // Display widget title if defined
    if ( $title ) {
      echo $before_title . $title . $after_title;
    }

    if (!is_array($services))
    {
      ?><h4>Pas de service configuré !</h4><p>Vous devez commencer par configurer un service avec un fournisseur de données dans les options de World Weather.<?
    } else {

      $params['mode']=$mode;
      if (!isset($params['display'])) {  $params['display']='default'; }
      $worldweather = new worldweather;
      //  $provider = $worldweather->get_provider($service);
      $params['provider']=$service;
      $apiurlfull=$worldweather->worldweather_api_url($params);
      if (isset($apiurlfull['errors'])) {   $errors[]=$apiurlfull['errors'];     }
      $apiurl=$apiurlfull['apiurl'];

      //  var_dump($apiurl);
      $mainmode=$apiurlfull['mainmode'];
      $provider=$apiurlfull['provider'];
      if (isset($apiurlfull['errors'])) {    foreach ($apiurlfull['errors'] as $apierror) {   $errors[]=$apierror;   }    }

      //  Execution de la requête
      if (count($errors)==0)
      {
        $weatherdata = $worldweather->worldweather_get_data($apiurl,false);
        //  die(var_dump($weatherdata));
        if (isset($weatherdata['errors']))
        {
          foreach ($weatherdata['errors'] as $error)
          {
            $errors[]=$error;
          }
        } else {
          $weatherdata = $worldweather->worldweather_data_convert($mainmode,$service,$weatherdata);
          //  die(var_dump($weatherdata));
          if (isset($weatherdata['errors'])) {    foreach ($weatherdata['errors'] as $wderror) {   $errors[]=$wderror;    }    }
          if (count($errors)==0)
          {
            echo $worldweather->worldweather_widget_contents($weatherdata,$provider,$mainmode,$params['display']);
          }
        }

      }

      if (count($errors)>0)
      {

        $htmlout='<div class="worldweather_errors">';
        if (count($errors)==1)
        {
          $htmlout.='<h4>Erreur</h4><ul class="worldweather_errors_errorlist">';
        } else {
          $htmlout.='<h4>Erreurs</h4><ul class="worldweather_errors_errorlist">';
        }
        foreach ($errors as $error)
        {
          $htmlout.='<li class="worldweather_errors_error">'.$error.'</li>';
        }
        $htmlout.='</ul></div><br>';
        echo $htmlout;
      }

      //  echo var_dump($apiurlfull['apiurl']);

    }

    echo '</div>';

    // WordPress core after_widget hook (always include )
    echo $after_widget;

    $widget_cpt++;

  }

}

?>
