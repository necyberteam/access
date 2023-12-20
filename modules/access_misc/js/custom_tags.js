
const buttons = document.getElementsByClassName('tags-select');
const selected = [];

// When button is selected add selected class and check the corresponding checkbox.
const buttonPressed = e => {
  currentButtonTid = e.target.dataset.tid;
  isSelected = e.target.classList.contains("selected");
  if (document.getElementById("edit-tags-" + currentButtonTid).checked) {
    document.getElementById("edit-tags-" + currentButtonTid).checked = false;
    if (isSelected) {
      e.target.classList.remove("selected");
    }
  } else {
    document.getElementById("edit-tags-" + currentButtonTid).checked = true;
    if (!isSelected) {
      e.target.classList.add("selected");
    }
  }
}

for (let button of buttons) {
  button.addEventListener("click", buttonPressed);
}

var checkboxes = document.querySelectorAll('input[type=checkbox]');
// Array to store the values of the selected checkboxes.
var tid_values = [];
// looping through all checkboxes
for (var i = 0; i < checkboxes.length; i++) {
  if (checkboxes[i].checked) {
    tid_values.push(checkboxes[i].value);
  }
}
// Select buttons with the same tid as the selected checkboxes.
for (let tid_value of tid_values ) {
  currentButton = document.querySelectorAll("[data-tid='" + tid_value + "']");
  // If a title is selected then the button won't exist.
  if (currentButton.length > 0) {
    currentButton[0].classList.add("selected");
  }
}
