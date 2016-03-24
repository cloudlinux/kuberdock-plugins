define(['backbone'], function(Backbone) {
    'use strict';

    var Message = {};

    Message.Model = Backbone.Model.extend({
        defaults: function () {
            return {};
        }
    });

    return Message;
});