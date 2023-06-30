var notes = document.getElementById("edit-field-notes-to-author-wrapper");
notes.classList.add("hide");

var checkBoxEmail = document.getElementById("edit-field-email-user-value");
checkBoxEmail.onclick = function () {
  notes.classList.toggle("hide");
};

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
