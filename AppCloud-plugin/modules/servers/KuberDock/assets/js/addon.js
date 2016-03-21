// Fix jQuery twice loaded conflict
var $$ = jQuery.noConflict();

$(function() {

    var hash = window.location.hash || '#kubes';
    $('ul.nav a[href="' + hash + '"]').tab('show');

    $('.nav-tabs a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
        var curPos=$(document).scrollTop();
        window.location.hash = this.hash;
        $(document).scrollTop(curPos);
    });

    var pricing = $('span.pricing');
    pricing.on('click', function (e){
        var text = $(e.target).text();
        $('#package_' + $(this).parent().data('id')).toggle();
        $(this).parent().toggleClass('active');
        $(e.target).text(text == "$ Pricing settings" ? "∧ Hide settings" : "$ Pricing settings");
    });

    var cancelPriceChange = function(span) {
        var input = span.siblings('input[name="kube_price"]');
        input.val(input.data('prev'));
        span.addClass('hidden');
    };

    $(document).on('submit', '.price_package_form', function(e) {
        e.preventDefault();
        var _this = $(this);

        $.ajax({
            url: 'addonmodules.php?module=KuberDock&a=kubePrice',
            type: 'POST',
            data: _this.serialize(),
            dataType: 'json'
        }).success(function(data) {
            var span = _this.find('span');
            span.addClass('hidden');
            if (data.error) {
                cancelPriceChange(span);
                _this.append('<span class="error">' + data.message + '</span>');
            } else {
                var values = data.values;
                _this.find('input[name="kube_price"]').data('prev', values.kube_price).val(values.kube_price);
                _this.find('input[name="id"]').val(values.id);
            }
        });
    });

    $(document).on('click', 'button[type="cancel"]', function(e) {
        e.preventDefault();
        cancelPriceChange($(this).parents('span'));
    });

    $(document).on('focusin', 'input[name="kube_price"]', function(e) {
        var span = $(this).siblings('span');
        span.removeClass('hidden');
        var error = $('span.error');
        if (error.length) {
            error.remove();
        }
    });

    $(document).on('focusout', 'input[name="kube_price"]', function(e) {
        var _this = $(this);
        if (_this.data('prev')==_this.val()) {
            _this.siblings('span').addClass('hidden');
        }
    });
});