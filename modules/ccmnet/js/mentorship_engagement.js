// Set maxlength of #edit-title-0-value to 65.
document.getElementById('edit-title-0-value').setAttribute('maxlength', '65');

function setMentoree(mentoree) {
  const selection = document.querySelector('input[name="field_me_looking_for"]:checked').value;
  if (selection === 'mentor') {
    document.getElementById('edit-field-mentor-0-target-id').value = '';
    document.getElementById('edit-field-mentee-0-target-id').value = mentoree;
  }
  else if (selection === 'mentee') {
    document.getElementById('edit-field-mentee-0-target-id').value = '';
    document.getElementById('edit-field-mentor-0-target-id').value = mentoree;
  }
}
