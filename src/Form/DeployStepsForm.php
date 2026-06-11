<?php

declare(strict_types=1);

namespace Drupal\deploy_steps\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\deploy_steps\DeployStepsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Deploy Steps.
 */
class DeployStepsForm extends ConfigFormBase {

  /**
   * The Deploy Steps service.
   *
   * @var \Drupal\deploy_steps\DeployStepsService
   */
  protected DeployStepsService $yourExtensionService;

  /**
   * Constructs a DeployStepsForm instance.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    DeployStepsService $deploy_steps_service,
  ) {
    // @phpstan-ignore-next-line
    parent::__construct($config_factory, $typedConfigManager);
    $this->yourExtensionService = $deploy_steps_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): DeployStepsForm {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('deploy_steps.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['deploy_steps.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deploy_steps_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('deploy_steps.settings');

    $form['text'] = [
      '#title' => $this->t('Text for <code>noscript</code> Tag'),
      '#type' => 'textarea',
      '#description' => $this->t('Enter the text to be included in the <code>noscript</code> tag.'),
      '#default_value' => $config->get('text'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('deploy_steps.settings');
    $config->set('text', $form_state->getValue('text'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
