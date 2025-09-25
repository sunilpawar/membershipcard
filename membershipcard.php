<?php

require_once 'membershipcard.civix.php';

use CRM_Membershipcard_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function membershipcard_civicrm_config(&$config): void {
  _membershipcard_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function membershipcard_civicrm_install(): void {
  _membershipcard_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function membershipcard_civicrm_enable(): void {
  _membershipcard_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function membershipcard_civicrm_navigationMenu(&$menu) {
  _membershipcard_civix_insert_navigation_menu($menu, 'Memberships', array(
    'label' => E::ts('Membership Cards'),
    'name' => 'membership_cards',
    'url' => 'civicrm/membership-cards',
    'permission' => 'access CiviMember',
    'operator' => 'OR',
    'separator' => 0,
  ));

  _membershipcard_civix_insert_navigation_menu($menu, 'Memberships', array(
    'label' => E::ts('Card Templates'),
    'name' => 'membership_card_templates',
    'url' => 'civicrm/membership-card-templates',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ));

  _membershipcard_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_pageRun().
 */
function membershipcard_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');

  if ($pageName == 'CRM_Member_Page_Tab') {
    // Add membership card link to membership tab
    //CRM_Core_Resources::singleton()->addScriptFile('com.skvare
    //.membershipcard', 'js/membership-tab.js');
    //CRM_Core_Resources::singleton()->addStyleFile('com.skvare
    //.membershipcard', 'css/membership-card.css');
  }
}

/**
 * Get available tokens for membership cards
 */
function membershipcard_get_tokens() {
  return [
    'contact' => [
      'display_name' => 'Full Name',
      'first_name' => 'First Name',
      'last_name' => 'Last Name',
      'email' => 'Email Address',
      'phone' => 'Phone Number',
      'street_address' => 'Street Address',
      'city' => 'City',
      'state_province' => 'State/Province',
      'postal_code' => 'Postal Code',
      'image_URL' => 'Contact Photo',
    ],
    'membership' => [
      'membership_type' => 'Membership Type',
      'status' => 'Membership Status',
      'start_date' => 'Start Date',
      'end_date' => 'End Date',
      'join_date' => 'Join Date',
      'membership_id' => 'Membership ID',
      'source' => 'Membership Source',
    ],
    'organization' => [
      'organization_name' => 'Organization Name',
      'organization_logo' => 'Organization Logo',
      'organization_address' => 'Organization Address',
      'organization_phone' => 'Organization Phone',
      'organization_email' => 'Organization Email',
    ],
    'system' => [
      'current_date' => 'Current Date',
      'qr_code' => 'QR Code',
      'barcode' => 'Barcode',
    ],
  ];
}
/**
 * Implements hook_civicrm_qType().
 */
function membershipcard_civicrm_entityTypes(&$entityTypes) {
  $civiVersion = CRM_Utils_System::version();
  $membershipType = 'CRM_Member_DAO_MembershipType';
  if (version_compare($civiVersion, '5.75.0') >= 0) {
    $membershipType = 'MembershipType';
  }
  $entityTypes[$membershipType]['fields_callback'][]
    = function ($class, &$fields) {
    $fields['template_id'] = [
      'name' => 'template_id',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => ts('Membership Template Card'),
      'description' => 'List of membership card',
      'localizable' => 0,
      'maxlength' => 128,
      'size' => CRM_Utils_Type::HUGE,
      'import' => TRUE,
      'where' => 'civicrm_membership_type.template_id',
      'export' => TRUE,
      'table_name' => 'civicrm_membership_type',
      'entity' => 'MembershipType',
      'bao' => 'CRM_Member_BAO_MembershipType',
      'localizable' => 1,
      'input_attrs' => [
        'multiple' => '0',
      ],
      'html' => [
        'type' => 'Select',
        'multiple' => FALSE,
        'label' => ts("Membership Template Card"),
      ],
      'pseudoconstant' => [
        'callback' => 'CRM_Membershipcard_API_MembershipCardTemplate::cardTemplates',
      ],
      //'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
    ];
  };
}

function membershipcard_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Member_Form_MembershipType') {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType');
    $shippableTo = CRM_Membershipcard_API_MembershipCardTemplate::cardTemplates();
    $form->add('select', 'template_id', E::ts('Membership Template Card'),
      $shippableTo, FALSE, ['class' => 'crm-select2 huge', 'multiple' => 0]);
    if ($form->_action & CRM_Core_Action::UPDATE) {
      $membershipExtras = CRM_Membershipcard_API_MembershipCardTemplate::getCardTemplatesSettings($form->_id);
      $form->setDefaults($membershipExtras);
    }
  }
}

/**
 * Implements hook_civicrm_links().
 *
 * Add custom links to membership records in the membership tab.
 *
 * @param string $op
 *   The type of operation being performed.
 * @param string $objectName
 *   The name of the object.
 * @param int $objectId
 *   The ID of the object.
 * @param array $links
 *   The array of links to be displayed.
 * @param int $mask
 *   The permission mask for the current user.
 * @param array $values
 *   The values for the current object.
 */
function membershipcard_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  // Only act on membership records
  if ($objectName != 'Membership') {
    return;
  }

  // Only act on the membership tab view operations
  if ($op == 'membership.tab.row') {

    // Add "Generate Card" link
    $links[] = [
      'name' => ts('Generate Card'),
      'title' => ts('Generate Membership Card'),
      'url' => '#', // Use # since we'll handle with JavaScript
      'class' => 'action-item no-popup crm-membership-generate-card-ajax',
      'icon' => 'fa-id-card',
      'weight' => 100,
      'extra' => "data-membership-id={$objectId}",
    ];

    // Add "View Cards" link if cards exist
    $cardID = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_membership_card WHERE membership_id = %1",
      [1 => [$objectId, 'Integer']]
    );

    if ($cardID) {
      $links[] = [
        'name' => ts('Download Card'),
        'title' => ts('Download Membership Card'),
        'url' => CRM_Utils_System::url('civicrm/membership-card/download', [
          'card_id' => $cardID,
          'reset' => 1,
        ]),
        'class' => 'action-item no-popup',
        'icon' => 'fa-eye',
        'weight' => 101,
      ];
    }

    // Add "Email Card" link for active memberships
    if (FALSE && !empty($values['status']) && $values['status'] == 'Current') {
      $links[] = [
        'name' => ts('Email Card'),
        'title' => ts('Email Membership Card'),
        'url' => '#',
        'onclick' => "emailMembershipCard({$objectId}); return false;",
        'class' => 'action-item',
        'icon' => 'fa-envelope',
        'weight' => 102,
      ];
    }
  }

  // For membership selector/search results
  if (FALSE && $op == 'membership.selector.row') {
    $links[] = [
      'name' => ts('Cards'),
      'title' => ts('Manage Membership Cards'),
      'url' => CRM_Utils_System::url('civicrm/membership-cards', [
        'membership_id' => $objectId,
        'reset' => 1,
      ]),
      'class' => 'action-item small-popup',
      'icon' => 'fa-id-card-o',
      'weight' => 50,
    ];
  }
}
