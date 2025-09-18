<?php
/**
 * api/v3/MembershipCardTemplate.php
 * API for Membership Card Template operations
 */

/**
 * MembershipCardTemplate.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_template_create($params) {
  $required = ['name'];
  foreach ($required as $field) {
    if (empty($params[$field])) {
      throw new API_Exception("Missing required field: $field");
    }
  }

  try {
    // Prepare data for database
    $templateData = [
      'name' => $params['name'],
      'description' => CRM_Utils_Array::value('description', $params),
      'card_width' => CRM_Utils_Array::value('card_width', $params, 350),
      'card_height' => CRM_Utils_Array::value('card_height', $params, 220),
      //'background_color' => CRM_Utils_Array::value('background_color',$params, '#ffffff'),
      'front_background_color' => CRM_Utils_Array::value('front_background_color', $params, '#ffffff'),
      'back_background_color' => CRM_Utils_Array::value('back_background_color', $params, '#ffffff'),
      // 'background_image' => CRM_Utils_Array::value('background_image',$params),
      'front_background_image' => CRM_Utils_Array::value('front_background_image', $params),
      'back_background_image' => CRM_Utils_Array::value('back_background_image', $params),
      // 'elements' => CRM_Utils_Array::value('elements', $params, '{}'),
      'front_elements' => CRM_Utils_Array::value('front_elements', $params, '{}'),
      'back_elements' => CRM_Utils_Array::value('back_elements', $params, '{}'),
      'is_active' => CRM_Utils_Array::value('is_active', $params, 1),
      'is_dual_sided' => CRM_Utils_Array::value('is_dual_sided', $params, 0),
      'modified_date' => date('Y-m-d H:i:s'),
    ];

    // Check if updating existing template
    if (!empty($params['id'])) {
      $templateData['id'] = $params['id'];

      // Verify template exists
      $existingTemplate = CRM_Core_DAO::executeQuery("
        SELECT id FROM civicrm_membership_card_template WHERE id = %1
      ", [1 => [$params['id'], 'Integer']]);

      if (!$existingTemplate->fetch()) {
        throw new API_Exception("Template with ID {$params['id']} not found");
      }
    }
    else {
      $templateData['created_date'] = date('Y-m-d H:i:s');
    }

    // Validate elements JSON
    if (FALSE && !empty($templateData['elements']) && !is_string($templateData['elements'])) {
      $templateData['elements'] = json_encode($templateData['elements']);
    }

    if (FALSE && !empty($templateData['elements'])) {
      $decoded = json_decode($templateData['elements'], TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new API_Exception("Invalid JSON in elements field");
      }
    }

    if (!empty($templateData['front_elements']) && !is_string($templateData['front_elements'])) {
      $templateData['front_elements'] = json_encode($templateData['front_elements']);
    }
    if (!empty($templateData['front_elements'])) {
      $decoded = json_decode($templateData['front_elements'], TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new API_Exception("Invalid JSON in front_elements field");
      }
    }

    if (!empty($templateData['back_elements']) && !is_string($templateData['back_elements'])) {
      $templateData['back_elements'] = json_encode($templateData['back_elements']);
    }
    if (!empty($templateData['back_elements'])) {
      $decoded = json_decode($templateData['back_elements'], TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new API_Exception("Invalid JSON in back_elements field");
      }
    }

    // Validate dimensions
    if ($templateData['card_width'] < 100 || $templateData['card_width'] > 1000) {
      throw new API_Exception("Card width must be between 100 and 1000 pixels");
    }
    if ($templateData['card_height'] < 50 || $templateData['card_height'] > 1000) {
      throw new API_Exception("Card height must be between 50 and 1000 pixels");
    }

    // Validate background color
    if (FALSE && !preg_match('/^#[a-fA-F0-9]{6}$/', $templateData['background_color'])) {
      throw new API_Exception("Invalid background color format");
    }

    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $templateData['front_background_color'])) {
      throw new API_Exception("Invalid background color format");
    }
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $templateData['back_background_color'])) {
      throw new API_Exception("Invalid background color format");
    }

    // Save to database
    $cardTemplate = CRM_Membershipcard_BAO_MembershipCardTemplate::create($templateData);
    $templateId = $cardTemplate->id;

    // Return created/updated template
    $result = civicrm_api3('MembershipCardTemplate', 'getsingle', ['id' => $templateId]);

    return civicrm_api3_create_success([$result], $params, 'MembershipCardTemplate', 'create');

  }
  catch (Exception $e) {
    throw new API_Exception('Error creating/updating template: ' . $e->getMessage());
  }
}

/**
 * MembershipCardTemplate.Create API specification
 */
