function toggleEmail(id) {
  if (document.getElementById(id).checked) {
    document.getElementsByClassName('form-item-new-email')[0].style.visibility = 'visible';
  }
  else document.getElementsByClassName('form-item-new-email')[0].style.visibility = 'hidden';
}
function radioSelected() {
  // If id edit-actions-current-edit-email exists.
  if (document.getElementById('edit-actions-dup-new-email') && document.getElementById('edit-actions-current-edit-email')) {
    if (document.getElementById('edit-actions-current-edit-email').checked) {
      toggleEmail('edit-actions-current-edit-email');
    }
    else if (document.getElementById('edit-actions-dup-new-email').checked) {
      toggleEmail('edit-actions-dup-new-email');
    } else {
      toggleEmail('edit-actions-dup-new-email');
    }
  }
  else if (document.getElementById('edit-actions-current-edit-email')) {
    toggleEmail('edit-actions-current-edit-email');
  }
  else if (document.getElementById('edit-actions-dup-new-email')) {
    toggleEmail('edit-actions-dup-new-email');
  }
}

const radioInput = document.getElementsByClassName('form-radio');
for (var i = 0; i < radioInput.length; i++) {
  radioInput[i].addEventListener('click', radioSelected);
}

radioSelected();
