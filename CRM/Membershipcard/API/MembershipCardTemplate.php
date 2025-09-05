<?php
/**
 * CRM/Membershipcard/API/MembershipCardTemplate.php
 * API for managing membership card templates
 */

class CRM_Membershipcard_API_MembershipCardTemplate {

  /**
   * Create or update a membership card template
   */
  public static function create($params) {
    $required = ['name'];
    foreach ($required as $field) {
      if (empty($params[$field])) {
        throw new API_Exception("Missing required field: $field");
      }
    }

    $template = new CRM_Membershipcard_DAO_MembershipCardTemplate();

    if (!empty($params['id'])) {
      $template->id = $params['id'];
      $template->find(TRUE);
      if (!$template->id) {
        throw new API_Exception("Template not found");
      }
    }

    $template->name = $params['name'];
    $template->description = CRM_Utils_Array::value('description', $params);
    $template->card_width = CRM_Utils_Array::value('card_width', $params, 350);
    $template->card_height = CRM_Utils_Array::value('card_height', $params, 220);
    $template->background_color = CRM_Utils_Array::value('background_color', $params, '#ffffff');
    $template->front_background_color = CRM_Utils_Array::value('front_background_color', $params, '#ffffff');
    $template->back_background_color = CRM_Utils_Array::value('back_background_color', $params, '#ffffff');
    $template->background_image = CRM_Utils_Array::value('background_image', $params);
    $template->front_background_image = CRM_Utils_Array::value('front_background_image', $params);
    $template->back_background_image = CRM_Utils_Array::value('back_background_image', $params);
    $template->elements = $params['elements'];
    $template->front_elements = $params['front_elements'];
    $template->back_elements = $params['back_elements'];
    $template->is_dual_sided = CRM_Utils_Array::value('is_dual_sided', $params, 0);
    $template->is_active = CRM_Utils_Array::value('is_active', $params, 1);

    if (empty($template->id)) {
      $template->created_date = date('Y-m-d H:i:s');
    }
    $template->modified_date = date('Y-m-d H:i:s');

    $template->save();

    return [
      'id' => $template->id,
      'name' => $template->name,
      'is_active' => $template->is_active,
    ];
  }

  /**
   * Get membership card templates
   */
  public static function get($params) {
    $template = new CRM_Membershipcard_DAO_MembershipCardTemplate();

    if (!empty($params['id'])) {
      $template->id = $params['id'];
    }

    if (isset($params['is_active'])) {
      $template->is_active = $params['is_active'];
    }

    $template->find();

    $results = [];
    while ($template->fetch()) {
      $results[] = [
        'id' => $template->id,
        'name' => $template->name,
        'description' => $template->description,
        'card_width' => $template->card_width,
        'card_height' => $template->card_height,
        'background_color' => $template->background_color,
        'background_image' => $template->background_image,
        'elements' => $template->elements,
        'is_active' => $template->is_active,
        'created_date' => $template->created_date,
        'modified_date' => $template->modified_date,
      ];
    }

    return ['values' => $results];
  }

  /**
   * Delete a membership card template
   */
  public static function delete($params) {
    if (empty($params['id'])) {
      throw new API_Exception("Missing required field: id");
    }

    $template = new CRM_Membershipcard_DAO_MembershipCardTemplate();
    $template->id = $params['id'];

    if (!$template->find(TRUE)) {
      throw new API_Exception("Template not found");
    }

    $template->delete();

    return ['is_error' => 0];
  }
}
