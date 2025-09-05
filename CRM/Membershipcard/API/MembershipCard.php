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

  // Add these new helper methods to the class:

  private static function getTemplateWithSides($templateId) {
    $sql = "
    SELECT t.*,
           COALESCE(t.front_elements, t.elements) as front_elements,
           t.back_elements,
           COALESCE(t.front_background_color, t.background_color, '#ffffff') as front_background_color,
           COALESCE(t.back_background_color, '#ffffff') as back_background_color,
           COALESCE(t.front_background_image, t.background_image) as front_background_image,
           t.back_background_image
    FROM civicrm_membership_card_template t
    WHERE t.id = %1 AND t.is_active = 1
  ";

    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$templateId, 'Integer']]);

    if ($dao->fetch()) {
      return (array)$dao;
    }

    return null;
  }

  private static function determineSidesToGenerate($template, $params) {
    $sides = [];

    // Check if specific sides are requested
    if (!empty($params['sides'])) {
      $requestedSides = is_array($params['sides']) ? $params['sides'] : explode(',', $params['sides']);
      foreach ($requestedSides as $side) {
        $side = trim($side);
        if (in_array($side, ['front', 'back'])) {
          $sides[] = $side;
        }
      }
    } else {
      // Default behavior
      $sides[] = 'front'; // Always generate front

      // Generate back if template supports it and has back elements
      if (!empty($template['is_dual_sided']) && !empty($template['back_elements'])) {
        $sides[] = 'back';
      }
    }

    return array_unique($sides);
  }

  private static function processSideTemplate($side, $template, $contact, $membership, $membershipType) {
    // Prepare token data
    $tokenData = self::getTokenData($contact, $membership, $membershipType);

    // Get side-specific template data
    $elementsKey = $side . '_elements';
    $bgColorKey = $side . '_background_color';
    $bgImageKey = $side . '_background_image';

    $elements = json_decode($template[$elementsKey] ?? '{}', TRUE);
    $backgroundColor = $template[$bgColorKey] ?? '#ffffff';
    $backgroundImage = $template[$bgImageKey] ?? '';

    // Process elements with token replacement
    if (!empty($elements['objects'])) {
      foreach ($elements['objects'] as &$obj) {
        if ($obj['type'] === 'text' && !empty($obj['text'])) {
          $obj['text'] = str_replace(array_keys($tokenData), array_values($tokenData), $obj['text']);
        }

        // Add side identifier to each object
        $obj['cardSide'] = $side;
      }
    }

    return [
      'side' => $side,
      'template' => $template,
      'elements' => $elements,
      'background_color' => $backgroundColor,
      'background_image' => $backgroundImage,
      'tokenData' => $tokenData,
      'membership' => $membership,
      'contact' => $contact,
    ];
  }

  private static function getTokenData($contact, $membership, $membershipType) {
    return [
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
      '{organization.organization_name}' => CRM_Core_BAO_Domain::getDomain()->name,
      '{system.current_date}' => date('Y-m-d'),
      '{system.qr_code}' => "MEMBER:{$membership['id']}",
      '{system.barcode}' => str_pad($membership['id'], 12, '0', STR_PAD_LEFT),
    ];
  }

  private static function generateSideQRCodes($membership, $sides) {
    $qrCodes = [];

    foreach ($sides as $side) {
      $verifyUrl = CRM_Utils_System::url('civicrm/membership-card/verify',
        "id={$membership['id']}&side={$side}", TRUE, NULL, TRUE, TRUE);

      $qrCodes[$side] = [
        'data' => $verifyUrl,
        'verification_url' => $verifyUrl,
        'membership_id' => $membership['id'],
        'contact_id' => $membership['contact_id'],
        'side' => $side,
        'generated_date' => date('Y-m-d H:i:s'),
      ];
    }

    return $qrCodes;
  }

  private static function generateSideBarcodes($membership, $sides) {
    $barcodes = [];

    foreach ($sides as $side) {
      $barcodes[$side] = [
        'data' => str_pad($membership['id'], 12, '0', STR_PAD_LEFT),
        'type' => 'CODE128',
        'membership_id' => $membership['id'],
        'side' => $side,
        'generated_date' => date('Y-m-d H:i:s'),
      ];
    }

    return $barcodes;
  }

  private static function saveCardRecord($params, $cardData, $qrCodes, $barcodes) {
    $card = new CRM_Membershipcard_DAO_Card();

    // Check if card already exists
    $existingCard = CRM_Core_DAO::executeQuery("
    SELECT id FROM civicrm_membership_card
    WHERE membership_id = %1 AND template_id = %2
  ", [
      1 => [$params['membership_id'], 'Integer'],
      2 => [$params['template_id'], 'Integer'],
    ]);

    if ($existingCard->fetch()) {
      $card->id = $existingCard->id;
      $card->find(TRUE);
    }

    $card->membership_id = $params['membership_id'];
    $card->template_id = $params['template_id'];

    // Store side-specific data if columns exist
    if (isset($cardData['front_data'])) {
      if (property_exists($card, 'front_card_data')) {
        $card->front_card_data = json_encode($cardData['front_data']);
      }
      if (property_exists($card, 'front_qr_code')) {
        $card->front_qr_code = json_encode($qrCodes['front'] ?? null);
      }
      if (property_exists($card, 'front_barcode')) {
        $card->front_barcode = json_encode($barcodes['front'] ?? null);
      }
    }

    if (isset($cardData['back_data'])) {
      if (property_exists($card, 'back_card_data')) {
        $card->back_card_data = json_encode($cardData['back_data']);
      }
      if (property_exists($card, 'back_qr_code')) {
        $card->back_qr_code = json_encode($qrCodes['back'] ?? null);
      }
      if (property_exists($card, 'back_barcode')) {
        $card->back_barcode = json_encode($barcodes['back'] ?? null);
      }
    }

    // Legacy fields for backward compatibility
    $card->card_data = json_encode($cardData['front_data'] ?? $cardData['back_data'] ?? []);
    $card->qr_code = json_encode($qrCodes['front'] ?? $qrCodes['back'] ?? []);
    $card->barcode = json_encode($barcodes['front'] ?? $barcodes['back'] ?? []);

    // Set sides generated if column exists
    if (property_exists($card, 'sides_generated')) {
      $sidesGenerated = implode(',', $cardData['sides_generated']);
      $card->sides_generated = $sidesGenerated;
    }

    if (empty($card->id)) {
      $card->created_date = date('Y-m-d H:i:s');
    }
    $card->modified_date = date('Y-m-d H:i:s');

    $card->save();
    return $card;
  }

  private static function downloadBothSides($params) {
    $frontResult = self::download(['card_id' => $params['card_id'], 'side' => 'front']);
    $backResult = self::download(['card_id' => $params['card_id'], 'side' => 'back']);

    $format = CRM_Utils_Array::value('format', $params, 'separate');

    switch ($format) {
      case 'combined':
        $combinedImage = self::combineCardSides($frontResult['image_data'], $backResult['image_data']);
        return [
          'image_data' => $combinedImage,
          'filename' => "membership-card-both-sides.png",
          'mime_type' => 'image/png',
          'sides' => 'both'
        ];

      case 'pdf':
        $pdfData = self::generateDualSidePDF($frontResult, $backResult);
        return [
          'pdf_data' => $pdfData,
          'filename' => "membership-card-both-sides.pdf",
          'mime_type' => 'application/pdf',
          'sides' => 'both'
        ];

      default: // separate
        return [
          'front_image' => $frontResult['image_data'],
          'back_image' => $backResult['image_data'],
          'filename' => "membership-card-both-sides.zip",
          'mime_type' => 'application/zip',
          'sides' => 'both'
        ];
    }
  }

  private static function combineCardSides($frontImageData, $backImageData) {
    // Placeholder for image combination logic
    // In a real implementation, you would use GD or ImageMagick
    return $frontImageData; // Return front as placeholder
  }

  private static function generateDualSidePDF($frontResult, $backResult) {
    // Placeholder for PDF generation logic
    // In a real implementation, you would use TCPDF or similar
    return base64_encode("PDF content would go here");
  }

  /**
   * Enhanced getByContact method to include dual-sided information
   */
  public static function getByContact2($params) {
    if (empty($params['contact_id'])) {
      throw new API_Exception("Missing required field: contact_id");
    }

    $sql = "
    SELECT mc.*, mct.name as template_name, mct.is_dual_sided,
           m.membership_type_id, m.status_id, m.start_date, m.end_date,
           mt.name as membership_type_name
    FROM civicrm_membership_card mc
    INNER JOIN civicrm_membership_card_template mct ON mc.template_id = mct.id
    INNER JOIN civicrm_membership m ON mc.membership_id = m.id
    INNER JOIN civicrm_membership_type mt ON m.membership_type_id = mt.id
    WHERE m.contact_id = %1
    ORDER BY mc.created_date DESC
  ";

    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$params['contact_id'], 'Integer']]);

    $results = [];
    while ($dao->fetch()) {
      // Determine which sides are available
      $sidesGenerated = [];
      if (property_exists($dao, 'sides_generated') && !empty($dao->sides_generated)) {
        $sidesGenerated = explode(',', $dao->sides_generated);
      } else {
        // Fallback logic
        $sidesGenerated = ['front'];
        if (!empty($dao->is_dual_sided)) {
          $sidesGenerated[] = 'back';
        }
      }

      $cardInfo = [
        'card_id' => $dao->id,
        'membership_id' => $dao->membership_id,
        'template_id' => $dao->template_id,
        'template_name' => $dao->template_name,
        'is_dual_sided' => (bool)$dao->is_dual_sided,
        'sides_generated' => $sidesGenerated,
        'membership_type_id' => $dao->membership_type_id,
        'membership_type_name' => $dao->membership_type_name,
        'status_id' => $dao->status_id,
        'start_date' => $dao->start_date,
        'end_date' => $dao->end_date,
        'created_date' => $dao->created_date,
        'download_urls' => []
      ];

      // Generate download URLs for each side
      foreach ($sidesGenerated as $side) {
        $cardInfo['download_urls'][$side] = CRM_Utils_System::url(
          'civicrm/membership-card/download',
          "card_id={$dao->id}&side={$side}",
          TRUE
        );
      }

      // Add combined download if dual-sided
      if ($dao->is_dual_sided && count($sidesGenerated) > 1) {
        $cardInfo['download_urls']['both'] = CRM_Utils_System::url(
          'civicrm/membership-card/download',
          "card_id={$dao->id}&sides=both",
          TRUE
        );
      }

      $results[] = $cardInfo;
    }

    return ['values' => $results];
  }
}