function _civicrm_api3_membership_card_template_create_spec(&$spec) {
  $spec['background_color']['title'] = 'Background Color';
  $spec['background_color']['description'] = 'Background color in hex format';
  $spec['background_color']['type'] = CRM_Utils_Type::T_STRING;
  $spec['background_color']['api.default'] = '#ffffff';

  $spec['front_background_color']['title'] = 'Front Background Color';
  $spec['front_background_color']['description'] = 'Front Background color in hex format';
  $spec['front_background_color']['type'] = CRM_Utils_Type::T_STRING;
  $spec['front_background_color']['api.default'] = '#ffffff';

  $spec['back_background_color']['title'] = 'Back Background Color';
  $spec['back_background_color']['description'] = 'Back Background color in hex format';
  $spec['back_background_color']['type'] = CRM_Utils_Type::T_STRING;
  $spec['back_background_color']['api.default'] = '#ffffff';

  $spec['background_image']['title'] = 'Background Image';
  $spec['background_image']['description'] = 'Path to background image';
  $spec['background_image']['type'] = CRM_Utils_Type::T_STRING;

  $spec['front_background_image']['title'] = 'Front Background Image';
  $spec['front_background_image']['description'] = 'Path to background image';
  $spec['front_background_image']['type'] = CRM_Utils_Type::T_STRING;

  $spec['back_background_image']['title'] = 'Back Background Image';
  $spec['back_background_image']['description'] = 'Path to background image';
  $spec['back_background_image']['type'] = CRM_Utils_Type::T_STRING;


  $spec['elements']['title'] = 'Elements';
  $spec['elements']['description'] = 'JSON string of card elements';
  $spec['elements']['type'] = CRM_Utils_Type::T_LONGTEXT;

  $spec['front_elements']['title'] = 'Front Elements';
  $spec['front_elements']['description'] = 'JSON string of card elements';
  $spec['front_elements']['type'] = CRM_Utils_Type::T_LONGTEXT;

  $spec['back_elements']['title'] = 'Back Elements';
  $spec['back_elements']['description'] = 'JSON string of card elements';
  $spec['back_elements']['type'] = CRM_Utils_Type::T_LONGTEXT;


  $spec['is_active']['title'] = 'Is Active';
  $spec['is_active']['description'] = 'Whether the template is active';
  $spec['is_active']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['is_active']['api.default'] = 1;

  $spec['is_dual_sided']['title'] = 'Is Dual Sided';
  $spec['is_dual_sided']['description'] = 'Whether the template is Dual Sided';
  $spec['is_dual_sided']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['is_dual_sided']['api.default'] = 0;
}

/**
 * MembershipCardTemplate.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_template_get($params) {
  try {
    $whereClause = '';
    $sqlParams = [];
    $paramIndex = 1;

    // Build WHERE clause
    if (!empty($params['id'])) {
      $whereClause .= " AND id = %{$paramIndex}";
      $sqlParams[$paramIndex] = [$params['id'], 'Integer'];
      $paramIndex++;
    }

    if (isset($params['is_active'])) {
      $whereClause .= " AND is_active = %{$paramIndex}";
      $sqlParams[$paramIndex] = [$params['is_active'], 'Integer'];
      $paramIndex++;
    }

    if (!empty($params['name'])) {
      $whereClause .= " AND name LIKE %{$paramIndex}";
      $sqlParams[$paramIndex] = ['%' . $params['name'] . '%', 'String'];
      $paramIndex++;
    }

    // Handle sequential parameter
    $sequential = CRM_Utils_Array::value('sequential', $params, FALSE);

    // Build ORDER BY clause
    $orderBy = 'ORDER BY name';
    if (!empty($params['options']['sort'])) {
      $orderBy = 'ORDER BY ' . CRM_Utils_Type::escape($params['options']['sort'], 'String');
    }

    // Build LIMIT clause
    $limit = '';
    if (!empty($params['options']['limit'])) {
      $limitValue = (int) $params['options']['limit'];
      $offset = !empty($params['options']['offset']) ? (int) $params['options']['offset'] : 0;
      $limit = "LIMIT {$offset}, {$limitValue}";
    }

    $sql = "
      SELECT *
      FROM civicrm_membership_card_template
      WHERE 1=1 {$whereClause}
      {$orderBy}
      {$limit}
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);

    $results = [];
    while ($dao->fetch()) {
      $template = [
        'id' => $dao->id,
        'name' => $dao->name,
        'description' => $dao->description,
        'card_width' => $dao->card_width,
        'card_height' => $dao->card_height,
        'front_background_color' => $dao->front_background_color,
        'back_background_color' => $dao->back_background_color,
        'front_background_image' => $dao->front_background_image,
        'back_background_image' => $dao->back_background_image,
        'front_elements' => $dao->front_elements,
        'back_elements' => $dao->back_elements,
        'is_active' => $dao->is_active,
        'is_dual_sided' => $dao->is_dual_sided,
        'created_date' => $dao->created_date,
        'modified_date' => $dao->modified_date,
      ];

      // Decode elements if requested
      if (!empty($params['decode_front_elements']) && !empty($template['front_elements'])) {
        $template['front_elements_decoded'] = json_decode($template['front_elements'], TRUE);
      }

      if (!empty($params['decode_back_elements']) && !empty($template['back_elements'])) {
        $template['back_elements_decoded'] = json_decode($template['back_elements'], TRUE);
      }

      if ($sequential) {
        $results[] = $template;
      } else {
        $results[$dao->id] = $template;
      }
    }

    // Get count for pagination
    if (!empty($params['options']['limit'])) {
      $countSql = "
        SELECT COUNT(*)
        FROM civicrm_membership_card_template
        WHERE 1=1 {$whereClause}
      ";
      $count = CRM_Core_DAO::singleValueQuery($countSql, $sqlParams);
    } else {
      $count = count($results);
    }
    $dao = NULL;
    return civicrm_api3_create_success($results, $params, 'MembershipCardTemplate', 'get', $dao, [
      'count' => $count,
    ]);

  } catch (Exception $e) {
    throw new API_Exception('Error retrieving templates: ' . $e->getMessage());
  }
}

/**
 * MembershipCardTemplate.Get API specification
 */
