{literal}
    <script type="text/javascript">
      CRM.$(document).ready(function($) {
        // Handle the AJAX call for generate card
        $(document).on('click', '.crm-membership-generate-card-ajax', function(e) {
          e.preventDefault();

          var membershipId = $(this).data('membership-id');
          var $link = $(this);

          // Disable the link and show loading
          $link.addClass('disabled').find('.fa-id-card').removeClass('fa-id-card').addClass('fa-spinner fa-spin');

          $.ajax({
            url: CRM.url('civicrm/membership/generate-card'),
            type: 'POST',
            data: {
              membership_id: membershipId,
              reset: 1,
              ajax: 1 // Flag to indicate AJAX request
            },
            dataType: 'json',
            success: function(response) {
              // Re-enable the link
              $link.removeClass('disabled').find('.fa-spinner').removeClass('fa-spinner fa-spin').addClass('fa-id-card');

              if (response.success) {
                CRM.alert(response.message || 'Membership card generated successfully', 'Success', 'success');
              } else {
                CRM.alert(response.error || 'Error generating membership card', 'Error', 'error');
              }
            },
            error: function(xhr, status, error) {
              // Re-enable the link
              $link.removeClass('disabled').find('.fa-spinner').removeClass('fa-spinner fa-spin').addClass('fa-id-card');
              CRM.alert('Error generating membership card: ' + error, 'Error', 'error');
            }
          });
        });
      });
    </script>
{/literal}