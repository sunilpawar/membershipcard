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
    CRM_Core_Resources::singleton()->addScriptFile('com.skvare.membershipcard', 'js/membership-tab.js');
    CRM_Core_Resources::singleton()->addStyleFile('com.skvare.membershipcard', 'css/membership-card.css');
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
