/* Hide notes box until Coordinator checks the box to send a note */
var notes = document.getElementById("edit-field-notes-to-author-wrapper");
if (notes) {
  notes.classList.add("hide");
}

var checkBoxEmail = document.getElementById("edit-field-email-user-value");
if (checkBoxEmail) {
  checkBoxEmail.onclick = function () {
    notes.classList.toggle("hide");
  };
}

// hide the milestones fieldset header if nothing is inside, which should
// be the case for forms in a non-accepted state
var fieldset = document.getElementById("milestones-fieldset");
var wrapper = fieldset.getElementsByClassName("details-wrapper")[0];
if (wrapper && wrapper.children.length == 0) {
  fieldset.classList.add("hide");
}
