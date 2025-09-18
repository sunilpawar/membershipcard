<?php

use CRM_Membershipcard_ExtensionUtil as E;

/**
 * Page for listing and managing membership cards
 */
class CRM_Membershipcard_Page_MembershipCards extends CRM_Core_Page {

  protected $_action;
  protected $_id;

  public function run() {
    CRM_Utils_System::setTitle(ts('Membership Cards'));

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    // Add resources
    CRM_Core_Resources::singleton()->addScriptFile('com.skvare.membershipcard', 'js/membership-cards.js');
    CRM_Core_Resources::singleton()->addStyleFile('com.skvare.membershipcard', 'css/membership-cards.css');

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->deleteCard();
    }
    else {
      $this->showCardList();
    }

    parent::run();
  }

  private function showCardList() {
    // Get filter parameters
    $contactId = CRM_Utils_Request::retrieve('contact_id', 'Positive', $this);
    $membershipTypeId = CRM_Utils_Request::retrieve('membership_type_id', 'Positive', $this);
    $templateId = CRM_Utils_Request::retrieve('template_id', 'Positive', $this);
    $status = CRM_Utils_Request::retrieve('status', 'String', $this);

    // Pagination
    $offset = CRM_Utils_Request::retrieve('offset', 'Integer', $this, FALSE, 0);
    $offset = $offset ?? 0;
    $limit = 25; // Cards per page

    // Build WHERE conditions
    $whereConditions = [];
    $sqlParams = [];
    $paramIndex = 1;

    if ($contactId) {
      $whereConditions[] = "m.contact_id = %{$paramIndex}";
      $sqlParams[$paramIndex] = [$contactId, 'Integer'];
      $paramIndex++;
    }

    if ($membershipTypeId) {
      $whereConditions[] = "m.membership_type_id = %{$paramIndex}";
      $sqlParams[$paramIndex] = [$membershipTypeId, 'Integer'];
      $paramIndex++;
    }

    if ($templateId) {
      $whereConditions[] = "mc.template_id = %{$paramIndex}";
      $sqlParams[$paramIndex] = [$templateId, 'Integer'];
      $paramIndex++;
    }

    if ($status) {
      if ($status === 'current') {
        $whereConditions[] = "ms.name IN ('Current', 'New', 'Grace')";
      }
      elseif ($status === 'expired') {
        $whereConditions[] = "(m.end_date < NOW() OR ms.name IN ('Expired', 'Cancelled'))";
      }
    }

    $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

    // Get total count for pagination
    $countSql = "
      SELECT COUNT(*)
      FROM civicrm_membership_card mc
      INNER JOIN civicrm_membership m ON mc.membership_id = m.id
      INNER JOIN civicrm_contact c ON m.contact_id = c.id
      INNER JOIN civicrm_membership_type mt ON m.membership_type_id = mt.id
      INNER JOIN civicrm_membership_status ms ON m.status_id = ms.id
      INNER JOIN civicrm_membership_card_template mct ON mc.template_id = mct.id
      {$whereClause}
    ";

    $totalCount = CRM_Core_DAO::singleValueQuery($countSql, $sqlParams);

    // Get cards with all related data
    $sql = "
      SELECT
        mc.id as card_id,
        mc.membership_id,
        mc.template_id,
        mc.created_date as card_created_date,
        mc.modified_date as card_modified_date,
        m.contact_id,
        m.membership_type_id,
        m.status_id,
        m.start_date,
        m.end_date,
        m.join_date,
        m.source as membership_source,
        c.display_name,
        c.first_name,
        c.last_name,
        e.email,
        p.phone,
        c.image_URL,
        mt.name as membership_type_name,
        mt.minimum_fee,
        ms.name as membership_status,
        ms.label as membership_status_label,
        mct.name as template_name,
        mct.card_width,
        mct.card_height,
        mct.front_background_color,
        mct.back_background_color
      FROM civicrm_membership_card mc
      INNER JOIN civicrm_membership m ON mc.membership_id = m.id
      INNER JOIN civicrm_contact c ON m.contact_id = c.id
      LEFT JOIN civicrm_email e ON e.contact_id = c.id and e.is_primary = 1
      LEFT JOIN civicrm_phone p ON p.contact_id = c.id and p.is_primary = 1
      INNER JOIN civicrm_membership_type mt ON m.membership_type_id = mt.id
      INNER JOIN civicrm_membership_status ms ON m.status_id = ms.id
      INNER JOIN civicrm_membership_card_template mct ON mc.template_id = mct.id
      {$whereClause}
      ORDER BY mc.created_date DESC, c.display_name
      LIMIT {$offset}, {$limit}
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);

    $cards = [];
    while ($dao->fetch()) {
      $isExpired = (!empty($dao->end_date) && strtotime($dao->end_date) < time());
      $isActive = in_array($dao->membership_status, ['Current', 'New', 'Grace']) && !$isExpired;

      $cards[] = [
        'card_id' => $dao->card_id,
        'membership_id' => $dao->membership_id,
        'template_id' => $dao->template_id,
        'contact_id' => $dao->contact_id,
        'display_name' => $dao->display_name,
        'first_name' => $dao->first_name,
        'last_name' => $dao->last_name,
        'email' => $dao->email,
        'phone' => $dao->phone,
        'image_URL' => $dao->image_URL,
        'membership_type_name' => $dao->membership_type_name,
        'membership_status' => $dao->membership_status,
        'membership_status_label' => $dao->membership_status_label,
        'start_date' => $dao->start_date,
        'end_date' => $dao->end_date,
        'join_date' => $dao->join_date,
        'membership_source' => $dao->membership_source,
        'template_name' => $dao->template_name,
        'card_width' => $dao->card_width,
        'card_height' => $dao->card_height,

        'front_background_color' => $dao->front_background_color,
        'back_background_color' => $dao->front_background_color,

        'card_created_date' => $dao->card_created_date,
        'card_modified_date' => $dao->card_modified_date,
        'is_active' => $isActive,
        'is_expired' => $isExpired,
        'days_until_expiry' => $isExpired ? 0 : (!empty($dao->end_date) ? round((strtotime($dao->end_date) - time()) / (60 * 60 * 24)) : NULL),
        'download_url' => CRM_Utils_System::url('civicrm/membership-card/download', "card_id={$dao->card_id}", TRUE),
        'verify_url' => CRM_Utils_System::url('civicrm/membership-card/verify', "id={$dao->membership_id}", TRUE),
        'contact_url' => CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$dao->contact_id}", TRUE),
        'membership_url' => CRM_Utils_System::url('civicrm/contact/view/membership', "reset=1&cid={$dao->contact_id}&id={$dao->membership_id}&action=view", TRUE),
      ];
    }

    // Get filter options
    $membershipTypes = CRM_Member_PseudoConstant::membershipType();
    $templates = [];
    $templateDao = CRM_Core_DAO::executeQuery("SELECT id, name FROM civicrm_membership_card_template WHERE is_active = 1 ORDER BY name");
    while ($templateDao->fetch()) {
      $templates[$templateDao->id] = $templateDao->name;
    }

    // Calculate pagination
    $totalPages = ceil($totalCount / $limit);
    $currentPage = floor($offset / $limit) + 1;

    // Assign template variables
    $this->assign('cards', $cards);
    $this->assign('totalCount', $totalCount);
    $this->assign('currentCount', count($cards));
    $this->assign('membershipTypes', $membershipTypes);
    $this->assign('templates', $templates);
    $this->assign('currentFilters', [
      'contact_id' => $contactId,
      'membership_type_id' => $membershipTypeId,
      'template_id' => $templateId,
      'status' => $status,
    ]);

    // Pagination info
    $this->assign('pagination', [
      'total_pages' => $totalPages,
      'current_page' => $currentPage,
      'offset' => $offset,
      'limit' => $limit,
      'showing_from' => $offset + 1,
      'showing_to' => min($offset + $limit, $totalCount),
    ]);

    // Get summary stats
    $this->assignSummaryStats();
  }

  private function assignSummaryStats() {
    // Get various statistics
    $stats = [];

    // Total cards
    $stats['total_cards'] = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_membership_card");

    // Active memberships with cards
    $stats['active_cards'] = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*)
      FROM civicrm_membership_card mc
      INNER JOIN civicrm_membership m ON mc.membership_id = m.id
      INNER JOIN civicrm_membership_status ms ON m.status_id = ms.id
      WHERE ms.name IN ('Current', 'New', 'Grace')
      AND (m.end_date IS NULL OR m.end_date >= CURDATE())
    ");

    // Expired cards
    $stats['expired_cards'] = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*)
      FROM civicrm_membership_card mc
      INNER JOIN civicrm_membership m ON mc.membership_id = m.id
      INNER JOIN civicrm_membership_status ms ON m.status_id = ms.id
      WHERE (m.end_date < CURDATE() OR ms.name IN ('Expired', 'Cancelled'))
    ");

    // Cards expiring soon (next 30 days)
    $stats['expiring_soon'] = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*)
      FROM civicrm_membership_card mc
      INNER JOIN civicrm_membership m ON mc.membership_id = m.id
      INNER JOIN civicrm_membership_status ms ON m.status_id = ms.id
      WHERE ms.name IN ('Current', 'New', 'Grace')
      AND m.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ");

    // Most used template
    $mostUsedTemplate = CRM_Core_DAO::executeQuery("
      SELECT mct.name, COUNT(*) as usage_count
      FROM civicrm_membership_card mc
      INNER JOIN civicrm_membership_card_template mct ON mc.template_id = mct.id
      GROUP BY mc.template_id
      ORDER BY usage_count DESC
      LIMIT 1
    ");

    if ($mostUsedTemplate->fetch()) {
      $stats['most_used_template'] = $mostUsedTemplate->name . ' (' . $mostUsedTemplate->usage_count . ' cards)';
    }
    else {
      $stats['most_used_template'] = 'None';
    }

    // Recent activity (cards generated in last 7 days)
    $stats['recent_cards'] = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*)
      FROM civicrm_membership_card
      WHERE created_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");

    $this->assign('stats', $stats);
  }

  private function deleteCard() {
    if (!$this->_id) {
      CRM_Core_Error::fatal('Card ID is required for deletion');
    }

    try {
      // Get card info before deletion
      $card = CRM_Core_DAO::executeQuery("
        SELECT mc.*, c.display_name
        FROM civicrm_membership_card mc
        INNER JOIN civicrm_membership m ON mc.membership_id = m.id
        INNER JOIN civicrm_contact c ON m.contact_id = c.id
        WHERE mc.id = %1
      ", [1 => [$this->_id, 'Integer']]);

      if (!$card->fetch()) {
        CRM_Core_Error::fatal('Card not found');
      }

      $memberName = $card->display_name;

      // Delete the card
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_membership_card WHERE id = %1", [
        1 => [$this->_id, 'Integer']
      ]);

      CRM_Core_Session::setStatus(
        ts('Membership card for %1 has been deleted.', [1 => $memberName]),
        ts('Card Deleted'),
        'success'
      );

    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(
        ts('Error deleting membership card: %1', [1 => $e->getMessage()]),
        ts('Error'),
        'error'
      );
    }

    // Redirect back to list
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/membership-cards'));
  }

  /**
   * Get breadcrumb links
   */
  public function getTemplateFileName() {
    return 'CRM/Membershipcard/Page/MembershipCards.tpl';
  }

  /**
   * Get page title for breadcrumb
   */
  public function getTitle() {
    return ts('Membership Cards');
  }
}
