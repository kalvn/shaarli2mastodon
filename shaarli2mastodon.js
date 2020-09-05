(function () {

var privateInput = document.getElementsByName('lf_private')[0];
var tootInput = document.getElementsByName('toot')[0];

privateInput.addEventListener('click', function(event) {
  if (!tootInput) {
    return;
  }

  tootInput.disabled = privateInput.checked;
});

})();
