
var item = document.querySelectorAll('.cssn-directory-item');
item.forEach(addCount);
function addCount(item) {
  // count .square-tag li inside index
  var squareTags = item.querySelectorAll('li').length;
  if (squareTags > 5) {
    var squareTags = squareTags - 5;
    var more = "+ " + squareTags + " more";
    item.querySelector('li:last-child').innerHTML = more;
    item.querySelector('li:last-child').style.display = "inline-block";
  }
}
