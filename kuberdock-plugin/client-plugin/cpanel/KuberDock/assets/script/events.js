var _$ = $.noConflict();

(function($) {
    $(document).ready(function() {
        window.setInterval(function() {
            getPodList();
        }, 120 * 1000);

        $('[data-toggle="tooltip"]').tooltip();
    });

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
    var deletePod = function(pod) {
        var el = $('button.container-delete[data-app="' + pod + '"]'),
            loaderParent = el.parents('tr:eq(0)').length ? el.parents('tr:eq(0)') : el.parents('div:eq(1)'),
            loader = loaderParent.find('.pod.ajax-loader');

        $.ajax({
            type: 'POST',
            url: '?a=deleteContainer',
            data: { container: el.data('app') },
            dataType: 'json',
            beforeSend: function() {
                $('.confirm-modal').modal('hide');
                loader.removeClass('hidden');
            },
            complete: function() {
                loader.addClass('hidden');
            }
        }).done(function(data) {
            displayMessage(data.message);
            if(data.redirect) {
                window.location.href = data.redirect;
            }
        }).error(function(data) {
            displayMessage(data.responseJSON.message);
        });
    };

    var stopPod = function(pod) {
        var el = $('button.container-stop[data-app="' + pod + '"]'),
            loaderParent = el.parents('tr:eq(0)').length ? el.parents('tr:eq(0)') : el.parents('div:eq(1)'),
            loader = loaderParent.find('.pod.ajax-loader');

        $.ajax({
            type: 'POST',
            url: '?a=stopContainer',
            data: { container: el.data('app') },
            dataType: 'json',
            beforeSend: function() {
                $('.confirm-modal').modal('hide');
                loader.removeClass('hidden');
            },
            complete: function() {
                loader.addClass('hidden');
            }
        }).done(function(data) {
            displayMessage(data.message);
            el.toggleClass('container-stop').addClass('container-start').attr('title', 'Start')
                .removeClass('btn-danger').addClass('btn-success');
            el.find('span:eq(0)').removeClass('glyphicon-stop').addClass('glyphicon-play');
            el.find('span:eq(1)').text('Start');
            el.parents('tr').find('td:eq(2)').html('Stopped');
        }).error(function(data) {
            displayMessage(data.responseJSON.message);
        });
    };

    // Popups
    $(document).on('click', '.confirm-modal .btn-action', function(e) {
        switch($(this).data('action')) {
            case 'delete':
                deletePod($(this).data('app'));
                break;
            case 'stop':
                stopPod($(this).data('app'));
                break;
        }
    });

    $(document).on('click', '.container-delete', function(e) {
        $($(this).data('target')).find('.modal-header').html('Do you want to delete application?');
        $($(this).data('target')).find('button.btn-action').text('Delete')
            .data('action', 'delete')
            .data('app', $(this).data('app'));
        $($(this).data('target')).modal('show');
    });

    $(document).on('click', '.container-stop', function(e) {
        $($(this).data('target')).find('.modal-header').html('Do you want to stop application?');
        $($(this).data('target')).find('button.btn-action').text('Stop')
            .data('action', 'stop')
            .data('app', $(this).data('app'));
        $($(this).data('target')).modal('show');
    });

    $(document).on('click', '.container-start', function(e) {
        var el = $(this),
            loaderParent = el.parents('tr:eq(0)').length ? el.parents('tr:eq(0)') : el.parents('div:eq(1)'),
            loader = loaderParent.find('.pod.ajax-loader');

        $.ajax({
            type: 'POST',
            url: '?a=startContainer',
            data: { container: el.data('app') },
            dataType: 'json',
            beforeSend: function() {
                loader.removeClass('hidden');
            },
            complete: function() {
                loader.addClass('hidden');
            }
        }).done(function(data) {
            displayMessage(data.message);
            el.toggleClass('container-start').addClass('container-stop').attr('title', 'Stop')
                .removeClass('btn-success').addClass('btn-danger');
            el.find('span:eq(0)').removeClass('glyphicon-play').addClass('glyphicon-stop');
            el.find('span:eq(1)').text('Stop');
            el.parents('tr').find('td:eq(2)').html('Running');
        }).error(function(data) {
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

    $(document).on('submit', 'form.container-install', function(e) {
        e.preventDefault();
        e.stopPropagation();

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

    $(document).on('click', 'a.show-container-details', function(e) {
        e.preventDefault();
        var details = $(this).parents('tr').next('.container-details');

        details.is(':hidden') ? details.show(400) : details.hide(400);
    });
}(_$));