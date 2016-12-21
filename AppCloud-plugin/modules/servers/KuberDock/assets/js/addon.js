// Fix jQuery twice loaded conflict
jQuery.noConflict(true);

(function ($) {
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

        $(document).on('click', 'span.pricing', function (e){
            var text = $(e.target).text();
            $('#package_' + $(this).parent().data('id')).toggle();
            $(this).parent().toggleClass('active');
            $(e.target).text(text === "$ Pricing settings" ? "∧ Hide settings" : "$ Pricing settings");
        });

        $(document).on('change', '.active_kube_checkbox' , function () {
            var input = $('#price_input_' + $(this).data('input'));
            var checked = this.checked;
            var prev = input.data('prev');

            input
                .val(checked ? prev : '')
                .prop('disabled', !checked)
                .parent('.price_package_form').trigger({type:"submit", prev:prev})
                .siblings('span').addClass('hidden');
        });

        var cancelPriceChange = function(span) {
            var input = span.siblings('input[name="kube_price"]');
            input.val(input.data('prev'));
            span.addClass('hidden');
        };

        var showError = function(self, message) {
            cancelPriceChange(self.find('span'));
            self.append('<span class="error">' + message + '</span>');
        };

        $(document).on('submit', '.price_package_form', function(e) {
            e.preventDefault();
            var self = $(this);
            var called_from_checkbox = (typeof e.prev!=='undefined');

            if (!called_from_checkbox && self.find('input[name="kube_price"]').val()=='') {
                showError(self, 'Price required');
                return false;
            }

            $.ajax({
                url: 'addonmodules.php?module=KuberDock&a=kubePrice',
                type: 'POST',
                data: self.serialize(),
                dataType: 'json'
            }).success(function(data) {
                var span = self.find('span');
                span.addClass('hidden');
                if (data.error) {
                    showError(self, data.message);
                    if (called_from_checkbox) {
                        var input = self.find('input[name="kube_price"]');
                        input.prop('disabled', !input.prop('disabled'));
                        var checkbox = $('#active_kube_checkbox_' + input.data('input'));
                        checkbox.prop('checked', !checkbox.prop('checked'));
                    }
                } else {
                    var values = data.values;

                    var deleteButton = self.parents('tr:eq(1)').prev('tr').find('.kube-delete');
                    if (values.deletable) {
                        deleteButton.removeClass('hidden');
                    } else {
                        deleteButton.addClass('hidden');
                    }

                    var prev = called_from_checkbox
                        ? e.prev
                        : values.kube_price;

                    self.find('input[name="kube_price"]').data('prev', prev).val(values.kube_price);
                    self.find('input[name="id"]').val(values.id);
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

        $(document).on('click', 'button.migration', function(e) {
            var self = $(this);

            $.ajax({
                url: 'addonmodules.php?module=KuberDock&a=migrate',
                type: 'POST',
                dataType: 'json'
            }).success(function(data) {
                var modal = $('#myModal');
                modal.find('.modal-body').html(data.message);
                modal.modal('show');
                self.remove();
            });
        });

        $(document).on('click', 'button.kube-delete', function (e) {
            e.preventDefault();
            var self = $(this);

            if (!confirm('You want to delete kube?')) {
                return false;
            }

            $.ajax({
                url: 'addonmodules.php?module=KuberDock&a=delete',
                type: 'POST',
                data: {
                    id: $(this).data('kube-id')
                },
                dataType: 'json',
                beforeSend: function () {
                    $('div.alert').addClass('hidden');
                }
            }).success(function (response) {
                var message = $('.message').text(response.message).parents('div.alert');
                if (response.error) {
                    message.addClass('alert-danger').removeClass('alert-success');
                } else {
                    self.parents('tr').next('tr').remove();
                    self.parents('tr').remove();
                    message.removeClass('alert-danger').addClass('alert-success');
                }
                message.removeClass('hidden');
            });
        });

        $.tablesorter.addParser({
            id: 'kubeTypeParser',
            is: function(s) {
                return false;
            },
            format: function(s, table, cell, cellIndex) {
                return $(cell).data('id');
            },
            type: 'numeric'
        });

        $('#kubes_table').tablesorter({
            sortList: [[0,0]],
            selectorHeaders: 'thead tr.sorted th',
            cssChildRow: 'package_row',
            headers: {
                0 : {
                    sorter: 'kubeTypeParser'
                },
                4: {
                    sorter: false
                }
            }
        });
    });
})(jQuery);