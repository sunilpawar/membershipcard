<?php

/**
 * CRM/Membershipcard/API/MembershipCard.php
 * API for generating and managing membership cards
 */
class CRM_Membershipcard_API_MembershipCard {

  /**
   * Generate a membership card
   */
  public static function generate($params) {
    $required = ['membership_id', 'template_id'];
    foreach ($required as $field) {
      if (empty($params[$field])) {
        throw new API_Exception("Missing required field: $field");
      }
    }

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
    $template = CRM_Membershipcard_API_MembershipCardTemplate::get([
      'id' => $params['template_id']
    ]);

    if (empty($template['values'])) {
      throw new API_Exception("Template not found");
    }

    $template = $template['values'][0];

    // Generate card data
    $cardData = self::processTemplate($template, $contact, $membership, $membershipType);

    // Save card record
    $card = new CRM_Membershipcard_DAO_Card();
    $card->membership_id = $params['membership_id'];
    $card->template_id = $params['template_id'];
    $card->card_data = json_encode($cardData);
    $card->qr_code = self::generateQRCode($membership);
    $card->barcode = self::generateBarcode($membership);
    $card->created_date = date('Y-m-d H:i:s');
    $card->save();

    return [
      'card_id' => $card->id,
      'card_data' => $cardData,
      'qr_code' => $card->qr_code,
      'barcode' => $card->barcode,
      'download_url' => CRM_Utils_System::url('civicrm/membership-card/download', "card_id={$card->id}", TRUE),
    ];
  }

  /**
   * Process template with actual data
   */
  private static function processTemplate($template, $contact, $membership, $membershipType) {
    // Prepare token data
    $tokenData = [
      '{contact.display_name}' => $contact['display_name'],
      '{contact.first_name}' => $contact['first_name'],
      '{contact.last_name}' => $contact['last_name'],
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
      '{organization.organization_name}' => CRM_Core_BAO_Domain::getDomain()->name,
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
   * Generate QR code data
   */
  private static function generateQRCode($membership) {
    // Generate QR code with membership verification URL
    $verifyUrl = CRM_Utils_System::url('civicrm/membership-card/verify',
      "id={$membership['id']}", TRUE, NULL, TRUE, TRUE);

    return [
      'data' => $verifyUrl,
      'membership_id' => $membership['id'],
      'contact_id' => $membership['contact_id'],
    ];
  }

  /**
   * Generate barcode data
   */
  private static function generateBarcode($membership) {
    return [
      'data' => str_pad($membership['id'], 12, '0', STR_PAD_LEFT),
      'type' => 'CODE128',
      'membership_id' => $membership['id'],
    ];
  }

  /**
   * Get membership cards for a contact
   */
  public static function getByContact($params) {
    if (empty($params['contact_id'])) {
      throw new API_Exception("Missing required field: contact_id");
    }

    $sql = "
      SELECT mc.*, mct.name as template_name, m.membership_type_id, m.status_id
      FROM civicrm_membership_card mc
      INNER JOIN civicrm_membership_card_template mct ON mc.template_id = mct.id
      INNER JOIN civicrm_membership m ON mc.membership_id = m.id
      WHERE m.contact_id = %1
      ORDER BY mc.created_date DESC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$params['contact_id'], 'Integer']
    ]);

    $results = [];
    while ($dao->fetch()) {
      $results[] = [
        'card_id' => $dao->id,
        'membership_id' => $dao->membership_id,
        'template_id' => $dao->template_id,
        'template_name' => $dao->template_name,
        'membership_type_id' => $dao->membership_type_id,
        'status_id' => $dao->status_id,
        'created_date' => $dao->created_date,
        'download_url' => CRM_Utils_System::url('civicrm/membership-card/download', "card_id={$dao->id}", TRUE),
      ];
    }

    return ['values' => $results];
  }

  /**
   * Download membership card
   */
  public static function download($params) {
    if (empty($params['card_id'])) {
      throw new API_Exception("Missing required field: card_id");
    }

    $card = new CRM_Membershipcard_DAO_Card();
    $card->id = $params['card_id'];

    if (!$card->find(TRUE)) {
      throw new API_Exception("Card not found");
    }

    $cardData = json_decode($card->card_data, TRUE);

    // Generate card image using template processor
    $imageData = self::generateCardImage($cardData);

    return [
      'image_data' => $imageData,
      'filename' => "membership-card-{$card->membership_id}.png",
      'mime_type' => 'image/png',
    ];
  }

  /**
   * Generate card image from template data
   */
  private static function generateCardImage($cardData) {
    // This would use a server-side image generation library
    // For now, return base64 placeholder
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
  }

  /**
   * Verify membership card
   */
  public static function verify($params) {
    if (empty($params['id'])) {
      throw new API_Exception("Missing membership ID");
    }

    try {
      $membership = civicrm_api3('Membership', 'getsingle', [
        'id' => $params['id'],
      ]);

      $contact = civicrm_api3('Contact', 'getsingle', [
        'id' => $membership['contact_id'],
      ]);

      $membershipType = civicrm_api3('MembershipType', 'getsingle', [
        'id' => $membership['membership_type_id'],
      ]);

      return [
        'is_valid' => TRUE,
        'membership_id' => $membership['id'],
        'contact_name' => $contact['display_name'],
        'membership_type' => $membershipType['name'],
        'status' => CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'status_id', $membership['status_id']),
        'start_date' => $membership['start_date'],
        'end_date' => $membership['end_date'],
        'verified_date' => date('Y-m-d H:i:s'),
      ];

    }
    catch (Exception $e) {
      return [
        'is_valid' => FALSE,
        'error' => 'Membership not found or invalid',
      ];
    }
  }
}
