var editor;
var KDReq = requirejs.config({
    baseUrl: '/modules/KuberDock/assets/script/lib/',
    urlArgs: "bust=" +  (new Date()).getTime(),
    paths: {
        jquery: 'jquery.min',
        noconflict: '../plesk/admin/noconflict',
        bootstrap: 'bootstrap.min',
        CodeMirrorYaml: 'codemirror/mode/yaml/yaml',
        CodeMirror: 'codemirror/codemirror.min',
        formValidator: 'jquery.form-validator.min',
        'jquery.ui.widget': 'fileupload/js/vendor/jquery.ui.widget',
        'jquery.iframe.transport': 'fileupload/js/jquery.iframe-transport',
        fileupload: 'fileupload/js/jquery.fileupload'
    },
    shim: {
        bootstrap: {
            deps: ['jquery']
        },
        CodeMirror: {
            deps: ['CodeMirrorYaml']
        },
        formValidator: ['jquery'],
        'jquery.ui.widget': ['jquery'],
        'jquery.iframe.transport': ['jquery'],
        fileupload: {
            exports: ['jquery'],
            deps: ['jquery', 'jquery.iframe.transport']
        }
    },
    map: {
        '*': {
            'jquery': 'noconflict'
        },
        'noconflict': {
            'jquery': 'jquery'
        }
    },
    waitSeconds: 30
});

require(['jquery', 'CodeMirror', 'fileupload', 'formValidator'], function ($, CodeMirror) {
    'use strict';

    $.validate();

    editor = CodeMirror.fromTextArea(document.getElementById('template'), {
        mode: "yaml",
        lineNumbers: true,
        lineWrapping: true
    });

    $('#yaml_file-element').append('<div id="progress" class="progress">' +
        '<div class="progress-bar progress-bar-success"></div>' +
        '<div class="clearfix"></div>' +
    '</div>');

    var setProgress = function(percent, name) {
        if (percent) {
            name += ' (uploaded: ' + percent + '%)';
        }
        $('.progress-bar').width(percent + '%').text(name);
    };

    $('#yaml_file').after('<span class="help-block form-error yaml_file_error"></span>');

    $('#yaml_file').on('change', function() {
        $('div.alert.upload').html('').hide();
        $('#progress .progress-bar').width('0px');
        $('.progress-bar').text('');
    });

    $('#yaml_file').fileupload({
        url: '/modules/KuberDock/index.php/admin/extract-yaml',
        dataType: 'json',
        done: function (e, data) {
            if(!data.result.error) {
                editor.getDoc().setValue(data.result.yaml);
                $('.yaml_file_error').text('');
            } else {
                $('.yaml_file_error').text(data.result.error);
            }
            setProgress(100, data.files[0].name);
        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            setProgress(progress);
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');

    $('#button-confirm').on('click', function (e) {
        $('form').data('submit', true).trigger('submit');
    })
});

function validate_form()
{
    if (jQuery('form').data('submit')) {
        return true;
    }

    jQuery.ajax({
        url: '/modules/KuberDock/index.php/admin/validate-yaml',
        type: 'POST',
        data: {
            'template': editor.getDoc().getValue()
        },
        dataType: 'json'
    }).done(function(data) {
        if (data.errors) {
            jQuery('#validationConfirm').modal('show').find('.modal-body').html(data.errors);
        } else {
            jQuery('form').data('submit', true).trigger('submit');
        }
    });

    return false;
}