{* templates/CRM/Membershipcard/Form/CardTemplates.tpl *}

<div class="crm-block crm-form-block crm-membership-card-templates-form-block">

  {if $action eq 'browse' or $action eq 'add' or $action eq 'update'}

    {if $action eq 'browse'}
      {* Template listing and management form *}
      <div class="crm-content-block">

        <div class="crm-submit-buttons">
          <a href="{crmURL p='civicrm/membership-card-templates' q='action=add'}" class="btn btn-primary">
            <i class="fa fa-plus"></i> {ts}Add New Template{/ts}
          </a>

          {if $templates}
            <div class="btn-group">
              <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-cog"></i> {ts}Bulk Actions{/ts} <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" onclick="bulkAction('activate')">{ts}Activate Selected{/ts}</a></li>
                <li><a href="#" onclick="bulkAction('deactivate')">{ts}Deactivate Selected{/ts}</a></li>
                <li><a href="#" onclick="bulkAction('export')">{ts}Export Selected{/ts}</a></li>
                <li role="separator" class="divider"></li>
                <li><a href="#" onclick="bulkAction('delete')" class="text-danger">{ts}Delete Selected{/ts}</a></li>
              </ul>
            </div>

            <a href="{crmURL p='civicrm/membership-card-template/import'}" class="btn btn-info">
              <i class="fa fa-upload"></i> {ts}Import Template{/ts}
            </a>
          {/if}
        </div>

        {if $templates}
          <form method="post" id="bulk-action-form">
            <div class="crm-results-block">
              <table class="selector row-highlight" id="templates-table">
                <thead>
                <tr>
                  <th class="hiddenElement">
                    <input type="checkbox" id="select-all-templates" />
                  </th>
                  <th>{ts}Name{/ts}</th>
                  <th>{ts}Description{/ts}</th>
                  <th>{ts}Dimensions{/ts}</th>
                  <th>{ts}Status{/ts}</th>
                  <th>{ts}Cards Generated{/ts}</th>
                  <th>{ts}Created{/ts}</th>
                  <th>{ts}Modified{/ts}</th>
                  <th>{ts}Actions{/ts}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$templates item=template}
                  <tr id="template-{$template.id}" class="{cycle values="odd-row,even-row"}">
                    <td class="hiddenElement">
                      <input type="checkbox" name="template_ids[]" value="{$template.id}" class="template-checkbox" />
                    </td>
                    <td class="crm-template-name">
                      <div class="template-name-wrapper">
                        <strong>
                          <a href="{crmURL p='civicrm/membership-card-templates' q="action=update&id=`$template.id`"}"
                             title="{ts}Edit Template{/ts}">
                            {$template.name}
                          </a>
                        </strong>
                        {if $template.is_default}
                          <span class="label label-info">{ts}Default{/ts}</span>
                        {/if}
                        {if $template.is_shared}
                          <span class="label label-warning">{ts}Shared{/ts}</span>
                        {/if}
                      </div>
                    </td>
                    <td class="crm-template-description">
                      <div class="template-description">
                        {$template.description|truncate:80:"...":true}
                        {if $template.description|count_characters > 80}
                          <a href="#" class="show-full-description" data-description="{$template.description|escape}">
                            {ts}more{/ts}
                          </a>
                        {/if}
                      </div>
                    </td>
                    <td class="crm-template-dimensions">
                        <span class="dimensions-info">
                          {$template.card_width} × {$template.card_height}px
                        </span>
                      <div class="dimension-preview">
                        <div class="card-size-indicator"
                             style="width: {math equation="x/10" x=$template.card_width}px;
                               height: {math equation="x/10" x=$template.card_height}px;
                               background: {$template.background_color|default:'#f0f0f0'};
                               border: 1px solid #ccc;
                               display: inline-block;
                               vertical-align: middle;
                               margin-left: 5px;">
                        </div>
                      </div>
                    </td>
                    <td class="crm-template-status">
                      {if $template.is_active}
                        <span class="label label-success">{ts}Active{/ts}</span>
                      {else}
                        <span class="label label-default">{ts}Inactive{/ts}</span>
                      {/if}
                    </td>
                    <td class="crm-template-usage">
                      <div class="usage-stats">
                        <span class="card-count">{$template.cards_generated|default:0}</span>
                        {if $template.cards_generated > 0}
                          <a href="{crmURL p='civicrm/membership-cards' q="template_id=`$template.id`"}"
                             class="view-cards-link" title="{ts}View Generated Cards{/ts}">
                            <i class="fa fa-external-link"></i>
                          </a>
                        {/if}
                      </div>
                    </td>
                    <td class="crm-template-created">
                        <span title="{$template.created_date|crmDate:'%B %d, %Y at %l:%M %p'}">
                          {$template.created_date|crmDate:'%m/%d/%Y'}
                        </span>
                      {if $template.created_by_name}
                        <div class="created-by">
                          <small class="text-muted">by {$template.created_by_name}</small>
                        </div>
                      {/if}
                    </td>
                    <td class="crm-template-modified">
                      {if $template.modified_date}
                        <span title="{$template.modified_date|crmDate:'%B %d, %Y at %l:%M %p'}">
                            {$template.modified_date|crmDate:'%m/%d/%Y'}
                          </span>
                      {else}
                        <span class="text-muted">—</span>
                      {/if}
                    </td>
                    <td class="crm-template-actions">
                      <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
                          {ts}Actions{/ts} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                          <li>
                            <a href="{crmURL p='civicrm/membership-card-templates' q="action=update&id=`$template.id`"}">
                              <i class="fa fa-pencil"></i> {ts}Edit{/ts}
                            </a>
                          </li>
                          <li>
                            <a href="{crmURL p='civicrm/membership-card-template/preview' q="id=`$template.id`"}">
                              <i class="fa fa-eye"></i> {ts}Preview{/ts}
                            </a>
                          </li>
                          <li>
                            <a href="{crmURL p='civicrm/membership-card-templates' q="action=copy&id=`$template.id`"}">
                              <i class="fa fa-copy"></i> {ts}Duplicate{/ts}
                            </a>
                          </li>
                          <li role="separator" class="divider"></li>
                          <li>
                            <a href="{crmURL p='civicrm/membership-card-template/export' q="id=`$template.id`"}">
                              <i class="fa fa-download"></i> {ts}Export{/ts}
                            </a>
                          </li>
                          {if $template.cards_generated > 0}
                            <li>
                              <a href="{crmURL p='civicrm/membership-cards/bulk-generate' q="template_id=`$template.id`"}">
                                <i class="fa fa-refresh"></i> {ts}Regenerate Cards{/ts}
                              </a>
                            </li>
                          {/if}
                          <li role="separator" class="divider"></li>
                          {if $template.is_active}
                            <li>
                              <a href="#" onclick="toggleTemplateStatus({$template.id}, 0)">
                                <i class="fa fa-pause"></i> {ts}Deactivate{/ts}
                              </a>
                            </li>
                          {else}
                            <li>
                              <a href="#" onclick="toggleTemplateStatus({$template.id}, 1)">
                                <i class="fa fa-play"></i> {ts}Activate{/ts}
                              </a>
                            </li>
                          {/if}
                          <li role="separator" class="divider"></li>
                          <li>
                            <a href="#" onclick="deleteTemplate({$template.id}, '{$template.name|escape:"javascript"}')"
                               class="text-danger">
                              <i class="fa fa-trash"></i> {ts}Delete{/ts}
                            </a>
                          </li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                {/foreach}
                </tbody>
              </table>
            </div>
          </form>

          {* Pagination *}
          {if $pager}
            <div class="crm-pager">
              {include file="CRM/common/pager.tpl" location="bottom"}
            </div>
          {/if}

        {else}
          <div class="messages status no-popup">
            <div class="icon inform-icon"></div>
            <p>
              {ts}No membership card templates found.{/ts}
              <a href="{crmURL p='civicrm/membership-card-templates' q='action=add'}">
                {ts}Create your first template{/ts}
              </a>
              {ts}to get started with membership cards.{/ts}
            </p>
          </div>

          <div class="template-getting-started">
            <h3>{ts}Getting Started with Membership Cards{/ts}</h3>
            <div class="row">
              <div class="col-md-4">
                <div class="getting-started-step">
                  <div class="step-number">1</div>
                  <h4>{ts}Create Template{/ts}</h4>
                  <p>{ts}Design your membership card with our drag-and-drop editor{/ts}</p>
                  <a href="{crmURL p='civicrm/membership-card-templates' q='action=add'}" class="btn btn-primary btn-sm">
                    {ts}Create Template{/ts}
                  </a>
                </div>
              </div>
              <div class="col-md-4">
                <div class="getting-started-step">
                  <div class="step-number">2</div>
                  <h4>{ts}Generate Cards{/ts}</h4>
                  <p>{ts}Create cards for individual members or in bulk{/ts}</p>
                  <a href="{crmURL p='civicrm/contact/search' q='reset=1'}" class="btn btn-secondary btn-sm">
                    {ts}Find Members{/ts}
                  </a>
                </div>
              </div>
              <div class="col-md-4">
                <div class="getting-started-step">
                  <div class="step-number">3</div>
                  <h4>{ts}Verify & Use{/ts}</h4>
                  <p>{ts}Use QR codes to verify membership status{/ts}</p>
                  <a href="{crmURL p='civicrm/membership-card/verify'}" class="btn btn-info btn-sm">
                    {ts}Verify Card{/ts}
                  </a>
                </div>
              </div>
            </div>
          </div>
        {/if}
      </div>

    {else}
      {* Template creation/editing form *}
      <div class="crm-form-block">

        {if $action eq 'add'}
          <h3>{ts}Create New Membership Card Template{/ts}</h3>
          <div class="help">
            {ts}Use the visual designer below to create a professional membership card template. You can drag tokens onto the card, add images, and customize the layout.{/ts}
          </div>
        {else}
          <h3>{ts}Edit Template: {$template.name}{/ts}</h3>
          <div class="help">
            {ts}Make changes to your membership card template using the visual designer below.{/ts}
          </div>
        {/if}

        <div class="template-form-wrapper">
          <div class="basic-info-section">
            <div class="row">
              <div class="col-md-6">
                <div class="crm-section">
                  <div class="label">{$form.name.label}</div>
                  <div class="content">{$form.name.html}</div>
                  <div class="clear"></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="crm-section">
                  <div class="label">{$form.is_active.label}</div>
                  <div class="content">{$form.is_active.html}</div>
                  <div class="clear"></div>
                </div>
              </div>
            </div>

            <div class="crm-section">
              <div class="label">{$form.description.label}</div>
              <div class="content">{$form.description.html}</div>
              <div class="clear"></div>
            </div>
          </div>

          {* Visual Designer Integration *}
          <div class="designer-integration">
            <div class="designer-placeholder">
              <div class="designer-loading">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p>{ts}Loading card designer...{/ts}</p>
              </div>
              <div class="designer-error" style="display: none;">
                <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
                <p>{ts}Error loading designer. Please refresh the page.{/ts}</p>
              </div>
            </div>
          </div>

          {* Hidden fields for template data *}
          {$form.card_width.html}
          {$form.card_height.html}
          {$form.background_color.html}
          {$form.background_image.html}
          {$form.elements.html}

          <div class="crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl" location="bottom"}
          </div>
        </div>
      </div>
    {/if}

  {/if}
