<div class="crm-block crm-content-block crm-membership-cards-block">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-content">
      <h1 class="page-title">
        <i class="fa fa-id-card"></i>
        {ts}Membership Cards{/ts}
        <span class="page-subtitle">({$totalCount} {if $totalCount == 1}{ts}card{/ts}{else}{ts}cards{/ts}{/if})</span>
      </h1>
      <div class="page-actions">
        <div class="btn-group">
          <a href="{crmURL p='civicrm/membership-card-templates'}" class="btn btn-secondary">
            <i class="fa fa-cogs"></i> {ts}Manage Templates{/ts}
          </a>
          <a href="{crmURL p='civicrm/membership-cards/bulk-generate'}" class="btn btn-success">
            <i class="fa fa-refresh"></i> {ts}Bulk Generate{/ts}
          </a>
          <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-download"></i> {ts}Export{/ts} <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
              <li><a href="#" onclick="exportCards('csv')">{ts}Export as CSV{/ts}</a></li>
              <li><a href="#" onclick="exportCards('pdf')">{ts}Export as PDF{/ts}</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Summary Statistics -->
  <div class="stats-section">
    <div class="row" style="display: flex; flex-wrap: wrap; gap: 15px;">
      <div class="col-md-2">
        <div class="stat-card stat-total">
          <div class="stat-icon">
            <i class="fa fa-id-card"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{$stats.total_cards}</div>
            <div class="stat-label">{ts}Total Cards{/ts}</div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="stat-card stat-active">
          <div class="stat-icon">
            <i class="fa fa-check-circle"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{$stats.active_cards}</div>
            <div class="stat-label">{ts}Active{/ts}</div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="stat-card stat-expired">
          <div class="stat-icon">
            <i class="fa fa-times-circle"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{$stats.expired_cards}</div>
            <div class="stat-label">{ts}Expired{/ts}</div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="stat-card stat-expiring">
          <div class="stat-icon">
            <i class="fa fa-clock-o"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{$stats.expiring_soon}</div>
            <div class="stat-label">{ts}Expiring Soon{/ts}</div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="stat-card stat-recent">
          <div class="stat-icon">
            <i class="fa fa-calendar"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{$stats.recent_cards}</div>
            <div class="stat-label">{ts}This Week{/ts}</div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="stat-card stat-info">
          <div class="stat-content">
            <div class="stat-label">{ts}Most Used Template{/ts}</div>
            <div class="stat-text">{$stats.most_used_template}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters Section -->
  <div class="filters-section">
    <form method="get" action="{crmURL p='civicrm/membership-cards'}" class="filters-form">
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label for="membership_type_id">{ts}Membership Type{/ts}:</label>
            <select name="membership_type_id" id="membership_type_id" class="form-control">
              <option value="">{ts}- All Types -{/ts}</option>
              {foreach from=$membershipTypes key=id item=name}
                <option value="{$id}" {if $currentFilters.membership_type_id == $id}selected{/if}>{$name}</option>
              {/foreach}
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label for="template_id">{ts}Template{/ts}:</label>
            <select name="template_id" id="template_id" class="form-control">
              <option value="">{ts}- All Templates -{/ts}</option>
              {foreach from=$templates key=id item=name}
                <option value="{$id}" {if $currentFilters.template_id == $id}selected{/if}>{$name}</option>
              {/foreach}
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label for="status">{ts}Status{/ts}:</label>
            <select name="status" id="status" class="form-control">
              <option value="">{ts}- All Status -{/ts}</option>
              <option value="current" {if $currentFilters.status == 'current'}selected{/if}>{ts}Current{/ts}</option>
              <option value="expired" {if $currentFilters.status == 'expired'}selected{/if}>{ts}Expired{/ts}</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>&nbsp;</label>
            <div class="filter-actions">
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-search"></i> {ts}Filter{/ts}
              </button>
              <a href="{crmURL p='civicrm/membership-cards'}" class="btn btn-secondary">
                <i class="fa fa-refresh"></i> {ts}Clear{/ts}
              </a>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Cards Grid -->
  {if $cards}
    <div class="membership-cards-table-section">
      <table class="table table-striped table-hover">
        <thead>
        <tr>
          <th>{ts}Member{/ts}</th>
          <th>{ts}Type{/ts}</th>
          <th>{ts}Status{/ts}</th>
          <th>{ts}Expires{/ts}</th>
          <th>{ts}Template{/ts}</th>
          <th>{ts}Generated{/ts}</th>
          <th class="text-center">{ts}Actions{/ts}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$cards item=card}
          <tr class="membership-card-row {if $card.is_expired}card-expired{elseif $card.days_until_expiry <= 30}card-expiring{/if}" data-card-id="{$card.card_id}">

            <!-- Member Information -->
            <td class="member-info-cell">
              <div class="member-summary">
                {if $card.image_URL}
                  <div class="member-photo-small">
                    <img src="{$card.image_URL}" alt="{$card.display_name}" class="img-circle" width="32" height="32" />
                  </div>
                {/if}
                <div class="member-details">
                  <div class="member-name">
                    <a href="{$card.contact_url}" title="{ts}View Contact{/ts}">
                      {$card.display_name}
                    </a>
                  </div>
                  <div class="member-id text-muted">ID: {$card.membership_id}</div>
                </div>
              </div>
            </td>

            <!-- Membership Type -->
            <td class="membership-type-cell">
              {$card.membership_type_name}
            </td>

            <!-- Status -->
            <td class="status-cell">
              {if $card.is_active}
                <span class="badge badge-success">{$card.membership_status_label}</span>
              {elseif $card.is_expired}
                <span class="badge badge-danger">{$card.membership_status_label}</span>
              {else}
                <span class="badge badge-warning">{$card.membership_status_label}</span>
              {/if}
            </td>

            <!-- Expiry Date -->
            <td class="expiry-cell">
              {if $card.end_date}
                <div class="expiry-date">{$card.end_date|crmDate}</div>
                {if $card.days_until_expiry !== null && $card.days_until_expiry > 0}
                  <div class="expiry-info text-muted small">({$card.days_until_expiry} {ts}days{/ts})</div>
                {/if}
              {else}
                <span class="text-muted">{ts}No expiry{/ts}</span>
              {/if}
            </td>

            <!-- Template -->
            <td class="template-cell">
              <span class="template-name" title="{$card.template_name}">
                <i class="fa fa-file-text-o"></i> {$card.template_name}
              </span>
            </td>

            <!-- Generated Date -->
            <td class="generated-cell">
              {$card.card_created_date|crmDate}
            </td>

            <!-- Actions -->
            <td class="actions-cell text-center">
              <div class="btn-group btn-group-sm">
                <!-- Primary Actions -->
                <a href="{$card.download_url}" class="btn btn-primary btn-xs" title="{ts}Download Card{/ts}">
                  <i class="fa fa-download"></i>
                </a>
                <a href="{$card.verify_url}" class="btn btn-info btn-xs" title="{ts}Verify Card{/ts}" target="_blank">
                  <i class="fa fa-qrcode"></i>
                </a>

                <!-- More Actions Dropdown -->
                <div class="secondary-actions">
                <div class="btn-group">
                  <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" title="{ts}More Actions{/ts}">
                    <i class="fa fa-ellipsis-h"></i>
                    <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-right" style="background-color: var(--crm-c-background);">
                    <li>
                      <a href="#" onclick="previewCard({$card.card_id})">
                        <i class="fa fa-eye"></i> {ts}Preview{/ts}
                      </a>
                    </li>
                    <li>
                      <a href="#" onclick="emailCard({$card.card_id})">
                        <i class="fa fa-envelope"></i> {ts}Email Card{/ts}
                      </a>
                    </li>
                    <li>
                      <a href="#" onclick="regenerateCard({$card.card_id})">
                        <i class="fa fa-refresh"></i> {ts}Regenerate{/ts}
                      </a>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li>
                      <a href="{$card.contact_url}">
                        <i class="fa fa-user"></i> {ts}View Contact{/ts}
                      </a>
                    </li>
                    <li>
                      <a href="{$card.membership_url}">
                        <i class="fa fa-id-badge"></i> {ts}View Membership{/ts}
                      </a>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li>
                      <a href="#" onclick="deleteCard({$card.card_id}, '{$card.display_name|escape:'javascript'}')" class="text-danger">
                        <i class="fa fa-trash"></i> {ts}Delete Card{/ts}
                      </a>
                    </li>
                  </ul>
                </div>
                </div>
              </div>
            </td>
          </tr>
        {/foreach}
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    {*
    {if $pagination.total_pages > 1}
      <div class="pagination-section">
        <div class="pagination-info">
          {ts 1=$pagination.showing_from 2=$pagination.showing_to 3=$totalCount}Showing %1 to %2 of %3 cards{/ts}
        </div>
        <div class="pagination-controls">
          <ul class="pagination">
            {if $pagination.current_page > 1}
              <li>
                <a href="{crmURL p='civicrm/membership-cards' q="offset=0"}" title="{ts}First Page{/ts}">
                  <i class="fa fa-angle-double-left"></i>
                </a>
              </li>
              <li>
                <a href="{crmURL p='civicrm/membership-cards' q="offset=`$pagination.offset-$pagination.limit`"}" title="{ts}Previous Page{/ts}">
                  <i class="fa fa-angle-left"></i>
                </a>
              </li>
            {/if}

            {assign var="start_page" value=`$pagination.current_page-2`}
            {assign var="end_page" value=`$pagination.current_page+2`}
            {if $start_page < 1}
              {assign var="start_page" value=1}
            {/if}
            {if $end_page > $pagination.total_pages}
              {assign var="end_page" value=$pagination.total_pages}
            {/if}

            {for $page=$start_page to $end_page}
              {assign var="page_offset" value=`($page-1)*$pagination.limit`}
              <li {if $page == $pagination.current_page}class="active"{/if}>
                <a href="{crmURL p='civicrm/membership-cards' q="offset=`$page_offset`"}">{$page}</a>
              </li>
            {/for}

            {if $pagination.current_page < $pagination.total_pages}
              <li>
                <a href="{crmURL p='civicrm/membership-cards' q="offset=`$pagination.offset+$pagination.limit`"}" title="{ts}Next Page{/ts}">
                  <i class="fa fa-angle-right"></i>
                </a>
              </li>
              <li>
                <a href="{crmURL p='civicrm/membership-cards' q="offset=`($pagination.total_pages-1)*$pagination.limit`"}" title="{ts}Last Page{/ts}">
                  <i class="fa fa-angle-double-right"></i>
                </a>
              </li>
            {/if}
          </ul>
        </div>
      </div>
    {/if}
    *}

  {else}
    <!-- Empty State -->
    <div class="empty-state">
      <div class="empty-state-icon">
        <i class="fa fa-id-card fa-5x"></i>
      </div>
      <h3>{ts}No Membership Cards Found{/ts}</h3>
      <p class="empty-state-message">
        {if $currentFilters.membership_type_id || $currentFilters.template_id || $currentFilters.status}
          {ts}No cards match your current filters. Try adjusting your search criteria.{/ts}
        {else}
          {ts}You haven't generated any membership cards yet.{/ts}
        {/if}
      </p>
      <div class="empty-state-actions">
        {if $currentFilters.membership_type_id || $currentFilters.template_id || $currentFilters.status}
          <a href="{crmURL p='civicrm/membership-cards'}" class="btn btn-secondary">
            <i class="fa fa-refresh"></i> {ts}Clear Filters{/ts}
          </a>
        {else}
          <a href="{crmURL p='civicrm/membership-cards/bulk-generate'}" class="btn btn-primary">
            <i class="fa fa-refresh"></i> {ts}Generate Cards{/ts}
          </a>
          <a href="{crmURL p='civicrm/membership-card-templates'}" class="btn btn-secondary">
            <i class="fa fa-cogs"></i> {ts}Manage Templates{/ts}
          </a>
        {/if}
      </div>
    </div>
  {/if}

