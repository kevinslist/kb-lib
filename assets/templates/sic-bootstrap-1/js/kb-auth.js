$(document).ready(kb_auth_global_init);
var kb_google_client_id = '';
var kb_google_auth_type = '';

function kb_auth_global_init() {
  if ($('div.kb-google-auth-load').length > 0) {
    kb_google_client_id = $('#kb-google-signin-block').attr('data-clientid');
    kb_google_auth_type = $('#kb-auth-type').attr('data-kb-auth-type');
    $.getScript('https://apis.google.com/js/client:plusone.js');
  }
}

function kb_google_signin_callback(auth_result) {
  if (auth_result) {
    if (auth_result['status'] != undefined && auth_result['status']['signed_in']) {
      if ('logout' == kb_google_auth_type) {
        gapi.auth.signOut();
        //kb_structure_resize();
      } else {
        //kb_finalize_login_anime();
        var access_token = auth_result['access_token'];
        auth_result['provider'] = 'google';
        auth_result['access_token'] = access_token;
        kb_login_check_registration(auth_result);
      }
    } else {
      var google_params = {
        'clientid': kb_google_client_id,
        'cookiepolicy': 'single_host_origin',
        'callback': kb_google_signin_callback,
        'scope': 'https://www.googleapis.com/auth/plus.me'
      };
      gapi.auth.signIn(google_params);

    }
  } else {
    var p = {
      'client_id': kb_google_client_id,
      'scope': 'https://www.googleapis.com/auth/plus.me',
      'immediate': true
    };
    gapi.auth.authorize(p, google_signin_callback);
  }
  return;
}

function kb_login_check_registration(params){
  params[kb_csrf_name] = kb_csrf_hash;
  $.ajax({
    url: app_home + 'login/check-registration-ajax',
    dataType: 'json',
    data: params,
    type: 'POST',
    success: kb_login_check_registration_response,
    error: kb_login_check_registration_response
  });
}

function kb_login_check_registration_response(r){
  log('kb_login_check_registration_response:');
  log(kb_login_check_registration_response);
}