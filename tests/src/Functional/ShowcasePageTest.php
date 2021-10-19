<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Ensure OE Showcase Pages are created.
 *
 * @group oe_showcase
 */
class ShowcasePageTest extends BrowserTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'system',
    'user',
    'ckeditor',
    'oe_showcase_page'
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'oe_showcase';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * The test user permissions.
   *
   * @var array
   */
  protected $userPermissions = [
    'access toolbar',
    'create oe_showcase_page content',
    'edit any oe_showcase_page content',
    'delete any oe_showcase_page content',
    'access content overview',
  ];

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->testUser = $this->drupalCreateUser($this->userPermissions, NULL, TRUE);
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests the view exists and has a block display.
   */
  public function testCreateShowCasePage() {
    $assert_session = $this->assertSession();

    // Create a Rich text paragraph to be added to the test page.
    $richText = Paragraph::create([
      'type' => 'oe_rich_text',
      'field_oe_title' => 'Rich Text paragraphs title test',
      'field_oe_text_long' => array(
        "value"  =>  'Rich Text paragraphs Body test',
        "format" => "full_html"
      ),
    ]);
    $richText->save();

    // Create page node.
    $settings = [
      'title' => 'Demo showcase page',
      'field_description' => 'Demo showcase page description',
      'field_body' => array(
        array(
          'target_id' => $richText->id(),
          'target_revision_id' => $richText->getRevisionId(),
        ),
      ),
      'type' => 'oe_showcase_page',
      'uid' => \Drupal::currentUser()->id()
    ];
    $node = Node::create($settings);
    $node->save();

    // Get Demo page created.
    $this->drupalGet('node/' . $node->id());
    $assert_session->statusCodeEquals(200);

    // Confirm page has been created and has the following elements
    $assert_session->pageTextContains('Demo showcase page');
    // Assert that the page description is correct.
    $assert_session->pageTextContains('Demo showcase page description');
    // Assert the Rich text title.
    $assert_session->pageTextContains('Rich Text paragraphs title test');
    // Assert the Rich text body.
    $assert_session->pageTextContains('Rich Text paragraphs body test');
  }

}
