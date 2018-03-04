<?php

namespace Drupal\hello\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\Url;

use Drupal\hello\Quote;

/**
* Cutest dog block.
*
* @Block(
*   id = "cutest_dog_block",
*   admin_label = @Translation("Cutest dog"),
* )
*/
class HelloDogBlock extends BlockBase implements ContainerFactoryPluginInterface {

    /**
    * Service definition.
    *
    * @var \Drupal\hello\Quote
    */
    protected $quoteService;
    /**
    * @var \Drupal\Core\Config\ConfigFactoryInterface
    */
    protected $configFactory;
    
    /**
    * @var Drupal\Core\Utility\LinkGenerator
    */
    protected $linkGenerator;
        

    /**
    * Construct.
    *
    * @param array $configuration
    *   A configuration array containing information about the plugin instance.
    * @param string $plugin_id
    *   The plugin_id for the plugin instance.
    * @param string $plugin_definition
    *   The plugin implementation definition.
    * @param $quote_service
    *   Quote service.
    */
    public function __construct(array $configuration, $plugin_id,
            $plugin_definition, 
            $quote_service, $config_factory, $link_generator) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->quoteService = $quote_service;
        $this->configFactory = $config_factory;
        $this->linkGenerator = $link_generator;
    }
    
    /**
    * {@inheritdoc}
    */
    public static function create(ContainerInterface $container, 
            array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('hello.quote'),
            $container->get('config.factory'),
            $container->get('link_generator')
        );
    }
    /**
    * {@inheritdoc}
    */
    public function build() {
        $config = $this->configFactory->get('hello.dog');
        $dog = $config->get('dog_name');
        $blockConfig = $this->getConfiguration();
        $wagging = $blockConfig['tail wagging'];
        $msg = '<p>Tail wagging? ';
        $msg .= $wagging ? 'Yes!' : 'No';
        $msg .= '</p>';
        $url = Url::fromRoute('hello.dog', ['name'=>'Rosie']);
        $link = $this->linkGenerator->generate('Hello', $url);
        return [
            '#markup' => '<p>Penguin</p><p>'. $this->quoteService->tellMe() .'!</p>'
            . '<p>Yes, it is ' . $dog . '!!</p>' . $msg
            . $link,
        ];
    }
    
    /**
    * {@inheritdoc}
    */
    public function defaultConfiguration() {
        return [
            'tail wagging' => 1,
        ];
    }
    
    /**
    * {@inheritdoc}
    */
    public function blockForm($form, FormStateInterface $form_state) {
        $config = $this->getConfiguration();
        $form['tail wagging'] = array(
            '#type' => 'checkbox',
            '#title' => t('Tail wagging'),
            '#description' => t('Check this box if Rosie is wagging her tail.'),
            '#default_value' => $config['tail wagging'],
        );
        return $form;
    }
    
    /**
    * {@inheritdoc}
    */
    public function blockSubmit($form, FormStateInterface $form_state) {
        $this->configuration['tail wagging'] = $form_state->getValue('tail wagging');
    }
    


}

