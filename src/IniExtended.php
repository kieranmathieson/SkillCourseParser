<?php
/**
 * Extends ini format with . notation.
 *
 * Based on code by arnapou at http://php.net/manual/en/function.parse-ini-file.php
 */

namespace Drupal\hello;


class IniExtended {
  /**
   * Write ini array to file.
   *
   * @param $filename
   * @param $ini
   */
  static function write($filename, $ini) {
    $string = '';
    foreach(array_keys($ini) as $key) {
      $string .= '['.$key."]\n";
      $string .= self::writeGetString($ini[$key], '')."\n";
    }
    file_put_contents($filename, $string);
  }

  /**
   *  Recursive function to descend through ini array when writing.
   * @param array $ini Ini array.
   * @param string $prefix Prefix for dotted notation.
   * @return string Result for writing.
   */
  static function writeGetString(& $ini, $prefix) {
    $string = '';
    ksort($ini);
    foreach($ini as $key => $val) {
      if (is_array($val)) {
        $string .= self::writeGetString($ini[$key], $prefix.$key.'.');
      } else {
        $string .= $prefix.$key.' = '.str_replace("\n", "\\\n", self::setValue($val))."\n";
      }
    }
    return $string;
  }

  /**
   * Manage keys.
   *
   * @param mixed $val Value to represent.
   * @return string String equivalent.
   */
  static function setValue($val) {
    if ($val === true) { return 'true'; }
    else if ($val === false) { return 'false'; }
    return $val;
  }

  /**
   * Read ini file.
   *
   * @param string $filename Name of the file.
   * @return array Result.
   */
  static function read($filename) {
    $lines = file($filename);
    return self::parse($lines);
  }

  /**
   * Parse a string into an ini array.
   *
   * @param $source String to parse, lines separated by EOL.
   * @return array Result.
   */
  static function parse($source) {
    $ini = array();
    $section = 'default';
    $multi = '';
    $lines = explode("\n", $source);
    foreach($lines as $line) {
      if (substr($line, 0, 1) !== ';') {
        $line = str_replace("\r", "", str_replace("\n", "", $line));
        if (preg_match('/^\[(.*)\]/', $line, $m)) {
          $section = $m[1];
        } else if ($multi === '' && preg_match('/^([a-z0-9_.\[\]-]+)\s*=\s*(.*)$/i', $line, $m)) {
          $key = $m[1];
          $val = $m[2];
          if (substr($val, -1) !== "\\") {
            $val = trim($val);
            self::manageKeys($ini[$section], $key, $val);
            $multi = '';
          } else {
            $multi = substr($val, 0, -1)."\n";
          }
        } else if ($multi !== '') {
          if (substr($line, -1) === "\\") {
            $multi .= substr($line, 0, -1)."\n";
          } else {
            self::manageKeys($ini[$section], $key, $multi.$line);
            $multi = '';
          }
        }
      }
    }
    $buf = get_defined_constants(true);
    $consts = array();
    foreach($buf['user'] as $key => $val) {
      $consts['{'.$key.'}'] = $val;
    }
    array_walk_recursive($ini, 'self::replaceConsts', $consts);
    return $ini;
  }

  /**
   * Manage keys.
   *
   * @param mixed $val Value to convert.
   * @return mixed Result.
   */
  static function getValue($val) {
    if (preg_match('/^-?[0-9]$/i', $val)) { return intval($val); }
    else if (strtolower($val) === 'true') { return true; }
    else if (strtolower($val) === 'false') { return false; }
    else if (preg_match('/^"(.*)"$/i', $val, $m)) { return $m[1]; }
    else if (preg_match('/^\'(.*)\'$/i', $val, $m)) { return $m[1]; }
    return $val;
  }

  /**
   * Manage keys.
   *
   * @param $val
   * @return int
   */
  static function getKey($val) {
    if (preg_match('/^[0-9]$/i', $val)) { return intval($val); }
    return $val;
  }

  /**
   * Manage keys.
   *
   * @param $ini
   * @param $key
   * @param $val
   */
  static function manageKeys(& $ini, $key, $val) {
    if (preg_match('/^([a-z0-9_-]+)\.(.*)$/i', $key, $m)) {
      self::manageKeys($ini[$m[1]], $m[2], $val);
    } else if (preg_match('/^([a-z0-9_-]+)\[(.*)\]$/i', $key, $m)) {
      if ($m[2] !== '') {
        $ini[$m[1]][self::getKey($m[2])] = self::getValue($val);
      } else {
        $ini[$m[1]][] = self::getValue($val);
      }
    } else {
      $ini[self::getKey($key)] = self::getValue($val);
    }
  }

  /**
   * Replace utility.
   *
   * @param $item
   * @param $key
   * @param $consts
   */
  static function replaceConsts(& $item, $key, $consts) {
    if (is_string($item)) {
      $item = strtr($item, $consts);
    }
  }
}

