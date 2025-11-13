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
  public function testContentPlugins(): void {
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);
    // Test editoria11y is enabled.
    $this->drupalGet('home');
    $this->assertSession()->waitForElement('css', 'ed11y-element-panel');
    $this->assertSession()->elementExists('css', 'ed11y-element-panel');

    // Check content suggestion plugins are available.
    $this->drupalGet('node/add/oe_sc_event');
    $this->assertSession()->pageTextContains('Alter tone');
    $this->assertSession()->pageTextContains('Evaluate Readability');
    $this->assertSession()->pageTextContains('Suggest title');
    $this->assertSession()->pageTextContains('Summarise text');
    $this->assertSession()->pageTextContains('Suggest taxonomy tags');
    $this->assertSession()->pageTextContains('AI Assistant');

  }

}
