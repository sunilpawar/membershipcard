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

    // Get action
    //$action = CRM_Utils_Request::retrieve('action', 'String', $this);
    //$templateId = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      //$this->edit();
      //$this->showDesigner($this->_id);
      $this->showDesigner($templateId);
    }
    else {
      $this->showTemplateList();
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
      $templateList[] = [
        'id' => $templates->id,
        'name' => $templates->name,
        'description' => $templates->description,
        'is_active' => $templates->is_active,
        'created_date' => $templates->created_date,
      ];
    }

    $this->assign('templates', $templateList);
    $this->assign('action', 'list');
  }


  private function showDesigner($templateId = NULL) {
    $template = NULL;

    if ($templateId) {
      $template = CRM_Core_DAO::executeQuery("
        SELECT * FROM civicrm_membership_card_template
        WHERE id = %1
      ", [1 => [$templateId, 'Integer']]);

      if ($template->fetch()) {
        $template = (array)$template;
        $template['elements'] = json_decode($template['elements'], TRUE);
      }
    }

    // Get available tokens
    $tokens = membershipcard_get_tokens();

    $this->assign('template', $template);
    $this->assign('tokens', $tokens);
    $this->assign('action', 'designer');
  }
}
