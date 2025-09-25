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
}
