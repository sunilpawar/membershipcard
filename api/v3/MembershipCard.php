<?php
/**
 * api/v3/MembershipCard.php
 * API for Membership Card operations
 */

/**
 * MembershipCard.Generate API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_generate($params) {
  $required = ['membership_id', 'template_id'];
  foreach ($required as $field) {
    if (empty($params[$field])) {
      throw new API_Exception("Missing required field: $field");
    }
  }

  try {
    // Get membership data
    $membership = civicrm_api3('Membership', 'getsingle', [
      'id' => $params['membership_id'],
    ]);

    // Get contact data
    $contact = civicrm_api3('Contact', 'getsingle', [
      'id' => $membership['contact_id'],
    ]);

    // Get membership type
    $membershipType = civicrm_api3('MembershipType', 'getsingle', [
      'id' => $membership['membership_type_id'],
    ]);

    // Get template
    $template = civicrm_api3('MembershipCardTemplate', 'getsingle', [
      'id' => $params['template_id'],
    ]);

    // Generate card data
    // $cardData = _membership_card_process_template($template, $contact,$membership, $membershipType);
    $cardDataFront = CRM_Membershipcard_API_MembershipCard::processTemplate($template, $contact, $membership, $membershipType, TRUE);
    $cardDataBack = CRM_Membershipcard_API_MembershipCard::processTemplate($template, $contact, $membership, $membershipType, FALSE);


    // Generate QR code and barcode data
    $qrData = CRM_Membershipcard_API_MembershipCard::generateQRCode($membership);
    $barcodeData = CRM_Membershipcard_API_MembershipCard::generateBarcode($membership);
    // Save card record
    $cardParams = [
      'membership_id' => $params['membership_id'],
      'template_id' => $params['template_id'],
      'front_card_data' => json_encode($cardDataFront),
      'back_card_data' => json_encode($cardDataBack),
      'qr_code' => json_encode($qrData),
      'barcode' => json_encode($barcodeData),
      'created_date' => date('Y-m-d H:i:s'),
    ];

    // Check if card already exists
    $existingCard = CRM_Core_DAO::executeQuery("
      SELECT id FROM civicrm_membership_card
      WHERE membership_id = %1 AND template_id = %2
    ", [
      1 => [$params['membership_id'], 'Integer'],
      2 => [$params['template_id'], 'Integer'],
    ]);

    if ($existingCard->fetch()) {
      $cardParams['id'] = $existingCard->id;
      $cardParams['modified_date'] = date('Y-m-d H:i:s');
    }

    $cardResult = civicrm_api3('MembershipCard', 'create', $cardParams);
    $cardId = $cardResult['id'];

    return civicrm_api3_create_success([
      'card_id' => $cardId,
      'front_card_data' => $cardDataFront,
      'back_card_data' => $cardDataBack,
      'qr_code' => $qrData,
      'barcode' => $barcodeData,
      'download_url' => CRM_Utils_System::url('civicrm/membership-card/download', "card_id={$cardId}", TRUE),
      'verification_url' => $qrData['verification_url'],
    ]);
  }
  catch (Exception $e) {
    throw new API_Exception('Error generating membership card: ' . $e->getMessage());
  }
}

/**
 * MembershipCard.Generate API specification
 */
function _civicrm_api3_membership_card_generate_spec(&$spec) {
  $spec['membership_id']['api.required'] = 1;
  $spec['membership_id']['title'] = 'Membership ID';
  $spec['membership_id']['description'] = 'The membership to generate a card for';
  $spec['membership_id']['type'] = CRM_Utils_Type::T_INT;

  $spec['template_id']['api.required'] = 1;
  $spec['template_id']['title'] = 'Template ID';
  $spec['template_id']['description'] = 'The card template to use';
  $spec['template_id']['type'] = CRM_Utils_Type::T_INT;

  $spec['force_regenerate']['title'] = 'Force Regenerate';
  $spec['force_regenerate']['description'] = 'Force regeneration even if card exists';
  $spec['force_regenerate']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['force_regenerate']['api.default'] = FALSE;
}

