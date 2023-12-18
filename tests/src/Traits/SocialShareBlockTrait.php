<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

use Drupal\Component\Serialization\Json;

/**
 * Contains methods to assert social share Block.
 */
trait SocialShareBlockTrait {

  /**
   * Asserts Social Share block.
   */
  protected function assertSocialShareBlock(): void {
    $main_content = $this->cssSelect('main > div.container')[0];
    $this->assertStringContainsString('Share this page', $main_content->getText());
    $social_share_config = [
      'service' => 'share',
      'popup' => FALSE,
      'selection' => TRUE,
      'to' => [
        'more',
        'twitter',
        'facebook',
        'linkedin',
        'e-mail',
      ],
      'stats' => TRUE,
    ];
    $this->assertStringContainsString(Json::encode($social_share_config), $main_content->getHtml());
  }

}
