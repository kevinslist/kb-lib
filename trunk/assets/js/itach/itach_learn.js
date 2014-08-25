
$(document).ready(call_itach);


function call_itach(){
  console.log('itached called');
  $.ajax({
      url: 'http://192.168.1.70/api/v1/irlearn', type: 'GET',
      crossDomain: true
    }).done(function (result) { 

    $.ajax({
      url: 'http://192.168.1.70/api/v1/irports/1/sendir', type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(result),
      crossDomain: true,
      success: itach_sent_ir,
      error: itach_sent_ir
    });

      console.log(result);
      console.log(JSON.stringify(result));
    });
}
function itach_sent_ir(r){
  log('itach_sent_ir:');
  log(r);
}
