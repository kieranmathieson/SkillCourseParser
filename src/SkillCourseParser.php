<?php

namespace Drupal\hello;

use Drupal\hello\Exception\SkillParserException;
use Drupal\Core\Utility\Token;
use Netcarver\Textile\Parser as TextileParser;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

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
   * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
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
    $this->addTagType('rosie', TRUE);
    $this->addTagType('warning', TRUE);
    $this->addTagType('fake1', TRUE);
  }

  protected function addTagType($tagName, $hasCloseTag) {
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
    for ($i = 0; $i < count($lines); $i++) {
      $lines[$i] = trim($lines[$i]);
    }
    //Convert from array back into one string.
    $source = implode("\n", $lines);
    return $source;
  }

  protected function processFake1Tag($content, $options) {
    $result = "\nFake " . $content . "Fake\n\n";
    return $result;
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
//        $openTagText = $tagType['tagName'] . ".\n";
        $openTagText = $tagType['tagName'] . ".";
        list($gotOne, $tagPos) = $this->findOpenTag($source, $tagType['tagName'], $startChar);
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
            //Is there a test?
            //TOdo: move to class constant
            if ( strlen($optionsParseErrorMessage) === 0 && isset($options['test']) ) {
//              $language = new ExpressionLanguage();
              $context = [];
              $expToEval = $options['test'];
              try {
                //Eval the expression.
                $result = $this->expressionLanguageService->evaluate($expToEval, $context);
//                $result = $language->evaluate($expToEval, $context);
                //Was is truthy?
                if ( ! $result ) {
                  $failedTestOption = TRUE;
                }
              } catch (SyntaxError $e) {
                $optionsParseErrorMessage = 'Error in expression: ' . $expToEval;
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
//              //Get char prior to tag.
//              $priorChar = substr($source, $loc - 1, 1);
//              //Get char prior to the prior char.
//              $priorCharPriorChar = substr($source, $loc - 2, 1);
//              //If the match isn't at the start of the line, it's not a tag.
//              if ( $priorChar === "\n" || ( $priorChar === "/" && $priorCharPriorChar === "\n" ) ) {
              if ( $this->isTagTextOnLineByItself($source, $tagType['tagName'], $loc) ) {
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
            list($gotOne, $tagPos) = $this->findOpenTag($source, $tagType['tagName'], $startChar);
//            $tagPos = stripos($source, $openTagText, $startChar);
          }
        } //End while there are more tags of $tagType.
//      } while ( $gotOne );
//      } while ( $foundCustomTag );
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
    //Try YAML parsing.
    try {
      $options = Yaml::parse($optionChars);
    } catch (ParseException $e) {
      //Make a message to be shown on the content output page.
      $message = 'Tag parameter parse error: ' . $e->getMessage();
      return [ [], $message ];
    }
    if ( is_string($options) ) {
      //Make a message to be shown on the content output page.
      //Todo: Adjust for missing space error?
      $message = "Tag parameters don't parse. Missing required spaces is a common error.";
      return [ [], $message ];
    }
    //Replace tokens.
    foreach($options as $indx=>$val) {
      //Todo: what happens for invalid token?
      $newVal = $this->tokenService->replace($val);
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
  protected function findOpenTag(string $textToSearch, string $tag, int $searchPosStart) {
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
  protected function isTagTextOnLineByItself(string $textToSearch, string $tag, int $tagPos) {
    //$tag . '.' should be at $tagPos.
    if ( substr($textToSearch, $tagPos, strlen($tag)+1 ) !== $tag . '.' ) {
      throw new SkillParserException('Tag is not in expected position. tag:' . $tag . ', pos:' . $tagPos);
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
