<?php
use CRM_Membershipcard_ExtensionUtil as E;

/**
 * CRM/Membershipcard/Page/CardTemplates.php
 * Page for managing membership card templates
 */
class CRM_Membershipcard_Page_CardTemplates extends CRM_Core_Page {
  protected $_action;

  public function run() {
    CRM_Utils_System::setTitle(ts('Membership Card Templates'));
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    // Add resources
    CRM_Core_Resources::singleton()->addScriptFile('com.skvare.membershipcard', 'js/card-designer.js');
    CRM_Core_Resources::singleton()->addStyleFile('com.skvare.membershipcard', 'css/card-designer.css');
    CRM_Core_Resources::singleton()->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js');
    CRM_Core_Resources::singleton()->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js');

    $actionNames = CRM_Core_Action::$_names;
    $action = array_search($this->_action, $actionNames);
    switch ($action) {
      case 'preview':
        $this->previewTemplate($this->_id);
        break;
      case 'duplicate':
      case 'copy':
        $this->duplicateTemplate($this->_id);
        break;
      case 'delete':
        $this->deleteTemplate($this->_id);
        break;
      case 'add':
      case 'update':
        $this->showDesigner(($this->_id) ? $this->_id : NULL);
        break;
      default:
        //$this->showTemplateList();
        $templates = $this->getTemplates();
        $this->assign('templates', $templates);
        /*
        CRM_Core_Resources::singleton()->addScriptFile('com.skvare.membershipcard', 'js/card-templates.js');
        */
    }
    parent::run();
  }

  public function edit() {
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $title = ts('Update Card Template');
    }
    else {
      $title = ts('Create Card Template');
    }

