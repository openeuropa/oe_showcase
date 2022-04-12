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
    // @todo Improve the field locator.
    $page->fillField('oe_project_coordinators[form][0][name][0][value]', 'Coordinator 1');
    $page->pressButton('Create organisation');

    // Summary, objective, impacts and achievements and milestones.
    $page->fillField('Summary', 'Text summary');
    $page->fillField('Objective', 'Text Objective');
    $page->fillField('Impacts', 'Text Impacts');
    $page->fillField('Achievements and milestones', 'Text Achievements and milestones');

    // Participants.
    $page->pressButton('Add new participant');
    // @todo Improve the field locator.
    $page->fillField('oe_project_participants[form][0][name][0][value]', 'Developer participant name');
    $page->fillField('Acronym', 'Developer participant acronym');
    $page->fillField('Country', 'BE');
    $page->fillField('Contribution to the budget', '19.9');
    $page->pressButton('Create participant');

    // Lead contributors.
    $page->pressButton('edit-oe-cx-lead-contributors-actions-ief-add');
    // @todo Improve the field locator.
    $page->fillField('oe_cx_lead_contributors[form][0][name][0][value]', 'Lead contributors name');
    $page->fillField('Acronym', 'Lead contributors acronym');
    $page->fillField('Country', 'BE');
    $page->fillField('oe_cx_lead_contributors[form][0][oe_cx_contribution_budget][0][value]', '22.9');
    $page->pressButton('Create organisation');
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
    $assert_session->pageTextContains('Developer participant acronym');
    $assert_session->pageTextContains('Developer participant name');
    $assert_session->pageTextContains('Address');
    $assert_session->pageTextContains('Belgium');
    $assert_session->pageTextContains('Contribution to the budget');
    $assert_session->pageTextContains('€19,90');

    // Assert lead contributors.
    $assert_session->pageTextContains('Lead contributors');
    $assert_session->pageTextContains('Lead contributors acronym');
    $assert_session->pageTextContains('Lead contributors name');
    $assert_session->pageTextContains('Address');
    $assert_session->pageTextContains('Belgium');
    $assert_session->pageTextContains('Contribution to the budget');
    $assert_session->pageTextContains('€22,90');
    $assert_session->pageTextContains('Project details');

    $this->drupalLogin($this->createUser([], '', TRUE));
    // Edit the node, remove project details.
    $node = $this->getNodeByTitle('Project page test');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->selectFieldOption('oe_project_dates[0][value][day]', '');
    $page->selectFieldOption('oe_project_dates[0][value][month]', '');
    $page->selectFieldOption('oe_project_dates[0][value][year]', '');
    $page->selectFieldOption('oe_project_dates[0][end_value][day]', '');
    $page->selectFieldOption('oe_project_dates[0][end_value][month]', '');
    $page->selectFieldOption('oe_project_dates[0][end_value][year]', '');
    $page->fillField('Overall budget', '');
    $page->fillField('EU contribution', '');
    $page->fillField('URL', '');
    $page->fillField('Link text', '');
    $page->fillField('oe_project_funding_programme[0][target_id]', '');
    $page->fillField('Reference', '');
    $page->pressButton('Remove');
    $page->pressButton('Remove');
    $page->pressButton('Save');

    $this->drupalGet('project/project-page-test');
    // Check Project details is not present when empty.
    $assert_session->pageTextNotContains('Project details');
    // Check the correct order.
    $correct_order = [
      1 => 'Summary',
      2 => 'Objective',
      3 => 'Impacts',
      4 => 'Lead contributors',
      5 => 'Participants',
    ];
    foreach ($correct_order as $key => $value) {
      $assert_session->elementContains('xpath', "(//ul[contains(@class, 'nav-pills')]//li[contains(@class, 'nav-item')])[" . $key . "]", $value);
    }

    $this->drupalGet('/node');

    // 1st node contains the period.
    $assert_session->elementExists('css', '.card-body:nth-child(1) .text-muted');
    // 2nd node doesn't contain any period, therefore no empty span.
    $assert_session->elementNotExists('css', '.card-body:nth-child(2) .text-muted');
  }

}
