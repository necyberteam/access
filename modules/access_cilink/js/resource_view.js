// Select all <a> elements inside elements with class '.cilinks-tags'
const cilinksTags = document.querySelectorAll('.cilinks-tags a');

// Loop through the selected elements and add the 'btn' class
cilinksTags.forEach(function (element) {
  element.classList.add('d-inline-flex', 'flag-tag', 'bg-white', 'border', 'border-black', 'm-1', 'p-1', 'text-decoration-none');
});

// Select all <li> elements inside elements with class '.cilinks-buttons'
const cilinksLi = document.querySelectorAll('.cilinks-buttons li');

// Loop through the selected elements and add the 'btn' class
cilinksLi.forEach(function (element) {
  element.classList.add('p-0', 'my-1', "mx-0");
});

// Select <ul> element inside elements with class '.cilinks-buttons'
const cilinksUl = document.querySelector('.cilinks-buttons ul');
cilinksUl.classList.add('list-group', 'list-unstyled');

// Select all <a> elements inside elements with class '.cilinks-buttons'
const cilinksButtons = document.querySelectorAll('.cilinks-buttons a');

// Loop through the selected elements and add the 'btn' class
cilinksButtons.forEach(function (element) {
  element.classList.add('btn', 'btn-outline-dark', 'btn-sm', 'py-1', 'px-2', 'm-0');
});

// Get all elements with the class "flag-upvote a"
const flagUpvoteElements = document.querySelectorAll('.flag-upvote a');

// Loop through each element and add the class "text-decoration-none"
flagUpvoteElements.forEach(element => {
  element.classList.add('d-flex', 'align-items-center', 'text-decoration-none', 'fw-normal');
});