function _civicrm_api3_membership_card_template_get_spec(&$spec) {
  $spec['id']['title'] = 'Template ID';
  $spec['id']['description'] = 'Unique template identifier';
  $spec['id']['type'] = CRM_Utils_Type::T_INT;

  $spec['name']['title'] = 'Template Name';
  $spec['name']['description'] = 'Name of the template (supports partial matching)';
  $spec['name']['type'] = CRM_Utils_Type::T_STRING;

  $spec['is_active']['title'] = 'Is Active';
  $spec['is_active']['description'] = 'Filter by active status';
  $spec['is_active']['type'] = CRM_Utils_Type::T_BOOLEAN;

  $spec['decode_elements']['title'] = 'Decode Elements';
  $spec['decode_elements']['description'] = 'Return decoded elements JSON as array';
  $spec['decode_elements']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['decode_elements']['api.default'] = FALSE;
}

/**
 * MembershipCardTemplate.Delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_template_delete($params) {
  if (empty($params['id'])) {
    throw new API_Exception("Missing required field: id");
  }

  try {
    // Check if template exists
    $template = CRM_Core_DAO::executeQuery("
      SELECT id, name FROM civicrm_membership_card_template WHERE id = %1
    ", [1 => [$params['id'], 'Integer']]);

    if (!$template->fetch()) {
      throw new API_Exception("Template with ID {$params['id']} not found");
    }

    $templateName = $template->name;

    // Check if template is being used by any cards
    $cardCount = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) FROM civicrm_membership_card WHERE template_id = %1
    ", [1 => [$params['id'], 'Integer']]);

    if ($cardCount > 0 && empty($params['force'])) {
      throw new API_Exception("Cannot delete template '{$templateName}' - it is being used by {$cardCount} membership card(s). Use 'force' parameter to delete anyway.");
    }

    // If force delete, remove associated cards first
    if (!empty($params['force']) && $cardCount > 0) {
      CRM_Core_DAO::executeQuery("
        DELETE FROM civicrm_membership_card WHERE template_id = %1
      ", [1 => [$params['id'], 'Integer']]);
    }

    // Delete the template
    CRM_Core_DAO::executeQuery("
      DELETE FROM civicrm_membership_card_template WHERE id = %1
    ", [1 => [$params['id'], 'Integer']]);

    return civicrm_api3_create_success([
      'id' => $params['id'],
      'name' => $templateName,
      'deleted_cards' => $cardCount,
    ], $params, 'MembershipCardTemplate', 'delete');

  } catch (Exception $e) {
    throw new API_Exception('Error deleting template: ' . $e->getMessage());
  }
}

/**
 * MembershipCardTemplate.Delete API specification
 */
function _civicrm_api3_membership_card_template_delete_spec(&$spec) {
  $spec['id']['api.required'] = 1;
  $spec['id']['title'] = 'Template ID';
  $spec['id']['description'] = 'ID of template to delete';
  $spec['id']['type'] = CRM_Utils_Type::T_INT;

  $spec['force']['title'] = 'Force Delete';
  $spec['force']['description'] = 'Delete template even if it has associated cards';
  $spec['force']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['force']['api.default'] = FALSE;
}

/**
 * MembershipCardTemplate.Copy API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_template_copy($params) {
  if (empty($params['id'])) {
    throw new API_Exception("Missing required field: id");
  }

  try {
    // Get original template
    $original = civicrm_api3('MembershipCardTemplate', 'getsingle', [
      'id' => $params['id']
    ]);

    // Prepare new template data
    $newName = CRM_Utils_Array::value('new_name', $params, $original['name'] . ' (Copy)');
    $newDescription = CRM_Utils_Array::value('new_description', $params,
      ($original['description'] ? $original['description'] . ' (Copy)' : 'Copy of ' . $original['name']));

    $copyData = [
      'name' => $newName,
      'description' => $newDescription,
      'card_width' => $original['card_width'],
      'card_height' => $original['card_height'],
      'background_color' => $original['background_color'],
      'background_image' => $original['background_image'],
      'elements' => $original['elements'],
      'is_active' => CRM_Utils_Array::value('is_active', $params, 1),
    ];

    // Create the copy
    $result = civicrm_api3('MembershipCardTemplate', 'create', $copyData);

    return civicrm_api3_create_success($result['values'], $params, 'MembershipCardTemplate', 'copy');

  } catch (Exception $e) {
    throw new API_Exception('Error copying template: ' . $e->getMessage());
  }
}

/**
 * MembershipCardTemplate.Copy API specification
 */
