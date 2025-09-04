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
    <div class="row">
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
    <div class="cards-grid-section">
      <div class="cards-grid">
        {foreach from=$cards item=card}
          <div class="membership-card-item {if $card.is_expired}card-expired{elseif $card.days_until_expiry <= 30}card-expiring{/if}" data-card-id="{$card.card_id}">

            <!-- Card Preview -->
            <div class="card-preview">
              <div class="card-visual" style="background-color: {$card.background_color|default:'#ffffff'}; aspect-ratio: {$card.card_width}/{$card.card_height};">
                <div class="card-overlay">
                  <div class="card-member-info">
                    {if $card.image_URL}
                      <div class="member-photo">
                        <img src="{$card.image_URL}" alt="{$card.display_name}" />
                      </div>
                    {/if}
                    <div class="member-details">
                      <div class="member-name">{$card.display_name}</div>
                      <div class="member-type">{$card.membership_type_name}</div>
                      <div class="member-id">ID: {$card.membership_id}</div>
                    </div>
                  </div>

                  <div class="card-status-badge">
                    {if $card.is_active}
                      <span class="badge badge-success">{ts}Active{/ts}</span>
                    {elseif $card.is_expired}
                      <span class="badge badge-danger">{ts}Expired{/ts}</span>
                    {else}
                      <span class="badge badge-warning">{$card.membership_status_label}</span>
                    {/if}
                  </div>
                </div>
              </div>
            </div>

            <!-- Card Information -->
            <div class="card-info">
              <div class="card-header">
                <h4 class="card-member-name">
                  <a href="{$card.contact_url}" title="{ts}View Contact{/ts}">
                    {$card.display_name}
                  </a>
                </h4>
                <div class="card-metadata">
                  <span class="card-template" title="{ts}Template{/ts}: {$card.template_name}">
                    <i class="fa fa-file-text-o"></i> {$card.template_name}
                  </span>
                </div>
              </div>

              <div class="card-details">
                <div class="detail-row">
                  <span class="detail-label">{ts}Type{/ts}:</span>
                  <span class="detail-value">{$card.membership_type_name}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">{ts}Status{/ts}:</span>
                  <span class="detail-value">
                    {if $card.is_active}
                      <span class="status-active">{$card.membership_status_label}</span>
                    {elseif $card.is_expired}
                      <span class="status-expired">{$card.membership_status_label}</span>
                    {else}
                      <span class="status-other">{$card.membership_status_label}</span>
                    {/if}
                  </span>
                </div>
                {if $card.end_date}
                  <div class="detail-row">
                    <span class="detail-label">{ts}Expires{/ts}:</span>
                    <span class="detail-value">
                      {$card.end_date|crmDate}
                      {if $card.days_until_expiry !== null && $card.days_until_expiry > 0}
                        <span class="expiry-info">({$card.days_until_expiry} {ts}days{/ts})</span>
                      {/if}
                    </span>
                  </div>
                {/if}
                <div class="detail-row">
                  <span class="detail-label">{ts}Generated{/ts}:</span>
                  <span class="detail-value">{$card.card_created_date|crmDate}</span>
                </div>
              </div>

              <!-- Card Actions -->
              <div class="card-actions">
                <div class="primary-actions">
                  <a href="{$card.download_url}" class="btn btn-sm btn-primary" title="{ts}Download Card{/ts}">
                    <i class="fa fa-download"></i> {ts}Download{/ts}
                  </a>
                  <a href="{$card.verify_url}" class="btn btn-sm btn-info" title="{ts}Verify Card{/ts}" target="_blank">
                    <i class="fa fa-qrcode"></i> {ts}Verify{/ts}
                  </a>
                </div>

                <div class="secondary-actions">
                  <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown" title="{ts}More Actions{/ts}">
                      <i class="fa fa-ellipsis-h"></i>
                      <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
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
            </div>
          </div>
        {/foreach}
      </div>
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
<div class="modal fade" id="card-preview-modal" tabindex="-1" role="dialog">
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

