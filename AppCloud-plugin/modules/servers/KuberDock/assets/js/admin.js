$(document).ready(function() {
    if(window.location.href.indexOf('clientsservices')) {
        console.log();
        $.ajax({
            url: 'addonmodules.php?module=KuberDock',
            data: { a: 'isKuberProduct', productId: $('select[name="packageid"]').val() },
            dataType: 'json'
        }).done(function(data) {
            if(data.kuberdock) {
                var password = $('input[name="password"]').clone()
                    .attr('name', 'password_hidden').prop('type', 'password');

                $('input[name="password"]').hide();
                $('input[name="username"]').prop('disabled', true);
                password.insertAfter($('input[name="password"]'));
            }
        });
    }
});

$(function() {
    $(document).on('change', 'input[name="password_hidden"]', function() {
        console.log(1);
        $('input[name="password"]').val($(this).val());
    });
});