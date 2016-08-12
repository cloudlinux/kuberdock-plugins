$(document).ready(function() {
    $.validate();
    
    var editor = CodeMirror.fromTextArea(document.getElementById('template'), {
        mode: "yaml",
        lineNumbers: false,
        lineWrapping: true
    });

    $('#upload_button').on('click', function (e) {
        $('#yaml_file').trigger('click');
    });

    $('#yaml_file').on('change', function (e) {
        var file = e.target.files[0],
            reader = new FileReader(),
            mimeRegEx = /(?:application\/(?:(?:x-)?yaml|json)|text.*)/;

        $(this).val('');

        if (file.type && !file.type.match(mimeRegEx)){
            utils.notifyWindow('Please, upload an yaml file.');
            return;
        }
        reader.onload = function(e){
            editor.getDoc().setValue(e.target.result);
        };

        reader.readAsText(file);
    });

    $('#button-confirm').on('click', function (e) {
        $('.check-yaml').data('submit', true).trigger('click');
    });

    $('.check-yaml').on('click', function(e) {
        if ($(this).data('submit')) {
            return true;
        }

        $.ajax({
            url: 'KuberDock/ajax.raw?a=validateYaml',
            type: 'POST',
            data: {
                'template': editor.getDoc().getValue()
            },
            dataType: 'json'
        }).done(function(data) {
            if (data.errors) {
                jQuery('#validationConfirm').modal('show').find('.modal-body').html(data.errors);
            } else {
                $('.check-yaml').data('submit', true).trigger('click');
            }
        });

        return false;
    });
});