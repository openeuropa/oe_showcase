<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSiteJavascript;

use Drupal\Tests\oe_showcase\Traits\UserTrait;

/**
 * Test ai plugins.
 */
class AiPluginsTest extends ShowcaseExistingSiteJavascriptTestBase {

  use UserTrait;

  /**
   * Test AI content plugins.
   */
  public function testContentPlugins() {
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);
    $this->drupalGet('node/add/oe_sc_event');

    // Check content suggestion plugins are available.
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Alter tone');
    $assert_session->pageTextContains('Evaluate Readability');
    $assert_session->pageTextContains('Suggest title');
    $assert_session->pageTextContains('Summarise text');
    $assert_session->pageTextContains('Suggest taxonomy tags');
    $assert_session->pageTextContains('AI Assistant');
  }

}
