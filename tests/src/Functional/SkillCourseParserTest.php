<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 3/18/2018
 * Time: 11:04 AM
 */
namespace Drupal\Tests\hello\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

class SkillCourseParserTest extends BrowserTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['token', 'hello'];

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