/**
 * MembershipCard.Getbycontact API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_getbycontact($params) {
  if (empty($params['contact_id'])) {
    throw new API_Exception("Missing required field: contact_id");
  }

  try {
    $sql = "
      SELECT mc.*, mct.name as template_name,
             m.membership_type_id, m.status_id, m.start_date, m.end_date,
             mt.name as membership_type_name
      FROM civicrm_membership_card mc
      INNER JOIN civicrm_membership_card_template mct ON mc.template_id = mct.id
      INNER JOIN civicrm_membership m ON mc.membership_id = m.id
      INNER JOIN civicrm_membership_type mt ON m.membership_type_id = mt.id
      WHERE m.contact_id = %1
      ORDER BY mc.created_date DESC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$params['contact_id'], 'Integer']
    ]);

    $results = [];
    while ($dao->fetch()) {
      $results[] = [
        'id' => $dao->id,
        'card_id' => $dao->id,
        'membership_id' => $dao->membership_id,
        'template_id' => $dao->template_id,
        'template_name' => $dao->template_name,
        'membership_type_id' => $dao->membership_type_id,
        'membership_type_name' => $dao->membership_type_name,
        'status_id' => $dao->status_id,
        'start_date' => $dao->start_date,
        'end_date' => $dao->end_date,
        'created_date' => $dao->created_date,
        'modified_date' => $dao->modified_date,
        'download_url' => CRM_Utils_System::url('civicrm/membership-card/download', "card_id={$dao->id}", TRUE),
        'verification_url' => CRM_Utils_System::url('civicrm/membership-card/verify', "id={$dao->membership_id}", TRUE),
      ];
    }

    return civicrm_api3_create_success($results, $params, 'MembershipCard', 'getbycontact');

  }
  catch (Exception $e) {
    throw new API_Exception('Error retrieving membership cards: ' . $e->getMessage());
  }
}

/**
 * MembershipCard.Getbycontact API specification
 */
function _civicrm_api3_membership_card_getbycontact_spec(&$spec) {
  $spec['contact_id']['api.required'] = 1;
  $spec['contact_id']['title'] = 'Contact ID';
  $spec['contact_id']['description'] = 'The contact to get cards for';
  $spec['contact_id']['type'] = CRM_Utils_Type::T_INT;

  $spec['template_id']['title'] = 'Template ID';
  $spec['template_id']['description'] = 'Filter by specific template';
  $spec['template_id']['type'] = CRM_Utils_Type::T_INT;

  $spec['membership_type_id']['title'] = 'Membership Type ID';
  $spec['membership_type_id']['description'] = 'Filter by membership type';
  $spec['membership_type_id']['type'] = CRM_Utils_Type::T_INT;
}

/**
 * MembershipCard.Verify API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_verify($params) {
  if (empty($params['id'])) {
    throw new API_Exception("Missing membership ID");
  }

  try {
    // Get membership data
    $membership = civicrm_api3('Membership', 'getsingle', [
      'id' => $params['id'],
    ]);

    // Get contact data
    $contact = civicrm_api3('Contact', 'getsingle', [
      'id' => $membership['contact_id'],
    ]);

    // Get membership type
    $membershipType = civicrm_api3('MembershipType', 'getsingle', [
      'id' => $membership['membership_type_id'],
    ]);

    // Get membership status
    $membershipStatus = civicrm_api3('MembershipStatus', 'getsingle', [
      'id' => $membership['status_id'],
    ]);

    // Check if membership is current
    $isValid = TRUE;
    $validationMessages = [];

    // Check expiration
    if (!empty($membership['end_date']) && strtotime($membership['end_date']) < time()) {
      $isValid = FALSE;
      $validationMessages[] = 'Membership has expired';
    }

    // Check status
    if (!in_array($membershipStatus['name'], ['Current', 'New', 'Grace'])) {
      $isValid = FALSE;
      $validationMessages[] = 'Membership is not current';
    }

    // Log verification attempt
    _membership_card_log_verification($params['id'], $isValid, $params);

    $result = [
      'is_valid' => $isValid,
      'membership_id' => $membership['id'],
      'contact_name' => $contact['display_name'],
      'contact_id' => $contact['id'],
      'membership_type' => $membershipType['name'],
      'status' => $membershipStatus['label'],
      'start_date' => $membership['start_date'],
      'end_date' => $membership['end_date'],
      'join_date' => $membership['join_date'],
      'verified_date' => date('Y-m-d H:i:s'),
      'verification_messages' => $validationMessages,
    ];

    if (!$isValid) {
      $result['reason'] = implode(', ', $validationMessages);
    }

    return civicrm_api3_create_success([$result], $params, 'MembershipCard', 'verify');

  }
  catch (Exception $e) {
    // Log failed verification
    _membership_card_log_verification($params['id'], FALSE, $params, $e->getMessage());

    return civicrm_api3_create_success([[
      'is_valid' => FALSE,
      'error' => 'Membership not found or verification failed',
      'verified_date' => date('Y-m-d H:i:s'),
    ]], $params, 'MembershipCard', 'verify');
  }
}

/**
 * MembershipCard.Verify API specification
 */