</div>

{* Additional modals and overlays *}

{* Template Description Modal *}
<div class="modal fade" id="description-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">{ts}Template Description{/ts}</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="{ts}Close{/ts}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="full-description-text"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{ts}Close{/ts}</button>
      </div>
    </div>
  </div>
</div>

{* Bulk Action Confirmation Modal *}
<div class="modal fade" id="bulk-action-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">{ts}Confirm Bulk Action{/ts}</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="{ts}Close{/ts}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="bulk-action-message"></p>
        <div id="selected-templates-list"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{ts}Cancel{/ts}</button>
        <button type="button" class="btn btn-primary" id="confirm-bulk-action">{ts}Confirm{/ts}</button>
      </div>
    </div>
  </div>
</div>

{* CSS and JavaScript *}
{literal}
  <style>
    .template-name-wrapper {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .template-description {
      max-width: 300px;
    }

    .show-full-description {
      color: #0073aa;
      text-decoration: none;
      font-size: 0.9em;
    }

    .dimensions-info {
      font-family: monospace;
      font-size: 0.9em;
    }

    .card-size-indicator {
      border-radius: 2px;
      max-width: 35px;
      max-height: 22px;
    }

    .usage-stats {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .card-count {
      font-weight: 500;
    }

    .view-cards-link {
      color: #0073aa;
      font-size: 0.8em;
    }

    .created-by {
      margin-top: 2px;
    }

    .getting-started-step {
      text-align: center;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .step-number {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #0073aa;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      font-weight: bold;
      font-size: 18px;
    }

    .template-form-wrapper {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #ddd;
    }

    .basic-info-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .designer-integration {
      min-height: 400px;
      background: #f5f5f5;
      border-radius: 8px;
      padding: 20px;
      margin: 20px 0;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .designer-placeholder {
      text-align: center;
      color: #666;
    }

    .designer-loading i {
      margin-bottom: 10px;
    }

    @media (max-width: 768px) {
      .template-actions .dropdown-menu {
        left: auto;
        right: 0;
      }

      .dimensions-info {
        display: block;
      }

      .card-size-indicator {
        display: none;
      }

      .usage-stats {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>

<script>
  CRM.$(function($) {
    // Select all checkbox functionality
    $('#select-all-templates').change(function() {
      $('.template-checkbox').prop('checked', this.checked);
      updateBulkActionButtons();
    });

    $('.template-checkbox').change(function() {
      updateBulkActionButtons();

      // Update select all checkbox
      var totalCheckboxes = $('.template-checkbox').length;
      var checkedCheckboxes = $('.template-checkbox:checked').length;
      $('#select-all-templates').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Show full description
    $('.show-full-description').click(function(e) {
      e.preventDefault();
      var description = $(this).data('description');
      $('#full-description-text').text(description);
      $('#description-modal').modal('show');
    });

    // Initialize DataTable if available
    if ($.fn.DataTable) {
      $('#templates-table').DataTable({
        "paging": false,
        "searching": true,
        "ordering": true,
        "info": false,
        "columnDefs": [
          { "orderable": false, "targets": [0, 8] },
          { "searchable": false, "targets": [0, 8] }
        ]
      });
    }
  });

  function updateBulkActionButtons() {
    var selectedCount = CRM.$('.template-checkbox:checked').length;
    var bulkActionButton = CRM.$('.bulk-actions .dropdown-toggle');

    if (selectedCount > 0) {
      bulkActionButton.prop('disabled', false);
      bulkActionButton.find('.selected-count').remove();
      bulkActionButton.append(' <span class="selected-count">(' + selectedCount + ')</span>');
    } else {
      bulkActionButton.prop('disabled', true);
      bulkActionButton.find('.selected-count').remove();
    }
  }

  function bulkAction(action) {
    var selectedIds = [];
    CRM.$('.template-checkbox:checked').each(function() {
      selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
      CRM.alert('{/literal}{ts escape="js"}Please select at least one template{/ts}{literal}', '{/literal}{ts escape="js"}No Selection{/ts}{literal}', 'warning');
      return;
    }

    var actionMessages = {
      'activate': '{/literal}{ts escape="js"}Are you sure you want to activate template(s)?{/ts}{literal}',
      'deactivate': '{/literal}{ts escape="js"}Are you sure you want to deactivate template(s)?{/ts}{literal}',
      'export': '{/literal}{ts escape="js"}Export template(s) as a ZIP file?{/ts}{literal}',
      'delete': '{/literal}{ts escape="js"}Are you sure you want to delete template(s)? This action cannot be undone.{/ts}{literal}'
    };

    var message = actionMessages[action].replace('{count}', selectedIds.length);
    CRM.$('#bulk-action-message').text(message);

    // Show selected templates
    var templateList = '<ul>';
    CRM.$('.template-checkbox:checked').each(function() {
      var templateName = $(this).closest('tr').find('.crm-template-name strong a').text();
      templateList += '<li>' + templateName + '</li>';
    });
    templateList += '</ul>';
    CRM.$('#selected-templates-list').html(templateList);

    CRM.$('#bulk-action-modal').modal('show');

    CRM.$('#confirm-bulk-action').off('click').on('click', function() {
      performBulkAction(action, selectedIds);
    });
  }

  function performBulkAction(action, templateIds) {
    var apiAction = '';
    var apiParams = { template_ids: templateIds };

    switch (action) {
      case 'activate':
        apiAction = 'bulkactivate';
        break;
      case 'deactivate':
        apiAction = 'bulkdeactivate';
        break;
      case 'export':
        apiAction = 'bulkexport';
        break;
      case 'delete':
        apiAction = 'bulkdelete';
        break;
    }

    CRM.$('#bulk-action-modal').modal('hide');

    CRM.api3('MembershipCardTemplate', apiAction, apiParams)
      .done(function(result) {
        CRM.alert(result.values.message || '{/literal}{ts escape="js"}Bulk action completed successfully{/ts}{literal}', '{/literal}{ts escape="js"}Success{/ts}{literal}', 'success');
        window.location.reload();
      })
      .fail(function(error) {
        CRM.alert(error.error_message || '{/literal}{ts escape="js"}An error occurred{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
      });
  }

  function toggleTemplateStatus(templateId, status) {
    var statusText = status ? '{/literal}{ts escape="js"}activate{/ts}{literal}' : '{/literal}{ts escape="js"}deactivate{/ts}{literal}';

    if (confirm('{/literal}{ts escape="js"}Are you sure you want to {/ts}{literal}' + statusText + '{/literal}{ts escape="js"} this template?{/ts}{literal}')) {
      CRM.api3('MembershipCardTemplate', 'create', {
        id: templateId,
        is_active: status
      })
        .done(function(result) {
          CRM.alert('{/literal}{ts escape="js"}Template status updated successfully{/ts}{literal}', '{/literal}{ts escape="js"}Success{/ts}{literal}', 'success');
          window.location.reload();
        })
        .fail(function(error) {
          CRM.alert(error.error_message || '{/literal}{ts escape="js"}An error occurred{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
        });
    }
  }

  function deleteTemplate(templateId, templateName) {
    if (confirm('{/literal}{ts escape="js"}Are you sure you want to delete the template "{/ts}{literal}' + templateName + '{/literal}{ts escape="js"}"? This action cannot be undone.{/ts}{literal}')) {
      CRM.api3('MembershipCardTemplate', 'delete', {
        id: templateId
      })
        .done(function(result) {
          CRM.alert('{/literal}{ts escape="js"}Template deleted successfully{/ts}{literal}', '{/literal}{ts escape="js"}Success{/ts}{literal}', 'success');
          CRM.$('#template-' + templateId).fadeOut(function() {
            $(this).remove();
          });
        })
        .fail(function(error) {
          CRM.alert(error.error_message || '{/literal}{ts escape="js"}An error occurred{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
        });
    }
  }
</script>
{/literal}
