<?php
namespace Drupal\hello\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
class DogForm extends ConfigFormBase {
    /**
    * {@inheritdoc}
    */
    protected function getEditableConfigNames() {
        return ['hello.dog'];
    }
    /**
    * {@inheritdoc}
    */
    public function getFormId() {
        return 'dog_configuration_form';
    }
    /**
    * {@inheritdoc}
    */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('hello.dog');
        $form['dog_name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Cutest dog'),
            '#description' => $this->t('Who is the cutest dog?'),
            '#default_value' => $config->get('dog_name'),
        );
        return parent::buildForm($form, $form_state);
    }
    /**
    * {@inheritdoc}
    */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('hello.dog')
            ->set('dog_name', $form_state->getValue('dog_name'))
            ->save();
        parent::submitForm($form, $form_state);
    }
}
