<?php

declare(strict_types = 1);

namespace Drupal\oe_showcase_notifications\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\oe_showcase_notifications\Form\AnonymousSubscribeForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SubscriptionController extends ControllerBase {

  /**
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected FlagServiceInterface $flag;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, FormBuilderInterface $formBuilder, FlagServiceInterface $flag) {
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
    $this->flag = $flag;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('flag')
    );
  }

  public function subscribeAnonymous(string $subscription_id) {
    return $this->formBuilder->getForm(AnonymousSubscribeForm::class, $subscription_id);
  }

  public function confirmSubscription(string $hash, string $mail, string $subscription_id) {
    // @todo Validate hash from DB.
    //   Subscription ID and e-mail are retrieved from DB.
    [$flag_id, $entity_id] = explode(':', $subscription_id);
    $flag_storage = $this->entityTypeManager->getStorage('flag');
    // @todo Validate that all entities exists.
    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $flag_storage->load($flag_id);
    $entity = $this->flag->getFlaggableById($flag, $entity_id);

    $user_storage = $this->entityTypeManager->getStorage('user');
    $user = $user_storage->loadByProperties([
      'mail' => $mail,
    ]);

    if (!$user) {
      $user = $user_storage->create([
        'name' => 'Subscription user ' . time(),
        'pass' => 'random',
        'status' => 1,
        'mail' => $mail,
      ]);
      $user->save();
    }
    else {
      $user = reset($user);
    }

    if (!$flag->isFlagged($entity, $user)) {
      $flagging = $this->entityTypeManager->getStorage('flagging')->create([
        'uid' => $user->id(),
        'session_id' => NULL,
        'flag_id' => $flag->id(),
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'global' => $flag->isGlobal(),
      ]);
      $flagging->save();
    }

    $this->messenger()->addMessage($this->t('Your e-mail has been confirmed.'));

    return new RedirectResponse($entity->toUrl()->toString());
  }

}
