$(document).ready(kb_file_path_widget_ready);

function kb_file_path_widget_ready(){
  init_kb_file_path_fields();
}

function init_kb_file_path_fields(){
  $('div.kb-input-wrapper.file_path').each(init_kb_file_path_field);
}

function init_kb_file_path_field(){
  if(undefined == $(this).attr('data-did-init')){
    $(this).find('input.file_path.text').click(kb_file_path_search_triggered);
    $(this).find('input.kb-file-path-chooser').change(kb_file_path_file_selected);
    $(this).attr('data-did-init', 'true');
  }
}

function kb_file_path_search_triggered(){
  var p = $(this).parent();
  $(p).find('input.kb-file-path-chooser').click();
}

function kb_file_path_file_selected(e){
  
}