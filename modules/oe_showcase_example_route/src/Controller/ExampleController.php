<?php

declare(strict_types=1);

namespace Drupal\oe_showcase_example_route\Controller;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for menu_test routes.
 */
class ExampleController extends ControllerBase {

  /**
   * Route title callback.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   A string that can be used for comparison.
   */
  public function title(string $arg): MarkupInterface {
    return $this->t("Example page '@page' title", ['@page' => $arg]);
  }

  /**
   * Route callback.
   *
   * @param string $arg
   *   Argument from url.
   *
   * @return array
   *   Page content render element.
   */
  public function page(string $arg) {
    return [
      '#markup' => $this->t("Example page '@page' text.", ['@page' => $arg]),
    ];
  }

}
