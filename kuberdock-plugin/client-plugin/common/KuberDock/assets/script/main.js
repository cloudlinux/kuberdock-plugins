var KDReq = requirejs.config({
    baseUrl: 'assets/script',
    urlArgs: "bust=" +  (new Date()).getTime(),
    paths: {
        jquery: 'lib/jquery.min',
        underscore: 'lib/underscore-min',
        backbone: 'lib/backbone-min',
        bootstrap: 'lib/bootstrap.min',
        marionette: 'lib/backbone.marionette.min',
        text: 'lib/text',
        tpl: 'lib/underscore-tpl',
        slider: 'lib/slider/jquery.nouislider.all.min',
        bbcode: 'lib/xbbcode'
    },
    shim: {
        jquery: {
            exports: '$'
        },
        underscore: {
            exports: '_'
        },
        backbone: {
            deps: ['jquery', 'underscore'],
            exports: 'Backbone'
        },
        bootstrap: {
            deps: ['jquery']
        },
        marionette: ['backbone'],
        tpl: ['text'],
        bbcode: {
            exports: 'XBBCODE'
        }
    },
    waitSeconds: 30
});

KDReq(['app'], function(App) {
    App.start();
});