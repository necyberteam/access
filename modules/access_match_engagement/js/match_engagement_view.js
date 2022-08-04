articles = document.querySelectorAll('article');
for (let i = 0; i < articles.length; i++) { 
  articles[i].insertAdjacentHTML('beforeend', '<button type="button" data-article="' + i + '" class="btn btn-primary ml-0 mt-3 more-match">+ More</button>');
}

var more = document.getElementsByClassName("more-match");
var showMore = function() {
    var attribute = this.getAttribute("data-article");
    if (articles[attribute].querySelector('.field--type-text-with-summary').classList.contains('visually-hidden')) {
      var hasSummary = articles[attribute].getElementsByClassName('field--type-text-with-summary');
      if (hasSummary.length > 0) {
        articles[attribute].querySelector('.field--type-text-with-summary').classList.remove("visually-hidden");
      }
      var hasTags = articles[attribute].getElementsByClassName('field--name-field-tags');
      if (hasTags.length > 0) {
        articles[attribute].querySelector('.field--name-field-tags').classList.remove("visually-hidden");
      }
      articles[attribute].querySelector('.more-match').innerHTML = "- Less";
    } else {
      var hasSummary = articles[attribute].getElementsByClassName('field--type-text-with-summary');
      if (hasSummary.length > 0) {
        articles[attribute].querySelector('.field--type-text-with-summary').classList.add("visually-hidden");
      }
      var hasTags = articles[attribute].getElementsByClassName('field--name-field-tags');
      if (hasTags.length > 0) {
        articles[attribute].querySelector('.field--name-field-tags').classList.add("visually-hidden");
      }
      articles[attribute].querySelector('.more-match').innerHTML = "+ More";
    }
};
for (var i = 0; i < more.length; i++) {
    more[i].addEventListener('click', showMore, false);
}
