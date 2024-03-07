function bioMore() {
  const bio = document.getElementById('full-bio');
  const summary = document.getElementById('bio-summary');
  // Remove the hidden class
  bio.classList.remove('hidden');
  // Add the hidden class
  summary.classList.add('hidden');
}

function bioLess() {
  const bio = document.getElementById('full-bio');
  const summary = document.getElementById('bio-summary');
  // Remove the hidden class
  bio.classList.add('hidden');
  // Add the hidden class
  summary.classList.remove('hidden');
}
