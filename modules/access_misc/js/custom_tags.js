Drupal.behaviors.nodeAddTags = {
  attach: function () {
    var tid_values = [];

    if (Drupal.ajax) {

      var tagWrapper = document.getElementById("field-tags-replace");
      var suggested_tids = tagWrapper.getAttribute('data-suggest').split(', ');
      var selectElementTags = document.getElementById("edit-tags--wrapper");

     if (suggested_tids != 0) {
       Array.from(suggested_tids).forEach(function (suggested_tid) {
         if (selectElementTags.querySelector('input[type=checkbox][value="' + suggested_tid + '"]').checked != true) {
           // selectElementTags.querySelector('option[value="' + suggested_tid + '"]').selected = true;
           tid_values.push(suggested_tid);
           selectElementTags.querySelector('input[type=checkbox][value="' + suggested_tid +'"]').checked = true;
         }
       });
      addToList();
     }
    }




    /* Show liste of selected tags */
    function addToList() {
        // Select all elements with the 'selected' class
        const selectedElements = document.querySelectorAll('.tags-select.selected');
        // Initialize an array to store the text content
        const textArray = [];
        // Loop through each selected element and add its text content to the array
        selectedElements.forEach(element => {
          textArray.push(element.textContent.trim());
        });
        if (textArray.length > 0) {
          var divElement = document.getElementById('tag-suggstions');
          var textTagListing = '';
          for (let textArrayItem of textArray) {
            textTagListing = textTagListing + '<a class="font-normal text-sky-900" href="#tag-' + textArrayItem + '">' + textArrayItem  + '</a>, '
          }
          // Replace the text content
          divElement.innerHTML = '<div class="bg-slate-100 p-5 my-5"><strong>Selected Tags:</strong><br/>' + textTagListing + '</div>';
        }
    }




    const buttons = document.getElementsByClassName('tags-select');
    // const selected = [];

    // When button is selected add selected class and check the corresponding checkbox.
    const buttonPressed = e => {
      currentButtonTid = e.target.dataset.tid;
      isSelected = e.target.classList.contains("selected");
      if (document.getElementById("edit-tags-" + currentButtonTid).checked) {
        document.getElementById("edit-tags-" + currentButtonTid).checked = false;
        if (isSelected) {
          e.target.classList.remove("selected");
        }
      } else {
        document.getElementById("edit-tags-" + currentButtonTid).checked = true;
        if (!isSelected) {
          e.target.classList.add("selected");
        }
      }
      addToList();
    }

    document.addEventListener('DOMContentLoaded', function () {
      for(let button of buttons) {
        button.addEventListener("click", buttonPressed, false);
      }
    });

    var checkboxes = document.querySelectorAll('input[type=checkbox]');
      // Array to store the values of the selected checkboxes.
      // looping through all checkboxes
      for(var i = 0; i<checkboxes.length; i++) {
      if (checkboxes[i].checked) {
        tid_values.push(checkboxes[i].value);
      }
    }
    // Select buttons with the same tid as the selected checkboxes.
    for (let tid_value of tid_values) {
      currentButton = document.querySelectorAll("[data-tid='" + tid_value + "']");
      // If a title is selected then the button won't exist.
      if (currentButton.length > 0) {
        currentButton[0].classList.add("selected");
      }
    }

    addToList();


  }
};
