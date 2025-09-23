<div class="crm-block crm-content-block crm-membership-card-verify-block">

  <div class="verify-header">
    <h2>{ts}Membership Card Verification{/ts}</h2>
    <p class="lead">{ts}Verify the authenticity and status of a membership card{/ts}</p>
  </div>

  {if $verification_result}
    {* Verification result display *}
    <div class="verification-result">
      {if $verification_result.is_valid}
        {* Valid membership *}
        <div class="alert alert-success verification-success">
          <div class="verification-icon">
            <i class="fa fa-check-circle fa-3x"></i>
          </div>
          <div class="verification-content">
            <h3>{ts}Valid Membership{/ts}</h3>
            <p>{ts}This membership card is valid and active.{/ts}</p>
          </div>
        </div>

        <div class="member-details">
          <h4>{ts}Member Information{/ts}</h4>
          <div class="row">
            <div class="col-md-6">
              <table class="member-info-table">
                <tr>
                  <td><strong>{ts}Membership Type{/ts}:</strong></td>
                  <td>{$verification_result.membership_type}</td>
                </tr>
                <tr>
                  <td><strong>{ts}Status{/ts}:</strong></td>
                  <td>
                    <span class="label label-success">{$verification_result.status}</span>
                  </td>
                </tr>
              </table>
            </div>
            <div class="col-md-6">
              <table class="member-info-table">
                <tr>
                  <td><strong>{ts}Start Date{/ts}:</strong></td>
                  <td>{$verification_result.start_date|crmDate}</td>
                </tr>
                <tr>
                  <td><strong>{ts}End Date{/ts}:</strong></td>
                  <td>{$verification_result.end_date|crmDate}</td>
                </tr>
                <tr>
                  <td><strong>{ts}Verified{/ts}:</strong></td>
                  <td>{$verification_result.verified_date|crmDate:'%B %d, %Y at %l:%M %p'}</td>
                </tr>
                <tr>
                  <td><strong>{ts}Valid Until{/ts}:</strong></td>
                  <td>
                    {assign var="end_date" value=$verification_result.end_date|crmDate}
                    {assign var="current_date" value=$smarty.now}
                    {if $end_date > $current_date}
                      <span class="text-success">{$verification_result.end_date|crmDate}</span>
                    {else}
                      <span class="text-danger">{ts}Expired{/ts}</span>
                    {/if}
                  </td>
                </tr>
              </table>
            </div>
          </div>
        </div>

        {* Additional validation indicators *}
        <div class="validation-indicators">
          <div class="indicator-item">
            <i class="fa fa-shield text-success"></i>
            <span>{ts}Digitally Verified{/ts}</span>
          </div>
          <div class="indicator-item">
            <i class="fa fa-calendar-check text-success"></i>
            <span>{ts}Current Membership{/ts}</span>
          </div>
          <div class="indicator-item">
            <i class="fa fa-clock text-info"></i>
            <span>{ts}Verified{/ts}: {$verification_result.verified_date|crmDate:'%l:%M %p'}</span>
          </div>
        </div>

      {else}
        {* Invalid membership *}
        <div class="alert alert-danger verification-failed">
          <div class="verification-icon">
            <i class="fa fa-times-circle fa-3x"></i>
          </div>
          <div class="verification-content">
            <h3>{ts}Invalid Membership{/ts}</h3>
            <p>
              {if $verification_result.error}
                {$verification_result.error}
              {else}
                {ts}This membership card could not be verified. It may be expired, suspended, or invalid.{/ts}
              {/if}
            </p>
          </div>
        </div>

        <div class="verification-help">
          <h4>{ts}What does this mean?{/ts}</h4>
          <ul>
            <li>{ts}The membership may have expired{/ts}</li>
            <li>{ts}The membership may have been suspended or cancelled{/ts}</li>
            <li>{ts}The QR code or membership ID may be invalid{/ts}</li>
            <li>{ts}There may be a technical issue with verification{/ts}</li>
          </ul>

          <p class="help-text">
            {ts}If you believe this is an error, please contact the organization directly.{/ts}
          </p>
        </div>
      {/if}
    </div>

  {else}
    {* Manual verification form *}
    <div class="manual-verification">
      <div class="verification-methods">
        <div class="row">
          <div class="col-md-6">
            <div class="verification-method">
              <h4><i class="fa fa-qrcode"></i> {ts}QR Code Verification{/ts}</h4>
              <p>{ts}Scan the QR code on the membership card with your device camera.{/ts}</p>
              <button type="button" class="btn btn-primary" onclick="startQRScanner()">
                <i class="fa fa-camera"></i> {ts}Start QR Scanner{/ts}
              </button>
            </div>
          </div>

          <div class="col-md-6">
            <div class="verification-method">
              <h4><i class="fa fa-keyboard-o"></i> {ts}Manual Verification{/ts}</h4>
              <p>{ts}Enter the membership ID manually to verify.{/ts}</p>
              <form method="get" action="{crmURL p='civicrm/membership-card/verify'}">
                <div class="form-group">
                  <label for="membership_id">{ts}Membership ID{/ts}:</label>
                  <input type="text" id="membership_id" name="id" class="form-control"
                         placeholder="{ts}Enter membership ID{/ts}" required>
                </div>
                <button type="submit" class="btn btn-success">
                  <i class="fa fa-search"></i> {ts}Verify{/ts}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="verification-info">
        <h4>{ts}About Membership Verification{/ts}</h4>
        <div class="row">
          <div class="col-md-4">
            <div class="info-item">
              <i class="fa fa-shield-alt fa-2x text-primary"></i>
              <h5>{ts}Secure{/ts}</h5>
              <p>{ts}All verifications are processed securely and logged for audit purposes.{/ts}</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="info-item">
              <i class="fa fa-clock fa-2x text-info"></i>
              <h5>{ts}Real-time{/ts}</h5>
              <p>{ts}Membership status is checked in real-time against the current database.{/ts}</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="info-item">
              <i class="fa fa-mobile fa-2x text-success"></i>
              <h5>{ts}Mobile Friendly{/ts}</h5>
              <p>{ts}Works on any device with a camera or web browser.{/ts}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  {/if}

  {* QR Scanner Modal *}
  <div class="modal fade" id="qr-scanner-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">{ts}QR Code Scanner{/ts}</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="{ts}Close{/ts}">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="qr-scanner-container">
            <video id="qr-scanner-video" style="width: 100%; height: 300px; background: #000;"></video>
            <div id="qr-scanner-status" class="text-center mt-3">
              <p>{ts}Position the QR code in front of your camera{/ts}</p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{ts}Cancel{/ts}</button>
        </div>
      </div>
    </div>
  </div>

