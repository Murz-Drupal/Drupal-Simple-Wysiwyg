(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.myfeature = {
    attach(context) {
      function stripTags(str, allow) {
        // making sure the allow arg is a string containing only tags in lowercase (<a><b><c>)
        allow = (((allow || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');

        var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
        var commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
        return str.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
          return allow.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
        });
      }

      const elements = once('simpleWysiwyg', 'div.simple-wysiwyg-editor', context);
      elements.forEach((editorElement) => {
        // Finding the related elements.
        const formElement = editorElement.parentElement;
        const inputElement = formElement.getElementsByTagName('input')[0];
        // inputElement.hidden = true;
        const settings = JSON.parse(editorElement.getAttribute('data-simple-wysiwyg-settings'));
        const buttonsBlock = formElement.getElementsByClassName('simple-wysiwyg-buttons')[0];
        buttonsBlock.hidden = true;
        // Adding a sync of content with input field.
        editorElement.addEventListener("input", (el) => {
          if(settings['allowed_tags']) {
            const input = editorElement.innerHTML;
            const filtered = stripTags(input, settings['allowed_tags']);
            inputElement.value = filtered;
            // The update of html will lose cursor position, so
            // updating only in case when the filter changes something.
            if(input !== filtered) {
              editorElement.innerHTML = filtered;
            }
          }
          else {
            inputElement.value = editorElement.innerHTML;
          }
        });
        editorElement.addEventListener('keypress', (event) => {
          if (event.which === 13) {
            if (settings?.multiline == true) {
              document.execCommand('formatBlock', false, 'p');
              // document.execCommand('insertParagraph');
              // event.preventDefault();
            }
            else {
              event.preventDefault();
            }
          }
        });
        let buttonsTimeout;
        editorElement.addEventListener('focus', () => {
          clearTimeout(buttonsTimeout);
          buttonsBlock.hidden = false;
        });
        editorElement.addEventListener('blur', () => {
          buttonsTimeout = setTimeout(() => {buttonsBlock.hidden = true}, 200);
        });

        const buttons = buttonsBlock.getElementsByTagName('a');
        const buttonsList = Array.prototype.slice.call(buttons); // copies elements, creates array

        buttonsList.forEach((buttonElement) => {
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
        });
      });
    }
  };
}(Drupal, drupalSettings, once));