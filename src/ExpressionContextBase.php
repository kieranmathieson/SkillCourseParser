<?php
/**
 * A class for the context of an ExpressionLanguage evaluation, giving
 * access to entity fields
 * .
 * User: kieran
 * Date: 4/2/18
 * Time: 6:02 PM
 */

namespace Drupal\hello;


abstract class ExpressionContextBase {
  /**
   * Check whether a field has the value given.
   *
   * @param string $fieldMachineName
   * @param mixed $value
   * @return bool True if field has the value.
   */
  public function fieldHasValue(string $fieldMachineName, $value) {
    return false;
  }

  /**
   * Check whether a field has no value.
   *
   * @param string $fieldMachineName
   * @return bool True if field has no value.
   */
  public function fieldHasNoValue(string $fieldMachineName) {
    return false;
  }

  /**
   * Check whether a field has any value at all.
   *
   * @param string $fieldMachineName
   * @return bool True if field has any value.
   */
  public function fieldHasAnyValue(string $fieldMachineName) {
    return false;
  }

}