    $controller = new CRM_Core_Controller_Simple('CRM_Membershipcard_Form_CardTemplates', $title, $this->_action);
    $controller->setEmbedded(TRUE);
    $controller->set('id', $this->_id);
    return $controller->run();
  }

  private function showTemplateList() {
    // Get all templates
    $templates = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_membership_card_template
      ORDER BY name
    ");

    $templateList = [];
    while ($templates->fetch()) {
      $templateList[] = $templates->toArray();
    }

    $this->assign('templates', $templateList);
    $this->assign('action', 'list');
  }


  private function showDesigner($templateId = NULL) {
    $template = NULL;
    if ($templateId) {
      $template = CRM_Membershipcard_BAO_MembershipCardTemplate::getTemplateById($templateId, TRUE);
      $template = (array)$template;
      foreach (['front_background_color', 'front_background_image', 'front_elements'] as $sideField) {
        $template['front_side'] = $template[$sideField] ?? NULL;
      }
      foreach (['back_background_color', 'back_background_image', 'back_elements'] as $sideField) {
        $template['back_side'] = $template[$sideField] ?? NULL;
      }
    }

    // Get available tokens
    $tokens = membershipcard_get_tokens();

    $this->assign('template', $template);
    $this->assign('tokens', $tokens);
    $this->assign('action', 'designer');
  }

  private function previewTemplate($templateId) {
    try {
      // Get template data
      $template = civicrm_api3('MembershipCardTemplate', 'getsingle', [
        'id' => $templateId,
      ]);

      // Get sample contact and membership data for preview
      $sampleData = $this->getSamplePreviewData();
      // Generate preview HTML
      $previewHtml = $this->generatePreviewHtml($template, $sampleData);

      $this->sendJsonResponse([
        'success' => TRUE,
        'template' => $template,
        'previewHtml' => $previewHtml,
        'sampleData' => $sampleData,
      ]);

    }
    catch (Exception $e) {
      $this->sendJsonError('Error generating preview: ' . $e->getMessage());
    }
  }

  /**
   * Duplicate a template
   */
  private function duplicateTemplate($templateId) {
    try {
      // Get original template
      $originalTemplate = civicrm_api3('MembershipCardTemplate', 'getsingle', [
        'id' => $templateId,
      ]);

      // Create duplicate with new name
      $duplicateData = $originalTemplate;
      unset($duplicateData['id']);
      $duplicateData['name'] = $originalTemplate['name'] . ' - ' . ts('Copy');
      $duplicateData['created_date'] = date('Y-m-d H:i:s');
      $result = civicrm_api3('MembershipCardTemplate', 'create', $duplicateData);
      $this->sendJsonResponse([
        'success' => TRUE,
        'message' => ts('Template duplicated successfully'),
        'new_template_id' => $result['id'],
        'redirect' => CRM_Utils_System::url('civicrm/membership-card-templates', 'reset=1'),
      ]);

    }
    catch (Exception $e) {
      $this->sendJsonError('Error duplicating template: ' . $e->getMessage());
    }
  }

  /**
   * Delete a template
   */
  private function deleteTemplate($templateId) {
    try {
      // Check if template is being used
      $usageCount = $this->checkTemplateUsage($templateId);

      if ($usageCount > 0) {
        $this->sendJsonError(ts('Cannot delete template. It is currently being used by %1 membership card(s).', [1 => $usageCount]));
        return;
      }

      // Delete the template
      civicrm_api3('MembershipCardTemplate', 'delete', [
        'id' => $templateId,
      ]);

      $this->sendJsonResponse([
        'success' => TRUE,
        'message' => ts('Template deleted successfully'),
        'redirect' => CRM_Utils_System::url('civicrm/membership-card-templates', 'reset=1'),
      ]);

    }
    catch (Exception $e) {
      $this->sendJsonError('Error deleting template: ' . $e->getMessage());
    }
  }

  /**
   * Get all templates with additional data
   */
  private function getTemplates() {
    try {
      $result = civicrm_api3('MembershipCardTemplate', 'get', [
        'sequential' => 1,
        'options' => ['sort' => 'name ASC'],
      ]);

      $templates = [];
      foreach ($result['values'] as $template) {
        $template['usage_count'] = $this->checkTemplateUsage($template['id']);
        $template['preview_url'] = CRM_Utils_System::url('civicrm/membership/card-templates',
          'action=preview&id=' . $template['id'] . '&snippet=json');
        $template['duplicate_url'] = CRM_Utils_System::url('civicrm/membership/card-templates',
          'action=copy&id=' . $template['id'] . '&snippet=json');
        $template['delete_url'] = CRM_Utils_System::url('civicrm/membership/card-templates',
          'action=delete&id=' . $template['id'] . '&snippet=json');
        $template['edit_url'] = CRM_Utils_System::url('civicrm/membership/card-designer',
          'id=' . $template['id']);

        $templates[] = $template;
      }

      return $templates;

    }
    catch (Exception $e) {
      CRM_Core_Error::statusBounce('Error loading templates: ' . $e->getMessage());
    }
  }

  /**
   * Check how many cards are using this template
   */
  private function checkTemplateUsage($templateId) {
    try {
      $result = civicrm_api3('MembershipCard', 'getcount', [
        'template_id' => $templateId,
      ]);
      return $result;
    }
    catch (Exception $e) {
      return 0;
    }
  }

  /**
   * Get sample data for preview
   */
  private function getSamplePreviewData() {
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
        'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        'barcode' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
      ],
    ];
  }

  /**
   * Generate preview HTML for template
   */
  private function generatePreviewHtml($template, $sampleData) {
    $elements = json_decode($template['front_elements'], TRUE);
    if (!$elements) {
      return '<div class="preview-error">No elements found in template</div>';
    }

    $html = '<div class="card-preview" style="width: ' . $template['card_width'] . 'px; height: ' . $template['card_height'] . 'px; position: relative; background: ' . ($template['background_color'] ?? '#ffffff') . ';">';

    foreach ($elements as $element) {
      $html .= $this->renderPreviewElement($element, $sampleData);
    }

    $html .= '</div>';
    return $html;
  }

  /**
   * Render individual element for preview
   */
  private function renderPreviewElement($element, $sampleData) {
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
        $text = $this->replaceTokens($text, $sampleData);
        $html .= htmlspecialchars($text);
        break;

      case 'image':
        $src = $element['src'] ?? '';
        $src = $this->replaceTokens($src, $sampleData);
        $html .= '<img src="' . htmlspecialchars($src) . '" style="width: 100%; height: 100%; object-fit: cover;" />';
        break;

      default:
        $html .= '<span>Unknown element type</span>';
    }

    $html .= '</div>';
    return $html;
  }

  /**
   * Replace tokens in text with sample data
   */
  private function replaceTokens($text, $sampleData) {
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
   * Send JSON response
   */
  private function sendJsonResponse($data) {
    if (!empty($data['redirect'])) {
      CRM_Utils_System::redirect($data['redirect']);
      exit;
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    CRM_Utils_System::civiExit();
  }

  /**
   * Send JSON error response
   */
  private function sendJsonError($message) {
    $this->sendJsonResponse([
      'success' => FALSE,
      'error' => $message,
    ]);
  }
}
