<div class="crm-block crm-content-block crm-membership-card-download-block">

  {if $card_data}
    <div class="crm-section">
      <h3>{ts}Membership Card Download{/ts}</h3>

      <div class="card-preview-section">
        <div class="card-preview-container">
          <div class="card-preview">
            {if $card_image}
              <img src="{$card_image}" alt="{ts}Membership Card{/ts}" class="membership-card-image" />
            {else}
              <div class="card-placeholder">
                <i class="fa fa-id-card-o fa-5x"></i>
                <p>{ts}Card Preview{/ts}</p>
              </div>
            {/if}
          </div>
        </div>

        <div class="card-info">
          <h4>{ts}Card Information{/ts}</h4>
          <table class="card-details">
            <tr>
              <td><strong>{ts}Member Name{/ts}:</strong></td>
              <td>{$contact.display_name}</td>
            </tr>
            <tr>
              <td><strong>{ts}Membership Type{/ts}:</strong></td>
              <td>{$membership.membership_type}</td>
            </tr>
            <tr>
              <td><strong>{ts}Membership ID{/ts}:</strong></td>
              <td>{$membership.id}</td>
            </tr>
            <tr>
              <td><strong>{ts}Status{/ts}:</strong></td>
              <td>{$membership.status}</td>
            </tr>
            <tr>
              <td><strong>{ts}Valid Until{/ts}:</strong></td>
              <td>{$membership.end_date|crmDate}</td>
            </tr>
            {if $qr_code}
              <tr>
                <td><strong>{ts}QR Code{/ts}:</strong></td>
                <td>
                  <img src="{$qr_code}" alt="{ts}QR Code{/ts}" style="width: 100px; height: 100px;" />
                </td>
              </tr>
            {/if}
          </table>
        </div>
      </div>

      <div class="card-actions">
        <div class="download-buttons">
          <a href="{$download_url_png}" class="btn btn-primary" download>
            <i class="fa fa-download"></i> {ts}Download PNG{/ts}
          </a>
          {if $download_url_pdf}
            <a href="{$download_url_pdf}" class="btn btn-secondary" download>
              <i class="fa fa-file-pdf-o"></i> {ts}Download PDF{/ts}
            </a>
          {/if}
          <button type="button" class="btn btn-info" onclick="window.print()">
            <i class="fa fa-print"></i> {ts}Print{/ts}
          </button>
          <button type="button" class="btn btn-success" onclick="emailCard()">
            <i class="fa fa-envelope"></i> {ts}Email Card{/ts}
          </button>
        </div>

        <div class="share-options">
          <h5>{ts}Share Options{/ts}</h5>
          <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" onclick="copyVerificationUrl()">
              <i class="fa fa-link"></i> {ts}Copy Verification URL{/ts}
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="shareCard()">
              <i class="fa fa-share"></i> {ts}Share{/ts}
            </button>
          </div>
        </div>
      </div>

      {if $verification_url}
        <div class="verification-section">
          <h5>{ts}Verification{/ts}</h5>
          <p class="help">
            {ts}This card includes a QR code that can be scanned to verify membership status. The verification URL is:{/ts}
          </p>
          <div class="verification-url">
            <input type="text" id="verification-url" class="form-control" value="{$verification_url}" readonly />
            <button type="button" class="btn btn-sm btn-secondary" onclick="copyVerificationUrl()">
              <i class="fa fa-copy"></i> {ts}Copy{/ts}
            </button>
          </div>
        </div>
      {/if}

    </div>

  {else}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {ts}Card data not found. Please regenerate the membership card.{/ts}
    </div>

    <div class="crm-submit-buttons">
      <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contact_id`&selectedChild=member"}" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> {ts}Back to Membership{/ts}
      </a>
    </div>
  {/if}

</div>

{* CSS Styles *}
{literal}
  <style>
    .card-preview-section {
      display: flex;
      gap: 30px;
      margin: 20px 0;
      flex-wrap: wrap;
    }

    .card-preview-container {
      flex: 1;
      min-width: 300px;
    }

    .card-preview {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
      border: 1px solid #dee2e6;
    }

    .membership-card-image {
      max-width: 100%;
      height: auto;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .card-placeholder {
      color: #6c757d;
      padding: 40px;
    }

    .card-info {
      flex: 1;
      min-width: 300px;
    }

    .card-details {
      width: 100%;
      border-collapse: collapse;
    }

    .card-details td {
      padding: 8px 12px;
      border-bottom: 1px solid #dee2e6;
    }

    .card-actions {
      margin: 30px 0;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 8px;
    }

    .download-buttons {
      margin-bottom: 20px;
    }

    .download-buttons .btn {
      margin-right: 10px;
      margin-bottom: 10px;
    }

    .share-options h5 {
      margin-bottom: 10px;
      color: #495057;
    }

    .verification-section {
      margin-top: 30px;
      padding: 20px;
      background: #e3f2fd;
      border-radius: 8px;
      border: 1px solid #bbdefb;
    }

    .verification-url {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }

    .verification-url input {
      flex: 1;
    }

    @media (max-width: 768px) {
      .card-preview-section {
        flex-direction: column;
      }

      .download-buttons .btn {
        display: block;
        width: 100%;
        margin-bottom: 10px;
        margin-right: 0;
      }

      .verification-url {
        flex-direction: column;
      }

      .verification-url button {
        margin-top: 10px;
      }
    }

    @media print {
      .card-actions,
      .verification-section,
      .crm-submit-buttons {
        display: none;
      }

      .card-preview {
        background: white;
        border: none;
      }
    }
  </style>
{/literal}

{* JavaScript Functions *}
{literal}
<script>
  function copyVerificationUrl() {
    const urlInput = document.getElementById('verification-url');
    if (urlInput) {
      urlInput.select();
      urlInput.setSelectionRange(0, 99999);
      document.execCommand('copy');

      CRM.alert('{/literal}{ts escape="js"}Verification URL copied to clipboard{/ts}{literal}', '{/literal}{ts escape="js"}Success{/ts}{literal}', 'success');
    }
  }

  function emailCard() {
    const cardId = {/literal}{$card_id|default:0}{literal};
    if (cardId) {
      CRM.loadForm(CRM.url('civicrm/membership-card/email', {card_id: cardId}))
        .on('crmFormSuccess', function() {
          CRM.alert('{/literal}{ts escape="js"}Card emailed successfully{/ts}{literal}', '{/literal}{ts escape="js"}Success{/ts}{literal}', 'success');
        });
    }
  }

  function shareCard() {
    if (navigator.share) {
      navigator.share({
        title: '{/literal}{ts escape="js"}Membership Card{/ts}{literal}',
        text: '{/literal}{ts escape="js"}My membership card{/ts}{literal}',
        url: window.location.href
      });
    } else {
      // Fallback for browsers that don't support Web Share API
      copyVerificationUrl();
    }
  }

  // Auto-download functionality
  CRM.$(function($) {
    // If auto-download parameter is present, trigger download
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('auto_download') === '1') {
      setTimeout(function() {
        const downloadBtn = document.querySelector('.download-buttons .btn-primary');
        if (downloadBtn) {
          downloadBtn.click();
        }
      }, 1000);
    }
  });
</script>
{/literal}
