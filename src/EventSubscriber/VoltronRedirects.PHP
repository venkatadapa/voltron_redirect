<?php

namespace Drupal\voltron_redirect\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;

/**
 * Redirect subscriber for controller requests.
 */
class VoltronRedirects implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entity_query;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(LoggerChannelFactoryInterface $logger, QueryFactory $query) {
    $this->logger = $logger;
    $this->entity_query = $query;
  }

  /**
   * Handles the redirection based on old page id.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function VoltronRedirections(GetResponseEvent $event) {
    $path = $event->getRequest()->getRequestUri();
    $nids = [];
    // If url contains some old page ID ex: url-1234.htm, 
    // Check for respective node and redirect if exists.
    if (!preg_match('/(?<=-)[\d]+(?=.htm)/', $path, $matches)) {
      return;
    }
    
    try {
      $old_page_id = $matches[0];
      $query = $this->entity_query->get('node')
        ->condition('type', 'page')
        ->condition('field_old_page_id', $old_page_id)
        ->range(0, 1);
      $nids = $query->execute();
    }
    catch (\Exception $e) {
      $this->logger->get('voltron_redirect')->error("Error occured during redirection
        from old pattern url: %message", ['%message' => $e->getMessage()]
      );
    }

    if (!empty($nids)) {
      $nid = reset($nids);
      $routeName = 'entity.node.canonical';
      $routeParameters = ['node' => $nid];
      $url = Url::fromRoute($routeName, $routeParameters)->toString();
      $event->setResponse(new RedirectResponse($url, 301));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['VoltronRedirections', 100];
    return $events;
  }

}
