//  Javascript

function show_add_provider()
{
  var code = jQuery('#worldweather_provider_add_id').val();
  jQuery('.worldweather_provider_add').css('display','none');
  jQuery('#worldweather_provider_add_' + code).css('display','block');
}

function shortcode_generator()
{
    console.log('E> Button Shortcode Generator');
    var shortcode = '[worldweather';
    shortcode +=' provider="' + jQuery('#worldweather_shortcode_generator_service').val() + '"';
    shortcode +=' service="' + jQuery('#worldweather_shortcode_generator_api').val() + '"';
    //  shortcode +=' display="' + jQuery('#worldweather_shortcode_generator_display').val() + '"';
    shortcode +=']';
    console.log('Shortcode => ',shortcode);
    jQuery('.worldweather_shortcode_generator_result').html(shortcode);
}

function geobase_new_geocode()
{
    var query = jQuery('#worldweather_geo_query').val();

    if (query.length===0)
    {
      alert('Vous n\'avez pas indiqué d\'adresse');
    } else {
      jQuery("#worldweather_options_overlay").show();
      var post_data = { action: 'worldweather_ajax_request',nonce: adminAjax.nonce,  do: 'geobase_new_geocode', query: query };
      jQuery.ajax({
        url: adminAjax.url,
        type: 'POST',
        data: post_data,
        cache: false,
        success: function (data) {
          if (data.data.ok=="1")
          {
            jQuery("#worldweather_options_overlay").hide();
            alert('Votre adresse a été géocodée.');
            location.reload();
          } else {
            jQuery("#worldweather_options_overlay").hide();
            alert('Cette adresse n\'a pas pu être géocodée.');
          }

        },
        error: function () {
          alert('Erreur système !');
          jQuery("#worldweather_options_overlay").hide();
        }
      });
    }
}

function add_service_cancel(divid)
{
  jQuery('#' + divid).css('display','none');
}

function remove_service(service,name)
{
  if (confirm("Voulez vous vraiment retirer le service "+name+" ?"))
  {
    jQuery("#worldweather_options_overlay").show();
    var post_data = { action: 'worldweather_ajax_request',nonce: adminAjax.nonce,  do: 'remove_service', service: service };
    jQuery.ajax({
      url: adminAjax.url,
      type: 'POST',
      data: post_data,
      cache: false,
      success: function (data) {
        if (data.ok===0)
        {
          jQuery("#worldweather_options_overlay").hide();
          alert('Le service n\'a pas pu être retiré.');
        } else {
          jQuery("#worldweather_options_overlay").hide();
          alert('Le service a été correctement retiré.');
          location.reload();
        }

      },
      error: function () {
        alert('Erreur système !');
        jQuery("#worldweather_options_overlay").hide();
      }
    });
  }
}

function add_service(provider)
{
  var newkey = jQuery('#provider_' + provider + '_apikey').val();
  if (newkey.length==0)
  {
    alert('La clé API est manquante !');
  } else {
    jQuery("#worldweather_options_overlay").show();
    var post_data = { action: 'worldweather_ajax_request',nonce: adminAjax.nonce, do: 'add_service', provider: provider, apikey: newkey };
    jQuery.ajax({
      url: adminAjax.url,
      type: 'POST',
      data: post_data,
      cache: false,
      success: function (data) {
        if (data.data.ok===0)
        {
          jQuery("#worldweather_options_overlay").hide();
          alert('Le service n\'a pas pu être ajouté.');
        } else {
          jQuery("#worldweather_options_overlay").hide();
          alert('Le service a été correctement ajouté.');
          location.reload();
        }

      },
      error: function () {
        alert('Erreur système !');
        jQuery("#worldweather_options_overlay").hide();
      }
    });
  }
}

function service_load_apis()
{
    var servicecode = jQuery('#worldweather_shortcode_generator_service').val();
    console.log('adminAjax => ',adminAjax);
    var post_data = { action: 'worldweather_ajax_request', nonce: adminAjax.nonce, do: 'service_load_api', service: servicecode };
    var htmlselect = '';
    jQuery("#worldweather_options_overlay").show();
    jQuery.ajax({
      url: adminAjax.url,
      type: 'POST',
      data: post_data,
      cache: false,
      success: function (data) {
        jQuery.each(data.data.apis, function(index, value) {
          htmlselect +='<option value="' + index + '">' + value.name + '</option>';
        });
        jQuery('#worldweather_shortcode_generator_api').html(htmlselect);
        jQuery("#worldweather_options_overlay").hide();
      },
      error: function () {
        alert('Erreur système !');
        jQuery("#worldweather_options_overlay").hide();
      }
    });
}

function clearcache()
{

  var post_data = {  action: 'worldweather_ajax_request', do: 'clear_cache', nonce: adminAjax.nonce };

  if (confirm('Voulez vous vraiment effacer tous les fichiers se trouvant dans le cache ?'))
  {
    jQuery("#worldweather_options_overlay").show();
    jQuery.ajax({
      url: adminAjax.url,
      type: 'POST',
      data: post_data,
      cache: false,
      success: function (data) {
        if (data.ok=="1")
        {
          alert('Vous avez effacé ' + data.deleted + ' fichier(s) en vidant le cache.');
          jQuery("#worldweather_options_overlay").hide();
          location.reload();
        } else {
          alert('Vous n\'avez pas effacé de fichiers');
          jQuery("#worldweather_options_overlay").hide();
        }

      },
      error: function () {
        alert('Erreur système !');
        jQuery("#worldweather_options_overlay").hide();
      }
    });
  }
}

function show_licence_details(view)
{
  if (view=="1")
  {
    jQuery('#worldweather_options_rightcol_license_details').css('display','flex');
    jQuery('#show_license_details_button_show').css('display','none');
    jQuery('#show_license_details_button_hide').css('display','inline-block');
  } else {
    jQuery('#worldweather_options_rightcol_license_details').css('display','none');
    jQuery('#show_license_details_button_show').css('display','inline-block');
    jQuery('#show_license_details_button_hide').css('display','none');
  }
}
