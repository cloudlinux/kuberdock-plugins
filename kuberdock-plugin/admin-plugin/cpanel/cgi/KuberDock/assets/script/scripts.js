var resDelete = function(obj) {
    var owner = obj.getAttribute('owner');

    window.location.href = window.location.protocol + '//' +  window.location.host +  window.location.pathname
        + '?a=deleteReseller&o=' + owner;

    return false;
};

var getKubes = function() {
    if(!$('#packageId').length) {
        return;
    }

    $.ajax({
        url: 'addon_kuberdock.cgi?a=getPackageKubes&reqType=json',
        data: {
            packageId: $('#packageId').val()
        },
        dataType: 'json'
    }).done(function(data) {
        $('#kubeType').empty();
        $.each(data, function(k, v) {
            $('#kubeType').append($('<option>', {
                value: v.id,
                text: v.name
            }));
        });
    });
};

$(document).on('change', '#packageId', function() {
    getKubes();
});

$(document).ready(function() {
    getKubes();

    if(location.hash) {
        $('a[href="' + location.hash + '"]').tab('show');

    }

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (typeof(editor) !== 'undefined') {
            editor.refresh();
        }
    })
});