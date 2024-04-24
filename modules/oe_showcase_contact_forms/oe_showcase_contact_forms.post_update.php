<?php

/**
 * @file
 * OE Showcase Contact form post updates.
 */

declare(strict_types=1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Add fields in contact form message.
 */
function oe_showcase_contact_forms_post_update_00001(&$sandbox): void {
  ConfigImporter::importSingle(
    'module',
    'oe_showcase_contact_forms',
    '/config/post_updates/00001_show_fields',
    'contact.form.example_contact_form'
  );
}
