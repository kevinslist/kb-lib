$(document).ready(kb_auth_global_init);
var kb_auth_type = '';
var kb_google_client_id = '';

function kb_auth_global_init() {
  if ($('div#kb-athentication-provider-wrapper').length > 0) {
    kb_auth_type = $('div#kb-athentication-provider-wrapper').attr('data-kb-auth-type');
    if($('#kb-google-signin-block').length){
      kb_google_client_id = $('#kb-google-signin-block').attr('data-clientid');
      $.getScript('https://apis.google.com/js/client:plusone.js');
    }
  }
}

function kb_google_signin_callback(auth_result) {
  if (auth_result) {
    if (auth_result['status'] != undefined && auth_result['status']['signed_in']) {
      if ('logout' == kb_auth_type) {
        gapi.auth.signOut();
      } else {
        var p = {};
        p['provider'] = 'google';
        p['access_token'] = auth_result['access_token'];
        $('#kb-athentication-provider-wrapper').trigger('kb-login-user-authenticated', p);
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