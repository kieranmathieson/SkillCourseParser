<?php

namespace Drupal\hello;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;

class Quote {
    use StringTranslationTrait;
    
    /**
    * @var \Drupal\Core\Config\ConfigFactoryInterface
    */
    protected $configFactory;
    /**
    * Quote constructor.
    *
    * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
    */
    public function __construct(ConfigFactoryInterface $config_factory) {
        $this->configFactory = $config_factory;
    }
    
    
    public function tellMe() {
        $config = $this->configFactory->get('hello.dog');
        $dog = $config->get('dog_name');
        if ( $dog == '' ) {
          $dog = '(unknown)';
        }
        return $this->t('The Dude abides, dude. Cutest: ' . $dog);
    }
}