function _civicrm_api3_membership_card_template_copy_spec(&$spec) {
  $spec['id']['api.required'] = 1;
  $spec['id']['title'] = 'Template ID';
  $spec['id']['description'] = 'ID of template to copy';
  $spec['id']['type'] = CRM_Utils_Type::T_INT;

  $spec['new_name']['title'] = 'New Name';
  $spec['new_name']['description'] = 'Name for the copied template';
  $spec['new_name']['type'] = CRM_Utils_Type::T_STRING;

  $spec['new_description']['title'] = 'New Description';
  $spec['new_description']['description'] = 'Description for the copied template';
  $spec['new_description']['type'] = CRM_Utils_Type::T_TEXT;

  $spec['is_active']['title'] = 'Is Active';
  $spec['is_active']['description'] = 'Whether the copied template should be active';
  $spec['is_active']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['is_active']['api.default'] = 1;
}

/**
 * MembershipCardTemplate.Export API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_template_export2($params) {
  if (empty($params['id'])) {
    throw new API_Exception("Missing required field: id");
  }

  try {
    // Get template
    $template = civicrm_api3('MembershipCardTemplate', 'getsingle', [
      'id' => $params['id']
    ]);

    // Prepare export data
    $exportData = [
      'name' => $template['name'],
      'description' => $template['description'],
      'card_width' => $template['card_width'],
      'card_height' => $template['card_height'],
      'background_color' => $template['background_color'],
      'background_image' => $template['background_image'],
      'elements' => json_decode($template['elements'], TRUE),
      'export_date' => date('Y-m-d H:i:s'),
      'export_version' => '1.0',
    ];

    $format = CRM_Utils_Array::value('format', $params, 'json');

    if ($format === 'json') {
      $exportContent = json_encode($exportData, JSON_PRETTY_PRINT);
      $filename = preg_replace('/[^a-zA-Z0-9-_]/', '_', $template['name']) . '_template.json';
      $mimeType = 'application/json';
    } else {
      throw new API_Exception("Unsupported export format: {$format}");
    }

    return civicrm_api3_create_success([
      'content' => $exportContent,
      'filename' => $filename,
      'mime_type' => $mimeType,
      'template_id' => $params['id'],
      'template_name' => $template['name'],
    ], $params, 'MembershipCardTemplate', 'export');

  } catch (Exception $e) {
    throw new API_Exception('Error exporting template: ' . $e->getMessage());
  }
}

/**
 * MembershipCardTemplate.Export API specification
 */
function _civicrm_api3_membership_card_template_export_spec(&$spec) {
  $spec['id']['api.required'] = 1;
  $spec['id']['title'] = 'Template ID';
  $spec['id']['description'] = 'ID of template to export';
  $spec['id']['type'] = CRM_Utils_Type::T_INT;

  $spec['format']['title'] = 'Export Format';
  $spec['format']['description'] = 'Export format (json)';
  $spec['format']['type'] = CRM_Utils_Type::T_STRING;
  $spec['format']['api.default'] = 'json';
}

/**
 * MembershipCardTemplate.Import API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_membership_card_template_import2($params) {
  if (empty($params['template_data'])) {
    throw new API_Exception("Missing required field: template_data");
  }

  try {
    // Parse template data
    $templateData = json_decode($params['template_data'], TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new API_Exception("Invalid JSON in template_data");
    }

    // Validate required fields
    if (empty($templateData['name'])) {
      throw new API_Exception("Template data missing required field: name");
    }

    // Prepare import data
    $importData = [
      'name' => $templateData['name'],
      'description' => CRM_Utils_Array::value('description', $templateData),
      'card_width' => CRM_Utils_Array::value('card_width', $templateData, 350),
      'card_height' => CRM_Utils_Array::value('card_height', $templateData, 220),
      'background_color' => CRM_Utils_Array::value('background_color', $templateData, '#ffffff'),
      'background_image' => CRM_Utils_Array::value('background_image', $templateData),
      'elements' => json_encode(CRM_Utils_Array::value('elements', $templateData, [])),
      'is_active' => CRM_Utils_Array::value('is_active', $params, 1),
    ];

    // Handle name conflicts
    if (!empty($params['handle_duplicates'])) {
      $existingCount = CRM_Core_DAO::singleValueQuery("
        SELECT COUNT(*) FROM civicrm_membership_card_template WHERE name = %1
      ", [1 => [$importData['name'], 'String']]);

      if ($existingCount > 0) {
        if ($params['handle_duplicates'] === 'rename') {
          $importData['name'] .= ' (Imported ' . date('Y-m-d H:i:s') . ')';
        } elseif ($params['handle_duplicates'] === 'skip') {
          throw new API_Exception("Template with name '{$importData['name']}' already exists");
        }
      }
    }

    // Create the template
    $result = civicrm_api3('MembershipCardTemplate', 'create', $importData);

    return civicrm_api3_create_success($result['values'], $params, 'MembershipCardTemplate', 'import');

  } catch (Exception $e) {
    throw new API_Exception('Error importing template: ' . $e->getMessage());
  }
}

/**
 * MembershipCardTemplate.Import API specification
 */
