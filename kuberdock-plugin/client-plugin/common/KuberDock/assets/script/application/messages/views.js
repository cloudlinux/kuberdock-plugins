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
                if (this.model.has('message')) {
                    return successTpl;
                } else {
                    return _.template('');
                }
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