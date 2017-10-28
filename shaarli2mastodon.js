var privateInput = document.getElementsByName('lf_private')[0];
var tootInput = document.getElementsByName('toot')[0];

privateInput.addEventListener('click', function(event) {
    tootInput.disabled = privateInput.checked;
});