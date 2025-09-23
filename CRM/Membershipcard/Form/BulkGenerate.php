<?php

use CRM_Membershipcard_ExtensionUtil as E;

/**
 * Form for bulk generating membership cards
 */
class CRM_Membershipcard_Form_BulkGenerate extends CRM_Core_Form {
  protected $_templates;
  protected $_membershipTypes;

  public function preProcess() {
    CRM_Utils_System::setTitle(ts('Bulk Generate Membership Cards'));
    // Load templates
    $this->_templates = $this->getTemplates();

    // Load membership types
    $this->_membershipTypes = CRM_Member_PseudoConstant::membershipType();

    parent::preProcess();
  }

  public function buildQuickForm() {

    // Template selection
    $templateOptions = ['' => ts('- Select Template -')];
    foreach ($this->_templates as $template) {
      $templateOptions[$template['id']] = $template['name'];
    }

    $this->add('select', 'template_id', ts('Template'), $templateOptions, TRUE, [
      'class' => 'crm-select2 huge',
      'placeholder' => ts('- Select Template -')
    ]);

    // Membership type filter
    $membershipTypeOptions = ['' => ts('- All Membership Types -')] + $this->_membershipTypes;
    $this->add('select', 'membership_type_id', ts('Membership Type'), $membershipTypeOptions, FALSE, [
      'class' => 'crm-select2 huge',
      'placeholder' => ts('- All Membership Types -')
    ]);

    // Status filter
    $statusOptions = [
      '' => ts('- All Status -'),
      'current' => ts('Current'),
      'new' => ts('New'),
      'grace' => ts('Grace Period'),
    ];
    $this->add('select', 'membership_status', ts('Membership Status'), $statusOptions, FALSE, [
      'class' => 'crm-select2',
      'placeholder' => ts('- All Status -')
    ]);

    // Date range
    $this->add('datepicker', 'start_date', ts('Start Date From'), [], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', ts('End Date From'), [], FALSE, ['time' => FALSE]);

    // Options
    $this->add('checkbox', 'regenerate_existing', ts('Regenerate Existing Cards'));
    $this->add('checkbox', 'email_cards', ts('Email Cards to Members'));

    // Limit
    $this->add('text', 'limit', ts('Limit'), ['placeholder' => ts('Leave empty for no limit')]);
    $this->addRule('limit', ts('Please enter a valid number.'), 'positiveInteger');

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Preview Selection'),
        'subName' => 'preview',
        'isDefault' => FALSE,
        'js' => ['onclick' => "return submitOnce(this,'" . ts('Processing') . "');"],
      ],
      [
        'type' => 'submit',
        'name' => ts('Generate Cards'),
        'subName' => 'generate',
        'isDefault' => TRUE,
        'js' => ['onclick' => "return submitOnce(this,'" . ts('Processing') . "');"],
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->assign('templates', $this->_templates);
    $this->assign('membershipTypes', $this->_membershipTypes);
  }

  public function postProcess() {
    $values = $this->exportValues();
    $buttonName = $this->controller->getButtonName();

    try {
      // Get memberships based on criteria
      $memberships = $this->getMemberships($values);

      if (empty($memberships)) {
        CRM_Core_Session::setStatus(ts('No memberships found matching the criteria.'), ts('No Results'), 'warning');
        return;
      }

      if ($buttonName == $this->getButtonName('submit', 'preview')) {
        // Preview mode - show results
        $this->previewResults($memberships, $values);
      }
      else {
        // Generate cards
        $this->generateCards($memberships, $values);
      }

    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(
        ts('Error processing bulk generation: %1', [1 => $e->getMessage()]),
        ts('Error'),
        'error'
      );
    }
  }

  protected function getMemberships($values) {
    $whereClause = "WHERE 1=1";
    $params = [];
    $paramIndex = 1;

    // Membership type filter
    if (!empty($values['membership_type_id'])) {
      $whereClause .= " AND m.membership_type_id = %{$paramIndex}";
      $params[$paramIndex] = [$values['membership_type_id'], 'Integer'];
      $paramIndex++;
    }

    // Status filter
    if (!empty($values['membership_status'])) {
      switch ($values['membership_status']) {
        case 'current':
          $whereClause .= " AND ms.name IN ('Current', 'New', 'Grace')";
          break;
        case 'new':
          $whereClause .= " AND ms.name = 'New'";
          break;
        case 'grace':
          $whereClause .= " AND ms.name = 'Grace'";
          break;
      }
    }

    // Date filters
    if (!empty($values['start_date'])) {
      $whereClause .= " AND m.start_date >= %{$paramIndex}";
      $params[$paramIndex] = [$values['start_date'], 'String'];
      $paramIndex++;
    }

    if (!empty($values['end_date'])) {
      $whereClause .= " AND m.end_date <= %{$paramIndex}";
      $params[$paramIndex] = [$values['end_date'], 'String'];
      $paramIndex++;
    }

    // Exclude existing cards if not regenerating
    if (empty($values['regenerate_existing'])) {
      $whereClause .= " AND m.id NOT IN (
        SELECT DISTINCT membership_id
        FROM civicrm_membership_card
        WHERE template_id = %{$paramIndex}
      )";
      $params[$paramIndex] = [$values['template_id'], 'Integer'];
      $paramIndex++;
    }

    // Limit
    $limitClause = '';
    if (!empty($values['limit'])) {
      $limitClause = "LIMIT " . (int)$values['limit'];
    }

    $sql = "
      SELECT
        m.id as membership_id,
        m.contact_id,
        m.membership_type_id,
        m.status_id,
        m.start_date,
        m.end_date,
        c.display_name,
        c.email,
        mt.name as membership_type_name,
        ms.name as membership_status
      FROM civicrm_membership m
      INNER JOIN civicrm_contact c ON m.contact_id = c.id
      INNER JOIN civicrm_membership_type mt ON m.membership_type_id = mt.id
      INNER JOIN civicrm_membership_status ms ON m.status_id = ms.id
      {$whereClause}
      AND c.is_deleted = 0
      ORDER BY c.display_name
      {$limitClause}
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $memberships = [];
    while ($dao->fetch()) {
      $memberships[] = [
        'membership_id' => $dao->membership_id,
        'contact_id' => $dao->contact_id,
        'display_name' => $dao->display_name,
        'email' => $dao->email,
        'membership_type_name' => $dao->membership_type_name,
        'membership_status' => $dao->membership_status,
        'start_date' => $dao->start_date,
        'end_date' => $dao->end_date,
      ];
    }

    return $memberships;
  }