function _civicrm_api3_membership_card_verify_spec(&$spec) {
  $spec['id']['api.required'] = 1;
  $spec['id']['title'] = 'Membership ID';
  $spec['id']['description'] = 'The membership ID to verify';
  $spec['id']['type'] = CRM_Utils_Type::T_INT;

  $spec['verification_source']['title'] = 'Verification Source';
  $spec['verification_source']['description'] = 'Source of verification (qr, manual, etc.)';
  $spec['verification_source']['type'] = CRM_Utils_Type::T_STRING;
  $spec['verification_source']['api.default'] = 'manual';

  $spec['verifier_contact_id']['title'] = 'Verifier Contact ID';
  $spec['verifier_contact_id']['description'] = 'ID of person performing verification';
  $spec['verifier_contact_id']['type'] = CRM_Utils_Type::T_INT;
}

/**
 * MembershipCard.Download API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_download($params) {
  if (empty($params['card_id'])) {
    throw new API_Exception("Missing required field: card_id");
  }

  try {
    $sql = "
      SELECT mc.*, m.contact_id, m.membership_type_id
      FROM civicrm_membership_card mc
      INNER JOIN civicrm_membership m ON mc.membership_id = m.id
      WHERE mc.id = %1
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$params['card_id'], 'Integer']
    ]);

    if (!$dao->fetch()) {
      throw new API_Exception("Card not found");
    }

    $cardData = json_decode($dao->card_data, TRUE);

    // Generate card image
    $imageData = _membership_card_generate_image($cardData, $params);

    $result = [
      'card_id' => $dao->id,
      'membership_id' => $dao->membership_id,
      'image_data' => $imageData,
      'filename' => "membership-card-{$dao->membership_id}.png",
      'mime_type' => 'image/png',
      'card_data' => $cardData,
    ];

    return civicrm_api3_create_success([$result], $params, 'MembershipCard', 'download');

  }
  catch (Exception $e) {
    throw new API_Exception('Error downloading card: ' . $e->getMessage());
  }
}

/**
 * MembershipCard.Download API specification
 */
function _civicrm_api3_membership_card_download_spec(&$spec) {
  $spec['card_id']['api.required'] = 1;
  $spec['card_id']['title'] = 'Card ID';
  $spec['card_id']['description'] = 'The card to download';
  $spec['card_id']['type'] = CRM_Utils_Type::T_INT;

  $spec['format']['title'] = 'Format';
  $spec['format']['description'] = 'Output format (png, pdf)';
  $spec['format']['type'] = CRM_Utils_Type::T_STRING;
  $spec['format']['api.default'] = 'png';

  $spec['quality']['title'] = 'Quality';
  $spec['quality']['description'] = 'Image quality (1-100)';
  $spec['quality']['type'] = CRM_Utils_Type::T_INT;
  $spec['quality']['api.default'] = 90;
}

// Helper functions

/**
 * Process template with membership data
 */
