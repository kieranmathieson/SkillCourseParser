<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 2/19/18
 * Time: 3:55 PM
 */

namespace Drupal\hello;


class ElExercise {
  public $submitted;
  public $completed;
  public $firstLanguage;

  public function __construct($firstLanguage='english') {
    $this->firstLanguage = $firstLanguage;
  }

  public function times2($x) {
    return $x*2;
  }

  public function copy2($x) {
    return $x . $x;
  }

  public function languages() {
    return ['french', 'german'];
  }
  
}