function _civicrm_api3_membership_card_template_import_spec(&$spec) {
  $spec['template_data']['api.required'] = 1;
  $spec['template_data']['title'] = 'Template Data';
  $spec['template_data']['description'] = 'JSON string of template data to import';
  $spec['template_data']['type'] = CRM_Utils_Type::T_LONGTEXT;

  $spec['handle_duplicates']['title'] = 'Handle Duplicates';
  $spec['handle_duplicates']['description'] = 'How to handle duplicate names (rename, skip, overwrite)';
  $spec['handle_duplicates']['type'] = CRM_Utils_Type::T_STRING;
  $spec['handle_duplicates']['api.default'] = 'rename';

  $spec['is_active']['title'] = 'Is Active';
  $spec['is_active']['description'] = 'Whether the imported template should be active';
  $spec['is_active']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['is_active']['api.default'] = 1;

  $spec['name']['title'] = 'Template Name';
  $spec['name']['description'] = 'Name of the card template';
  $spec['name']['type'] = CRM_Utils_Type::T_STRING;
  $spec['name']['maxlength'] = 255;

  $spec['description']['title'] = 'Description';
  $spec['description']['description'] = 'Description of the template';
  $spec['description']['type'] = CRM_Utils_Type::T_TEXT;

  $spec['card_width']['title'] = 'Card Width';
  $spec['card_width']['description'] = 'Width of the card in pixels';
  $spec['card_width']['type'] = CRM_Utils_Type::T_INT;
  $spec['card_width']['api.default'] = 350;

  $spec['card_height']['title'] = 'Card Height';
  $spec['card_height']['description'] = 'Height of the card in pixels';
  $spec['card_height']['type'] = CRM_Utils_Type::T_INT;
  $spec['card_height']['api.default'] = 220;
}


/**
 * API function to get template preview data
 */
function civicrm_api3_membership_card_template_preview($params) {
  try {
    // Validate template ID
    if (empty($params['id'])) {
      throw new API_Exception('Template ID is required');
    }

    // Get template
    $template = civicrm_api3('MembershipCardTemplate', 'getsingle', [
      'id' => $params['id'],
    ]);

    // Get sample data for preview
    $sampleData = _membershipcard_get_sample_preview_data();

    // Generate preview HTML
    $previewHtml = _membershipcard_generate_preview_html($template, $sampleData);

    return civicrm_api3_create_success([
      'template' => $template,
      'preview_html' => $previewHtml,
      'sample_data' => $sampleData,
    ]);

  } catch (Exception $e) {
    throw new API_Exception('Error generating preview: ' . $e->getMessage());
  }
}

/**
 * API function to duplicate a template
 */
function civicrm_api3_membership_card_template_duplicate($params) {
  try {
    // Validate template ID
    if (empty($params['id'])) {
      throw new API_Exception('Template ID is required');
    }

    // Get original template
    $originalTemplate = civicrm_api3('MembershipCardTemplate', 'getsingle', [
      'id' => $params['id'],
    ]);

    // Create duplicate
    $duplicateData = $originalTemplate;
    unset($duplicateData['id']);

    // Generate unique name
    $baseName = $originalTemplate['name'];
    $copyNumber = 1;
    $newName = $baseName . ' - Copy';

    // Check if name already exists and increment if needed
    while (_membershipcard_template_name_exists($newName)) {
      $copyNumber++;
      $newName = $baseName . ' - Copy ' . $copyNumber;
    }

    $duplicateData['name'] = $newName;
    $duplicateData['created_date'] = date('Y-m-d H:i:s');
    $duplicateData['modified_date'] = date('Y-m-d H:i:s');

    $result = civicrm_api3('MembershipCardTemplate', 'create', $duplicateData);

    return civicrm_api3_create_success([
      'id' => $result['id'],
      'name' => $newName,
      'message' => ts('Template duplicated successfully'),
    ]);

  } catch (Exception $e) {
    throw new API_Exception('Error duplicating template: ' . $e->getMessage());
  }
}

/**
 * API function to check template usage
 */
function civicrm_api3_membership_card_template_getusage($params) {
  try {
    // Validate template ID
    if (empty($params['id'])) {
      throw new API_Exception('Template ID is required');
    }

    // Count usage
    $usageCount = _membershipcard_get_template_usage_count($params['id']);

    // Get detailed usage if requested
    $detailedUsage = [];
    if (!empty($params['detailed']) && $params['detailed']) {
      $detailedUsage = _membershipcard_get_template_usage_details($params['id']);
    }

    return civicrm_api3_create_success([
      'template_id' => $params['id'],
      'usage_count' => $usageCount,
      'detailed_usage' => $detailedUsage,
      'can_delete' => ($usageCount == 0),
    ]);

  } catch (Exception $e) {
    throw new API_Exception('Error checking template usage: ' . $e->getMessage());
  }
}

/**
 * Enhanced delete function with usage checking
 */
