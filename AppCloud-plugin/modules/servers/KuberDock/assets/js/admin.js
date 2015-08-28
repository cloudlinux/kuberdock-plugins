$(document).ready(function() {
    if(window.location.href.indexOf('clientsservices') >= 0) {
        $.ajax({
            url: 'addonmodules.php?module=KuberDock',
            data: {
                a: 'isKuberProduct',
                productId: $('select[name="packageid"]').val(),
                serviceId: $('select[name="id"]').val()
            },
            dataType: 'json'
        }).done(function(data) {
            if(!data.kuberdock) {
                return;
            }

            var password = $('input[name="password"]').clone()
                .attr('name', 'password_hidden').prop('type', 'password'),
                nextDueDate = jQuery('<input>', {
                    type: 'text',
                    name: 'nextduedate',
                    class: 'datepick',
                    size: 12,
                    value: data.nextduedate
                });

            $('input[name="password"]').hide();
            $('input[name="username"]').prop('disabled', true);
            password.insertAfter($('input[name="password"]'));

            $('input[name="domain"]').parents('tr:eq(0)').find('td.fieldarea:last').html(nextDueDate);

            nextDueDate.datepicker({
                dateFormat: datepickerformat,
                showOn: "button",
                buttonImage: "images/showcalendar.gif",
                buttonImageOnly: true,
                showButtonPanel: true,
                showOtherMonths: true,
                selectOtherMonths: true
            });
        });
    }
});

$(function() {
    $(document).on('change', 'input[name="password_hidden"]', function() {
        $('input[name="password"]').val($(this).val());
    });
});