</div>

{* Modals *}

{* Card Preview Modal *}
<div class="modal fade" id="card-preview-modal" tabindex="-1" role="dialog" style="display: none;">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">{ts}Card Preview{/ts}</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="{ts}Close{/ts}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="card-preview-content" class="text-center">
          <div class="loading-spinner">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>{ts}Loading card preview...{/ts}</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="download-preview-card">
          <i class="fa fa-download"></i> {ts}Download{/ts}
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{ts}Close{/ts}</button>
      </div>
    </div>
  </div>
</div>

{* Email Card Modal *}
<div class="modal fade" id="email-card-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">{ts}Email Membership Card{/ts}</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="{ts}Close{/ts}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="email-card-form">
          <div class="form-group">
            <label for="email-to">{ts}Send To{/ts}:</label>
            <input type="email" id="email-to" name="email_to" class="form-control" required>
            <small class="form-text text-muted">{ts}Leave blank to send to member's email address{/ts}</small>
          </div>
          <div class="form-group">
            <label for="email-subject">{ts}Subject{/ts}:</label>
            <input type="text" id="email-subject" name="email_subject" class="form-control"
                   value="{ts}Your Membership Card{/ts}" required>
          </div>
          <div class="form-group">
            <label for="email-message">{ts}Message{/ts}:</label>
            <textarea id="email-message" name="email_message" class="form-control" rows="4"
                      placeholder="{ts}Add a personal message (optional){/ts}"></textarea>
          </div>
          <input type="hidden" id="email-card-id" name="card_id">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="sendCardEmail()">
          <i class="fa fa-envelope"></i> {ts}Send Email{/ts}
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{ts}Cancel{/ts}</button>
      </div>
    </div>
  </div>
