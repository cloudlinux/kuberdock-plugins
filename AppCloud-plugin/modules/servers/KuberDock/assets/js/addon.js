// Fix jQuery twice loaded conflict
var $$ = jQuery.noConflict();

$(function() {
    // Not used
    var getKubes = function(productId) {
        var location = window.location.href;

        if(location.indexOf('a=add') < 0) {
            return false;
        }

        $.ajax({
            url: 'addonmodules.php?module=KuberDock&a=getKubes',
            data: { productId: productId },
            dataType: 'json',
            beforeSend: function() {
                $('<div>', { class: 'loader'}).appendTo('h3.section');
            },
            complete: function() {
                $('.loader').remove();
            }
        }).success(function(data) {
            var s = $('#kuber_kube_id'),
                selected = s.val(),
                text, info = [];

            s.find('option').remove();
            $('<option />', {value: 'new', text: 'Add new'}).appendTo(s);

            for(var i in data) {
                info = [
                    'CPU ' + data[i].cpu,
                    'Memory ' + data[i].memory,
                    'HDD ' + data[i].disk_space,
                    'Traffic ' + data[i].included_traffic
                ];
                text = data[i].name + ' - (' + info.join(', ') + ')';
                $('<option />', {value: data[i].id, text: text}).prop('selected', (data[i].id == selected)).appendTo(s);
            }

            s.trigger('change');
        });
    };

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

    $(document).ready(function() {
        var tab = window.location.hash.substr(1),
            productId = getParam('product_id');

        if(tab) {
            if(productId) {
                $('#kube_price_form #product_id').val(productId);
                $('#kube_price_form #product_id').trigger('change');
            }
            $$('#kuber_tab a[href="#' + tab + '"]').tab('show');
        }
    });

    // Kube price section
    $(document).on('change', '#kube_price_form #product_id', function() {
        $.ajax({
            url: 'addonmodules.php?module=KuberDock&a=kubePrice',
            data: { product_id: $(this).val() },
            beforeSend: function() {
                $('<div>', { class: 'loader'}).appendTo('h3.section');
            },
            complete: function() {
                $('.loader').remove();
            }
        }).success(function(data) {
            $('#kube_price_form').replaceWith(data);
        });
    });

    $(document).on('submit', '#kube_price_form', function(e) {
        e.preventDefault();
        var _this = $(this),
            msg;

        $.ajax({
            url: 'addonmodules.php?module=KuberDock&a=kubePrice',
            type: 'POST',
            data: _this.serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('<div>', { class: 'loader'}).appendTo('h3.section');
            },
            complete: function() {
                $('.loader').remove();
            }
        }).success(function(data) {
            if(data.error === false) {
                window.location.search = data.redirect.replace('addonmodules.php', '');
                window.location.reload();
            } else {
                showMessage(data.message, 'error');
            }
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