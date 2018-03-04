<?php

namespace Drupal\hello\EventSubscriber;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;


/**
* Subscribes to the Kernel Request event and redirects to the homepage
* when the user has the "non_grata" role.
*/
class HelloRedirectSubscriber implements EventSubscriberInterface {
    /**
    * @var \Drupal\Core\Session\AccountProxyInterface
    */
    protected $currentUser;
    
    /**
    * HelloRedirectSubscriber constructor.
    *
    * @param \Drupal\Core\Session\AccountProxyInterface $current_user
    */
    public function __construct(AccountProxyInterface $current_user) {
        $this->currentUser = $current_user;
    }
    
    /**
    * {@inheritdoc}
    */
    public static function getSubscribedEvents() {
        $events[KernelEvents::REQUEST][] = ['onRequest', 0];
        return $events;
    }
    
    /**
    * Handler for the kernel request event.
    *
    * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
    */
    public function onRequest(GetResponseEvent $event) {
        /** @var Request $request */
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if ($path !== '/hello') {
            return;
        }
        $roles = $this->currentUser->getRoles();
        if (in_array('non_grata', $roles)) {
            $event->setResponse(new RedirectResponse('/'));
        }
    }
}

