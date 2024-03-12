// Get all elements with the class "flag-upvote a"
const flagUpvoteElements = document.querySelectorAll('.flag-upvote a');

// Loop through each element and add the class "text-decoration-none"
flagUpvoteElements.forEach(element => {
  element.classList.add('text-decoration-none', 'fw-normal');
});

function copyclip(url, event) {
  navigator.clipboard.writeText(url);

  // Get the button that was clicked.
  var clickedButton = event.currentTarget;

  // Get the span elements within the clicked button.
  var copyDefault = clickedButton.parentElement.parentElement.querySelector('.default-message');
  var copySuccess = clickedButton.parentElement.parentElement.querySelector('.copied-message');

  // Add 'hidden' class to the default message and remove it from the success message.
  copyDefault.classList.add('hidden');
  copySuccess.classList.remove('hidden')
  // After 3 seconds, remove the 'hidden' class from the default message and add it to the success message.
  setTimeout(function() {
    copyDefault.classList.remove('hidden');
    copySuccess.classList.add('hidden');
  }, 3000);
}
