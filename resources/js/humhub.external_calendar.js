humhub.module('external_calendar', function (module, require, $) {
    var client = require('client');

    var removeCalendar = function(event) {
        client.post(event);
    }

    module.export({
        removeCalendar: removeCalendar
    });
});
