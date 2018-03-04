<?php
namespace Drupal\hello\Rst;


use Gregwar\RST\Parser;
use Gregwar\RST\SubDirective;
use Gregwar\RST\Nodes\WrapperNode;

/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 2/21/18
 * Time: 12:11 PM
 */
class Puppy extends SubDirective {

  /**
   * Get the directive name
   */
  public function getName() {
    return 'puppy';
  }

  public function processSub(Parser $parser, $document, $variable, $data, array $options)
  {
    if ( $data !== 'hide' ) {

      return new WrapperNode($document, '<h1>Puppy</h1><p>Start puppy</p><div class="' . $data . '">', '</div><p>End puppy</p>');
    }
//    $environment = $parser->getEnvironment();
//    $url = $environment->relativeUrl($data);
//
//    return new FigureNode(new ImageNode($url, $options), $document);
  }

}