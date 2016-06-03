define(['backbone', 'marionette', 'application/utils', 'application/messages/model', 'application/messages/views'],
function(Backbone, Marionette, Utils, MessageModel, MessageView) {
    'use strict';

    var App = new Backbone.Marionette.Application({
        regions: {
            contents: '#contents',
            message: '#message_content'
        },

        initialize: function () {
            var _that = this;

            $.ajaxSetup({
                mimeType: 'text/plain'
            });

            $(document).ajaxStart(function(e) {
                $('#page-preloader').show();
            });

            $(document).ajaxStop(function(e, response) {
                $('#page-preloader').hide();
            });

            $(document).ajaxSuccess(function(e, response) {
                var json = response.responseJSON;
                _that.message.show(new MessageView.View({
                    model: new MessageModel.Model(json)
                }));
            });

            $(document).ajaxError(function(e, response) {
                var json = response.responseJSON;
                _that.message.show(new MessageView.View({
                    model: new MessageModel.Model(json)
                }));
            });
        }
    });

    App.on('start', function() {
        KDReq(['application/controller'], function (Controller) {
            App.Controller = new Controller();
            App.Router = new Backbone.Marionette.AppRouter({
                controller: App.Controller,
                appRoutes: {
                    '': 'podList',
                    'pod/image/search': 'podSearch',
                    'pod/upgrade/:name': 'podUpgrade',
                    'pod/create/:name1': 'podCreate',
                    'pod/create/:name1/:name2': 'podCreate',
                    'pod/:name': 'podDetails',
                    'pod/:name/:description': 'podDetails',
                    'predefined/:id': 'processPredefined',
                    'predefined/new/:id': 'predefinedCreate',
                    'predefined/new/:id/:plan': 'predefinedSetup'
                }
            });

            if (_.indexOf(['DirectAdmin'], panelType) != -1) {
                Backbone.emulateHTTP = true;
                Backbone.emulateJSON = true;
            }

            Backbone.history.start();
            App.eventHandler();
        });
    });

    App.navigate = function (route, options) {
        options || (options = {});
        if (typeof options.trigger == 'undefined') {
            options.trigger = true;
        }
        Backbone.history.navigate(route, options);
    };

    App.eventHandler = function () {
        var source = new EventSource(rootURL + '?request=stream');

        source.addEventListener('pod:change', function(e) {
            App.sync();
        }, false);

        source.addEventListener('pod:delete', function(e) {
            App.sync('delete');
        }, false);

        source.addEventListener('message', function(e) {
            console.log(e);
        }, false);

        source.addEventListener('error', function(e) {
            console.info('SSE connection lost');
            source.close();
            setTimeout(App.eventHandler, 5000);
        }, false);
    };

    App.sync = function (type) {
        if (App.Controller.pod && type != 'delete') {
            App.Controller.pod.fetch();
        } else if (App.Controller.podCollection) {
            App.Controller.podCollection.fetch({reset: true});
        }
    };

    return App;
});