</div>

{* CSS Styles *}
{literal}
  <style>
    .verify-header {
      text-align: center;
      margin-bottom: 30px;
      padding: 30px 0;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 8px;
      margin-bottom: 30px;
    }

    .verify-header h2 {
      color: white;
      margin-bottom: 10px;
    }

    .verification-result {
      margin: 30px 0;
    }

    .verification-success,
    .verification-failed {
      display: flex;
      align-items: center;
      padding: 30px;
      border-radius: 8px;
      margin-bottom: 30px;
    }

    .verification-icon {
      margin-right: 20px;
      flex-shrink: 0;
    }

    .verification-content h3 {
      margin: 0 0 10px 0;
      color: inherit;
    }

    .verification-content p {
      margin: 0;
      font-size: 16px;
    }

    .member-details {
      background: #f8f9fa;
      padding: 25px;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .member-info-table {
      width: 100%;
      border-collapse: collapse;
    }

    .member-info-table td {
      padding: 8px 12px;
      border-bottom: 1px solid #dee2e6;
    }

    .member-info-table td:first-child {
      width: 40%;
      font-weight: 500;
    }

    .validation-indicators {
      display: flex;
      justify-content: center;
      gap: 30px;
      margin: 20px 0;
      flex-wrap: wrap;
    }

    .indicator-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 15px;
      background: #f8f9fa;
      border-radius: 20px;
      font-size: 14px;
    }

    .verification-methods {
      margin: 30px 0;
    }

    .verification-method {
      background: #ffffff;
      padding: 25px;
      border-radius: 8px;
      border: 1px solid #dee2e6;
      height: 100%;
      text-align: center;
    }

    .verification-method h4 {
      color: #495057;
      margin-bottom: 15px;
    }

    .verification-method i {
      margin-right: 8px;
    }

    .verification-info {
      margin: 40px 0;
      padding: 30px;
      background: #f8f9fa;
      border-radius: 8px;
    }

    .info-item {
      text-align: center;
      padding: 20px;
    }

    .info-item i {
      margin-bottom: 15px;
    }

    .info-item h5 {
      margin: 10px 0;
      color: #495057;
    }

    .verification-help {
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 8px;
      padding: 20px;
      margin-top: 20px;
    }

    .verification-help h4 {
      color: #856404;
      margin-bottom: 15px;
    }

    .verification-help ul {
      margin-bottom: 15px;
    }

    .verification-help .help-text {
      color: #856404;
      font-style: italic;
      margin: 0;
    }

    .label {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 500;
    }

    .label-success {
      background-color: #d4edda;
      color: #155724;
    }

    @media (max-width: 768px) {
      .verification-success,
      .verification-failed {
        flex-direction: column;
        text-align: center;
      }

      .verification-icon {
        margin-right: 0;
        margin-bottom: 15px;
      }

      .validation-indicators {
        flex-direction: column;
        align-items: center;
      }

      .member-details .row {
        flex-direction: column;
      }
    }

    #qr-scanner-container {
      position: relative;
    }

    #qr-scanner-video {
      border-radius: 8px;
    }

    .qr-scan-line {
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 2px;
      background: #ff0000;
      animation: qr-scan 2s infinite;
    }

    @keyframes qr-scan {
      0% { transform: translateY(-50px); opacity: 0; }
      50% { opacity: 1; }
      100% { transform: translateY(50px); opacity: 0; }
    }
  </style>
{/literal}

