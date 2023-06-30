var notes = document.getElementById("edit-field-notes-to-author-wrapper");
notes.classList.add("hide");

var checkBoxEmail = document.getElementById("edit-field-email-user-value");
checkBoxEmail.onclick = function () {
  notes.classList.toggle("hide");
};
