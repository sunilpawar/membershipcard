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
    $cardDataFront = self::processTemplate($template, $contact, $membership, $membershipType, TRUE);
    $cardDataBack = self::processTemplate($template, $contact, $membership, $membershipType, FALSE);
    $qrData = self::generateQRCode($membership);
    $barcodeData = self::generateBarcode($membership);

    // Save card record
    $card = new CRM_Membershipcard_DAO_MembershipCard();
    $card->membership_id = $params['membership_id'];
    $card->template_id = $params['template_id'];
    $card->front_card_data = json_encode($cardDataFront);
    $card->back_card_data = json_encode($cardDataBack);
    $card->qr_code = json_encode($qrData);
    $card->barcode = json_encode($barcodeData);
    $card->created_date = date('Y-m-d H:i:s');
    $card->save();

    return [
      'card_id' => $card->id,
      'front_card_data' => $cardDataFront,
      'back_card_data' => $cardDataBack,
      'qr_code' => $card->qr_code,
      'barcode' => $card->barcode,
      'download_url' => CRM_Utils_System::url('civicrm/membership-card/download', "card_id={$card->id}", TRUE),
      'verification_url' => $qrData['verification_url'],
    ];
  }

  /**
   * Process template with actual data
   */
  public static function processTemplate($template, $contact, $membership, $membershipType, $isFront = TRUE) {
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
    if ($isFront) {
      $elements = json_decode($template['front_elements'], TRUE);
    }
    else {
      $elements = json_decode($template['back_elements'], TRUE);
    }
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
  public static function generateQRCode($membership) {
    // Generate QR code with membership verification URL
    $verifyUrl = CRM_Utils_System::url('civicrm/membership-card/verify',
      "id={$membership['id']}", TRUE, NULL, TRUE, TRUE);

    return [
      'data' => $verifyUrl,
      'verification_url' => $verifyUrl,
      'membership_id' => $membership['id'],
      'contact_id' => $membership['contact_id'],
      'generated_date' => date('Y-m-d H:i:s'),
    ];
  }

  /**
   * Generate barcode data
   */
  public static function generateBarcode($membership) {
    return [
      'data' => str_pad($membership['id'], 12, '0', STR_PAD_LEFT),
      'type' => 'CODE128',
      'membership_id' => $membership['id'],
      'generated_date' => date('Y-m-d H:i:s'),
    ];
  }

  /**
   * Get membership cards for a contact
   */
  public static function getByContactOld($params) {
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

    $card = new CRM_Membershipcard_DAO_MembershipCard();
    $card->id = $params['card_id'];

    if (!$card->find(TRUE)) {
      throw new API_Exception("Card not found");
    }

    if ($params['side'] === 'front') {
      $cardData = json_decode($card->front_card_data, TRUE);
    }
    else {
      $cardData = json_decode($card->back_card_data, TRUE);
    }
    // Generate card image using template processor
    $imageData = self::generateCardImage($cardData, $params['side']);

    return [
      'image_data' => $imageData,
      'filename' => "membership-card-{$card->membership_id}.png",
      'mime_type' => 'image/png',
    ];
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
           t.front_elements,
           t.back_elements,
           COALESCE(t.front_background_color, '#ffffff') as front_background_color,
           COALESCE(t.back_background_color, '#ffffff') as back_background_color,
           t.front_background_image,
           t.back_background_image
    FROM civicrm_membership_card_template t
    WHERE t.id = %1 AND t.is_active = 1
  ";

    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$templateId, 'Integer']]);

    if ($dao->fetch()) {
      return (array)$dao;
    }

    return NULL;
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
    }
    else {
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
    $card = new CRM_Membershipcard_DAO_MembershipCard();

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
        $card->front_qr_code = json_encode($qrCodes['front'] ?? NULL);
      }
      if (property_exists($card, 'front_barcode')) {
        $card->front_barcode = json_encode($barcodes['front'] ?? NULL);
      }
    }

    if (isset($cardData['back_data'])) {
      if (property_exists($card, 'back_card_data')) {
        $card->back_card_data = json_encode($cardData['back_data']);
      }
      if (property_exists($card, 'back_qr_code')) {
        $card->back_qr_code = json_encode($qrCodes['back'] ?? NULL);
      }
      if (property_exists($card, 'back_barcode')) {
        $card->back_barcode = json_encode($barcodes['back'] ?? NULL);
      }
    }

    // Legacy fields for backward compatibility
    $card->front_card_data = json_encode($cardData['front_data'] ?? []);
    $card->back_card_data = json_encode($cardData['back_data'] ?? []);
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

  public static function downloadBothSides($params) {
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

  /**
   * Combine front and back card sides into a single image
   * @param string $frontImageData Base64 encoded front image data
   * @param string $backImageData Base64 encoded back image data
   * @param array $options Optional configuration for layout
   * @return string Base64 encoded combined image data
   */
  private static function combineCardSides($frontImageData, $backImageData, $options = []) {
    // Default options
    $defaultOptions = [
      'layout' => 'horizontal', // 'horizontal', 'vertical'
      'spacing' => 20,
      'background_color' => [255, 255, 255],
      'card_width' => 640,
      'card_height' => 400,
      'quality' => 90
    ];
    $options = array_merge($defaultOptions, $options);

    try {
      // Remove data URL prefix if present
      $frontImageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $frontImageData);
      $backImageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $backImageData);

      // Decode base64 images
      $frontImageBinary = base64_decode($frontImageData);
      $backImageBinary = base64_decode($backImageData);

      if (!$frontImageBinary || !$backImageBinary) {
        throw new Exception("Invalid image data provided");
      }

      // Create image resources from binary data
      $frontImage = imagecreatefromstring($frontImageBinary);
      $backImage = imagecreatefromstring($backImageBinary);

      if (!$frontImage || !$backImage) {
        throw new Exception("Failed to create image resources");
      }

      // Get image dimensions
      $frontWidth = imagesx($frontImage);
      $frontHeight = imagesy($frontImage);
      $backWidth = imagesx($backImage);
      $backHeight = imagesy($backImage);

      // Calculate combined image dimensions
      if ($options['layout'] === 'horizontal') {
        $combinedWidth = $frontWidth + $backWidth + $options['spacing'];
        $combinedHeight = max($frontHeight, $backHeight);
      }
      else { // vertical
        $combinedWidth = max($frontWidth, $backWidth);
        $combinedHeight = $frontHeight + $backHeight + $options['spacing'];
      }

      // Create combined image canvas
      $combinedImage = imagecreatetruecolor($combinedWidth, $combinedHeight);

      // Set background color
      $bgColor = imagecolorallocate(
        $combinedImage,
        $options['background_color'][0],
        $options['background_color'][1],
        $options['background_color'][2]
      );
      imagefill($combinedImage, 0, 0, $bgColor);

      // Calculate positions for centering
      if ($options['layout'] === 'horizontal') {
        $frontX = 0;
        $frontY = ($combinedHeight - $frontHeight) / 2;
        $backX = $frontWidth + $options['spacing'];
        $backY = ($combinedHeight - $backHeight) / 2;
      }
      else { // vertical
        $frontX = ($combinedWidth - $frontWidth) / 2;
        $frontY = 0;
        $backX = ($combinedWidth - $backWidth) / 2;
        $backY = $frontHeight + $options['spacing'];
      }

      // Copy images to combined canvas
      imagecopy($combinedImage, $frontImage, $frontX, $frontY, 0, 0, $frontWidth, $frontHeight);
      imagecopy($combinedImage, $backImage, $backX, $backY, 0, 0, $backWidth, $backHeight);

      // Generate output
      ob_start();
      imagepng($combinedImage, NULL, 9);
      $combinedImageData = ob_get_contents();
      ob_end_clean();

      // Clean up memory
      imagedestroy($frontImage);
      imagedestroy($backImage);
      imagedestroy($combinedImage);

      return 'data:image/png;base64,' . base64_encode($combinedImageData);

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('combineCardSides error: ' . $e->getMessage());

      // Return front image as fallback
      return $frontImageData;
    }
  }

  /**
   * Generate a dual-sided PDF from front and back card results
   * @param array $frontResult Front card generation result
   * @param array $backResult Back card generation result
   * @param array $options PDF generation options
   * @return string Base64 encoded PDF data
   */
  private static function generateDualSidePDF($frontResult, $backResult, $options = []) {
    // Check if TCPDF is available
    if (!class_exists('TCPDF')) {
      throw new Exception("TCPDF library is required for PDF generation");
    }

    $defaultOptions = [
      'page_format' => 'A4',
      'orientation' => 'L', // L=Landscape, P=Portrait
      'margins' => [10, 10, 10, 10], // top, right, bottom, left
      'card_width' => 86, // mm (standard credit card width)
      'card_height' => 54, // mm (standard credit card height)
      'cards_per_row' => 2,
      'spacing' => 10
    ];
    $options = array_merge($defaultOptions, $options);

    try {
      // Create new PDF document
      $pdf = new TCPDF($options['orientation'], 'mm', $options['page_format'], TRUE, 'UTF-8', FALSE);

      // Set document information
      $pdf->SetCreator('CiviCRM Membership Card System');
      $pdf->SetAuthor('CiviCRM');
      $pdf->SetTitle('Membership Card - Dual Sided');
      $pdf->SetSubject('Membership Card');

      // Remove default header/footer
      $pdf->setPrintHeader(FALSE);
      $pdf->setPrintFooter(FALSE);

      // Set margins
      $pdf->SetMargins($options['margins'][3], $options['margins'][0], $options['margins'][1]);
      $pdf->SetAutoPageBreak(TRUE, $options['margins'][2]);

      // Add a page
      $pdf->AddPage();

      // Convert base64 images to temporary files
      $frontImageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $frontResult['image_data']);
      $backImageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $backResult['image_data']);

      $frontTempFile = tempnam(sys_get_temp_dir(), 'front_card_') . '.png';
      $backTempFile = tempnam(sys_get_temp_dir(), 'back_card_') . '.png';

      file_put_contents($frontTempFile, base64_decode($frontImageData));
      file_put_contents($backTempFile, base64_decode($backImageData));

      // Calculate positions
      $cardWidth = $options['card_width'];
      $cardHeight = $options['card_height'];
      $spacing = $options['spacing'];

      // Position front card
      $frontX = $options['margins'][3];
      $frontY = $options['margins'][0];

      // Position back card
      if ($options['cards_per_row'] == 1) {
        $backX = $frontX;
        $backY = $frontY + $cardHeight + $spacing;
      }
      else {
        $backX = $frontX + $cardWidth + $spacing;
        $backY = $frontY;
      }

      // Add front card image
      $pdf->Image($frontTempFile, $frontX, $frontY, $cardWidth, $cardHeight, 'PNG', '', 'T', FALSE, 300, '', FALSE, FALSE, 0, FALSE, FALSE, FALSE);

      // Add back card image
      $pdf->Image($backTempFile, $backX, $backY, $cardWidth, $cardHeight, 'PNG', '', 'T', FALSE, 300, '', FALSE, FALSE, 0, FALSE, FALSE, FALSE);

      // Add labels
      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetTextColor(0, 0, 0);

      // Front label
      $pdf->SetXY($frontX, $frontY + $cardHeight + 2);
      $pdf->Cell($cardWidth, 5, 'FRONT', 0, 0, 'C');

      // Back label
      $pdf->SetXY($backX, $backY + $cardHeight + 2);
      $pdf->Cell($cardWidth, 5, 'BACK', 0, 0, 'C');

      // Add cutting guidelines if requested
      if (!empty($options['show_cut_lines'])) {
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.1);

        // Front card guidelines
        $pdf->Rect($frontX, $frontY, $cardWidth, $cardHeight);

        // Back card guidelines
        $pdf->Rect($backX, $backY, $cardWidth, $cardHeight);
      }

      // Clean up temporary files
      unlink($frontTempFile);
      unlink($backTempFile);

      // Output PDF as string
      $pdfContent = $pdf->Output('', 'S');

      return base64_encode($pdfContent);

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('generateDualSidePDF error: ' . $e->getMessage());
      throw new Exception("Failed to generate PDF: " . $e->getMessage());
    }
  }

  /**
   * Enhanced getByContact method to include dual-sided information
   */
  public static function getByContact($params) {
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
      }
      else {
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

  /**
   * Generate card image from template data using server-side rendering
   * @param array $cardData Card data including template and elements
   * @param array $options Generation options
   * @return string Base64 encoded image data
   */
  private static function generateCardImage($cardData, $side = 'front', $options = []) {
    $defaultOptions = [
      'width' => 640,
      'height' => 400,
      'format' => 'png',
      'quality' => 90,
      'background_color' => '#ffffff',
      'dpi' => 300
    ];
    if ($side == 'front') {
      $cardData['background_color'] = $cardData['template']['front_background_color'] ?? '#ffffff';
      $cardData['background_image'] = $cardData['template']['front_background_image'] ?? '';
      $options['width'] = $cardData['template']['card_width'] ?? $defaultOptions['width'];
      $options['height'] = $cardData['template']['card_height'] ?? $defaultOptions['height'];
    }
    else {
      $cardData['background_color'] = $cardData['template']['back_background_color'] ?? '#ffffff';
      $cardData['background_image'] = $cardData['template']['back_background_image'] ?? '';
      $options['width'] = $cardData['template']['card_width'] ?? $defaultOptions['width'];
      $options['height'] = $cardData['template']['card_height'] ?? $defaultOptions['height'];
    }
    $options = array_merge($defaultOptions, $options);

    try {
      // Create image canvas
      $image = imagecreatetruecolor($options['width'], $options['height']);

      // Enable alpha blending
      imagealphablending($image, TRUE);
      imagesavealpha($image, TRUE);

      // Set background color
      $bgColor = self::hexToRgb($cardData['background_color'] ?? $options['background_color']);
      $backgroundColor = imagecolorallocate($image, $bgColor['r'], $bgColor['g'], $bgColor['b']);
      imagefill($image, 0, 0, $backgroundColor);

      // Process background image if exists
      if (!empty($cardData['background_image'])) {
        self::addBackgroundImage($image, $cardData['background_image'], $options);
      }

      // Process elements
      if (!empty($cardData['elements']['objects'])) {
        foreach ($cardData['elements']['objects'] as $element) {
          self::renderElement($image, $element, $options);
        }
      }

      // Generate QR code if needed
      if (!empty($cardData['qr_code'])) {
        self::addQRCode($image, $cardData['qr_code'], $options);
      }

      // Generate barcode if needed
      if (!empty($cardData['barcode'])) {
        self::addBarcode($image, $cardData['barcode'], $options);
      }

      // Output image
      ob_start();
      switch (strtolower($options['format'])) {
        case 'jpg':
        case 'jpeg':
          imagejpeg($image, NULL, $options['quality']);
          $format = 'jpeg';
          break;
        case 'png':
        default:
          imagepng($image, NULL, 9);
          $format = 'png';
          break;
      }
      $imageData = ob_get_contents();
      ob_end_clean();

      // Clean up
      imagedestroy($image);

      return 'data:image/' . $format . ';base64,' . base64_encode($imageData);

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('generateCardImage error: ' . $e->getMessage());

      // Return placeholder image
      return self::generatePlaceholderImage($options);
    }
  }

  /**
   * Helper function to convert hex color to RGB array
   */
  private static function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) == 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
      'r' => hexdec(substr($hex, 0, 2)),
      'g' => hexdec(substr($hex, 2, 2)),
      'b' => hexdec(substr($hex, 4, 2))
    ];
  }

  /**
   * Add background image to card
   */
  private static function addBackgroundImage($image, $backgroundImageUrl, $options) {
    try {
      // Download or load background image
      $bgImageData = file_get_contents($backgroundImageUrl);
      if ($bgImageData === FALSE) {
        return;
      }

      $bgImage = imagecreatefromstring($bgImageData);
      if (!$bgImage) {
        return;
      }

      // Resize background to fit card
      $bgResized = imagescale($bgImage, $options['width'], $options['height']);
      imagecopy($image, $bgResized, 0, 0, 0, 0, $options['width'], $options['height']);

      imagedestroy($bgImage);
      imagedestroy($bgResized);
    }
    catch (Exception $e) {
      // Silently fail for background images
    }
  }

  /**
   * Render individual element on card
   */
  private static function renderElement($image, $element, $options) {
    switch ($element['type']) {
      case 'text':
      case 'textbox':
        self::renderTextElement($image, $element, $options);
        break;
      case 'image':
        self::renderImageElement($image, $element, $options);
        break;
      case 'rect':
      case 'rectangle':
        self::renderRectElement($image, $element, $options);
        break;
      case 'circle':
        self::renderCircleElement($image, $element, $options);
        break;
      case 'group':
        if ($element['elementType'] === 'qrcode') {
          self::renderQRCodeElement($image, $element, $tokenData);
        }
        break;
    }
  }

  /**
   * Render text element with proper multi-line and dimension handling
   */
  private static function renderTextElement($image, $element, $options) {
    // Extract text properties
    $text = $element['text'] ?? '';
    $left = (int)($element['left'] ?? 0);
    $top = (int)($element['top'] ?? 0);
    $maxWidth = (int)($element['width'] ?? 200);
    $maxHeight = (int)($element['height'] ?? 50);
    $fontSize = (int)($element['fontSize'] ?? 12);
    $fontColor = $element['fill'] ?? '#000000';
    $fontFamily = $element['fontFamily'] ?? 'Arial';
    $textAlign = $element['textAlign'] ?? 'left';
    $lineHeight = (float)($element['lineHeight'] ?? 1.16);

    // Convert color
    $color = self::hexToRgb($fontColor);
    $textColor = imagecolorallocate($image, $color['r'], $color['g'], $color['b']);

    // Calculate actual line height in pixels
    $pixelLineHeight = (int)($fontSize * $lineHeight);

    // Use built-in font (scale based on fontSize)
    $font = self::getFontSize($fontSize);

    // Split text by explicit line breaks first
    $explicitLines = explode("\n", $text);
    $allLines = [];

    // Process each explicit line - only wrap if it exceeds maxWidth
    foreach ($explicitLines as $line) {
      $lineWidth = self::getTextWidth($line, $font);

      if ($lineWidth > $maxWidth && $maxWidth > 0) {
        // Line is too wide, wrap it
        $wrappedLines[] = $line;//self::wrapText($line, $maxWidth, $font);
        $allLines = array_merge($allLines, $wrappedLines);
      } else {
        // Line fits, keep it as is
        $allLines[] = $line;
      }
    }
    // Calculate total text height
    $totalTextHeight = count($allLines) * $pixelLineHeight;

    // Only truncate if text actually exceeds maxHeight AND maxHeight is reasonable
    if ($maxHeight > 0 && $totalTextHeight > $maxHeight) {
      $maxLines = max(1, (int)($maxHeight / $pixelLineHeight));
      if (count($allLines) > $maxLines) {
        $allLines = array_slice($allLines, 0, $maxLines);
        // Add ellipsis to last line if truncated and there's enough space
        if ($maxLines > 0 && $maxWidth > 30) {
          $lastLine = $allLines[$maxLines - 1];
          $allLines[$maxLines - 1] = self::truncateLineWithEllipsis($lastLine, $maxWidth, $font);
        }
      }
    }
    // echo '<pre>$allLines-'; print_r($allLines); echo '</pre>';

    // Calculate starting Y position
    $startY = $top;

    // Render each line
    foreach ($allLines as $index => $line) {
      $y = $startY + ($index * $pixelLineHeight);

      // Calculate X position based on text alignment
      $x = self::calculateTextX($left, $maxWidth, $line, $textAlign, $font);

      // Render the line
      imagestring($image, $font, $x, $y, $line, $textColor);
    }
  }
  /**
   * Wrap text to fit within specified width (only called when needed)
   */
  private static function wrapText($text, $maxWidth, $font) {
    if (empty($text) || $maxWidth <= 0) {
      return [$text];
    }

    $words = explode(' ', $text);
    $lines = [];
    $currentLine = '';

    foreach ($words as $word) {
      $testLine = empty($currentLine) ? $word : $currentLine . ' ' . $word;
      $testWidth = self::getTextWidth($testLine, $font);

      if ($testWidth <= $maxWidth) {
        $currentLine = $testLine;
      } else {
        // If current line is not empty, save it and start new line
        if (!empty($currentLine)) {
          $lines[] = $currentLine;
          $currentLine = $word;

          // Check if single word is still too long
          if (self::getTextWidth($word, $font) > $maxWidth) {
            $currentLine = self::truncateLineWithEllipsis($word, $maxWidth, $font);
            $lines[] = $currentLine;
            $currentLine = '';
          }
        } else {
          // Single word is too long, truncate it
          $currentLine = self::truncateLineWithEllipsis($word, $maxWidth, $font);
          $lines[] = $currentLine;
          $currentLine = '';
        }
      }
    }

    // Add the last line if not empty
    if (!empty($currentLine)) {
      $lines[] = $currentLine;
    }

    return empty($lines) ? [''] : $lines;
  }


  /**
   * Get appropriate built-in font size based on fontSize
   */
  private static function getFontSize($fontSize) {
    if ($fontSize <= 8) return 1;
    if ($fontSize <= 10) return 2;
    if ($fontSize <= 12) return 3;
    if ($fontSize <= 14) return 4;
    return 5; // Maximum built-in font size
  }

  /**
   * Calculate text width for built-in fonts
   */
  private static function getTextWidth($text, $font) {
    // Approximate character widths for built-in fonts
    $charWidths = [
      1 => 5,   // Very small
      2 => 6,   // Small
      3 => 7,   // Medium
      4 => 8,   // Large
      5 => 10   // Very large
    ];

    $charWidth = $charWidths[$font] ?? 7;
    return strlen($text) * $charWidth;
  }

  /**
   * Calculate X position based on text alignment
   */
  private static function calculateTextX($left, $maxWidth, $text, $textAlign, $font) {
    switch ($textAlign) {
      case 'center':
        $textWidth = self::getTextWidth($text, $font);
        return $left + (($maxWidth - $textWidth) / 2);

      case 'right':
        $textWidth = self::getTextWidth($text, $font);
        return $left + $maxWidth - $textWidth;

      case 'left':
      default:
        return $left;
    }
  }

  /**
   * Truncate line and add ellipsis if it's too long
   */
  private static function truncateLineWithEllipsis($text, $maxWidth, $font) {
    $ellipsis = '...';
    $ellipsisWidth = self::getTextWidth($ellipsis, $font);

    if (self::getTextWidth($text, $font) <= $maxWidth) {
      return $text;
    }

    // Find the maximum length that fits with ellipsis
    $maxLengthWithEllipsis = $maxWidth - $ellipsisWidth;

    for ($i = strlen($text) - 1; $i > 0; $i--) {
      $truncated = substr($text, 0, $i);
      if (self::getTextWidth($truncated, $font) <= $maxLengthWithEllipsis) {
        return $truncated . $ellipsis;
      }
    }

    return $ellipsis; // If even one character is too wide
  }
  /**
   * Render text element
   */
  private static function renderTextElement2($image, $element, $options) {
    $text = $element['text'] ?? '';
    $text = nl2br($text);
    $x = ($element['left'] ?? 0) * ($options['width'] / 640); // Scale to image size
    $y = ($element['top'] ?? 0) * ($options['height'] / 400);
    $fontSize = ($element['fontSize'] ?? 16) * ($options['width'] / 640);

    $color = self::hexToRgb($element['fill'] ?? '#000000');
    $textColor = imagecolorallocate($image, $color['r'], $color['g'], $color['b']);

    // Use built-in font (simple implementation)
    imagestring($image, 5, $x, $y, $text, $textColor);
  }

  /**
   * Render image element
   */
  private static function renderImageElement($image, $element, $options) {
    try {
      $src = $element['src'] ?? '';
      if (empty($src)) {
        return;
      }

      $imageData = file_get_contents($src);
      $elementImage = imagecreatefromstring($imageData);

      if ($elementImage) {
        $x = ($element['left'] ?? 0) * ($options['width'] / 640);
        $y = ($element['top'] ?? 0) * ($options['height'] / 400);
        $width = ($element['width'] ?? 100) * ($options['width'] / 640);
        $height = ($element['height'] ?? 100) * ($options['height'] / 400);

        $resized = imagescale($elementImage, $width, $height);
        imagecopy($image, $resized, $x, $y, 0, 0, $width, $height);

        imagedestroy($elementImage);
        imagedestroy($resized);
      }
    }
    catch (Exception $e) {
      // Silently fail for images
    }
  }

  /**
   * Render QR code element
   */
  private static function renderQRCodeElement($image, $element, $tokenData) {
    // For QR code, we'll render a placeholder rectangle
    // In production, you'd integrate with a QR code library
    $left = (int)($element['left'] ?? 0);
    $top = (int)($element['top'] ?? 0);
    $width = (int)($element['width'] ?? 80);
    $height = (int)($element['height'] ?? 80);

    // Create QR code placeholder
    $qrColor = imagecolorallocate($image, 0, 0, 0); // Black
    $qrBorder = imagecolorallocate($image, 204, 204, 204); // Gray border

    // Draw border
    imagerectangle($image, $left, $top, $left + $width, $top + $height, $qrBorder);

    // Fill with pattern (simplified QR representation)
    for ($x = $left + 2; $x < $left + $width - 2; $x += 4) {
      for ($y = $top + 2; $y < $top + $height - 2; $y += 4) {
        if (($x + $y) % 8 === 0) {
          imagerectangle($image, $x, $y, $x + 2, $y + 2, $qrColor);
        }
      }
    }

    // Add "QR" text in center
    $centerX = $left + ($width / 2) - 10;
    $centerY = $top + ($height / 2) - 5;
    $textColor = imagecolorallocate($image, 255, 255, 255); // White
    imagestring($image, 2, $centerX, $centerY, 'QR', $textColor);
  }

  /**
   * Render rectangle element
   */
  private static function renderRectElement($image, $element, $options) {
    $x1 = ($element['left'] ?? 0) * ($options['width'] / 640);
    $y1 = ($element['top'] ?? 0) * ($options['height'] / 400);
    $width = ($element['width'] ?? 100) * ($options['width'] / 640);
    $height = ($element['height'] ?? 100) * ($options['height'] / 400);

    $color = self::hexToRgb($element['fill'] ?? '#000000');
    $rectColor = imagecolorallocate($image, $color['r'], $color['g'], $color['b']);

    imagefilledrectangle($image, $x1, $y1, $x1 + $width, $y1 + $height, $rectColor);
  }

  /**
   * Render circle element
   */
  private static function renderCircleElement($image, $element, $options) {
    $x = ($element['left'] ?? 0) * ($options['width'] / 640);
    $y = ($element['top'] ?? 0) * ($options['height'] / 400);
    $radius = ($element['radius'] ?? 50) * ($options['width'] / 640);

    $color = self::hexToRgb($element['fill'] ?? '#000000');
    $circleColor = imagecolorallocate($image, $color['r'], $color['g'], $color['b']);

    imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $circleColor);
  }

  /**
   * Add QR code to image
   */
  private static function addQRCode($image, $qrData, $options) {
    // This would require a QR code library like phpqrcode
    // For now, we'll add a placeholder rectangle
    $x = $options['width'] - 100;
    $y = $options['height'] - 100;
    $size = 80;

    $qrColor = imagecolorallocate($image, 0, 0, 0);
    imagefilledrectangle($image, $x, $y, $x + $size, $y + $size, $qrColor);

    // Add QR label
    $white = imagecolorallocate($image, 255, 255, 255);
    imagestring($image, 2, $x + 25, $y + 35, 'QR', $white);
  }

  /**
   * Add barcode to image
   */
  private static function addBarcode($image, $barcodeData, $options) {
    // Simple barcode representation with vertical lines
    $x = 50;
    $y = $options['height'] - 50;
    $width = 200;
    $height = 30;

    $barcodeColor = imagecolorallocate($image, 0, 0, 0);

    // Create simple barcode pattern
    for ($i = 0; $i < $width; $i += 3) {
      imageline($image, $x + $i, $y, $x + $i, $y + $height, $barcodeColor);
    }

    // Add barcode number
    $barcodeText = $barcodeData['data'] ?? '';
    imagestring($image, 2, $x, $y + $height + 5, $barcodeText, $barcodeColor);
  }

  /**
   * Generate a placeholder image when rendering fails
   */
  private static function generatePlaceholderImage($options) {
    $image = imagecreatetruecolor($options['width'], $options['height']);
    $white = imagecolorallocate($image, 255, 255, 255);
    $gray = imagecolorallocate($image, 128, 128, 128);
    $lightGray = imagecolorallocate($image, 200, 200, 200);

    imagefill($image, 0, 0, $white);

    // Add border
    imagerectangle($image, 5, 5, $options['width'] - 6, $options['height'] - 6, $lightGray);

    // Add side-specific text
    $side = $options['side'] ?? 'card';
    $sideText = strtoupper($side) . ' SIDE';

    imagestring($image, 5, 50, 50, 'Membership Card', $gray);
    imagestring($image, 4, 50, 80, $sideText, $gray);
    imagestring($image, 3, 50, 110, 'Error generating image', $gray);

    // Add placeholder elements
    imagestring($image, 2, 50, 150, 'Name: [Member Name]', $lightGray);
    imagestring($image, 2, 50, 170, 'ID: [Member ID]', $lightGray);
    imagestring($image, 2, 50, 190, 'Expires: [Date]', $lightGray);

    // Add placeholder QR code area
    imagerectangle($image, $options['width'] - 120, $options['height'] - 120,
      $options['width'] - 20, $options['height'] - 20, $lightGray);
    imagestring($image, 2, $options['width'] - 110, $options['height'] - 80, 'QR CODE', $lightGray);

    ob_start();
    imagepng($image);
    $imageData = ob_get_contents();
    ob_end_clean();

    imagedestroy($image);

    return 'data:image/png;base64,' . base64_encode($imageData);
  }
}
