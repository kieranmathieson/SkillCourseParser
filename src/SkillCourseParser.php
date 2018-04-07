<?php

namespace Drupal\hello;

use Drupal\hello\Exception\SkillParserException;
use Drupal\Core\Utility\Token;
use Netcarver\Textile\Parser as TextileParser;
use Symfony\Component\Yaml\Exception\ParseException;
//use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\DependencyInjection\ExpressionLanguage;



/**
 * Class SkillCourseTags.
 */
class SkillCourseParser {

  const OPTION_PARSING_ERROR_CLASS = 'option-parsing-error';

  const CONDITION_TEST_PARAM_NAME = 'test';

  protected $tagTypes = [];

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * @var \Symfony\Component\DependencyInjection\ExpressionLanguage;
   */
  protected $expressionLanguageService;

  /**
   * Quote constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   */
  public function __construct(Token $token) {
    $this->tokenService = $token;
    $this->expressionLanguageService = new ExpressionLanguage();
    $this->addTagType('exercise', FALSE);
    $this->addTagType('condition', TRUE);
    $this->addTagType('rosie', TRUE);
    $this->addTagType('warning', TRUE);
    $this->addTagType('fake1', TRUE);
  }

  /**
   * @param string $tagName Tag, e.g., exercise.
   * @param boolean $hasCloseTag True is this tag has a close part.
   */
  protected function addTagType(string $tagName, bool $hasCloseTag) {
    $this->tagTypes[] = ['tagName'=>$tagName, 'hasCloseTag'=>$hasCloseTag];
  }

  /**
   * Remove leading and trailing whitespace from every line in a string.
   * Lines are defined by EOLs.
   * @param string $source String with lines to trim.
   *
   * @return string Source with whitespace removed.
   */
  protected function trimWhitespace(string $source) {
    //Remove CRs.
    $source = str_replace("\r", '', $source);
    //Explode into array, one element for each line.
    $lines = explode("\n", $source);
    //Strip whitespace from each line.
    $linesOut = [];
    for ($i = 0; $i < count($lines); $i++) {
      $linesOut[] = trim($lines[$i], " \t\n\r\0\x0B\xA0\xC2");
      //A0 is nbsp. C2 is..., er, don't know what this appears. TODO: why?
    }
    //Convert from array back into one string.
    $result = implode("\n", $linesOut);
    return $result;
  }

  protected function processFake1Tag($content, $options) {
    $result = "\nFake " . $content . "Fake\n\n";
    return $result;
  }

  /**
   * Passes content through, subject only to test that is processed
   * automatically by the parser.
   *
   * @param $content Content to parse.
   * @param $options Options, not used.
   * @return string Content.
   */
  protected function processConditionTag($content, $options) {
    return $content;
  }
  protected function processExerciseTag($content, $options) {
    $result = "\nThis is the exercise: " . $options['name'] . ".\n\n";
    return $result;
  }
  protected function processRosieTag($content, $options) {
    //Default to one yap.
    $numYaps = isset($options['yaps']) ? $options['yaps'] : 1;
    $yaps = str_repeat('Yap ', $numYaps);
    $result = "\n*$yaps!*\n\n" . $content . "$yaps!\n\n";
    return $result;
  }
  protected function processWarningTag($content, $options) {
    //Extra spaces before HTML tags are needed so that Textile does not wrap
    //them in p tags.
    $result = "\n\n <div class='warning-container'>\n <div class='warning-title'>Warning</div>\n\n" . $content . "\n\n </div>";
//    $result = "\n\n<div class='warning'><h3 class='warning'>Warning</h3>" . $content . "</div>\n\n";
    return $result;
  }

