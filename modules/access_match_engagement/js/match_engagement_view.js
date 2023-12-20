const more = document.getElementsByClassName("more-match");
const showMore = function () {
    const attribute = this.getAttribute("data-article");
    const row = rows[attribute]
    if (row.querySelector('.field--type-text-with-summary').classList.contains('visually-hidden')) {
      var hasSummary = row.getElementsByClassName('field--type-text-with-summary');
      if (hasSummary.length > 0) {
        row.querySelector('.field--type-text-with-summary').classList.remove("visually-hidden");
      }
      var hasTags = row.getElementsByClassName('field--name-field-tags');
      if (hasTags.length > 0) {
        row.querySelector('.field--name-field-tags').classList.remove("visually-hidden");
      }
      row.querySelector('.more-match span').innerHTML = "- Less";
    } else {
      var hasSummary = row.getElementsByClassName('field--type-text-with-summary');
      if (hasSummary.length > 0) {
        row.querySelector('.field--type-text-with-summary').classList.add("visually-hidden");
      }
      var hasTags = row.getElementsByClassName('field--name-field-tags');
      if (hasTags.length > 0) {
        row.querySelector('.field--name-field-tags').classList.add("visually-hidden");
      }
      row.querySelector('.more-match span').innerHTML = "+ More";
    }
};

rows = document.querySelectorAll('.view-content .col');
for (let i = 0; i < rows.length; i++) {
  const cardFooter = rows[i].querySelector('.card-footer')
  cardFooter.classList.add('more-match')
  cardFooter.dataset.article = i
  cardFooter.innerHTML = '<span>+ More</span>';
  cardFooter.addEventListener('click', showMore, false);
}
