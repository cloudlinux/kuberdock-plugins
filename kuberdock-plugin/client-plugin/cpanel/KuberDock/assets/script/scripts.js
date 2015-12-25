var kubeTemplate = _.template('<pre>' +
    'CPU: <%= cpu %><br>' +
    'Memory: <%= memory %><br>' +
    'Local storage: <%= hdd %><br>' +
    'Traffic: <%= traffic %><br>' +
    '<% if(ip) { %>' +
    'Public IP: yes<br>' +
    '<% } %>' +
    '<% if(pd) { %>' +
    'Persistent storage: <%= pd %><br>' +
    '<% } %>' +
'</pre>');

function getFormattedValue(value, unit, decimals) {
    decimals = typeof decimals === 'undefined' ? 2 : decimals;

    return wNumb({
        decimals: decimals,
        prefix: '',
        postfix: ' ' + unit
    }).to(value);
}

$(document).ready(function() {
    // Remove cPanel header. TODO: Temp or not
    $('h1.page-header').remove();
});