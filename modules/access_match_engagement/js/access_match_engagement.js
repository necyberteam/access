var checkBoxPromo = document.getElementById("edit-promote-value");
var checkBoxEmail = document.getElementById("edit-field-email-user-value");

function checkBoth(chkBoxId, checkBoxWhich) {
  if (checkBoxWhich.checked == true){
    console.log('checked');
    document.getElementById(chkBoxId).checked = true;
  }
}

checkBoxPromo.onclick = function(){checkBoth('edit-field-email-user-value', checkBoxPromo);};
checkBoxEmail.onclick = function(){checkBoth('edit-promote-value', checkBoxEmail);};
