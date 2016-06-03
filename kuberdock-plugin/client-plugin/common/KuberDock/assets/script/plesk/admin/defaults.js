var renderDefaults = function() {
    var selectedPackage = jQuery('#packageId').val();

    jQuery('#packageId').empty().append(jQuery('<option>', {text: 'Choose package'}));
    jQuery('#kubeType').empty().append(jQuery('<option>', {text: 'Choose Kube Type'}));

    if(typeof(defaults) == 'undefined' || typeof(packagesKubes) == 'undefined') {
        return;
    }

    jQuery.each(packagesKubes, function(k, v) {
        selectedPackage = selectedPackage ? selectedPackage : defaults.packageId;
        jQuery('#packageId').append(jQuery('<option>', {value: v.id, text: v.name, selected: selectedPackage == v.id}));

        if(defaults.packageId == v.id){
            jQuery('label[for="packageId"]').html('Default package <div class="grey">(' + v.name + ')</div>');
        }

        if(selectedPackage == v.id) {
            jQuery.each(v.kubes, function(kKube, vKube) {
                var selectedKube = vKube.id == defaults.kubeType;
                jQuery('#kubeType').append(jQuery('<option>', {
                    value: vKube.id, text: vKube.name, selected: selectedKube
                }));
                if(selectedKube) {
                    jQuery('label[for="kubeType"]').html('Default Kube Type <div class="grey">(' + vKube.name + ')</div>');
                }
            });
        }
    });
};

var toggleSubmit = function (){
    var submitDisabled = isNaN(jQuery('#packageId').val()) || isNaN(jQuery('#kubeType').val());
    jQuery('button[name="send"]')
        .prop('disabled', submitDisabled)
        .toggleClass('submit-disabled', submitDisabled);
};

jQuery(document).on('change', '#packageId', function() {
    renderDefaults();
    toggleSubmit();
});

jQuery(document).on('change', '#kubeType', function() {
    toggleSubmit();
});

jQuery(document).ready(function() {
    renderDefaults();
});