// Remove em from title.
let h1 = document. querySelector('h1');
h1.textContent = h1.textContent;
// rewrite h1 without the <em> tag.
h1.innerHTML = h1.textContent;

// Rename Body to Description.
let body = document.querySelector('[for="edit-body-0-value"]');
body.textContent = body.textContent;
body.innerHTML = 'Description';

// Rename label to Single Event.
let single = document.querySelector('[for="edit-recur-type-custom"]');
single.textContent = single.textContent;
single.innerHTML = 'Single Event';

// Select 'single event' radio button if no radio button is checked.
const radioButtons = document.querySelectorAll('input[name="recur_type"]');
radioSelect = 1;
for (const radioButton of radioButtons) {
  // check if the radio button is checked
  if (radioButton.checked) {
    radioSelect = 0;
    break;
  }
}
if (radioSelect) {
  document.getElementById("edit-recur-type-custom").checked = true;
}

(function ($, Drupal, once) {
  "use strict";
  Drupal.behaviors.nodeAddTags = {
    attach: function (context, settings) {
        // Select all buttons value="Remove" and change to "Delete".
        let buttons = document.querySelectorAll('input[value="Remove"]');
        for (let i = 0; i < buttons.length; i++) {
          buttons[i].value = 'X';
        }
    }
  };
})(jQuery, Drupal, once);
