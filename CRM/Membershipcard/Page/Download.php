<?php
use CRM_Membershipcard_ExtensionUtil as E;

/**
 * CRM/Membershipcard/Page/Download.php
 * Page for downloading membership cards
 */
class CRM_Membershipcard_Page_Download extends CRM_Core_Page {

  public function run() {
    $cardId = CRM_Utils_Request::retrieve('card_id', 'Positive', $this);
    if (!$cardId) {
      CRM_Core_Error::fatal('Card ID is required');
    }

    try {
      $result = CRM_Membershipcard_API_MembershipCard::downloadBothSides(['card_id' => $cardId, 'format' => 'combined']);
      // Set headers for file download
      header('Content-Type: ' . $result['mime_type']);
      header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');

      // Extract base64 data and output
      if (strpos($result['image_data'], 'data:') === 0) {
        $imageData = explode(',', $result['image_data'], 2)[1];
        echo base64_decode($imageData);
      }
      else {
        echo $result['image_data'];
      }

      CRM_Utils_System::civiExit();

    }
    catch (Exception $e) {
      CRM_Core_Error::fatal('Error downloading card: ' . $e->getMessage());
    }
  }

  public static function quickGenerateDownload() {
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive');
    $membershipID = CRM_Utils_Request::retrieve('mid', 'Positive');
    $contactID = $contactID ?? NULL;
    $membershipID = $membershipID ?? NULL;
    $template_id = 1;
    $result = CRM_Membershipcard_API_MembershipCard::quickGenerate($template_id, 'pdf', $contactID, $membershipID);

    // Set headers for file download
    header('Content-Type: ' . $result['mime_type']);
    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');

    // Extract base64 data and output
    if ($result['mime_type'] == 'application/pdf') {
      // For PDF, decode base64 data
      echo base64_decode($result['pdf_data']);
    }
    elseif (strpos($result['image_data'], 'data:') === 0) {
      $imageData = explode(',', $result['image_data'], 2)[1];
      echo base64_decode($imageData);
    }
    else {
      echo $result['image_data'];
    }

    CRM_Utils_System::civiExit();
  }

  public static function attachPdf(&$params, $template_id, $contactID, $membershipID = NULL) {
    $result = CRM_Membershipcard_API_MembershipCard::quickGenerate($template_id, 'pdf', $contactID, $membershipID);
    if ($result['mime_type'] == 'application/pdf') {
      // For PDF, decode base64 data
      $file = base64_decode($result['pdf_data']);
    }
    elseif (strpos($result['image_data'], 'data:') === 0) {
      $imageData = explode(',', $result['image_data'], 2)[1];
      $file = base64_decode($imageData);
    }
    else {
      $file = $result['image_data'];
    }
    $mimeType = $result['mime_type'];


    // attach to email
    $base = "membership_card_{$contactID}.pdf";
    $full = tempnam(sys_get_temp_dir(), $base);
    file_put_contents($full, $file);
    $params['attachments'][] = [
      'fullPath' => $full,
      'mime_type' => $mimeType,
      'cleanName' => $base,
    ];
    $params['card_processed_' . $contactID] = TRUE;
  }
}
