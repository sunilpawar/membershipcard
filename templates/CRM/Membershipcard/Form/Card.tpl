<div class="crm-block crm-form-block crm-membership-cards-bulk-generate-form-block">

  {if $preview}
    {* Preview Results *}
    <div class="bulk-preview-section">
      <h3><i class="fa fa-eye"></i> {ts}Preview Results{/ts}</h3>

      <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        {ts 1=$membershipCount}Found %1 memberships that will have cards generated.{/ts}
      </div>

      {if $template}
        <div class="template-info">
          <h4>{ts}Template: {$template.name}{/ts}</h4>
          <p>{$template.description}</p>
        </div>
      {/if}

      {if $memberships}
        <div class="preview-table-section">
          <table class="display" id="preview-table">
            <thead>
            <tr>
              <th>{ts}Name{/ts}</th>
              <th>{ts}Email{/ts}</th>
              <th>{ts}Membership Type{/ts}</th>
              <th>{ts}Status{/ts}</th>
              <th>{ts}Start Date{/ts}</th>
              <th>{ts}End Date{/ts}</th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$memberships item=membership}
              <tr>
                <td>
                  <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$membership.contact_id`"}"
                     title="{ts}View Contact{/ts}" target="_blank">
                    {$membership.display_name}
                  </a>
                </td>
                <td>{$membership.email|default:'-'}</td>
                <td>{$membership.membership_type_name}</td>
                <td>
                    <span class="badge {if $membership.membership_status == 'Current'}badge-success{elseif $membership.membership_status == 'New'}badge-info{else}badge-warning{/if}">
                      {$membership.membership_status}
                    </span>
                </td>
                <td>{$membership.start_date|crmDate}</td>
                <td>{$membership.end_date|crmDate}</td>
              </tr>
            {/foreach}
            </tbody>
          </table>
        </div>

        <div class="preview-actions">
          <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            {ts}Click "Generate Cards" to proceed with creating membership cards for the above members.{/ts}
          </div>

          <div class="form-buttons">
            <button type="submit" name="_qf_BulkGenerate_submit_generate" class="btn btn-primary btn-lg">
              <i class="fa fa-refresh"></i> {ts}Generate Cards Now{/ts}
            </button>
            <button type="submit" name="_qf_BulkGenerate_submit_back" class="btn btn-secondary">
              <i class="fa fa-arrow-left"></i> {ts}Back to Criteria{/ts}
            </button>
            <a href="{crmURL p='civicrm/membership-cards'}" class="btn btn-secondary">
              <i class="fa fa-times"></i> {ts}Cancel{/ts}
            </a>
          </div>
        </div>
      {else}
        <div class="empty-results">
          <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            {ts}No memberships found matching your criteria.{/ts}
          </div>
          <a href="javascript:history.back()" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> {ts}Back to Criteria{/ts}
          </a>
        </div>
      {/if}
    </div>

  {else}
    {* Bulk Generate Form *}
    <div class="bulk-generate-form">
      <h3><i class="fa fa-refresh"></i> {ts}Bulk Generate Membership Cards{/ts}</h3>
      <p class="lead">
        {ts}Generate membership cards for multiple members at once using the criteria below.{/ts}
      </p>

      <div class="form-section template-selection">
        <h4><i class="fa fa-file-text"></i> {ts}Template Selection{/ts}</h4>
        <div class="crm-section">
          <div class="label">{$form.template_id.label} <span class="crm-marker">*</span></div>
          <div class="content">{$form.template_id.html}</div>
          <div class="clear"></div>
          <div class="description">
            {ts}Select the card template to use for generating the cards.{/ts}
          </div>
        </div>
      </div>

      <div class="form-section member-criteria">
        <h4><i class="fa fa-filter"></i> {ts}Member Selection Criteria{/ts}</h4>

        <div class="row">
          <div class="col-md-6">
            <div class="crm-section">
              <div class="label">{$form.membership_type_id.label}</div>
              <div class="content">{$form.membership_type_id.html}</div>
              <div class="clear"></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="crm-section">
              <div class="label">{$form.membership_status.label}</div>
              <div class="content">{$form.membership_status.html}</div>
              <div class="clear"></div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="crm-section">
              <div class="label">{$form.start_date.label}</div>
              <div class="content">{$form.start_date.html}</div>
              <div class="clear"></div>
              <div class="description">{ts}Include memberships starting from this date{/ts}</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="crm-section">
              <div class="label">{$form.end_date.label}</div>
              <div class="content">{$form.end_date.html}</div>
              <div class="clear"></div>
              <div class="description">{ts}Include memberships ending up to this date{/ts}</div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="crm-section">
              <div class="label">{$form.limit.label}</div>
              <div class="content">{$form.limit.html}</div>
              <div class="clear"></div>
              <div class="description">{ts}Maximum number of cards to generate (leave empty for no limit){/ts}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-section generation-options">
        <h4><i class="fa fa-cogs"></i> {ts}Generation Options{/ts}</h4>

        <div class="crm-section">
          <div class="label"></div>
          <div class="content">
            {$form.regenerate_existing.html} {$form.regenerate_existing.label}
            <div class="description">
              {ts}Check this to regenerate cards for memberships that already have cards.{/ts}
            </div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section">
          <div class="label"></div>
          <div class="content">
            {$form.email_cards.html} {$form.email_cards.label}
            <div class="description">
              {ts}Automatically email the generated cards to members (only if they have email addresses).{/ts}
            </div>
          </div>
          <div class="clear"></div>
        </div>
      </div>

      <div class="template-preview" id="template-preview" style="display: none;">
        <h4><i class="fa fa-eye"></i> {ts}Template Preview{/ts}</h4>
        <div id="template-preview-content">
          <!-- Preview content will be loaded here -->
        </div>
      </div>
    </div>
  {/if}

  {if !$preview}
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
  {/if}

</div>

{literal}
  <style>
    .bulk-generate-form,
    .bulk-preview-section {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #dee2e6;
    }

    .form-section {
      margin-bottom: 30px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 6px;
      border-left: 4px solid #007bff;
    }

    .form-section h4 {
      color: #495057;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .form-section h4 i {
      margin-right: 8px;
      color: #007bff;
    }

    .template-selection {
      border-left-color: #28a745;
    }

    .member-criteria {
      border-left-color: #17a2b8;
    }

    .generation-options {
      border-left-color: #ffc107;
    }

    .template-info {
      background: #e3f2fd;
      border: 1px solid #bbdefb;
      border-radius: 6px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .template-info h4 {
      color: #1976d2;
      margin-bottom: 5px;
    }

    .preview-table-section {
      margin: 20px 0;
    }

    #preview-table {
      width: 100%;
    }

    .preview-actions {
      text-align: center;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #dee2e6;
    }

    .form-buttons {
      margin-top: 20px;
    }

    .form-buttons .btn {
      margin: 0 5px;
    }

    .empty-results {
      text-align: center;
      padding: 40px 20px;
    }

    .badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .badge-success {
      background: #d4edda;
      color: #155724;
    }

    .badge-info {
      background: #cce7f0;
      color: #055160;
    }

    .badge-warning {
      background: #fff3cd;
      color: #856404;
    }

    .template-preview {
      margin-top: 20px;
      padding: 20px;
      border: 1px solid #dee2e6;
      border-radius: 6px;
    }

    @media (max-width: 768px) {
      .row {
        flex-direction: column;
      }

      .col-md-6 {
        width: 100%;
        margin-bottom: 15px;
      }

      .form-buttons .btn {
        width: 100%;
        margin: 5px 0;
      }
    }
  </style>

<script>
  CRM.$(function($) {
    // Template preview functionality
    $('#template_id').change(function() {
      var templateId = $(this).val();
      if (templateId) {
        loadTemplatePreview(templateId);
        $('#template-preview').show();
      } else {
        $('#template-preview').hide();
      }
    });

    // Initialize DataTable for preview
    if ($('#preview-table').length) {
      $('#preview-table').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "pageLength": 25,
        "order": [[0, "asc"]],
        "columnDefs": [
          { "orderable": false, "targets": [] }
        ],
        "language": {
          "emptyTable": "{/literal}{ts escape='js'}No memberships found{/ts}{literal}"
        }
      });
    }

    function loadTemplatePreview(templateId) {
      $('#template-preview-content').html('<i class="fa fa-spinner fa-spin"></i> {/literal}{ts escape="js"}Loading preview...{/ts}{literal}');

      CRM.api3('MembershipCardTemplate', 'get', {
        id: templateId,
        sequential: 1
      }).done(function(result) {
        if (result.values && result.values[0]) {
          var template = result.values[0];
          var previewHtml = '<div class="template-preview-info">';
          previewHtml += '<h5>' + template.name + '</h5>';
          if (template.description) {
            previewHtml += '<p>' + template.description + '</p>';
          }
          previewHtml += '<p class="text-muted">Dimensions: ' + template.card_width + ' × ' + template.card_height + ' pixels</p>';
          previewHtml += '</div>';

          $('#template-preview-content').html(previewHtml);
        }
      }).fail(function() {
        $('#template-preview-content').html('<div class="text-danger">{/literal}{ts escape="js"}Error loading template preview{/ts}{literal}</div>');
      });
    }

    // Form validation
    $('form[name="BulkGenerate"]').submit(function(e) {
      var templateId = $('#template_id').val();
      if (!templateId) {
        CRM.alert('{/literal}{ts escape="js"}Please select a template{/ts}{literal}', '{/literal}{ts escape="js"}Template Required{/ts}{literal}', 'error');
        e.preventDefault();
        return false;
      }

      // Show loading state
      var submitButton = $(this).find('input[type="submit"]:focus');
      if (submitButton.length) {
        var originalText = submitButton.val();
        submitButton.val('{/literal}{ts escape="js"}Processing...{/ts}{literal}').prop('disabled', true);

        setTimeout(function() {
          if (submitButton.length) {
            submitButton.val(originalText).prop('disabled', false);
          }
        }, 30000); // Re-enable after 30 seconds as failsafe
      }
    });

    // Estimate counts
    function updateEstimate() {
      var formData = $('form[name="BulkGenerate"]').serialize();
      // You could add an AJAX call here to get estimated counts
    }

    $('#membership_type_id, #membership_status').change(updateEstimate);
  });
</script>
{/literal}
