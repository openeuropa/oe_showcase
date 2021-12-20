<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

/**
 * Tests the news content type.
 */
class NewsTest extends ShowcaseExistingSiteTestBase {

  /**
   * A user with permission to create News items.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor1;

  /**
   * A user with permission to create News items.
   *
   * @var \Drupal\user\UserInterface
   */
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
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

    // Create two editors for testing.
    $this->editor1 = $this->createUser($permissions);
    $this->editor2 = $this->createUser($permissions);
  }

  /**
   * Create a News item as Editor 1.
   */
  public function testNewsCreation(): void {
    // Assert session.
    $assert_session = $this->assertSession();

    // Since we will create a news through the UI, we need to make sure that
    // it's deleted at the end of the test.
    $this->markEntityTypeForCleanup('node');

    $this->drupalLogin($this->editor1);
    $this->drupalGet('node/add/oe_news_simple');
    $page = $this->getSession()->getPage();

    // Fill user fields.
    $page->fillField('Title', 'Example title');
    $page->fillField('Content', 'Example Content');

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
    $node = $this->getNodeByTitle('Example title');

    $this->assertNotEmpty($node, 'Node found in database.');
    // $this->drupalGet('node/' . $node->id() . '/edit');
    // Better:
    $this->drupalGet($node->toUrl('edit-form'));
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