function civicrm_api3_membership_card_template_safedelete($params) {
  try {
    // Validate template ID
    if (empty($params['id'])) {
      throw new API_Exception('Template ID is required');
    }

    // Check usage
    $usageCount = _membershipcard_get_template_usage_count($params['id']);

    if ($usageCount > 0 && empty($params['force'])) {
      throw new API_Exception(
        ts('Cannot delete template. It is currently being used by %1 membership card(s).',
          [1 => $usageCount])
      );
    }

    // If force delete is enabled, handle dependent records
    if ($usageCount > 0 && !empty($params['force'])) {
      _membershipcard_handle_template_dependencies($params['id'], $params);
    }

    // Delete the template
    $result = civicrm_api3('MembershipCardTemplate', 'delete', [
      'id' => $params['id'],
    ]);

    return civicrm_api3_create_success([
      'id' => $params['id'],
      'message' => ts('Template deleted successfully'),
      'usage_count_before_delete' => $usageCount,
    ]);

  } catch (Exception $e) {
    throw new API_Exception('Error deleting template: ' . $e->getMessage());
  }
}

/**
 * Helper function to get sample preview data
 */
function _membershipcard_get_sample_preview_data() {
  return [
    'contact' => [
      'display_name' => 'John Doe',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'email' => 'john.doe@example.com',
      'phone' => '(555) 123-4567',
      'street_address' => '123 Main Street',
      'city' => 'Anytown',
      'state_province' => 'CA',
      'postal_code' => '12345',
      'image_URL' => CRM_Utils_System::url('civicrm/contact/imagefile', 'photo=sample'),
    ],
    'membership' => [
      'membership_type' => 'Gold Member',
      'status' => 'Current',
      'start_date' => date('Y-m-d'),
      'end_date' => date('Y-m-d', strtotime('+1 year')),
      'join_date' => date('Y-m-d'),
      'membership_id' => 'MEM001234',
      'source' => 'Online Registration',
    ],
    'organization' => [
      'organization_name' => 'Sample Organization',
      'organization_logo' => CRM_Utils_System::url('civicrm/contact/imagefile', 'photo=org_logo'),
      'organization_address' => '456 Business Ave, Business City, BC 67890',
      'organization_phone' => '(555) 987-6543',
      'organization_email' => 'info@sampleorg.com',
    ],
    'system' => [
      'current_date' => date('Y-m-d'),
      'qr_code' => _membershipcard_generate_sample_qr(),
      'barcode' => _membershipcard_generate_sample_barcode(),
    ],
  ];
}

/**
 * Helper function to generate preview HTML
 */
function _membershipcard_generate_preview_html($template, $sampleData) {
  $elements = json_decode($template['elements'], TRUE);
  if (!$elements) {
    return '<div class="preview-error">No elements found in template</div>';
  }

  $html = '<div class="card-preview" style="width: ' . $template['card_width'] . 'px; height: ' . $template['card_height'] . 'px; position: relative; background: ' . ($template['background_color'] ?? '#ffffff') . ';">';

  foreach ($elements as $element) {
    $html .= _membershipcard_render_preview_element($element, $sampleData);
  }

  $html .= '</div>';
  return $html;
}

/**
 * Helper function to render individual elements
 */
function _membershipcard_render_preview_element($element, $sampleData) {
  $style = 'position: absolute; ';
  $style .= 'left: ' . ($element['left'] ?? 0) . 'px; ';
  $style .= 'top: ' . ($element['top'] ?? 0) . 'px; ';
  $style .= 'width: ' . ($element['width'] ?? 100) . 'px; ';
  $style .= 'height: ' . ($element['height'] ?? 30) . 'px; ';

  if (!empty($element['fontFamily'])) {
    $style .= 'font-family: ' . $element['fontFamily'] . '; ';
  }
  if (!empty($element['fontSize'])) {
    $style .= 'font-size: ' . $element['fontSize'] . 'px; ';
  }
  if (!empty($element['fill'])) {
    $style .= 'color: ' . $element['fill'] . '; ';
  }

  $html = '<div style="' . $style . '">';

  switch ($element['type']) {
    case 'text':
      $text = $element['text'] ?? '';
      $text = _membershipcard_replace_tokens($text, $sampleData);
      $html .= htmlspecialchars($text);
      break;

    case 'image':
      $src = $element['src'] ?? '';
      $src = _membershipcard_replace_tokens($src, $sampleData);
      $html .= '<img src="' . htmlspecialchars($src) . '" style="width: 100%; height: 100%; object-fit: cover;" alt="Card Image" />';
      break;

    case 'qrcode':
      $qrData = $element['data'] ?? '';
      $qrData = _membershipcard_replace_tokens($qrData, $sampleData);
      $html .= '<div style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 10px;">QR Code</div>';
      break;

    case 'barcode':
      $barcodeData = $element['data'] ?? '';
      $barcodeData = _membershipcard_replace_tokens($barcodeData, $sampleData);
      $html .= '<div style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 10px;">Barcode</div>';
      break;

    default:
      $html .= '<span style="color: #999;">Unknown element</span>';
  }

  $html .= '</div>';
  return $html;
}

/**
 * Helper function to replace tokens with sample data
 */
