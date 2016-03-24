define(['app', 'application/utils',
    'tpl!application/messages/view/error.tpl',
    'tpl!application/messages/view/success.tpl'
], function(App, Utils, errorTpl, successTpl) {
    'use strict';

    var MessageView = {};

    MessageView.View = Backbone.Marionette.ItemView.extend({
        getTemplate: function () {
            if(this.model.get('status') == 'ERROR') {
                return errorTpl;
            } else {
                var data = this.model.get('data');
                return _.isArray(data) || _.isObject(data) || !data
                    ? _.template('') : successTpl;
            }
        },

        initialize: function () {
            if(this.model.get('redirect')) {
                window.location.replace(this.model.get('redirect'));
            }
        }
    });

    return MessageView;
});