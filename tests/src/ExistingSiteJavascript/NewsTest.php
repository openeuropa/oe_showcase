<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\ExistingSiteJavascript;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\oe_showcase\Traits\WysiwygTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Class to test News content type on existing site tests.
 */
class NewsTest extends ShowcaseExistingSiteJavascriptTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;
  use WysiwygTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user.
    $user = $this->createUser([]);
    $this->drupalLogin($user);
  }

  /**
   * Check creation News content through the UI.
   */
  public function testCreateNews() {
    // Mark test content for deletion after the test has finished.
    $this->markEntityTypeForCleanup('node');
    $this->markEntityTypeForCleanup('media');
    $this->markEntityTypeForCleanup('file');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create a sample media entity to be embedded.
    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();
    $media_image = Media::create([
      'bundle' => 'image',
      'name' => 'Starter Image test',
      'oe_media_image' => [
        [
          'target_id' => $file->id(),
          'alt' => 'Starter Image test alt',
          'title' => 'Starter Image test title',
        ],
      ],
    ]);
    $media_image->save();

    // Assert editors don't have permissions to create News items.
    $this->drupalGet('node/add/oe_sc_news');
    $assert_session->pageTextContains('You are not authorized to access this page.');

    // Create editor user.
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);

    // Assert that the content field is required.
    $this->drupalGet('node/add/oe_sc_news');
    $page->fillField('Title', 'Example title');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Content field is required.');

    // Assert that editors have access to the Simple/Rich text formats.
    $assert_session->pageTextNotContains('This field has been disabled because you do not have sufficient permissions to edit it.');
    $introduction = $page->findField('Introduction');
    $this->assertEquals('simple_rich_text', $this->getWysiwigTextFormat($introduction));
    $text = '<p>Example Introduction with <strong>Strong</strong>, <em>Emphasised</em> and <a href="/">Link</a> text.</p>
    <ol><li>1</li> <li>2</li> <li>3</li></ol> <ul><li>4</li> <li>6</li> <li>5</li></ul> <blockquote>Lorem ipsum, famous last words</blockquote>';
    $this->enterTextInWysiwyg('Introduction', $text);
    $page->fillField('Title', 'Example title');
    $content = $page->findField('Content');
    $this->assertEquals('rich_text', $this->getWysiwigTextFormat($content));
    $this->enterTextInWysiwyg('Content', 'Example Content');
    // Assert that publication date was filled with a default value.
    $publication_date = $page->find('css', 'input[name="oe_publication_date[0][value][date]"]');
    $this->assertMatchesRegularExpression("/\d+\-\d+\-\d+/", $publication_date->getValue());
    // Set a custom publication date.
    $page->fillField('Date', '01/24/2022');
    $media_name = $media_image->getName() . ' (' . $media_image->id() . ')';
    $page->fillField('Media item', $media_name);
    $page->pressButton('Save');

    // Assert that news has been created.
    $assert_session->addressEquals('/en/news/example-title');
    $assert_session->pageTextContains('News Example title has been created.');
    $assert_session->pageTextContains('Example title');
    $assert_session->pageTextContains('Example Content');
    $assert_session->pageTextContains('Example Introduction with Strong, Emphasised and Link text. 1 2 3 4 6 5 Lorem ipsum, famous last words');
    $assert_session->elementExists('xpath', '//strong[text()="Strong"]');
    $assert_session->elementExists('xpath', '//em[text()="Emphasised"]');
    $assert_session->elementExists('xpath', '//a[@href="/" and text()="Link"]');
    $assert_session->elementNotExists('xpath', '//ol/li[text()="1" or text()="2" or text()="3"]');
    $assert_session->elementNotExists('xpath', '//ul/li[text()="4" or text()="6" or text()="5"]');
    $assert_session->elementNotExists('xpath', '//blockquote[text()="Lorem ipsum, famous last words"]');
    $assert_session->pageTextContains('24 January 2022');
    $assert_session->responseContains('image-test.png');
    $assert_session->responseContains('Starter Image test');
    $assert_session->responseContains('Starter Image test alt');

    $this->assertSocialShareBlock();
  }

}
