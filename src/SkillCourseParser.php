<?php

namespace Drupal\hello;

use Drupal\Core\Utility\Token;
use Netcarver\Textile\Parser as TextileParser;
use function PasswordCompat\binary\_strlen;
use function PasswordCompat\binary\_substr;
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
    $this->addTagType('warning', TRUE);
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
    $result = "\nThis is the exercise you're looking for: " . $options['name'] . ".\n\n";
    return $result;
  }
  protected function processRosieTag($content, $options) {
    $yaps = str_repeat('Yap ', $options['yaps']);
    $result = "\n*$yaps!*\n\n" . $content . "$yaps!\n\n";
    return $result;
  }
  protected function processWarningTag($content, $options) {
    $result = "\n\n<div class='warning'>" . $content . "</div>\n\n";
    return $result;
  }
  protected function parseCustomTags($source) {
    //Add LF to top of source, in case custom tag is first.
    $source = "\n" . $source;
    //Run through the custom tags.
    foreach ($this->tagTypes as $tagType) {
      //Keep processing $source, until don't find custom tag.
      //This is for nested tags.
//      do {
        $foundCustomTag = FALSE;
        $startChar = 0;
        $openTagText = $tagType['tagName'] . ".\n";
        list($gotOne, $tagPos) = $this->findOpenTag($source, $openTagText, $startChar);
        while ($gotOne) {
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
          $optionsParseErrorMessage = '';
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
                . 'bc. ' . $optionChars . "\n\n";
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
              //Get char prior to tag.
              $priorChar = substr($source, $loc - 1, 1);
              //Get char prior to the prior char.
              $priorCharPriorChar = substr($source, $loc - 2, 1);
              //If the match isn't at the start of the line, it's not a tag.
              if ( $priorChar === "\n" || ( $priorChar === "/" && $priorCharPriorChar === "\n" ) ) {
                //Is it an opening or closing tag?
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
            list($gotOne, $tagPos) = $this->findOpenTag($source, $openTagText, $startChar);
//            $tagPos = stripos($source, $openTagText, $startChar);
          }
        } //End while there are more tags of $tagType.
//      } while ( $gotOne );
//      } while ( $foundCustomTag );
    }
    return $source;

  }

  /**
   * @param string $textToSearch
   * @param string $tag
   * @param integer $searchPosStart
   *
   * @return array
   */
  protected function findOpenTag(string $textToSearch, string $tag, integer $searchPosStart) {
    $gotOne = false;
    $openTagText = $tag . '.';
    do {
      $tagPos = stripos($textToSearch, $openTagText, $searchPosStart);
      if ( $tagPos === false ) {
        //Nothing found.
        //Move searchPos to after end of textToSearch, so loop ends.
        $searchPosStart = strlen($textToSearch);
      }
      else {
        //Found something, but is it a tag, or random text?
        //Get char prior to tag.
        $priorChar = substr($textToSearch, $tagPos - 1, 1);
        //Get char prior to the prior char.
        $priorCharPriorChar = substr($textToSearch, $tagPos - 2, 1);
        //If the match is at the start of the line or content, it's a tag.
        if (($tagPos === 0) || $priorChar === "\n" || ($priorChar === "/" && $priorCharPriorChar === "\n")) {
          //Todo: add test to see if it's at the end of the line, too.
          $gotOne = TRUE;
        }
        if ( ! $gotOne) {
          //Not a tag.
          //Move the searchPos to after the text that was found.
          $searchPosStart = $tagPos + strlen($openTagText);
        }
      }
    } while ( ! $gotOne && $searchPosStart < strlen($textToSearch) );
    return [$gotOne, $tagPos];
  }

  /**
   * Test whether tag text is on a line by itself. Nothing in front of it, and
   * nothing or only spaces and/or tabs between the end of the tag, and EOL.
   *
   * @param string $textToSearch The content with the tag.
   * @param string $openTagText The tag's opening text, e.g., "exercise.".
   * @param int $tagPos Where the tag starts.
   *
   * @return bool True if the tag text is on a line by itself.
   */
  protected function isTagTextOnLineByItself(string $textToSearch, string $openTagText, integer $tagPos) {
    //Get char prior to tag.
    $priorChar = substr($textToSearch, $tagPos - 1, 1);
    //Get char prior to the prior char.
    $priorCharPriorChar = substr($textToSearch, $tagPos - 2, 1);
    //If the match is at the start of the line or content, it could be a tag.
    $tagStartLine =    $tagPos === 0
                    || $priorChar === "\n"
                    || $priorChar === "/" && $priorCharPriorChar === "\n";
    if ( ! $tagStartLine ) {
      //Matched text doesn't start line, so not a tag.
      return false;
    }
    //Start looking after the tag, for non-whitespace chars.
    $searchPoint = $tagPos + strlen($openTagText);
    $foundNonWhiteSpaceChar = false;
    $foundEol = false;
    $contentLength = strlen($textToSearch);
    while (
           ! $foundNonWhiteSpaceChar
        && ! $foundEol
        && $searchPoint < $contentLength ) {
      $charToTest = substr($textToSearch, $searchPoint, 1);
      if ( $charToTest === "\n" || $searchPoint >= $contentLength ) {
        $foundEol = true;
      }
      elseif ( $charToTest === ' ' || $charToTest === "\t" ) {
        //Whitespace - move to next char.
        $searchPoint ++;
      }
      else {
        $foundNonWhiteSpaceChar = true;
      }
    }
    //If didn't find non-whitespace chars, tag text has nothing between it and EOL.
    return ! $foundNonWhiteSpaceChar;
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
      ->setDimensionlessImages()
      ->setRestricted(FALSE)
    ;
    $result = $textileParser->parse($source);
    return $result;
  }

}
