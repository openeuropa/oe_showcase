<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Traits;

use Drupal\user\UserInterface;

/**
 * Provides user related methods to be used in tests.
 */
trait UserTrait {

  /**
   * Gets a user by username.
   *
   * @param string $username
   *   The users machine name.
   *
   * @return \Drupal\user\UserInterface
   *   The user.
   */
  protected function getUserByName(string $username): UserInterface {
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
        'name' => $username,
      ]);

    if (!$users) {
      throw new \Exception(sprintf('The user with the name %s does not exist.', $username));
    }

    return reset($users);
  }

  /**
   * Creates a user, assigns given roles and logs the user in.
   *
   * @param array $roles
   *   List of roles to add to the user.
   *
   * @return \Drupal\user\UserInterface
   *   The user created.
   */
  protected function createUserWithRoles(array $roles): UserInterface {
    $user = $this->createUser();

    foreach ($roles as $rolename) {
      $user->addRole($rolename);
    }
    $user->save();

    return $user;
  }

}
