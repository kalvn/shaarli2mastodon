(function () {

var linkForms = document.querySelectorAll('[name="linkform"]');

linkForms.forEach(function (linkForm) {
  var privateInput = linkForm.querySelector('[name="lf_private"]');

  var tootInput = linkForm.querySelector('[name="toot"]');
  var tootButton = linkForm.querySelector('.toot-button');
  var tootConfigure = linkForm.querySelector('.toot-configure');
  var reactiveFields = linkForm.querySelectorAll('input, textarea');

  // Disables mastodon publication if private flag is selected.
  privateInput.addEventListener('click', function (event) {
    if (!tootInput) {
      return;
    }

    tootInput.disabled = privateInput.checked;
  });

  // Toggles toot configuration panel.
  tootButton.addEventListener('click', function (event) {
    renderPreview(linkForm);
    tootConfigure.classList.toggle('toot-hidden');
  });

  // Updates preview when something changes.
  var timeout;
  ['change', 'keyup'].forEach(function (event) {
    reactiveFields.forEach(function (field) {
      field.addEventListener(event, function () {
        clearTimeout(timeout);
        timeout = setTimeout(function () {
          renderPreview(linkForm);
        }, 500);
      });
    });
  });
});

var placeholders = [
  'url',
  'permalink',
  'title',
  'tags',
  'description',
  'cw'
];

function renderPreview (linkForm) {
  var link = {
    'url': linkForm.querySelector('[name="lf_url"]').value,
    'permalink': '<permalink>',
    'title': linkForm.querySelector('[name="lf_title"]').value,
    'description': linkForm.querySelector('[name="lf_description"]').value,
    'tags': linkForm.querySelector('[name="lf_tags"]').value,
    'cw': ' '
  };

  var format = linkForm.querySelector('[name="toot-format"]').value;
  var maxLength = linkForm.querySelector('.toot-parameter-max-length').innerText || 500;
  var tagsSeparator = linkForm.querySelector('.toot-parameter-tags-separator').innerText;

  link['tags'] = tagify(link['tags'], tagsSeparator);
  link['description'] = link['description'].replace(/\n/g, '\\n');

  var output = format;
  for (i in placeholders) {
    var placeholder = placeholders[i];

    output = output.replace(new RegExp('\\$\\{' + placeholder + '\\}', 'g'), escapeHtml(link[placeholder]));
  }

  // output = output.replace(/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)/g, function (match, $1) {
  //   var url = $1.replace(/^http[s]?:\/\//, '');
  //   if (url.length > 30) {
  //     url = url.substring(0, 30) + '&hellip;';
  //   }
  //   return '<span class="toot-url">' + url + '</span>';
  // });
  // output = output.replace(/(#[\d\w_]+)/g, '<span class="toot-tag">$1</span>');
  output = output.replace(/\\n/g, '<br>');

  linkForm.querySelector('.toot-preview').innerHTML = output;
}

function tagify (tags, tagsSeparator) {
  var parts = tags.trim().split(tagsSeparator);
  var output = [];

  for (i in parts) {
    output.push('#' + parts[i].replace(/[^0-9_\p{L}]/gu, ''));
  }

  return output.join(' ');
}

function escapeHtml (text) {
  return text.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '"');
}

})();
