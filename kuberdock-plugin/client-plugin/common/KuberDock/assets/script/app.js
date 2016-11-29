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
                    'predefined/new/:id/:plan': 'predefinedSetup',
                    'predefined/change_plan/:name': 'predefinedChangePlan'
                }
            });

            if (_.indexOf(['DirectAdmin'], panelType) != -1) {
                Backbone.emulateHTTP = true;
                Backbone.emulateJSON = true;
            }

            Backbone.history.start();

            $.when(Utils.getToken2()).done(function (token2) {
                App.eventHandler(token2);
            });
        });
    });

    App.navigate = function (route, options) {
        options || (options = {});
        if (typeof options.trigger == 'undefined') {
            options.trigger = true;
        }
        Backbone.history.navigate(route, options);
    };

    App.back = function () {
        Backbone.history.history.back();
    };

    App.eventHandler = function (token2) {
        var source = new EventSource(rootURL + '?request=stream/' + token2);

        source.addEventListener('pod:change', function(e) {
            App.sync();
        }, false);

        source.addEventListener('pod:delete', function(e) {
            App.sync();
        }, false);

        source.addEventListener('message', function(e) {
            console.log(e);
        }, false);

        source.addEventListener('error', function(e) {
            console.info('SSE connection lost');
            source.close();
            setTimeout(App.eventHandler(token2), 5000);
        }, false);
    };

    App.sync = function () {
        if (App.Controller.podCollection) {
            App.Controller.podCollection.fetch({reset: true});

            if (App.Controller.predefinedCollection) {
                App.Controller.predefinedCollection.fetch({reset: true});
            }
        }
    };

    return App;
});
