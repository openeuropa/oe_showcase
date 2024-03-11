<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_showcase\Traits;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\oe_whitelabel\Traits\NodeCreationTrait as WhitelabelNodeCreationTrait;

/**
 * Contains methods to create node entities for testing.
 *
 * When adding a method to create a specific bundle, the method name MUST follow
 * the naming: "create" + bundle name in camel case + "Node".
 */
trait NodeCreationTrait {

  use WhitelabelNodeCreationTrait {
    createEventNode as whitelabelCreateEventNode;
    createNewsNode as whitelabelCreateNewsNode;
    createPersonNode as whitelabelCreatePersonNode;
    createPublicationNode as whitelabelCreatePublicationNode;
    createProjectNode as whitelabelCreateProjectNode;
  }
  use MediaCreationTrait {
    MediaCreationTrait::createDocumentMedia insteadof WhitelabelNodeCreationTrait;
    MediaCreationTrait::createImageMedia insteadof WhitelabelNodeCreationTrait;
    MediaCreationTrait::createMedia insteadof WhitelabelNodeCreationTrait;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewsNode(array $values = []): NodeInterface {
    $news = $this->whitelabelCreateNewsNode($values);
    $this->markEntityForCleanup($news);

    return $news;
  }

  /**
   * {@inheritdoc}
   */
  public function createEventNode(array $values = []): NodeInterface {
    $event = $this->whitelabelCreateEventNode($values);
    $this->markEntityForCleanup($event);

    if ($event->get('field_event_type')->isEmpty()) {
      $term = $this->createTerm(Vocabulary::load('event_type'));
      $event->set('field_event_type', $term);
      $event->save();
    }

    return $event;
  }

  /**
   * {@inheritdoc}
   */
  public function createPersonNode(array $values = []): NodeInterface {
    $person = $this->whitelabelCreatePersonNode($values);
    $this->markEntityForCleanup($person);

    return $person;
  }

  /**
   * {@inheritdoc}
   */
  public function createPublicationNode(array $values = []): NodeInterface {
    $publication = $this->whitelabelCreatePublicationNode($values);
    $this->markEntityForCleanup($publication);

    return $publication;
  }

  /**
   * {@inheritdoc}
   */
  public function createProjectNode(array $values = []): NodeInterface {
    $project = $this->whitelabelCreateProjectNode($values);
    $this->markEntityForCleanup($project);

    return $project;
  }

}
