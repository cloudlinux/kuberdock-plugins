var resDelete = function(obj) {
    var owner = obj.getAttribute('owner');

    window.location.href = window.location.protocol + '//' +  window.location.host +  window.location.pathname
        + '?a=deleteReseller&o=' + owner;

    return false;
};

var renderDefaults = function() {
    var selectedPackage = $('#packageId').val();
    $('#packageId').empty().append(jQuery('<option>', {text: 'Choose package'}));
    $('#kubeType').empty().append(jQuery('<option>', {text: 'Choose Kube Type'}));

    if(typeof defaults == 'undefined' || typeof packagesKubes == 'undefined') {
        return;
    }

    var defaultPackage = packagesKubes[defaults.packageId].name || 'Empty';
    $('label[for="packageId"]').html('Default package <span class="grey">(' + defaultPackage + ')</span>');

    var defaultKubeType = packagesKubes[defaults.packageId].kubes[defaults.kubeType].name || 'Empty';
    $('label[for="kubeType"]').html('Default Kube Type <span class="grey">(' + defaultKubeType + ')</span>');

    $.each(packagesKubes, function(k, v) {
        selectedPackage = selectedPackage ? selectedPackage : defaults.packageId;
        $('#packageId').append(jQuery('<option>', {value: v.id, text: v.name, selected: selectedPackage == v.id}));

        if(selectedPackage == v.id) {
            $.each(v.kubes, function(kKube, vKube) {
                $('#kubeType').append(jQuery('<option>', {
                    value: vKube.id, text: vKube.name, selected: vKube.id == defaults.kubeType
                }));
            });
        }
    });
};

$(document).on('change', '#packageId', function() {
    renderDefaults();
});

$(document).ready(function() {
    renderDefaults();

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (typeof(editor) !== 'undefined') {
            editor.refresh();
        }
    })

    if(location.hash) {
        $('a[href="' + location.hash + '"]').tab('show');
    }
});