$(function() {
    /*$.fn.modal.getOption = function(name) {
        debugger;
        return this.options[name] || this[name];
    };*/

    $(document).ready(function() {
        window.setInterval(function() {
            getPodList();
        }, 120 * 1000);

        $('[data-toggle="tooltip"]').tooltip();


    });

    var modalTarget = '';

    var displayMessage = function(message) {
        $('.message').html(message);
        // Use timeout to close message
        /*setTimeout(function() {
            $('.alert').alert('close');
        }, 10000);*/
    };

    var getPodList = function(el) {
        if(!$('div.container-content').length) return false;

        var loader = typeof el === 'undefined' ? $('.ajax-loader') :
            el.parents('tr:eq(0)').find('.pod-refresher.ajax-loader');

        $.ajax({
            url: '?a=podList',
            dataType: 'json',
            beforeSend: function() {
                if(el) el.addClass('hidden')
                loader.removeClass('hidden');
            }
        }).done(function(data) {
            if(el) el.removeClass('hidden')
            loader.addClass('hidden');
            $('div.container-content').replaceWith(data.content);
        }).error(function(data) {
            if(el) el.removeClass('hidden')
            loader.addClass('hidden');
            displayMessage(data.responseJSON.message);
        });
    };

    var searchImage = function(el) {
        var loader = el.parents('div.row:eq(0)').find('.ajax-loader');

        $.ajax({
            type: 'GET',
            url: '?a=search',
            data: { search: $('#image').val() },
            beforeSend: function() {
                loader.removeClass('hidden');
            }
        }).done(function(data) {
            loader.addClass('hidden');
            $('.search-content').replaceWith(data);
        });
    };

    // Containers
    $(document).on('click', '.container-delete', function(e) {
        if($(this).data('target')) {
            $($(this).data('target')).modal('show');
            $($(this).data('target')).find('button.container-delete').data('app', $(this).data('app'));
            return;
        }

        var el = $('tr button.container-delete[data-app="' + $(this).data('app') + '"]'),
            loader = el.parents('tr:eq(0)').find('.pod.ajax-loader');

        $.ajax({
            type: 'POST',
            url: '?a=deleteContainer',
            data: { container: el.data('app') },
            dataType: 'json',
            beforeSend: function() {
                $('.confirm-modal').modal('hide');
                loader.removeClass('hidden');
            }
        }).done(function(data) {
            loader.addClass('hidden');
            displayMessage(data.message);
            if(!data.error) {
                el.parents('tr:eq(0)').next('tr.container-details').remove();
                el.parents('tr:eq(0)').remove();
            }
        }).error(function(data) {
            loader.addClass('hidden');
            displayMessage(data.responseJSON.message);
        });
    });

    $(document).on('click', '.container-stop, .container-start', function(e) {
        var el = $(this),
            loader = el.parents('tr:eq(0)').find('.pod.ajax-loader'),
            action = el.hasClass('container-stop') ? 'stopContainer' : 'startContainer';

        $.ajax({
            type: 'POST',
            url: '?a=' + action,
            data: { container: el.attr('app') },
            dataType: 'json',
            beforeSend: function() {
                loader.removeClass('hidden');
            }
        }).done(function(data) {
            displayMessage(data.message);
            if(data.content) {
                $('.container-content').replaceWith(data.content);
            }
        }).error(function(data) {
            loader.addClass('hidden');
            displayMessage(data.responseJSON.message);
        });
    });

    $(document).on('click', '.container-edit', function(e) {
        e.preventDefault();
        var el = $(this),
            loader = el.parents('tr:eq(0)').find('.pod.ajax-loader');

        $.ajax({
            type: 'POST',
            url: '?a=redirect',
            data: { name: $(this).data('app') },
            dataType: 'json',
            beforeSend: function() {
                loader.removeClass('hidden');
            }
        }).done(function(data) {
            loader.addClass('hidden');
            window.open(data.redirect);
        });
    });

    $(document).on('submit', '.container-install', function(e) {
        e.preventDefault();
        var form = $(this),
            loader = $('.ajax-loader');

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: form.serialize(),
            dataType: 'json',
            beforeSend: function() {
                loader.removeClass('hidden');
            }
        }).done(function(data) {
            loader.addClass('hidden');
            displayMessage(data.message);
            if(data.content) {
                $('.container-content').replaceWith(data.content);
            }
            if(data.redirect) {
                window.location.href = data.redirect;
            }
        }).error(function(data) {
            loader.addClass('hidden');
        });
    });

    $(document).on('click', 'button.pod-refresh', function() {
        getPodList($(this));
    });

    // Search
    $(document).on('click', '.image-search', function(e) {
        e.preventDefault();
        searchImage($(this));
    });

    $(document).on('keyup', '#image', function(e) {
        e.preventDefault();

        if(e.which != 13) {
            return false;
        }

        searchImage($(this));
    });

    /*$(document).on('click', 'a.image-more-details', function(e) {
        $(this).parents('tr:eq(0)').find('.info').removeClass('hidden');
        $(this).remove();
    });*/

    $(document).on('click', 'a.show-container-details', function(e) {
        e.preventDefault();
        var details = $(this).parents('tr').next('.container-details');

        details.is(':hidden') ? details.show(400) : details.hide(400);
    });
});