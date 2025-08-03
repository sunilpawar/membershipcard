<?php

use CRM_Membershipcard_ExtensionUtil as E;

/**
 * Form for saving membership card templates
 */
class CRM_Membershipcard_Form_CardTemplates extends CRM_Core_Form {

  protected $_id;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
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
