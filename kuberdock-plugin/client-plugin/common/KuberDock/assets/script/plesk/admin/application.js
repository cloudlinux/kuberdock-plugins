var editor = null;

jQuery(function () {
    'use strict';

    jQuery.validate();

    editor = CodeMirror.fromTextArea(document.getElementById('template'), {
        mode: "yaml",
        lineNumbers: true,
        lineWrapping: true
    });

    var setProgress = function(percent) {
        var name = jQuery('#yaml_file').data('name');
        if (percent) {
            name += ' (uploaded: ' + percent + '%)';
        }
        jQuery('#yaml_file-label').text(name);
    };

    jQuery('#yaml_file').after('<span class="help-block form-error yaml_file_error"></span>');

    jQuery('#yaml_file').on('change', function() {
        jQuery('div.alert.upload').html('').hide();
        jQuery('#progress .progress-bar').width('0px');
        jQuery('.progress-bar').text('');
    });

    jQuery('#yaml_file').fileupload({
        url: '/modules/KuberDock/index.php/admin/extract-yaml',
        dataType: 'json',
        done: function (e, data) {
            if(!data.result.error) {
                editor.getDoc().setValue(data.result.yaml);
                jQuery('.yaml_file_error').text('');
            } else {
                jQuery('.yaml_file_error').text(data.result.error);
            }
            setProgress();
        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            setProgress(progress);
        }
    }).prop('disabled', !jQuery.support.fileInput)
        .parent().addClass(jQuery.support.fileInput ? undefined : 'disabled');

    jQuery('#button-confirm').on('click', function (e) {
        jQuery('form').data('submit', true).trigger('submit');
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