function _membershipcard_replace_tokens($text, $sampleData) {
  // Replace contact tokens
  foreach ($sampleData['contact'] as $key => $value) {
    $text = str_replace('{contact.' . $key . '}', $value, $text);
  }

  // Replace membership tokens
  foreach ($sampleData['membership'] as $key => $value) {
    $text = str_replace('{membership.' . $key . '}', $value, $text);
  }

  // Replace organization tokens
  foreach ($sampleData['organization'] as $key => $value) {
    $text = str_replace('{organization.' . $key . '}', $value, $text);
  }

  // Replace system tokens
  foreach ($sampleData['system'] as $key => $value) {
    $text = str_replace('{system.' . $key . '}', $value, $text);
  }

  return $text;
}

/**
 * Helper function to check if template name exists
 */
function _membershipcard_template_name_exists($name) {
  try {
    $result = civicrm_api3('MembershipCardTemplate', 'getcount', [
      'name' => $name,
    ]);
    return $result > 0;
  } catch (Exception $e) {
    return FALSE;
  }
}

/**
 * Helper function to get template usage count
 */
function _membershipcard_get_template_usage_count($templateId) {
  try {
    // Count membership cards using this template
    $cardCount = civicrm_api3('MembershipCard', 'getcount', [
      'template_id' => $templateId,
    ]);

    // You might also want to check other places where template is used
    // For example, scheduled jobs, default settings, etc.

    return $cardCount;
  } catch (Exception $e) {
    return 0;
  }
}

/**
 * Helper function to get detailed template usage
 */
function _membershipcard_get_template_usage_details($templateId) {
  $details = [];

  try {
    // Get membership cards using this template
    $cards = civicrm_api3('MembershipCard', 'get', [
      'template_id' => $templateId,
      'options' => ['limit' => 100],
    ]);

    foreach ($cards['values'] as $card) {
      // Get membership details
      try {
        $membership = civicrm_api3('Membership', 'getsingle', [
          'id' => $card['membership_id'],
        ]);

        // Get contact details
        $contact = civicrm_api3('Contact', 'getsingle', [
          'id' => $membership['contact_id'],
        ]);

        $details[] = [
          'card_id' => $card['id'],
          'membership_id' => $membership['id'],
          'contact_name' => $contact['display_name'],
          'membership_type' => $membership['membership_type_id'],
          'created_date' => $card['created_date'],
        ];
      } catch (Exception $e) {
        // Skip if we can't get details
        continue;
      }
    }

  } catch (Exception $e) {
    // Return empty if there's an error
  }

  return $details;
}

/**
 * Helper function to handle template dependencies when force deleting
 */
function _membershipcard_handle_template_dependencies($templateId, $params) {
  $action = $params['dependency_action'] ?? 'reassign';

  switch ($action) {
    case 'delete_cards':
      // Delete all cards using this template
      $cards = civicrm_api3('MembershipCard', 'get', [
        'template_id' => $templateId,
      ]);

      foreach ($cards['values'] as $card) {
        civicrm_api3('MembershipCard', 'delete', [
          'id' => $card['id'],
        ]);
      }
      break;

    case 'reassign':
      // Reassign cards to another template
      $newTemplateId = $params['new_template_id'] ?? NULL;
      if ($newTemplateId) {
        $cards = civicrm_api3('MembershipCard', 'get', [
          'template_id' => $templateId,
        ]);

        foreach ($cards['values'] as $card) {
          civicrm_api3('MembershipCard', 'create', [
            'id' => $card['id'],
            'template_id' => $newTemplateId,
          ]);
        }
      }
      break;

    case 'archive':
      // Archive the cards instead of deleting them
      $cards = civicrm_api3('MembershipCard', 'get', [
        'template_id' => $templateId,
      ]);

      foreach ($cards['values'] as $card) {
        civicrm_api3('MembershipCard', 'create', [
          'id' => $card['id'],
          'is_active' => 0,
          'template_id' => NULL,
        ]);
      }
      break;
  }
}

/**
 * Helper function to generate sample QR code data URL
 */
function _membershipcard_generate_sample_qr() {
  // Return a simple placeholder QR code data URL
  // In a real implementation, you would generate an actual QR code
  return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAdgAAAHYBTnsmCAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAFYSURBVBiVY/wPBAxUAGMjIyMuNjY2FhsbGxs7OzsbBwcHGycnJwcXFxcHNzc3Bzc3Nwc/Pz8HPz8/B4FAAA==';
}

/**
 * Helper function to generate sample barcode data URL
 */
function _membershipcard_generate_sample_barcode() {
  // Return a simple placeholder barcode data URL
  // In a real implementation, you would generate an actual barcode
  return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAdgAAAHYBTnsmCAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAFYSURBVBiVY/wPBAxUAGMjIyMuNjY2FhsbGxs7OzsbBwcHGycnJwcXFxcHNzc3Bzc3Nwc/Pz8HPz8/B4FAAA==';
}

/**
 * Batch operations for templates
 */
function civicrm_api3_membership_card_template_batch_duplicate($params) {
  try {
    $templateIds = $params['template_ids'] ?? [];
    $results = [];
    $errors = [];

    foreach ($templateIds as $templateId) {
      try {
        $result = civicrm_api3('MembershipCardTemplate', 'duplicate', [
          'id' => $templateId,
        ]);
        $results[] = $result['values'];
      } catch (Exception $e) {
        $errors[] = [
          'template_id' => $templateId,
          'error' => $e->getMessage(),
        ];
      }
    }

    return civicrm_api3_create_success([
      'successes' => $results,
      'errors' => $errors,
      'total_processed' => count($templateIds),
      'successful_count' => count($results),
      'error_count' => count($errors),
    ]);

  } catch (Exception $e) {
    throw new API_Exception('Error in batch duplicate operation: ' . $e->getMessage());
  }
}

