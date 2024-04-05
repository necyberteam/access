
var item = document.querySelectorAll('.border-orange-hover');
item.forEach(addCount);
function addCount(item) {
  // count .square-tag li inside index
  var squareTags = item.querySelectorAll('li').length;
  if (squareTags > 5) {
    var squareTags = squareTags - 5;
    item.querySelector('.count').innerHTML = "+ " + squareTags + " more";
  }
}
