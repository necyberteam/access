var checkBoxEmail = document.getElementById("edit-field-email-user-value");
if (checkBoxEmail) {
  checkBoxEmail.onclick = function () {
    notes.classList.toggle("hide");
  };
}

/* Handle tag selection using the node_add_tags view */
function selectElement(id) {
  let element = document.getElementById(id);
  //element.value = valueToSelect;
  Array.from(element.options).forEach(function (option) {

    // If the option's value is in the selected array, select it
    // Otherwise, deselect it
    if (selected.includes(option.value)) {
      option.selected = true;
    } else {
      option.selected = false;
    }

  });
}

const buttons = document.getElementsByClassName('tags-select');
const selected = [];

var options = document.getElementById('edit-field-tags').selectedOptions;
set_tid = Array.from(options).map(({ value }) => value);
if (set_tid.length > 0 && set_tid[0] != "_none") {
  selected.push(...set_tid);
  for (let tid of set_tid) {
    currentButton = document.querySelectorAll("[data-tid='" + tid + "']");
    // If a title is selected then the button won't exist.
    if (currentButton.length > 0) {
      currentButton[0].classList.add("selected");
    }
  }
}
selectElement('edit-field-tags');

const buttonPressed = e => {
  isSelected = e.target.classList.contains("selected");
  if (isSelected) {
    e.target.classList.remove("selected");
    const index = selected.indexOf(e.target.dataset.tid);
    if (index > -1) { // only splice array when item is found
      selected.splice(index, 1); // 2nd parameter means remove one item only
    }
  } else {
    e.target.classList.add("selected");
    selected.push(e.target.dataset.tid);
  }
  selectElement('edit-field-tags')
}

for (let button of buttons) {
  button.addEventListener("click", buttonPressed);
}


// Re-enable form elements that are disabled for non-ajax situations.
Drupal.behaviors.nodeAddTags = {
  attach: function () {
    // If ajax is enabled, we want to hide items that are marked as hidden in
    // our example.
    if (Drupal.ajax) {
      if (set_tid.length > 0 && set_tid[0] == "_none") {
        var tagWrapper = document.getElementById("field-tags-replace");
        var suggested_tids = tagWrapper.getAttribute('data-suggest').split(', ');

        if (suggested_tids != 0) {
          var selectElement = document.getElementById("edit-field-tags");
          var option684 = selectElement.querySelector('option[value="684"]');
          option684.selected = true;

          Array.from(suggested_tids).forEach(function (suggested_tid) {
            if (selectElement.querySelector('option[value="' + suggested_tid + '"]').selected != true) {
              selectElement.querySelector('option[value="' + suggested_tid + '"]').selected = true;
              console.log(suggested_tid);
            }
          });
        }

      }
    }
  }
};