</div>

{* Delete Confirmation Modal *}
<div class="modal fade" id="delete-card-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">{ts}Delete Membership Card{/ts}</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="{ts}Close{/ts}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="delete-card-message"></p>
        <div class="alert alert-warning">
          <i class="fa fa-exclamation-triangle"></i>
          {ts}This action cannot be undone. The member can regenerate their card if needed.{/ts}
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="confirm-delete-card">
          <i class="fa fa-trash"></i> {ts}Delete Card{/ts}
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{ts}Cancel{/ts}</button>
      </div>
    </div>
  </div>
</div>

{* JavaScript Functions *}
{literal}
<script>
  // Card preview functionality
  function previewCard(cardId) {
    CRM.$('#card-preview-modal').modal('show');
    CRM.$('#card-preview-content').html(`
      <div class="loading-spinner">
        <i class="fa fa-spinner fa-spin fa-2x"></i>
        <p>{/literal}{ts escape="js"}Loading card preview...{/ts}{literal}</p>
      </div>
    `);

    // Load preview
    CRM.api3('MembershipCard', 'download', {
      card_id: cardId,
      format: 'png'
    }).done(function(result) {
      if (result.values && result.values[0]) {
        const card = result.values[0];
        CRM.$('#card-preview-content').html(`
          <img src="${card.image_data}" alt="{/literal}{ts escape="js"}Membership Card{/ts}{literal}" class="img-responsive">
        `);
        CRM.$('#download-preview-card').off('click').on('click', function() {
          window.open(CRM.url('civicrm/membership-card/download', {card_id: cardId}), '_blank');
        });
      }
    }).fail(function(error) {
      CRM.$('#card-preview-content').html(`
        <div class="alert alert-danger">
          <i class="fa fa-exclamation-triangle"></i>
          {/literal}{ts escape="js"}Error loading preview{/ts}{literal}: ${error.error_message || 'Unknown error'}
        </div>
      `);
    });
  }

  // Email card functionality
  function emailCard(cardId) {
    CRM.$('#email-card-id').val(cardId);
    CRM.$('#email-card-modal').modal('show');
  }

  function sendCardEmail() {
    const formData = {
      card_id: CRM.$('#email-card-id').val(),
      email_to: CRM.$('#email-to').val(),
      email_subject: CRM.$('#email-subject').val(),
      email_message: CRM.$('#email-message').val()
    };

    CRM.api3('MembershipCard', 'email', formData)
      .done(function(result) {
        CRM.$('#email-card-modal').modal('hide');
        CRM.alert('{/literal}{ts escape="js"}Card emailed successfully{/ts}{literal}', '{/literal}{ts escape="js"}Success{/ts}{literal}', 'success');

        // Reset form
        CRM.$('#email-card-form')[0].reset();
      })
      .fail(function(error) {
        CRM.alert(error.error_message || '{/literal}{ts escape="js"}Error sending email{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
      });
  }

  // Regenerate card functionality
  function regenerateCard(cardId) {
    if (confirm('{/literal}{ts escape="js"}Are you sure you want to regenerate this card? This will replace the existing card.{/ts}{literal}')) {
      // Get card info first
      CRM.api3('MembershipCard', 'get', {id: cardId})
        .done(function(result) {
          if (result.values && result.values[0]) {
            const card = result.values[0];

            // Regenerate card
            CRM.api3('MembershipCard', 'generate', {
              membership_id: card.membership_id,
              template_id: card.template_id,
              force_regenerate: 1
            })
              .done(function(generateResult) {
                CRM.alert('{/literal}{ts escape="js"}Card regenerated successfully{/ts}{literal}', '{/literal}{ts escape="js"}Success{/ts}{literal}', 'success');
                window.location.reload();
              })
              .fail(function(error) {
                CRM.alert(error.error_message || '{/literal}{ts escape="js"}Error regenerating card{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
              });
          }
        });
    }
  }

  // Delete card functionality
  let deleteCardId = null;

  function deleteCard(cardId, memberName) {
    deleteCardId = cardId;
    CRM.$('#delete-card-message').text('{/literal}{ts escape="js"}Are you sure you want to delete the membership card for{/ts}{literal} ' + memberName + '?');
    CRM.$('#delete-card-modal').modal('show');
  }

  CRM.$(function($) {
    $('#confirm-delete-card').click(function() {
      if (deleteCardId) {
        window.location.href = CRM.url('civicrm/membership-cards', {
          action: 'delete',
          id: deleteCardId
        });
      }
    });
  });

  // Export functionality
  function exportCards(format) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('export', format);
    window.open(currentUrl.toString(), '_blank');
  }

  // Filter form enhancements
  CRM.$(function($) {
    // Auto-submit form when filters change
    $('.filters-form select').change(function() {
      // Optional: auto-submit on change
      // $(this).closest('form').submit();
    });

    // Clear individual filters
    $('.clear-filter').click(function(e) {
      e.preventDefault();
      const filterName = $(this).data('filter');
      $(`select[name="${filterName}"]`).val('').trigger('change');
    });
  });
</script>
{/literal}
