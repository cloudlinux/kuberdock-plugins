// Fix jQuery twice loaded conflict
var $$ = jQuery.noConflict();

$(function() {

    var showMessage = function(text, type) {
        var cl = 'alert alert-',
            msg;

        type = typeof type === 'undefined' ? 'success' : 'error';
        cl += type == 'error' ? 'danger' : type;

        msg = $('<div>', { class: cl, role: 'alert', text: text });
        if($('div.alert').length)
            $('div.alert').replaceWith(msg);
        else
            $('h3.section').after(msg);
    };
    
    var getParam = function(variable) {
        var query = window.location.search.substring(1);
        var vars = query.split('&');
        for (var i=0;i<vars.length;i++) {
            var pair = vars[i].split('=');
            if(pair[0] == variable){return pair[1];}
        }
        return(false);
    };

    $('td.pricing').on('click', function (e){
        $('#package_' + $(this).data('id')).slideToggle();
    });

    $(document).on('submit', '.price_package_form', function(e) {
        e.preventDefault();
        var _this = $(this),
            msg;

        $.ajax({
            url: 'addonmodules.php?module=KuberDock&a=kubePrice',
            type: 'POST',
            data: _this.serialize(),
            dataType: 'json',

            complete: function() {

            }
        }).success(function(data) {

        });
    });

    $(document).on('change', '#kuber_kube_id', function(e) {
        if($(this).val() == 'new') {
            $('div.new-kube').removeClass('hidden');
        } else {
            $('div.new-kube').addClass('hidden');
        }
    });

    $(document).on('click', '.search-show', function(e) {
        if($('.search-block').css('display') == 'none') {
            $('.search-block').show(300);
        } else {
            $('.search-block').hide();
        }
    });
});