$(document).ready(kb_arrow_navigation_init);
var kb_arrow_all_links = null;

function kb_arrow_navigation_init() {
    kb_arrow_all_links = $('body a');
    $('body').keydown(kb_arrow_check);
    kb_arrow_init_focus();
    /*
     log('kb_array_navigation_init2');
     var all_links = $.makeArray($('body a'));
     log(all_links[0]);
     */
}

function kb_arrow_init_focus() {
    $('body a:first').focus();
    log('kb_arrow_init_focus:');
}


function kb_arrow_check(e) {
    switch (e.keyCode) {
        case 37:
        case 38:
            kb_arrow_focus_previous();
            e.preventDefault();
            break;
        case 9:
        case 39:
        case 40:
            kb_arrow_focus_next();
            e.preventDefault();
            break;
        case 32:
            // = SPACE
            break; // do not block other keys
        default:
            log('kb_arrow_unmapped:' + e.keyCode);
            break; // do not block other keys
    }


}


function kb_arrow_focus_by_keycode(keycode) {
    switch (keycode) {
    }
}

function kb_arrow_check_focus_tagname() {
    var did_init = false;
    var tagname = $(document.activeElement).prop("tagName").toLowerCase();
    if ('a' != tagname) {
        kb_arrow_init_focus();
        did_init = true;
    }
    return did_init;
}


function kb_arrow_determine_focus() {
    var response = [];
    response['kb_arrow_previous'] = false;
    response['kb_arrow_next'] = false;
    response['kb_arrow_current'] = false;
    response['kb_arrow_first'] = false;
    response['kb_arrow_last'] = false;
    response['kb_arrow_previous'] = false;

    var do_set_next = false;

    $('body a').each(function () {
        if (do_set_next) {
            response['kb_arrow_next'] = $(this);
            do_set_next = false;
        }
        if (($(this).attr('id') == $(document.activeElement).attr('id'))) {
            response['kb_arrow_previous'] = response['kb_arrow_last'];
            response['kb_arrow_current'] = $(this);
            do_set_next = true;
        }
        if (!response['kb_arrow_first']) {
            response['kb_arrow_first'] = $(this);
        }
        response['kb_arrow_last'] = $(this);
    });

    if (!response['kb_arrow_current']) {
        kb_arrow_init_focus();
    } else {
        if (!response['kb_arrow_previous']) {
            response['kb_arrow_previous'] = response['kb_arrow_last'];
        }
        if (!response['kb_arrow_next']) {
            response['kb_arrow_next'] = response['kb_arrow_first'];
        }
    }
    return response;
}

function kb_arrow_focus_next() {
    kb_arrow_check_focus_tagname();
    var r = kb_arrow_determine_focus();
    log(r);
    $(r['kb_arrow_next']).focus();

}

function kb_arrow_focus_previous() {
    kb_arrow_check_focus_tagname();
    var r = kb_arrow_determine_focus();
    log(r);
    $(r['kb_arrow_previous']).focus();
}