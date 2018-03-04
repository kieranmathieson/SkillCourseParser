<?php

namespace Drupal\hello;

use Drupal\Core\Utility\Token;
use Netcarver\Textile\Parser as TextileParser;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class SkillCourseTags.
 */
class SkillCourseParser {

  const OPTION_PARSING_ERROR_CLASS = 'option-parsing-error';

  protected $tagTypes = [];

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * Quote constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   */
  public function __construct(Token $token) {
    $this->tokenService = $token;
    $this->addTagType('exercise', FALSE);
    $this->addTagType('rosie', TRUE);
  }

  protected function addTagType($tagName, $hasCloseTag) {
    $this->tagTypes[] = ['tagName'=>$tagName, 'hasCloseTag'=>$hasCloseTag];
  }

  protected function trimWhitespace($source) {
    //Remove CRs.
    $source = str_replace("\r", '', $source);
    //Explode into array, one element for each line.
    $lines = explode("\n", $source);
    //Strip whitespace from each line.
    for ($i = 0; $i < count($lines); $i++) {
      $lines[$i] = trim($lines[$i]);
    }
    //Convert from array back into one string.
    $source = implode("\n", $lines);
    return $source;
  }

  protected function processExerciseTag($content, $options) {
    $result = '<p>This is the exercise you\'re looking for: ' . $options['name'] . '.</p>';
    return $result;
  }
  protected function processRosieTag($content, $options) {
    $yaps = str_repeat('Yap ', $options['yaps']);
    $result = "<p>*$yaps!*</p>\n\n" . $content . "<p>$yaps!</p>\n\n";
    return $result;
  }
  protected function parseCustomTags($source) {
    //Add LF to top of source, in case custom tag is first.
    $source = "\n" . $source;
    //Run through the custom tags.
    foreach ($this->tagTypes as $tagType) {
      //Keep processing $source, until don't find custom tag.
      //This is for nested tags.
      do {
        $foundCustomTag = FALSE;
        $startChar = 0;
        $openTagText = $tagType['tagName'] . ".\n";
        $tagPos = stripos($source, $openTagText, $startChar);
        while ($tagPos !== FALSE) {
          //Found one.
          //Flag to continue processing after this tag, so nested tags are processed.
          $foundCustomTag = TRUE;
          //Flag to show whether there was a test option, and the tag failed the test.
          $failedTestOption = FALSE;
          //Error message for YAML parsing of options, if it happens.
          $optionsParseErrorMessage = '';
          //Get its options. YAML on the following lines until there's an MT line.
          //Look from the end of the tag until find two LFs in a row - MT line.
          //Accumulate chars until find it.
          $tagEndPoint = $tagPos + strlen($openTagText) - 1;
          $optionChars = '';
          $next2Chars = substr($source, $tagEndPoint, 2);
          while ($next2Chars !== "\n\n" && $tagEndPoint < strlen($source)) {
            $optionChars .= substr($source, $tagEndPoint, 1);
            $tagEndPoint++;
            $next2Chars = substr($source, $tagEndPoint, 2);
          }
          $options = [];
          //Parse the options, if there are any.
          $optionChars = trim($optionChars);
          if (strlen($optionChars) > 0) {
            try {
              $options = Yaml::parse($optionChars);
              //Process tokens.
              foreach($options as $indx=>$val) {
                $newVal = $this->tokenService->replace($val);
                //Is this a test?
                if ( strtolower($indx) === 'test' ) {
                  $language = new ExpressionLanguage();
                  $context = [];
                  $result = $language->evaluate($newVal, $context);
                  if ( ! $result ) {
                    $failedTestOption = TRUE;
                  }
                }
                $options[$indx] = $newVal;
              }
            } catch (Exception $e) {
              $optionsParseErrorMessage =
                "\n\np(" . self::OPTION_PARSING_ERROR_CLASS . '). '
                . 'Error parsing options for ' . $tagType['tagName'] . ".\n\n"
                . 'bc. ' . $optionChars . "";
            }
          } //End there are option chars.
          //Find the close tag, if there is one, and the content between end
          //of options, and close tag.
          $tagContent = '';
          if ($tagType['hasCloseTag']) {
            $lookFor = $tagType['tagName'] . ".\n";
            $openTagCount = 1;
            //Where the content for the tag starts.
            $contentStartPos = $tagEndPoint;
            while ($openTagCount > 0) {
              //Find the tag, either opening or closing.
              $loc = stripos($source, $lookFor, $tagEndPoint);
              //Is it an opening or closing tag? Check prior char.
              $priorChar = substr($source, $loc - 1, 1);
              $isEndTag = ($priorChar == '/');
              //Change open tag count
              if ($isEndTag) {
                $openTagCount--;
              }
              else {
                $openTagCount++;
              }
              //Remember where the tag started, in case need it to extract
              //content when the loop ends.
              $contentEndPos = $loc;
              if ($isEndTag) {
                $contentEndPos--;
              }
              //Move pointer past the tag just found.
              $tagEndPoint = $loc + strlen($lookFor);
            }
            //Extract the content.
            $tagContent = substr(
              $source, $contentStartPos, $contentEndPos - $contentStartPos);
          }
          //Process the tag.
          $replacementContent = '';
          //If test option failed, leave the replacement content MT.
          if ( ! $failedTestOption ) {
            $methodName = 'process' . ucfirst(strtolower($tagType['tagName'])) . 'Tag';
            $replacementContent
              = call_user_func([$this, $methodName], $tagContent, $options);
          }
          //Replace tag.
          $source = substr($source, 0, $tagPos) . $replacementContent . substr($source, $tagEndPoint);
          //Move pos to after new content.
          $startChar = $tagPos + strlen($replacementContent);
          if ($startChar >= strlen($source)) {
            $tagPos = FALSE;
          }
          else {
            $tagPos = stripos($source, $openTagText, $startChar);
          }
        } //End while there are more tags of $tagType.
      } while ( $foundCustomTag );
    }
    return $source;

  }

  public function parse($source) {
    //Trim whitespace. Authors can use indentation as they want, but it
    //will mess up Textile.
    $source = $this->trimWhitespace($source);
    //Parse custom tags.
    $source = $this->parseCustomTags($source);

    $textileParser = new TextileParser('html5');
    $textileParser
      ->setLineWrap(false)
      ->setSymbol('apostrophe', "'")
      ->setSymbol('quote_single_open', "'")
      ->setSymbol('quote_single_close', "'")
      ->setSymbol('quote_double_open', '"')
      ->setSymbol('quote_double_close', '"')
    ;
    $result = $textileParser->parse($source);
    return $result;

  }

}
