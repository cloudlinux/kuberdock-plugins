jQuery(function () {
    'use strict';

    jQuery.validate();

    var editor = CodeMirror.fromTextArea(document.getElementById('code'), {
        lineNumbers: true,
        lineWrapping: true
    });

    jQuery('#yaml_file').change(function() {
        jQuery('div.alert.upload').html('').hide();
        jQuery('#progress .progress-bar').width('0px');
        jQuery('.progress-bar').text('');
    });

    jQuery('#yaml_file').fileupload({
        url: '?a=createApp&reqType=json',
        dataType: 'json',
        done: function (e, data) {
            if(!data.result.error) {
                jQuery('#app_name').val(data.result.appName);
                editor.getDoc().setValue(data.result.yaml);
                jQuery('.progress-bar').text(data.files[0].name + ' uploaded').css('text-align', 'center');
            } else {
                jQuery('div.alert.upload').html(data.result.error).show();
            }
        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            jQuery('#progress .progress-bar').css('width', progress + '%');
        }
    }).prop('disabled', !jQuery.support.fileInput)
        .parent().addClass(jQuery.support.fileInput ? undefined : 'disabled');

    jQuery('#button-confirm').on('click', function (e) {
        jQuery('.check-yaml').data('submit', true).trigger('click');
    });

    jQuery('.check-yaml').on('click', function(e) {
        if (jQuery(this).data('submit')) {
            return true;
        }

        jQuery.ajax({
            url: '?a=validateYaml&reqType=json',
            type: 'POST',
            data: {
                'template': editor.getDoc().getValue()
            },
            dataType: 'json'
        }).done(function(data) {
            if (data.status=='ERROR') {
                var data = jQuery.parseJSON(data.message);
                var container = jQuery('#validationConfirm').modal('show').find('.modal-body');
                container.html('');

                flattenCustomFields(container, data.data.customFields);
                flatten(container, data.data.appPackages);
                flatten(container, data.data.common);
                if (typeof(data.data.schema)!='undefined') {
                    flatten(container, data.data.schema.kuberdock);
                }
            } else {
                jQuery('.check-yaml').data('submit', true).trigger('click');
            }
        });

        return false;
    });

    var flatten = function (container, value, field) {
        if (typeof(value)=='object') {
            jQuery.each(value, function(name, item){
                if (field) {
                    name = jQuery.isNumeric(name) ? field : (field + ' - ' + name);
                }
                flatten(container, item, name);
            });
        } else {
            if (field) {
                value = field + ': ' + value;
            }
            if (value) {
                container.append(value + '<br>');
            }
        }
    };

    var flattenCustomFields = function (container, fields) {
        if (typeof(fields)=='undefined') {
            return;
        }

        jQuery.each(fields, function(name, field){
            container.append(name + ': ' + field.message + ' (line: ' + field.line + ', column: ' + field.column + ')<br>');
        });
    };
});