function _membership_card_process_template($template, $contact, $membership, $membershipType) {
  // Get organization info
  $domain = CRM_Core_BAO_Domain::getDomain();
  $orgContact = civicrm_api3('Contact', 'getsingle', [
    'id' => $domain->contact_id,
  ]);

  // Prepare token data
  $tokenData = [
    '{contact.display_name}' => $contact['display_name'],
    '{contact.first_name}' => CRM_Utils_Array::value('first_name', $contact),
    '{contact.last_name}' => CRM_Utils_Array::value('last_name', $contact),
    '{contact.email}' => CRM_Utils_Array::value('email', $contact),
    '{contact.phone}' => CRM_Utils_Array::value('phone', $contact),
    '{contact.street_address}' => CRM_Utils_Array::value('street_address', $contact),
    '{contact.city}' => CRM_Utils_Array::value('city', $contact),
    '{contact.state_province}' => CRM_Utils_Array::value('state_province_name', $contact),
    '{contact.postal_code}' => CRM_Utils_Array::value('postal_code', $contact),
    '{contact.image_URL}' => CRM_Utils_Array::value('image_URL', $contact),
    '{membership.membership_type}' => $membershipType['name'],
    '{membership.status}' => CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'status_id', $membership['status_id']),
    '{membership.start_date}' => CRM_Utils_Date::customFormat($membership['start_date']),
    '{membership.end_date}' => CRM_Utils_Date::customFormat($membership['end_date']),
    '{membership.join_date}' => CRM_Utils_Date::customFormat($membership['join_date']),
    '{membership.membership_id}' => $membership['id'],
    '{membership.source}' => CRM_Utils_Array::value('source', $membership),
    '{organization.organization_name}' => $domain->name,
    '{organization.organization_address}' => CRM_Utils_Array::value('street_address', $orgContact),
    '{organization.organization_phone}' => CRM_Utils_Array::value('phone', $orgContact),
    '{organization.organization_email}' => CRM_Utils_Array::value('email', $orgContact),
    '{system.current_date}' => date('Y-m-d'),
    '{system.qr_code}' => "MEMBER:{$membership['id']}",
    '{system.barcode}' => str_pad($membership['id'], 12, '0', STR_PAD_LEFT),
  ];

  // Process template elements
  $elements = json_decode($template['elements'], TRUE);

  if (!empty($elements['objects'])) {
    foreach ($elements['objects'] as &$obj) {
      if ($obj['type'] === 'text' && !empty($obj['text'])) {
        // Replace tokens in text
        $obj['text'] = str_replace(array_keys($tokenData), array_values($tokenData), $obj['text']);
      }
    }
  }

  return [
    'template' => $template,
    'elements' => $elements,
    'tokenData' => $tokenData,
    'membership' => $membership,
    'contact' => $contact,
  ];
}

/**
 * Generate card image
 */
function _membership_card_generate_image($cardData, $params = []) {
  // This would use a server-side image generation library
  // For now, return a base64 placeholder
  // In production, you'd use libraries like:
  // - ImageMagick
  // - GD
  // - wkhtmltopdf for PDF generation
  // - Canvas rendering with headless Chrome

  $format = CRM_Utils_Array::value('format', $params, 'png');

  // Placeholder image data
  $placeholder = 'iVBORw0KGgoAAAANSUhEUgAAAV4AAADcCAYAAAAhkMnDAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAGPSURBVHhe7cExAQAAAMKg9U9tCj8gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAeAH+AABsGh8vAAAAAElFTkSuQmCC';

  return "data:image/{$format};base64,{$placeholder}";
}

/**
 * Log verification attempt
 */
function _membership_card_log_verification($membershipId, $isValid, $params, $error = NULL) {
  // Log to CiviCRM activity or custom log table
  try {
    civicrm_api3('Activity', 'create', [
      'activity_type_id' => 'Membership Card Verification',
      'subject' => 'Membership Card Verification: ' . ($isValid ? 'Valid' : 'Invalid'),
      'details' => json_encode([
        'membership_id' => $membershipId,
        'is_valid' => $isValid,
        'verification_source' => CRM_Utils_Array::value('verification_source', $params, 'unknown'),
        'verifier_contact_id' => CRM_Utils_Array::value('verifier_contact_id', $params),
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => CRM_Utils_System::ipAddress(),
        'user_agent' => CRM_Utils_Array::value('HTTP_USER_AGENT', $_SERVER),
      ]),
      'activity_date_time' => date('Y-m-d H:i:s'),
      'status_id' => 'Completed',
    ]);
  }
  catch (Exception $e) {
    // Log error but don't fail the API call
    CRM_Core_Error::debug_log_message('Failed to log verification: ' . $e->getMessage());
  }
}
