rows = document.querySelectorAll('.view-content .col');
for (let i = 0; i < rows.length; i++) {
  const cardFooter = rows[i].querySelector('.card-footer')
  cardFooter.innerHTML = '<button type="button" data-article="' + i + '" class="btn btn-primary more-match">+ More</button>';
}

var more = document.getElementsByClassName("more-match");
var showMore = function() {
    var attribute = this.getAttribute("data-article");
    if (rows[attribute].querySelector('.field--type-text-with-summary').classList.contains('visually-hidden')) {
      var hasSummary = rows[attribute].getElementsByClassName('field--type-text-with-summary');
      if (hasSummary.length > 0) {
        rows[attribute].querySelector('.field--type-text-with-summary').classList.remove("visually-hidden");
      }
      var hasTags = rows[attribute].getElementsByClassName('field--name-field-tags');
      if (hasTags.length > 0) {
        rows[attribute].querySelector('.field--name-field-tags').classList.remove("visually-hidden");
      }
      rows[attribute].querySelector('.more-match').innerHTML = "- Less";
    } else {
      var hasSummary = rows[attribute].getElementsByClassName('field--type-text-with-summary');
      if (hasSummary.length > 0) {
        rows[attribute].querySelector('.field--type-text-with-summary').classList.add("visually-hidden");
      }
      var hasTags = rows[attribute].getElementsByClassName('field--name-field-tags');
      if (hasTags.length > 0) {
        rows[attribute].querySelector('.field--name-field-tags').classList.add("visually-hidden");
      }
      rows[attribute].querySelector('.more-match').innerHTML = "+ More";
    }
};
for (var i = 0; i < more.length; i++) {
    more[i].addEventListener('click', showMore, false);
}
