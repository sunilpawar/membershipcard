<?php

use CRM_Membershipcard_ExtensionUtil as E;

/**
 * Form for saving membership card templates
 */
class CRM_Membershipcard_Form_CardTemplates extends CRM_Core_Form {

  protected $_id;

  protected $_templateCard;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    // Load existing template data if editing
    if ($this->_id) {
      try {
        $this->_templateCard = CRM_Membershipcard_BAO_MembershipCardTemplate::getTemplateById($this->_id);
        if (!$this->_templateCard) {
          CRM_Core_Error::statusBounce(ts('Template not found.'));
        }

        // Set page title for editing
        CRM_Utils_System::setTitle(ts('Edit Template: %1', [1 => $this->_templateCard->name]));
        $this->assign('action', CRM_Core_Action::UPDATE);
      } catch (Exception $e) {
        CRM_Core_Error::statusBounce(ts('Error loading template: %1', [1 => $e->getMessage()]));
      }
    } else {
      // Set page title for new template
      CRM_Utils_System::setTitle(ts('Add New Membership Card Template'));
      $this->assign('action', CRM_Core_Action::ADD);
    }
    parent::preProcess();
  }

  public function buildQuickForm() {
    $this->add('text', 'name', ts('Template Name'), ['class' => 'required'], TRUE);
    $this->add('textarea', 'description', ts('Description'));
    $this->add('hidden', 'card_width');
    $this->add('hidden', 'card_height');
    $this->add('hidden', 'background_color');
    $this->add('hidden', 'background_image');
    $this->add('hidden', 'elements');
    $this->add('checkbox', 'is_active', ts('Active'));

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Save Template'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
    if ($this->_id && $this->_templateCard) {
      $this->setDefaultValues();
    }
    $this->assign('template', $this->getTemplateForJS());
    $this->assign('tokens', membershipcard_get_tokens());
  }

  /**
   * Set default values for form fields when editing
   */
  public function setDefaultValues() {

    if (!$this->_templateCard) {
      return [];
    }

    $defaults = [
      'name' => $this->_templateCard->name,
      'description' => $this->_templateCard->description,
      'card_width' => $this->_templateCard->card_width ?: 350,
      'card_height' => $this->_templateCard->card_height ?: 220,
      'background_color' => $this->_templateCard->background_color ?: '#ffffff',
      'background_image' => $this->_templateCard->background_image,
      'elements' => $this->_templateCard->elements ?: '{}',
      'is_active' => $this->_templateCard->is_active,
    ];
    return $defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();

    $params = [
      'name' => $values['name'],
      'description' => $values['description'],
      'card_width' => $values['card_width'],
      'card_height' => $values['card_height'],
      'background_color' => $values['background_color'],
      'background_image' => $values['background_image'],
      'elements' => $values['elements'],
      'is_active' => CRM_Utils_Array::value('is_active', $values, 0),
      'modified_date' => date('Y-m-d H:i:s'),
    ];

    if ($this->_id) {
      $params['id'] = $this->_id;
    }
    else {
      $params['created_date'] = date('Y-m-d H:i:s');
    }

    CRM_Membershipcard_BAO_MembershipCardTemplate::create($params);

    CRM_Core_Session::setStatus(ts('Template saved successfully.'), ts('Success'), 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/membership-card-templates'));
  }
}
