<?php

    if ( ! defined( 'ABSPATH' ) ) exit;

    define('WORLD_WEATHER_SMT_CURRENT_VERSION', '1.05' );

    //  Fichier common.php utilisé par les extensions de Swiss Media Tools

    //  Fournisseurs de données Météo
    $providers = array();

    //  OpenWeatherMap
    $owm = array(
      'code' => 'owm',
      'name' => 'OpenWeatherMap',
      'url' => 'https://openweathermap.org/',
      'register' => 'https://home.openweathermap.org/users/sign_up',
      'type' => 'api',
      'ssl' => 0,
      'apikeyname' => 'APPID',
      'langkeyname' => 'lang',
      'qtype' => 'geo',
      'wind' => 'ms',
      'forecastmode' => 'hourly',
      'apis' => array(
        'actual' => array('name' => 'Temps actuel', 'apiurl' => 'api.openweathermap.org/data/2.5/weather?', 'mainmode' => 'actual'),
        'forecast' => array('name' => 'Prévisions 3h/5j', 'apiurl' => 'api.openweathermap.org/data/2.5/forecast?', 'mainmode' => 'forecast','forecastmode' => 'hourly')
      ),
      'queries' => array(
        'geo' => 'lat=##LAT##&lon=##LNG##',
        'city' => 'q=##QUERY##',
        'zip' => 'zip=##ZIP##',
      ),
      'icondef' => 'http://openweathermap.org/img/wn/##EXTKEY##@2x.png',
      'icons' => array(
        '01d' => 1, '02d' => 2, '03d' => 3, '04d' => 4, '09d' => 5, '10d' => 6, '11d' => 7, '13d' => 8, '50d' => 9, '01n' => 10, '02n' => 11, '03n' => 12, '04n' => 13, '09n' => 14, '10n' => 15, '11n' => 16, '13n' => 17, '50n' => 18),
      'mesure' => array(
        'm' => '&units=metric',
        'i' => '&units=imperial'
      )
    );
    $providers[]=$owm;

    //  Accuweather
    $accuweather = array(
      'code' => 'accu',
      'name' => 'AccuWeather',
      'url' => 'https://accuweather.com/',
      'register' => 'https://api.accuweather.com',
      'type' => 'api',
      'ssl' => 0,
      'apikeyname' => 'apikey',
      'langkeyname' => 'language',
      'qtype' => 'cityid',
      'wind' => 'kmh',
      'forecastmode' => 'daynight',
      'apis' => array(
        'actual' => array('name' => 'Temps actuel', 'apiurl' => 'dataservice.accuweather.com/currentconditions/v1/','mainmode' => 'actual'),
        'forecastday5' => array('name' => 'Prévisions 5 jours', 'apiurl' => 'dataservice.accuweather.com/forecasts/v1/daily/5day/','forecastmode' => 'daynight','mainmode' => 'forecast'),
        'forecastday1' => array('name' => 'Prévisions 1 jour', 'apiurl' => 'dataservice.accuweather.com/forecasts/v1/daily/5day/','forecastmode' => 'daynight','mainmode' => 'forecast'),
        'forecastday10' => array('name' => 'Prévisions 10 jours', 'apiurl' => 'dataservice.accuweather.com/forecasts/v1/daily/10day/','forecastmode' => 'daynight','mainmode' => 'forecast'),
        'forecastday15' => array('name' => 'Prévisions 15 jours', 'apiurl' => 'dataservice.accuweather.com/forecasts/v1/daily/15day/','forecastmode' => 'daynight','mainmode' => 'forecast'),
        'forecasthour12' => array('name' => 'Prévisions 12 heures', 'apiurl' => 'dataservice.accuweather.com/forecasts/v1/daily/12hour/','forecastmode' => 'daynight','mainmode' => 'forecast'),
        'forecasthour120' => array('name' => 'Prévisions 120 heures', 'apiurl' => 'dataservice.accuweather.com/forecasts/v1/daily/120hour/','forecastmode' => 'daynight','mainmode' => 'forecast'),
      ),
      'queries' => array(
        'cityid' => '##CID##?details=true',
      ),
      'icondef' => 'https://developer.accuweather.com/sites/default/files/##EXTKEY##.png',
      'icons' => array(
        '1' => 1, '2' => 1, '3' => 1, '4' => 1, '5' => 2, '6' => 2, '7' => 3, '8' => 4, '11' => 9, '12' => 5, '13' => 6, '14' => 6, '15' => 7, '16' => 19, '17' => 19, '18' => 5, '19' => 8, '20' => 21, '21' => 21, '22' => 8, '23' => 21, '24' => 23, '25' => 8,'26' => 5, '29' => 24, '30' => 26, '31-2' => 27, '32' => 28, '33' => 10, '34' => 10, '35' => 11, '36' => 11, '37' => 11, '38' => 11, '39' => 14, '40' => 14, '41' => 20, '42' => 20, '43' => 22, '44' => 22),
      'mesure' => array(
        'm' => '&metric=true',
        'i' => '&metric=false'
      )
    );
    $providers[]=$accuweather;

    $cachedelays = array(30,60,90,120,180,360,720);

    $shortcode = array();
    $shortcode['settings'][0]['name']="Mode";
    $shortcode['settings'][0]['key']="mode";
    $shortcode['settings'][0]['type']="select";
    $shortcode['settings'][0]['values'][0]['name']='Temps actuel';
    $shortcode['settings'][0]['values'][0]['code']='actual';
    $shortcode['settings'][0]['values'][1]['name']='Prévisions';
    $shortcode['settings'][0]['values'][1]['code']='forecast';
    $shortcode['settings'][1]['name']="Service";
    $shortcode['settings'][1]['key']="service";
    $shortcode['settings'][1]['type']="service";

    $credits=array();
    $credits[]=array('name' => 'Geonames', 'url' => 'https://geonames.org');
    $credits[]=array('name' => 'Laura Reen', 'url' => 'https://www.iconfinder.com/laurareen');
    $credits[]=array('name' => 'Kmg Design', 'url' => 'https://www.iconfinder.com/kmgdesignid');


?>
