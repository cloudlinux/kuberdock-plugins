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
            $('input[name="username"]').prop('readonly', true);
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
            trialTr = trial.closest('tr');

        $.each(trialTr.siblings(), function(index, element) {
            var isTrial = trial.prop('checked');
            var trialRow = $.inArray(index, [4]) !== -1;
            $(element).toggle(isTrial == trialRow);
            trialTr.find('td:gt(1)').toggle(isTrial);
        });
    };

    var billingTypeManage = function () {
        var billingType = $('input[name="packageconfigoption[9]"]:checked'),
            firstDeposit = $('input[type=text][name="packageconfigoption[8]"]');

        var paymentType = $('select[name="packageconfigoption[3]"]');
        var hourly = paymentType.find('option[value="hourly"]');

        if (billingType.val() == 'PAYG') {
            firstDeposit.prop('disabled', false);
            if (typeof hourly.val()=='undefined') {
                paymentType.append($('<option>', {value:'hourly', text:'hourly'}));
            }
        } else {
            firstDeposit.prop('disabled', true).val('');
            hourly.remove();
        }
    };

    $(document).on('change', 'input[type=checkbox][name="packageconfigoption[1]"]', function() {
        trialManage();
    });

    $(document).on('change', 'input[name="packageconfigoption[9]"]', function() {
        billingTypeManage();
    });

    if(window.location.href.indexOf('configproducts') >= 0) {
        $.ajax({
            url: 'addonmodules.php?module=KuberDock',
            data: {
                a: 'isKuberProduct',
                productId: $.url('?id')
            },
            dataType: 'json'
        }).done(function(data) {
            if(!data.kuberdock) {
                return false;
            }
            trialManage();
            billingTypeManage();
        });
    }

    var getPaymentType = function(selectType) {
        switch(selectType) {
            case 'annually':
                return 'year';
            case 'quarterly':
                return 'quarter';
            case 'monthly':
                return 'month';
            case 'daily':
                return 'day';
            case 'hourly':
                return 'hour';
            default:
                return 'unknown';
        }
    };

    var priceDescriptionManage = function() {
        var paymentType = getPaymentType($('select[name="packageconfigoption[3]"]').val());

        var priceIpSpan = $('input[name="packageconfigoption[5]"] + span');
        priceIpSpan.text('per IP/' + paymentType);

        var pricePsSpan = $('input[name="packageconfigoption[6]"] + span');
        pricePsSpan.text('per ' + pricePsSpan.data('unit') + '/' + paymentType);

        // AC-3783
        // var priceTrafficSpan = $('input[name="packageconfigoption[7]"] + span');
        // priceTrafficSpan.text('per ' + priceTrafficSpan.data('unit') + '/' + paymentType);
    };

    $(document).on('change', 'select[name="packageconfigoption[3]"]', function() {
        priceDescriptionManage();
    });

    priceDescriptionManage();

    // Edit product, radio buttons position
    $('td:contains("Billing type")').next('td').find('br').remove();
    $('td:contains("Restricted users")').next('td').find('input[type=checkbox]').css('position', 'relative');
});

$(function() {
    $(document).on('change', 'input[name="password_hidden"]', function() {
        $('input[name="password"]').val($(this).val());
    });
});
