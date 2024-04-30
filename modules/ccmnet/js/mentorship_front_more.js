var item = document.querySelectorAll('.square-tags');
item.forEach(addCount);
function addCount(item) {
  // count .square-tag li inside index
  var squareTags = item.querySelectorAll('a').length;
  if (squareTags > 5) {
    var squareTags = squareTags - 4;
    var more = "+ " + squareTags + " more";
    item.querySelector('.more_text').innerHTML = more;
  }
}
