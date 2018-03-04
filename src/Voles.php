<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 2/21/18
 * Time: 2:37 PM
 */

namespace Drupal\hello;



use cebe\markdown\Markdown;

class Voles extends Markdown {

  protected function identifyVole($line, $lines, $current) {
    // if a line starts with at least 3 backticks it is identified as a fenced code block
    if (strncmp($line, 'vole', 4) === 0) {
      return 'vole';
    }
//    return parent::identifyLine($lines, $current);

  }

  protected function consumeVole($lines, $current)
  {
    // create block array
    $block = [
      'vole',
      'content' => [],
    ];
    $line = rtrim($lines[$current]);

    // consume all lines until ```
    for($i = $current + 1, $count = count($lines); $i < $count; $i++) {
      if (rtrim($line = $lines[$i]) !== 'endvole') {
        $block['content'][] = $line;
      } else {
        // stop consuming when code block is over
        break;
      }
    }
    return [$block, $i];
  }

  protected function renderVole($block)
  {
    $result = "<h2>Voling</h2>";
    $result .= "<p>" . implode("\n", $block['content']) . "</p>";

    $result .= "<h2>Done voling</h2>";

    return $result;
  }

}