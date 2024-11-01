<?php

      if ( ! defined( 'ABSPATH' ) ) exit;

      class worldweather_geonames {

        var $apikey = '';

        function get_api($apiurl)
        {

          $response = wp_remote_post( $apiurl, array(
              "headers" => array(
                  "Content-Type" => "application/json"
              ),
            )
          );

          $responsebody = json_decode($response['body']);
          $http_code = wp_remote_retrieve_response_code( $response );

          $result=array();
          if ($http_code==200)
          {
            $result=$responsebody;
          } else {
            $result['error'][]='http code : '.$http_code;
          }
          return $result;
        }

        function geocode($query)
        {
            $apiurl = 'http://api.geonames.org/geoCodeAddressJSON?q='.urlencode($query).'&username='.$this->apikey;
            $result = $this->get_api($apiurl);
            return $result;
        }

      }

?>
