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

    var trialManage = function() {
        var trial = $('input[type=checkbox][name="packageconfigoption[1]"]'),
            trialPeriod = trial.closest('tr').find('input[name="packageconfigoption[2]"]'),
            trialTr = trial.closest('tr');

        if(trial.prop('checked')) {
            trialTr.nextAll('tr').hide();
            trialTr.find('td:gt(1)').show();
        } else {
            trialTr.nextAll('tr').show();
            trialTr.find('td:gt(1)').hide();
        }
    };

    $(document).on('change', 'input[type=checkbox][name="packageconfigoption[1]"]', function() {
        trialManage();
    });

    if(window.location.href.indexOf('configproducts') >= 0) {
        $.ajax({
            url: 'addonmodules.php?module=KuberDock',
            data: {
                a: 'isKuberProduct',
                productId: $.url('?id'),
            },
            dataType: 'json'
        }).done(function(data) {
            if(!data.kuberdock) {
                return false;
            }
            trialManage();
        });
    }
});

$(function() {
    $(document).on('change', 'input[name="password_hidden"]', function() {
        $('input[name="password"]').val($(this).val());
    });
});