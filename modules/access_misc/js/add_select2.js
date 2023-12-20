(function ($, Drupal) {
  $(document).ready(function () {
    // Alphabetize the options in the affected resources list.
    $("#edit-field-affected-infrastructure-target-id").html($("#edit-field-affected-infrastructure-target-id option").sort(function (a, b) {
      if (b.text != 'All') {
        return a.text == b.text ? 0 : a.text < b.text ? -1 : 1
      }
    }))
    $("#edit-field-affected-infrastructure-target-id").select2({});
  });
})(jQuery, Drupal);
