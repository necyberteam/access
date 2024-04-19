function bioMore() {
  const bio = document.getElementById('full-bio');
  const summary = document.getElementById('bio-summary');
  // Remove the hidden class
  bio.classList.remove('sr-only');
  // Add the hidden class
  summary.classList.add('hidden');
}

function bioLess() {
  const bio = document.getElementById('full-bio');
  const summary = document.getElementById('bio-summary');
  // Remove the hidden class
  bio.classList.add('sr-only');
  // Add the hidden class
  summary.classList.remove('hidden');
}

// Add the button to the bio summary on user profile for campus champions.
setTimeout(function () {
  const summaryElement = document.querySelector('.user-profile-view #bio-summary.more');
  summaryElement.innerHTML += "<button id='bio-more' onclick='bioMore()' style='border-width: 0 !important;' class='btn btn-primary p-3'aria-hidden='TRUE' type='button'><i class='fa-solid fa-chevron-down'></i> More</button>";
  const fullBioElement = document.querySelector('.user-profile-view #full-bio');
  fullBioElement.innerHTML += "<button id='bio-less' onclick='bioLess()' style='border-width: 0 !important;' class='btn btn-primary p-3' aria-hidden='TRUE' type='button'><i class='fa-solid fa-chevron-up'></i> Less</button>";
}, 500);