/**
 * Export template configuration
 */
function civicrm_api3_membership_card_template_export($params) {
  try {
    $templateId = $params['id'] ?? NULL;
    $format = $params['format'] ?? 'json';

    if (!$templateId) {
      throw new API_Exception('Template ID is required');
    }

    // Get template data
    $template = civicrm_api3('MembershipCardTemplate', 'getsingle', [
      'id' => $templateId,
    ]);

    // Remove system-specific fields
    unset($template['id']);
    unset($template['created_date']);
    unset($template['modified_date']);

    // Add export metadata
    $exportData = [
      'export_version' => '1.0',
      'export_date' => date('Y-m-d H:i:s'),
      'extension_version' => _membershipcard_get_extension_version(),
      'template' => $template,
    ];

    switch ($format) {
      case 'json':
        $content = json_encode($exportData, JSON_PRETTY_PRINT);
        $contentType = 'application/json';
        $filename = 'template_' . $template['name'] . '.json';
        break;

      case 'xml':
        $content = _membershipcard_array_to_xml($exportData);
        $contentType = 'application/xml';
        $filename = 'template_' . $template['name'] . '.xml';
        break;

      default:
        throw new API_Exception('Unsupported export format: ' . $format);
    }

    return civicrm_api3_create_success([
      'content' => $content,
      'content_type' => $contentType,
      'filename' => $filename,
      'template_name' => $template['name'],
    ]);

  } catch (Exception $e) {
    throw new API_Exception('Error exporting template: ' . $e->getMessage());
  }
}

/**
 * Import template configuration
 */
function civicrm_api3_membership_card_template_import($params) {
  try {
    $content = $params['content'] ?? NULL;
    $format = $params['format'] ?? 'json';
    $overwrite = $params['overwrite'] ?? FALSE;

    if (!$content) {
      throw new API_Exception('Content is required for import');
    }

    // Parse content based on format
    switch ($format) {
      case 'json':
        $importData = json_decode($content, TRUE);
        if (json_last_error() !== JSON_ERROR_NONE) {
          throw new API_Exception('Invalid JSON format');
        }
        break;

      case 'xml':
        $importData = _membershipcard_xml_to_array($content);
        break;

      default:
        throw new API_Exception('Unsupported import format: ' . $format);
    }

    // Validate import data
    if (!isset($importData['template'])) {
      throw new API_Exception('Invalid import file: template data not found');
    }

    $templateData = $importData['template'];

    // Check if template with same name exists
    $existingTemplate = NULL;
    try {
      $existingTemplate = civicrm_api3('MembershipCardTemplate', 'getsingle', [
        'name' => $templateData['name'],
      ]);
    } catch (Exception $e) {
      // Template doesn't exist, which is fine
    }

    if ($existingTemplate && !$overwrite) {
      throw new API_Exception('Template with name "' . $templateData['name'] . '" already exists. Use overwrite=1 to replace it.');
    }

    // Import the template
    if ($existingTemplate && $overwrite) {
      $templateData['id'] = $existingTemplate['id'];
    }

    $templateData['modified_date'] = date('Y-m-d H:i:s');
    if (!isset($templateData['created_date'])) {
      $templateData['created_date'] = date('Y-m-d H:i:s');
    }

    $result = civicrm_api3('MembershipCardTemplate', 'create', $templateData);

    return civicrm_api3_create_success([
      'template_id' => $result['id'],
      'template_name' => $templateData['name'],
      'action' => $existingTemplate ? 'updated' : 'created',
      'import_version' => $importData['export_version'] ?? 'unknown',
    ]);

  } catch (Exception $e) {
    throw new API_Exception('Error importing template: ' . $e->getMessage());
  }
}

/**
 * Helper function to get extension version
 */
function _membershipcard_get_extension_version() {
  // Return the current extension version
  // This should be updated to match your extension's version
  return '1.0.0';
}

/**
 * Helper function to convert array to XML
 */
function _membershipcard_array_to_xml($array, $rootElement = 'template_export') {
  $xml = new SimpleXMLElement('<' . $rootElement . '/>');
  _membershipcard_array_to_xml_recursive($array, $xml);
  return $xml->asXML();
}

/**
 * Recursive helper for array to XML conversion
 */
function _membershipcard_array_to_xml_recursive($array, &$xml) {
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      $subnode = $xml->addChild($key);
      _membershipcard_array_to_xml_recursive($value, $subnode);
    } else {
      $xml->addChild($key, htmlspecialchars($value));
    }
  }
}

/**
 * Helper function to convert XML to array
 */
function _membershipcard_xml_to_array($xmlString) {
  $xml = simplexml_load_string($xmlString);
  if ($xml === FALSE) {
    throw new API_Exception('Invalid XML format');
  }
  return json_decode(json_encode($xml), TRUE);
}