{* CSS Styles *}
{literal}
  <style>
    .page-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 25px 30px;
      border-radius: 8px;
      margin-bottom: 25px;
    }

    .page-header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 15px;
    }

    .page-title {
      margin: 0;
      font-size: 28px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .page-subtitle {
      font-size: 18px;
      font-weight: 400;
      opacity: 0.9;
    }

    .page-actions .btn-group {
      display: flex;
      gap: 10px;
    }

    /* Statistics Cards */
    .stats-section {
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      border-left: 4px solid #ddd;
      transition: transform 0.2s, box-shadow 0.2s;
      height: 100px;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .stat-total { border-left-color: #6c757d; }
    .stat-active { border-left-color: #28a745; }
    .stat-expired { border-left-color: #dc3545; }
    .stat-expiring { border-left-color: #ffc107; }
    .stat-recent { border-left-color: #17a2b8; }
    .stat-info { border-left-color: #6f42c1; }

    .stat-icon {
      font-size: 24px;
      color: #6c757d;
    }

    .stat-content {
      flex: 1;
    }

    .stat-number {
      font-size: 28px;
      font-weight: 700;
      line-height: 1;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 13px;
      color: #6c757d;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 500;
    }

    .stat-text {
      font-size: 12px;
      color: #495057;
      font-weight: 500;
    }

    /* Filters */
    .filters-section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 25px;
    }

    .filters-form .form-group label {
      font-weight: 600;
      color: #495057;
      margin-bottom: 5px;
    }

    .filter-actions {
      display: flex;
      gap: 10px;
      padding-top: 6px;
    }

    /* Cards Grid */
    .cards-grid-section {
      margin-bottom: 30px;
    }

    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 20px;
    }

    .membership-card-item {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: transform 0.2s, box-shadow 0.2s;
      border: 1px solid #e9ecef;
    }

    .membership-card-item:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .card-expired {
      opacity: 0.8;
      border-color: #dc3545;
    }

    .card-expiring {
      border-color: #ffc107;
    }

    /* Card Preview */
    .card-preview {
      position: relative;
      overflow: hidden;
    }

    .card-visual {
      width: 100%;
      min-height: 120px;
      position: relative;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border: 1px solid #dee2e6;
    }

    .card-overlay {
      position: absolute;
      inset: 0;
      padding: 15px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
    }

    .card-member-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .member-photo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      overflow: hidden;
      background: #f8f9fa;
      border: 2px solid white;
    }

    .member-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .member-details {
      color: #333;
    }

    .member-name {
      font-weight: 600;
      font-size: 14px;
      line-height: 1.2;
    }

    .member-type {
      font-size: 12px;
      color: #666;
      margin: 2px 0;
    }

    .member-id {
      font-size: 11px;
      color: #888;
      font-family: monospace;
    }

    .card-status-badge {
      align-self: flex-start;
    }

    .badge {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .badge-success {
      background-color: #d4edda;
      color: #155724;
    }

    .badge-danger {
      background-color: #f8d7da;
      color: #721c24;
    }

    .badge-warning {
      background-color: #fff3cd;
      color: #856404;
    }

    /* Card Information */
    .card-info {
      padding: 20px;
    }

    .card-header {
      margin-bottom: 15px;
    }

    .card-member-name {
      margin: 0 0 5px 0;
      font-size: 18px;
      font-weight: 600;
    }

    .card-member-name a {
      color: #495057;
      text-decoration: none;
    }

    .card-member-name a:hover {
      color: #007bff;
    }

    .card-metadata {
      display: flex;
      align-items: center;
      gap: 15px;
      font-size: 12px;
      color: #6c757d;
    }

    .card-template i {
      margin-right: 4px;
    }

    .card-details {
      margin-bottom: 20px;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 6px 0;
      border-bottom: 1px solid #f8f9fa;
    }

    .detail-label {
      font-weight: 500;
      color: #6c757d;
      font-size: 13px;
    }

    .detail-value {
      font-size: 13px;
      color: #495057;
      text-align: right;
    }

    .status-active {
      color: #28a745;
      font-weight: 600;
    }

    .status-expired {
      color: #dc3545;
      font-weight: 600;
    }

    .status-other {
      color: #ffc107;
      font-weight: 600;
    }

    .expiry-info {
      color: #6c757d;
      font-size: 11px;
    }

    /* Card Actions */
    .card-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 15px;
      border-top: 1px solid #f8f9fa;
    }

    .primary-actions {
      display: flex;
      gap: 8px;
    }

    .secondary-actions .dropdown-menu {
      right: 0;
      left: auto;
    }

    /* Pagination */
    .pagination-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .pagination-info {
      color: #6c757d;
      font-size: 14px;
    }

    .pagination {
      margin: 0;
      display: flex;
      list-style: none;
      gap: 5px;
    }

    .pagination li a {
      padding: 8px 12px;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      color: #495057;
      text-decoration: none;
      transition: all 0.2s;
    }

    .pagination li a:hover {
      background-color: #e9ecef;
      border-color: #adb5bd;
    }

    .pagination li.active a {
      background-color: #007bff;
      border-color: #007bff;
      color: white;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .empty-state-icon {
      color: #dee2e6;
      margin-bottom: 20px;
    }

    .empty-state h3 {
      color: #495057;
      margin-bottom: 10px;
    }

    .empty-state-message {
      color: #6c757d;
      margin-bottom: 25px;
      font-size: 16px;
    }

    .empty-state-actions {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
      .cards-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      }

      .page-header-content {
        flex-direction: column;
        text-align: center;
      }

      .stats-section .row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
      }

      .stats-section [class*="col-"] {
        flex: 1;
        min-width: 150px;
      }
    }

    @media (max-width: 768px) {
      .cards-grid {
        grid-template-columns: 1fr;
      }

      .pagination-section {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }

      .card-actions {
        flex-direction: column;
        gap: 10px;
      }

      .primary-actions {
        width: 100%;
        justify-content: center;
      }

      .stat-card {
        flex-direction: column;
        text-align: center;
        height: auto;
        padding: 15px;
      }

      .stat-icon {
        font-size: 20px;
      }

      .stat-number {
        font-size: 24px;
      }
    }

    /* Loading States */
    .loading-spinner {
      padding: 40px;
      text-align: center;
      color: #6c757d;
    }

    .loading-spinner i {
      margin-bottom: 10px;
    }

    /* Modal Customizations */
    .modal-body img {
      max-width: 100%;
      height: auto;
      border: 1px solid #dee2e6;
      border-radius: 4px;
    }
  </style>
{/literal}

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
