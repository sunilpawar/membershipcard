
CREATE TABLE IF NOT EXISTS civicrm_membership_card_template (
  id int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL COMMENT 'Template name',
  description text COMMENT 'Template description',
  card_width int DEFAULT 350 COMMENT 'Card width in pixels',
  card_height int DEFAULT 220 COMMENT 'Card height in pixels',
  background_color varchar(7) DEFAULT '#ffffff' COMMENT 'Background color',
  background_image varchar(255) COMMENT 'Background image path',
  elements longtext COMMENT 'JSON data for card elements',
  is_active tinyint DEFAULT 1 COMMENT 'Is template active',
  created_date datetime NOT NULL COMMENT 'When template was created',
  modified_date datetime COMMENT 'When template was modified'
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS civicrm_membership_card (
  id int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  membership_id int unsigned NOT NULL COMMENT 'FK to civicrm_membership',
  template_id int unsigned NOT NULL COMMENT 'FK to civicrm_membership_card_template',
  card_data longtext COMMENT 'Generated card data',
  qr_code varchar(255) COMMENT 'QR code data',
  barcode varchar(255) COMMENT 'Barcode data',
  created_date datetime NOT NULL COMMENT 'When card was created',
  modified_date datetime COMMENT 'When card was modified',
  CONSTRAINT FK_civicrm_membership_card_membership_id FOREIGN KEY (membership_id) REFERENCES civicrm_membership(id) ON DELETE CASCADE,
  CONSTRAINT FK_civicrm_membership_card_template_id FOREIGN KEY (template_id) REFERENCES civicrm_membership_card_template(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