  /**
   * Parse custom tags.
   * @param $source
   * @return string
   */
  protected function parseCustomTags(string $source) {
    //Add LF to start and end of source, in case custom tag is first or last.
    $source = "\n" . $source . "\n";
    //Run through the custom tags.
    foreach ($this->tagTypes as $tagType) {
      //Keep processing $source, until don't find custom tag.
      //This is for nested tags.
//        $foundCustomTag = FALSE;
        $startChar = 0;
        $openTagText = $tagType['tagName'] . ".";
        list($gotOne, $tagPos) = $this->findOpenTag(
          $source, $tagType['tagName'], $startChar
        );
        while ($gotOne) {
          //Found one.
          //Flag to continue processing after this tag, so nested tags
          //are processed.
//          $foundCustomTag = TRUE;
          //Flag to show whether there was a test option, and the tag
          //failed the test.
          $failedTestOption = FALSE;
          //Error message for parsing of options, if it happens.
          $optionsParseErrorMessage = '';
          //Get tag's options. Options on the following lines until there's
          // an MT line.
          //Look from the end of the tag until find two LFs in a row - MT line.
          //Accumulate chars until find it.
          $tagEndPoint = $tagPos + strlen($openTagText);
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
            //Try parsing tag params as YAML.
            list($options, $optionsParseErrorMessage) = $this->parseParams(
              $optionChars
            );
            //Is there no error, and a test?
            if (
                strlen($optionsParseErrorMessage) === 0
                && isset($options[self::CONDITION_TEST_PARAM_NAME]) ) {
              $context = [];
              $expToEval = $options[self::CONDITION_TEST_PARAM_NAME];
              try {
                //Eval the expression.
                $result = $this->expressionLanguageService->evaluate(
                  $expToEval, $context
                );
                //Was is truthy?
                if ( ! $result ) {
                  $failedTestOption = TRUE;
                }
              } catch (\Exception $e) {
//              } catch (SyntaxError $e) {
                $optionsParseErrorMessage = 'Error in expression: ' . $expToEval
                 . ': ' . $e->getMessage();
              }
            }
          } //End there are option chars.
          //Find the close tag, if there is one, and the content between end
          //of options, and close tag.
          $tagContent = '';
          if ($tagType['hasCloseTag']) {
            $lookFor = $tagType['tagName'] . ".";
            $openTagCount = 1;
            //Where the content for the tag starts.
            $contentStartPos = $tagEndPoint;
            while ($openTagCount > 0) {
              //Find the tag, either opening or closing.
              $loc = stripos($source, $lookFor, $tagEndPoint);
              //If didn't find anything, then missing end tag.
              if ( $loc === FALSE ) {
                return 'h2. Missing/invalid close tag? Missing . at end? Tag: '
                  . $tagType['tagName'];
              }
              if (
                    $this->isTagTextOnLineByItself(
                      $source, $tagType['tagName'], $loc
                    )
              ) {
                //Is it an opening or closing tag?
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
              }
              //Move pointer past the tag just found.
              $tagEndPoint = $loc + strlen($lookFor);
            }
            //Extract the content.
            $tagContent = substr(
              $source, $contentStartPos, $contentEndPos - $contentStartPos);
            //Append parse error, if there was one.
            if ( strlen($optionsParseErrorMessage) > 0 ) {
              $tagContent .= "\n\np(" . self::OPTION_PARSING_ERROR_CLASS . '). '
                . $optionsParseErrorMessage . "\n\n";
            }
          }
          //Process the tag.
          $replacementContent = '';
          //If test option failed, leave the replacement content MT.
          if ( ! $failedTestOption ) {
            $methodName
              = 'process' . ucfirst(strtolower($tagType['tagName'])) . 'Tag';
            if ( ! method_exists($this, $methodName) ) {
              $replacementContent
                = "\n\np(" . self::OPTION_PARSING_ERROR_CLASS . '). '
                  . "Custom tag method missing: $methodName\n\n";
            }
            else {
              $replacementContent
                = call_user_func([$this, $methodName], $tagContent, $options);
            }
          }
          //Replace tag.
          $source = substr($source, 0, $tagPos) . $replacementContent
            . substr($source, $tagEndPoint);
          //Move back to the start, and look for another custom tag.
          $startChar = 0;
          list($gotOne, $tagPos)
            = $this->findOpenTag($source, $tagType['tagName'], $startChar);
        } //End while there are more tags of $tagType.
    }
    return $source;

  }

  /**
   * Parse tag params as YAML. Substitute tokens.
   * @param string $optionChars Params as string to parse.
   *
   * @return array [0](array): options. [1](string): error message
   */
  protected function parseParams(string $optionChars){
    //Make sure each : is followed by a space.
    try {
      $options = parse_ini_string(strtolower($optionChars), FALSE, INI_SCANNER_TYPED);
    } catch (\Exception $e) {
      //Make a message to be shown on the content output page.
      $message = 'Tag parameter parse error: ' . $e->getMessage() . ', in: '
        . str_replace("\n", ' _NL_ ', $optionChars);
      return [ [], $message ];
    }
    if ( $options === FALSE ) {
      //Make a message to be shown on the content output page.
      $message = 'Tag parameter parse error in: ' . str_replace("\n", ' _NL_ ', $optionChars);
      return [ [], $message ];
    }
    //Replace tokens.
    foreach($options as $indx=>$val) {
      //Todo: what happens for invalid token?
      $newVal = strtolower($this->tokenService->replace($val));
      $options[$indx] = $newVal;
    }
    return [ $options, '' ];
  }

  /**
   * Find the next open tag, is there is one.
   *
   * @param string $textToSearch The text to search.
   * @param string $tag The text of the tag, e.g., "exercise"
   * @param int $searchPosStart Where to start the search.
   *
   * @return array [0] (boolean): whether found it, [1] (int) if found, where.
   */
  protected function findOpenTag(string $textToSearch, string $tag,
                                 int $searchPosStart) {
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
        if ( $this->isTagTextOnLineByItself($textToSearch, $tag, $tagPos) ) {
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
   * @param string $tag The tag's opening text, e.g., "exercise.".
   * @param int $tagPos Where the tag starts.
   *
   * @return bool True if the tag text is on a line by itself.
   */
  protected function isTagTextOnLineByItself(string $textToSearch, string $tag,
                                             int $tagPos) {
    //$tag . '.' should be at $tagPos.
    if ( substr($textToSearch, $tagPos, strlen($tag)+1 ) !== $tag . '.' ) {
      throw new SkillParserException(
        'Tag is not in expected position. tag:' . $tag . ', pos:' . $tagPos
      );
    }
    //Get char prior to tag.
    $priorChar = substr($textToSearch, $tagPos - 1, 1);
    //Get char prior to the prior char.
    $priorCharPriorChar = substr($textToSearch, $tagPos - 2, 1);
    //If the match is at the start of the line or content, it could be a tag.
    $tagStartsLine =    $tagPos === 0
                    || $priorChar === "\n"
                    || $priorChar === "/" && $priorCharPriorChar === "\n";
    if ( ! $tagStartsLine ) {
      //Matched text doesn't start line, so not a tag.
      return false;
    }
    //Start looking after the tag, for non-whitespace chars.
    $searchPoint = $tagPos + strlen($tag) + 1; //+1 for . at tag end.
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
    //If didn't find non-whitespace chars, nothing between tag and EOL.
    return ! $foundNonWhiteSpaceChar;
  }

  /**
   * Parse text.
   *
   * @param string $source Text to parse.
   * @return string Result.
   */
  public function parse($source) {
    //Trim whitespace. Authors can use indentation as they want, but it
    //will mess up Textile.
    $source = $this->trimWhitespace($source);
    //Parse custom tags.
    $source = $this->parseCustomTags($source);
    //Replace tokens in source.
    $source = $this->tokenService->replace($source);
    //Textile time.
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
