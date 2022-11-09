(function (Drupal, once) {
  Drupal.behaviors.myfeature = {
    attach(context) {
      function stripTags(str, allow) {
        allow = (((allow || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');
        let tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
        let commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
        return str.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
          return allow.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
        });
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
        editorElement.setAttribute('contenteditable', true);
        editorElement.setAttribute('class', 'form-element');
        editorElement.innerHTML = inputElement.value;
        inputElement.after(editorElement);
        inputElement.hidden = true;

        // Adding a sync of content with input field.
        editorElement.addEventListener("input", () => {
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
          let editorButtons = document.createElement("div");
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
