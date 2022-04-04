<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\ExistingSite;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Class to test Project content type on existing site tests.
 */
class ProjectTest extends ShowcaseExistingSiteTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user, use editor role.
    $user = $this->createUser([]);
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);
  }

  /**
   * Check creation Project content through the UI.
   */
  public function testCreateProject() {
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
      'name' => 'Project Image test',
      'oe_media_image' => [
        [
          'target_id' => $file->id(),
          'alt' => 'Project Image test alt',
          'title' => 'Project Image test title',
        ],
      ],
    ]);
    $media_image->save();

    // Create a node, fill the values.
    $this->drupalGet('node/add/oe_project');
    $page->fillField('Page title', 'Project page test');
    $page->fillField('Media item', 'Project Image test');
    $page->fillField('Teaser', 'Teaser text');
    $page->fillField('Subject', 'financing (http://data.europa.eu/uxp/1000)');

    // Project details.
    $page->selectFieldOption('oe_project_dates[0][value][day]', '3');
    $page->selectFieldOption('oe_project_dates[0][value][month]', '3');
    $page->selectFieldOption('oe_project_dates[0][value][year]', '2022');
    $page->selectFieldOption('oe_project_dates[0][end_value][day]', '3');
    $page->selectFieldOption('oe_project_dates[0][end_value][month]', '3');
    $page->selectFieldOption('oe_project_dates[0][end_value][year]', '2023');
    $page->fillField('Overall budget', '15000000');
    $page->fillField('EU contribution', '240000');
    $page->fillField('URL', 'http://example.com');
    $page->fillField('Link text', 'www.website-address.eu');
    $page->fillField('oe_project_funding_programme[0][target_id]', 'Anti Fraud Information System (AFIS) (http://publications.europa.eu/resource/authority/eu-programme/AFIS2020)');
    $page->fillField('Reference', '3425353');
    $page->pressButton('Add new organisation');
    $page->fillField('Name', 'Coordinator 1');
    $page->pressButton('Create organisation');

    // Summary, objective, impacts and achievements and milestones.
    $page->fillField('Summary', 'Text summary');
    $page->fillField('Objective', 'Text Objective');
    $page->fillField('Impacts', 'Text Impacts');
    $page->fillField('Achievements and milestones', 'Text Achievements and milestones');

    // Participants.
    $page->pressButton('Add new participant');
    $page->fillField('Name', 'Developer participant name');
    $page->fillField('Acronym', 'Developer participant acronym');
    $page->fillField('Country', 'BE');
    $page->fillField('Contribution to the budget', '19.9');
    $page->pressButton('Create participant');
    $page->pressButton('Save');

    // Assert values as anonymous user.
    $this->drupalLogout();
    $this->drupalGet('project/project-page-test');

    // Assert teaser.
    $assert_session->pageTextContains('Project page test');
    $assert_session->pageTextContains('Teaser text');
    $assert_session->responseContains('image-test.png');
    $assert_session->responseContains('financing');

    // Assert project details.
    $assert_session->pageTextContains('Project details');
    $assert_session->pageTextContains('Project period');
    $assert_session->pageTextContains('03 March 2022');
    $assert_session->pageTextContains('03 March 2023');
    $assert_session->pageTextContains('Overall budget');
    $assert_session->pageTextContains('€15.000.000,00');
    $assert_session->pageTextContains('EU contribution');
    $assert_session->pageTextContains('€240.000,00');
    $assert_session->pageTextContains('Website');
    $assert_session->pageTextContains('www.website-address.eu');
    $assert_session->pageTextContains('Funding programme');
    $assert_session->pageTextContains('Anti Fraud Information System (AFIS)');
    $assert_session->pageTextContains('Reference');
    $assert_session->pageTextContains('3425353');
    $assert_session->pageTextContains('Coordinators');
    $assert_session->pageTextContains('Coordinator 1');

    // Assert text fields.
    $assert_session->pageTextContains('Summary');
    $assert_session->pageTextContains('Text summary');
    $assert_session->pageTextContains('Objective');
    $assert_session->pageTextContains('Text Objective');
    $assert_session->pageTextContains('Impacts');
    $assert_session->pageTextContains('Text Impacts');
    $assert_session->pageTextContains('Achievements and milestones');
    $assert_session->pageTextContains('Text Achievements and milestones');

    // Assert participants.
    $assert_session->pageTextContains('Participants');
    $assert_session->pageTextContains('Name');
    $assert_session->pageTextContains('Developer participant acronym');
    $assert_session->pageTextContains('Address');
    $assert_session->pageTextContains('Belgium');
    $assert_session->pageTextContains('Contribution to the budget');
    $assert_session->pageTextContains('€19,90');
  }

}
