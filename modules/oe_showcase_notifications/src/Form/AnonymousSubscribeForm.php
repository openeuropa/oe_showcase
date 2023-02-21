<?php

declare(strict_types = 1);

namespace Drupal\oe_showcase_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AnonymousSubscribeForm extends FormBase {

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  public function __construct(MailManagerInterface $mailManager) {
    $this->mailManager = $mailManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail')
    );
  }

  public function getFormId() {
    return 'anonymous_subscribe_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $subscription_id = NULL) {
    $form_state->set('subscription_id', $subscription_id);

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your e-mail'),
      '#required' => TRUE,
    ];
    $form['accept_terms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I have read and agree with the data protection terms.'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'cancel' => [
        '#type' => 'button',
        '#value' => $this->t('No thanks') ,
        // This button will be used only to close the modal, so it doesn't have
        // any submit callback.
        '#submit' => [],
        '#attributes' => [
          'class' => ['dialog-cancel'],
        ],
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Subscribe me'),
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->mailManager->mail(
      'oe_showcase_notifications',
      'anonymous_subscribe_verify',
      $form_state->getValue('email'),
      'en',
      [
        'subscription_id' => $form_state->get('subscription_id')
      ],
    );

    $this->messenger()->addMessage($this->t('A confirmation e-email has been sent to your e-mail address.'));
  }

}
