<?php

use CRM_Membershipcard_ExtensionUtil as E;

class CRM_Membershipcard_BAO_MembershipCardTemplate extends CRM_Membershipcard_DAO_MembershipCardTemplate {

  /**
   * Create or update a membership card template.
   *
   * @param array $params
   *   Parameters for the template.
   *
   * @return CRM_Membershipcard_BAO_MembershipCardTemplate
   *   The created or updated template object.
   */
  public static function create(array $params) {
    $template = new self();
    $template->copyValues($params);
    $template->save();
    return $template;
  }

  public static function getTemplateById($id, $jsonDecode = FALSE) {
    $template = new self();
    $template->id = $id;
    if ($template->find(TRUE)) {
      if ($jsonDecode) {
        $template->elements = json_decode($template->elements, TRUE);
      }
      return $template;
    }
    return NULL;
  }

  public static function getAllTemplates() {
    $templates = [];
    $dao = new self();
    while ($dao->find()) {
      $templates[] = clone $dao;
    }
    return $templates;
  }
  public static function deleteTemplate($id) {
    $template = self::getTemplateById($id);
    if ($template) {
      $template->delete();
      return TRUE;
    }
    return FALSE;
  }

  public static function getActiveTemplates() {
    $templates = [];
    $dao = new self();
    $dao->is_active = 1;
    while ($dao->find()) {
      $templates[] = clone $dao;
    }
    return $templates;
  }

  public static function getTemplateByName($name) {
    $template = new self();
    $template->name = $name;
    if ($template->find(TRUE)) {
      return $template;
    }
    return NULL;
  }


}
