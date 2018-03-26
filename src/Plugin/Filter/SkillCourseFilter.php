<?php

namespace Drupal\hello\Plugin\Filter;

use Drupal\Core\Annotation\Translation;
use Drupal\filter\Annotation\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

use Drupal\Core\Form\FormStateInterface;

use Drupal\hello\SkillCourseParser;

use Symfony\Component\DependencyInjection\ExpressionLanguage;

/**
 * @Filter(
 *   id = "filter_skill_course",
 *   title = @Translation("SkillCourse Filter"),
 *   description = @Translation("Translate SkillCourse markup to HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class SkillCourseFilter extends FilterBase {

  /**
   * Performs the filter processing.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object, and possibly
   *   with associated assets, cacheability metadata and placeholders.
   *
   * @see \Drupal\filter\FilterProcessResult
   */
  public function process($text, $langcode) {
//    $chocMessage = 'There is '
//        . ($this->settings['there_is_chocolate'] ? '' : 'no ') . "chocolate.\n\n";
//    $text = $chocMessage . $text;
    $text = str_replace("<br />\n", "\n", $text, $count);
    $text = str_replace("<br />", "\n", $text, $count);
    $text = str_replace("&nbsp;", ' ', $text);
    //Find image tags, replace with path to file.
    $text = preg_replace_callback(
      '/!(.*)\<img.*src=(?:\'|\")(.*)(?:\'|\").*.*\/\>(.*)!/Ui',
      function ($matches) {
        //$matches[0]: original string
        //$matches[1]: stuff between the first bang, and the start of the img tag.
        //  (classes){styles} - could be MT.
        //$matches[2]: image path
        //$matches[3]: stuff between end of image tag and last bang
        //  (alt text) - could be MT.

        $fullMatch = $matches[0];
        $beforeImgTag = $matches[1];
        $imagePath = $matches[2];
        $afterImgTag = $matches[3];
        //All classes to output.
        $allClasses = '';
        //Grab the Textile class spec, if there is one. In ().
        preg_match("/\((.*)\)/Ui", $beforeImgTag, $textileClassMatches);
        if ( isset( $textileClassMatches[1] ) ) {
          //Append to allClasses.
          $allClasses .= $textileClassMatches[1];
        }
        //Grab classes spec in the img tag, if there is one.
        preg_match("/\<img.*class=(?:\'|\")(.*)(?:\'|\")/Ui", $fullMatch, $imgClassMatches);
        if ( isset( $imgClassMatches[1] ) ) {
          //Append to allClasses.
          $allClasses .= ' '. $imgClassMatches[1];
        }
        //All styles to output.
        $allStyles = '';
        //Grab the Textile style spec, if there is one. In {}.
        preg_match("/\{(.*)\}/Ui", $beforeImgTag, $textileStylesMatches);
        if ( isset( $textileStylesMatches[1] ) ) {
          //Append to $allStyles.
          $allStyles .= $textileStylesMatches[1];
          //Add a trailing;.
          if ( substr($allStyles, -1) !== ';' ) {
            $allStyles .= ';';
          }
        }
        //Grab styles spec in the img tag, if exists.
        preg_match("/\<img.*style=(?:\'|\")(.*)(?:\'|\")/Ui", $fullMatch, $imgStylesMatches);
        if ( isset( $imgStylesMatches[1] ) ) {
          //Append to $allStyles.
          $allStyles .= ' '. $imgStylesMatches[1];
          //Add a trailing;.
          if ( substr($allStyles, -1) !== ';' ) {
            $allStyles .= ';';
          }
        }
        //Find width attr in the img tag.
        preg_match("/width=(?:\'|\")(.*)(?:\'|\")/Ui", $fullMatch, $imgWidthMatches);
        if ( isset( $imgWidthMatches[1] ) ) {
          //Add a unit if there isn't one.
          $width = trim($imgWidthMatches[1]);
          //Is the last character a digit?
          if ( is_numeric(substr($width, -1)) ) {
            //Add px.
            $width .= 'px';
          }
          //Append to $allStyles.
          $allStyles .= 'width:' . $width . ';';
        }
        //Find height attr in the img tag.
        preg_match("/height=(?:\'|\")(.*)(?:\'|\")/Ui", $fullMatch, $imgHeightMatches);
        if ( isset( $imgHeightMatches[1] ) ) {
          //Add a unit if there isn't one.
          $height = trim($imgHeightMatches[1]);
          //Is the last character a digit?
          if ( is_numeric(substr($height, -1)) ) {
            //Add px.
            $height .= 'px';
          }
          //Append to $allStyles.
          $allStyles .= 'height:' . $height . ';';
        }
        //Build complete Textile tag.
        $completeTextileTag = '!';
        if ( strlen($allClasses) > 0 ) {
          $completeTextileTag .= '(' . $allClasses . ')';
        }
        if ( strlen($allStyles) > 0 ) {
          $completeTextileTag .= '{' . $allStyles . '}';
        }
        $completeTextileTag .= $imagePath . $afterImgTag . '!';
        return $completeTextileTag;
      },
      $text
    );

    $text = html_entity_decode($text);
    $tokenService = \Drupal::service('token');
    $expressionLanguage = new ExpressionLanguage();
    $parser = new SkillCourseParser($tokenService, $expressionLanguage);
//    $parserService = \Drupal::service('hello.skillcourseparser');
    $result = $parser->parse($text);
//    $result = $text;
    $markup = new FilterProcessResult($result);
    $markup->setAttachments(array(
      'library' => array('hello/hello-styles'),
    ));
    return $markup;
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['there_is_chocolate'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Is there chocolate?'),
      '#default_value' => $this->settings['there_is_chocolate'],
      '#description' => $this->t('Check if there is chocolate.'),
    );
    return $form;
  }
}