<?php

namespace Drupal\Tests\hello\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group hello
 */
class LoadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['hello'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
//    $this->drupalGet('/');
////    $this->drupalGet('node/add/test_content_type');
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSession()->statusCodeEquals(200);
  }


  public function testRosie1() {
    $entity = $this
      ->drupalCreateNode(array(
        'title' => t('Hello, world!'),
        'type' => 'article',
//        'url' =>
      ));
    $entity->save();

    $this->drupalGet("/node/1");
    $this->assertSession()->statusCodeEquals(200);
  }



}
