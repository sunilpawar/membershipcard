ALTER TABLE `civicrm_membership_type`
    ADD `template_id` int unsigned COMMENT 'FK to civicrm_membership_card_template',
    ADD CONSTRAINT FK_civicrm_membership_type_template_id FOREIGN KEY (`template_id`) REFERENCES `civicrm_membership_card_template`(`id`) ON DELETE SET NULL;