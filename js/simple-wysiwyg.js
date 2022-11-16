(function (Drupal, once) {
  Drupal.behaviors.simpleWysiwyg = {
    attach(context) {
      function stripTags(str, allow) {
        allow = (((allow || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');
        let tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
        let commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
        return str.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
          return allow.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
        });
      }
      function removeRequiredAttribute(inputElement) {
        // A workaround for the issue with hidden required fields
        // see https://stackoverflow.com/a/23215333/1395757
        if(inputElement.hasAttribute('required')) {
          inputElement.removeAttribute('required');
        }
      }
      function syncDisabledAttribute(source, target) {
        if(source.hasAttribute('disabled')) {
          target.setAttribute('disabled', source.getAttribute('disabled'));
        }
        else {
          target.removeAttribute('disabled');
        }

        if (target.hasAttribute('contenteditable')) {
          target.setAttribute('contenteditable', !target.hasAttribute('disabled'));
        }
      }

      const elements = once('simpleWysiwyg', '.simple-wysiwyg', context);
      elements.forEach((inputElement) => {
        const settings = JSON.parse(inputElement.getAttribute('data-simple-wysiwyg-settings'));

        // Workaround for issue https://bugzilla.mozilla.org/show_bug.cgi?id=1615852.
        const isFirefox = navigator.userAgent.includes("Firefox");

        if (settings.allowedTags) {
          if (settings.multiline) {
            settings.allowedTags += '<p>';
          }
          if (isFirefox) {
            settings.allowedTags += '<br>';
          }
        }

        let editorElement = document.createElement("div");
        editorElement.setAttribute('class', 'form-element');
        editorElement.innerHTML = inputElement.value;
        if (inputElement.classList.contains('error')) {
          editorElement.classList.add('error');
        }
        inputElement.after(editorElement);
        if(inputElement.type == 'input') {
          inputElement.hidden = true;
        }
        else {
          inputElement.style.display = 'none';
        }

        removeRequiredAttribute(inputElement);
        syncDisabledAttribute(inputElement, editorElement);

        const observer = new MutationObserver(function(mutations) {
          mutations.forEach(function(mutation) {
            if (mutation.type === "attributes") {
              removeRequiredAttribute(mutation.target);
              syncDisabledAttribute(mutation.target, editorElement);
            }
          });
        });
        observer.observe(inputElement, {attributes: true, attributeFilter: ['disabled', 'required']});

        // Workaround for pages with attached CKEditor, because the
        // `disableAutoInline` config options doesn't work.
        //
        // @see https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR.html#cfg-disableAutoInline
        // @see https://stackoverflow.com/questions/60955366/can-i-disable-ckeditor-initializing-on-elements-with-contenteditable-true
        const contenteditable = !editorElement.hasAttribute('disabled');
        if (typeof(CKEDITOR) !== 'undefined' && !window.ckeditorInstanceReady) {
          CKEDITOR.on('instanceReady', function() {
            editorElement.setAttribute('contenteditable', contenteditable);
            window.ckeditorInstanceReady = true;
          });
        }
        else {
          editorElement.setAttribute('contenteditable', contenteditable);
        }

        inputElement.addEventListener("change", () => {
          editorElement.innerHTML = inputElement.value;
        });

        editorElement.addEventListener("input", (event) => {
          const input = editorElement.innerHTML;
          let filtered = input;
          if (settings['allowedTags']) {
            filtered = stripTags(input, settings['allowedTags']);
          }
          if (settings.maxLength) {
            filtered = filtered.substring(0, settings.maxLength);
          }
          if (isFirefox) {
            // Workaround for issue https://bugzilla.mozilla.org/show_bug.cgi?id=1615852.
            inputElement.value = filtered.replace(/<br\/?>/, '');
          }
          else {
            inputElement.value = filtered;
          }
          // The update of html will lose cursor position, so
          // updating only in case when the filter changes something.
          if(input !== filtered) {
            // @todo Try to keep cursor position.
            editorElement.innerHTML = filtered;
          }
        });

        editorElement.addEventListener('keypress', (event) => {
          if (settings.maxLength && editorElement.innerHTML.length >= settings.maxLength) {
            event.preventDefault();
          }

          if (event.key == 'Enter') {
            if (settings.multiline) {
              document.execCommand('formatBlock', false, 'p');
            }
            else {
              event.preventDefault();
            }
          }
        });

        if(settings.buttons) {
          let editorButtons = document.createElement('div');
          editorButtons.setAttribute('class', 'simple-wysiwyg-buttons');
          editorButtons.hidden = true;
          editorElement.before(editorButtons);

          Object.keys(settings.buttons).forEach((buttonId) => {
            let buttonElement = document.createElement("a");
            buttonElement.innerHTML = settings.buttons[buttonId].button,
            buttonElement.setAttribute('data-command', settings.buttons[buttonId].command);
            buttonElement.setAttribute('title', settings.buttons[buttonId].title);
            buttonElement.setAttribute('href', '#');

            buttonElement.addEventListener('click', function (e) {
              editorElement.focus();
              const command = buttonElement.getAttribute('data-command')
              if (command == 'showSource') {
                // @todo Replace to Drupal Dialog API.
                window.alert(editorElement.innerHTML);
              }
              else {
                // @todo execCommand is deprecated, replace to a modern alternative.
                document.execCommand(command, false, null);
              }
              e.preventDefault();
            });

            editorButtons.appendChild(buttonElement);
          });

          let buttonsTimeout;
          editorElement.addEventListener('focus', () => {
            clearTimeout(buttonsTimeout);
            editorButtons.hidden = false;
          });
          editorElement.addEventListener('blur', () => {
            buttonsTimeout = setTimeout(() => {editorButtons.hidden = true}, 200);
          });
        }
      });
    }
  };
}(Drupal, once));