{* JavaScript for QR Scanner *}
{literal}

<script type="text/javascript">
  let qrScanner = null;

  function startQRScanner() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      CRM.alert('{/literal}{ts escape="js"}Camera access is not supported in this browser{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
      return;
    }

    CRM.$('#qr-scanner-modal').modal('show');

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
      .then(function(stream) {
        const video = document.getElementById('qr-scanner-video');
        video.srcObject = stream;
        video.play();

        // Start QR code detection (would need a QR library like jsQR)
        startQRDetection(video);
      })
      .catch(function(err) {
        console.error('Error accessing camera:', err);
        CRM.alert('{/literal}{ts escape="js"}Unable to access camera. Please check permissions.{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
      });
  }

  function startQRDetection(video) {
    // This would use a QR detection library like jsQR
    // For demo purposes, we'll simulate QR detection

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    function scanFrame() {
      if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);

        // Here you would use jsQR or similar library to decode QR code
        // const code = jsQR(imageData.data, imageData.width, imageData.height);

        // For demo, we'll just simulate detection after 3 seconds
        // if (code) {
        //   processQRResult(code.data);
        //   return;
        // }
      }

      requestAnimationFrame(scanFrame);
    }

    scanFrame();
  }

  function processQRResult(data) {
    // Stop camera
    const video = document.getElementById('qr-scanner-video');
    if (video.srcObject) {
      video.srcObject.getTracks().forEach(track => track.stop());
    }

    CRM.$('#qr-scanner-modal').modal('hide');

    // Extract membership ID from QR data and redirect to verification
    try {
      const url = new URL(data);
      const membershipId = url.searchParams.get('id');
      if (membershipId) {
        window.location.href = CRM.url('civicrm/membership-card/verify', {id: membershipId});
      } else {
        throw new Error('Invalid QR code format');
      }
    } catch (err) {
      CRM.alert('{/literal}{ts escape="js"}Invalid QR code format{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
    }
  }

  // Clean up camera when modal is closed
  CRM.$('#qr-scanner-modal').on('hidden.bs.modal', function() {
    const video = document.getElementById('qr-scanner-video');
    if (video.srcObject) {
      video.srcObject.getTracks().forEach(track => track.stop());
      video.srcObject = null;
    }
  });

  // Auto-focus on membership ID input
  CRM.$(function($) {
    $('#membership_id').focus();
  });
</script>
{/literal}