  protected function previewResults($memberships, $values) {
    $template = $this->getTemplate($values['template_id']);

    $this->assign('preview', TRUE);
    $this->assign('memberships', $memberships);
    $this->assign('membershipCount', count($memberships));
    $this->assign('template', $template);
    $this->assign('formValues', $values);

    CRM_Core_Session::setStatus(
      ts('Found %1 memberships matching your criteria.', [1 => count($memberships)]),
      ts('Preview Results'),
      'info'
    );
  }

  protected function generateCards($memberships, $values) {
    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    foreach ($memberships as $membership) {
      try {
        // Generate card
        $result = civicrm_api3('MembershipCard', 'generate', [
          'membership_id' => $membership['membership_id'],
          'template_id' => $values['template_id'],
          'force_regenerate' => !empty($values['regenerate_existing']),
        ]);

        $successCount++;

        // Email card if requested
        if (!empty($values['email_cards']) && !empty($membership['email'])) {
          $this->emailCard($result['id'], $membership);
        }

      }
      catch (Exception $e) {
        $errorCount++;
        $errors[] = ts('Error generating card for %1: %2', [
          1 => $membership['display_name'],
          2 => $e->getMessage()
        ]);
      }
    }

    // Display results
    if ($successCount > 0) {
      CRM_Core_Session::setStatus(
        ts('Successfully generated %1 membership cards.', [1 => $successCount]),
        ts('Success'),
        'success'
      );
    }

    if ($errorCount > 0) {
      CRM_Core_Session::setStatus(
        ts('%1 errors occurred during generation.', [1 => $errorCount]) .
        (!empty($errors) ? '<br>' . implode('<br>', array_slice($errors, 0, 5)) : ''),
        ts('Errors'),
        'error'
      );
    }

    // Redirect to cards list
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/membership-cards'));
  }

  protected function emailCard($cardId, $membership) {
    try {
      civicrm_api3('MembershipCard', 'email', [
        'card_id' => $cardId,
        'email_to' => $membership['email'],
        'email_subject' => ts('Your Membership Card'),
        'email_message' => ts('Dear %1, please find your membership card attached.', [
          1 => $membership['display_name']
        ]),
      ]);
    }
    catch (Exception $e) {
      // Log error but don't fail the process
      CRM_Core_Error::debug_log_message('Failed to email card: ' . $e->getMessage());
    }
  }

  protected function getTemplates() {
    try {
      $result = civicrm_api3('MembershipCardTemplate', 'get', [
        'is_active' => 1,
        'options' => ['sort' => 'name ASC'],
      ]);
      return $result['values'];
    }
    catch (Exception $e) {
      return [];
    }
  }

  protected function getTemplate($templateId) {
    try {
      return civicrm_api3('MembershipCardTemplate', 'getsingle', [
        'id' => $templateId,
      ]);
    }
    catch (Exception $e) {
      return NULL;
    }
  }
}
