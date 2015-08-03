var kubeTemplate = _.template('<pre>' +
    'CPU: <%= cpu %><br>' +
    'Memory: <%= memory %><br>' +
    'Traffic: <%= traffic %>' +
'</pre>');

function getFormattedValue(value, unit, decimals) {
    decimals = typeof decimals === 'undefined' ? 2 : decimals;

    return wNumb({
        decimals: decimals,
        prefix: '',
        postfix: ' ' + unit
    }).to(value);
}