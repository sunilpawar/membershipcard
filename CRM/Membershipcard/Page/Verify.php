<?php
use CRM_Membershipcard_ExtensionUtil as E;

/**
 * CRM/Membershipcard/Page/Verify.php
 * Page for verifying membership cards
 */
class CRM_Membershipcard_Page_Verify extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(ts('Verify Membership Card'));

    $membershipId = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if ($membershipId) {
      $result = CRM_Membershipcard_API_MembershipCard::verify(['id' => $membershipId]);
      $this->assign('verification_result', $result);
    }

    parent::run();
  }
}
