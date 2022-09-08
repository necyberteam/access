var checkBoxPromo = document.getElementById("edit-promote-value");
var checkBoxEmail = document.getElementById("edit-field-email-user-value");

function checkBoth(chkBoxId, checkBoxWhich) {
  var element = document.getElementById("edit-field-notes-to-author-wrapper");
  if (checkBoxWhich.checked == TRUE){
    document.getElementById(chkBoxId).checked = TRUE;
    element.classList.remove("hide");
  } else{
    element.classList.add("hide");
  }
}

checkBoxPromo.onclick = function () {checkBoth('edit-field-email-user-value', checkBoxPromo);};
checkBoxEmail.onclick = function () {checkBoth('edit-promote-value', checkBoxEmail);};
