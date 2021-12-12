<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\oe_showcase\Traits\AuthenticationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Ensure OE Showcase News are created.
 *
 * @group oe_showcase
 */
class ShowcaseNewsTest extends BrowserTestBase {

  use SparqlConnectionTrait;
  use AuthenticationTrait;
  use ContentTypeCreationTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'config',
    'datetime',
    'image',
    'oe_showcase_news',
    'path',
    'system',
    'user',
    'allowed_formats',
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_settings',
    'oe_paragraphs',
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
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * A user with permission to create News items.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor1;
  protected $editor2;

  /**
   * The test user permissions.
   *
   * @var array
   */
  protected $userPermissions = [
    'access content overview',
    'create oe_news_simple content',
    'delete any oe_news_simple content',
    'delete oe_news_simple revisions',
    'delete own oe_news_simple content',
    'edit any oe_news_simple content',
    'edit own oe_news_simple content',
    'revert oe_news_simple revisions',
    'view oe_news_simple revisions',
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
   * The test node.
   *
   * @var array
   */
  protected $node;

  /**
   * The test media document.
   *
   * @var array
   */
  protected $document;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpSparql();

    // Disable EU Login.
    $this->disableForcedLogin();

    // Create both editors for testing.
    $this->editor1 = $this->drupalCreateUser($this->userPermissions, NULL, FALSE);
    $this->editor2 = $this->drupalCreateUser($this->userPermissions, NULL, FALSE);
  }

  /**
   * Create a News item as Editor 1.
   */
  public function testCreateShowCaseNews() {
    // Assert session.
    $assert_session = $this->assertSession();
    $now = \Drupal::time()->getRequestTime();
    
    $this->drupalLogin($this->editor1);
    $this->drupalGet('node/add/oe_news_simple');
    $page = $this->getSession()->getPage();

    // Fill user fields.
    $page->fillField('Title', 'Example title');
    $page->fillField('Content', 'Example Content');
    
    // Create document media.
    $pdf_file = file_save_data(file_get_contents(__DIR__ . '/fixtures/example_1.jpeg'), 'public://example_1.jpeg');
    $pdf_file->setPermanent();
    $pdf_file->save();
    
    $date = $now - 86400;
    $page->fillField('field_news_publication_date[0][value][date]', '2021-12-12');
    $page->fillField('field_news_publication_date[0][value][time]', '00:00:00');
    
    // $test_image = current($this->drupalGetTestFiles('image'));
    // $page->fillField('files[field_news_image_0]', \Drupal::service('file_system')->realpath($test_image->uri));
    
    
    // $field = $this->getSession()->getPage()->findField('files[field_news_image_0]');
    // $field->attachFile($this->container->get('file_system')->realpath($test_image->uri));
    // $page->fillField('field_news_image[0][alt]', 'sdfsdfsdfs');
    
    
    
    $page->pressButton('Save');
    
    $this->drupalGet('admin/content');
    $page = $this->getSession()->getPage();
    $page->clickLink('Example title');

    $assert_session->pageTextContains('Example title');
    $assert_session->pageTextContains('Example Content');
    // $assert_session->pageTextContains('Publication date 12/12/2021 - 00:00');
    
    
    // Edit node by another editor.
    // Assert session.
    $this->drupalLogout();
    $this->drupalLogin($this->editor2);
    
    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle('Example title');

    $this->assertNotEmpty($node, 'Node found in database.');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page = $this->getSession()->getPage();

    // Fill user fields.
    $page->fillField('Title', 'Example title editor 2');
    $page->fillField('Content', 'Example Content editor 2');
    $page->pressButton('Save');
    
    $this->drupalGet('admin/content');
    $page = $this->getSession()->getPage();
    $page->clickLink('Example title editor 2');
    
    $assert_session->pageTextContains('Example title editor 2');
    $assert_session->pageTextContains('Example Content editor 2');
  }
}
