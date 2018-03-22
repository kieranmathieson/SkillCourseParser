<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 3/16/2018
 * Time: 9:42 AM
 */

namespace Drupal\Tests\hello\Unit;

use Drupal\hello\Exception\SkillParserException;
use Drupal\hello\SkillCourseParser;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Utility\Token;

class TestableParser extends SkillCourseParser {
  public function __construct(Token $token, ExpressionLanguage $expressionLanguage) {
    parent::__construct($token);
  }

  public function findOpenTag(string $textToSearch, string $tag, int $searchPosStart) {
    return parent::findOpenTag($textToSearch, $tag, $searchPosStart);
  }

  public function isTagTextOnLineByItself(string $textToSearch, string $openTagText, int $tagPos) {
    return parent::isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
  }

  public function trimWhitespace(string $source) {
    return parent::trimWhitespace($source);
  }

  public function parseParams(string $optionChars) {
    return parent::parseParams($optionChars);
  }

  public function parseCustomTags(string $source) {
    return parent::parseCustomTags($source);
  }

//  public function fixMissingSpaceInParams(string $source) {
//    return parent::fixMissingSpaceInParams($source);
//  }

}

class ExpressionLanguage {

  /**
   * Gets the string representation of the expression.
   */
  public function __toString() {
    return 'dummy_expression';
  }

  public function evaluate($something) {
    return eval($something);
  }

}

/**
 * Tests stuff.
 *
 * @group hello
 */
class SkillCourseParserTest extends UnitTestCase  {

  public function makeTestableParser() {
    $tokenMock = $this->getMockBuilder('Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()
      ->getMock();
    $tokenMock->method('replace')
      ->will($this->returnArgument(0));
    $parser = new TestableParser($tokenMock, new ExpressionLanguage());





    return $parser;
  }

  public function testIsTagTextOnLineByItself1() {
    $parser = $this->makeTestableParser();
    $textToSearch = "Find\n\nhere.\n\nThe end\n";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is on a line by itself.');
  }

  public function testIsTagTextOnLineByItself2() {
    $parser = $this->makeTestableParser();
    $textToSearch = "Find\n\nhere.\nThe end\n";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is also on a line by itself.');
  }

  public function testIsTagTextOnLineByItself3() {
    $parser = $this->makeTestableParser();
    $textToSearch = "Find\n\nhere.    \nThe end\n";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is still on a line by itself.');
  }

  public function testIsTagTextOnLineByItself4() {
    $parser = $this->makeTestableParser();
    $textToSearch = "Find\n\nis here.\n\nThe end\n";
    $openTagText = 'here';
    $tagPos = 9;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertfalse($tagOnLineByItself, 'Tag is not on a line by itself.');
  }

  public function testIsTagTextOnLineByItself5() {
    $parser = $this->makeTestableParser();
    $textToSearch = "Find\n\nhere.";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is on a line by itself at EOF.');
  }

  public function testIsTagTextOnLineByItself6() {
    $parser = $this->makeTestableParser();
    $textToSearch = "Find\n\nhere. ";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is on a line by itself at EOF with spaces.');
  }

  public function testIsTagTextOnLineByItself7() {
    $parser = $this->makeTestableParser();
    $textToSearch = "Find\n\nhere. Aye!";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertFalse($tagOnLineByItself, 'Tag is not on a line by itself at EOF.');
  }

  public function testIsTagTextOnLineByItself8() {
    $parser = $this->makeTestableParser();
    $textToSearch = "Find\n\nhere. Aye!";
    $openTagText = 'here';
    $tagPos = 3; //Wrong.
    $this->setExpectedException(
      SkillParserException::class
    );
    $tagOnLineByItself = $parser->isTagTextOnLineByItself(
      $textToSearch,
      $openTagText,
      $tagPos
    );
  }

  public function testIsTagTextOnLineByItself9() {
    $parser = $this->makeTestableParser();
    $textToSearch = "Find\n\nhere. Aye!";
    $openTagText = 'dog'; //Wrong.
    $tagPos = 6;
    $this->setExpectedException(
      SkillParserException::class
    );
    $tagOnLineByItself = $parser->isTagTextOnLineByItself(
      $textToSearch,
      $openTagText,
      $tagPos
    );
  }


  public function testFindOpenTag1() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag should be found.');
  }

  public function testFindOpenTag2() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nHere is the thing.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertFalse($gotOne, 'Tag should not be found.');
  }

  public function testFindOpenTag3() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag should be found.');
    $this->assertEquals(6, $tagPos,'Tag should be at pos 6.');
  }

  public function testFindOpenTag4() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nHere is the here.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertFalse($gotOne, 'Thing that looks like a tag but i\'snt should not be found.');
  }

  public function testFindOpenTag5() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nHere is the here.\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag after distractors should be found.');
  }

  public function testFindOpenTag6() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nHere is the here.\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag after distractors should be found.');
    $this->assertEquals(25, $tagPos,'Tag after distractors should be at pos 25.');
  }

  public function testFindOpenTag7() {
    $parser = $this->makeTestableParser();
    $source = "here.\n\nFind\n\nHere is the here.\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag at front should be found.');
    $this->assertEquals(0, $tagPos,'Tag at front should be at pos 0.');
  }

  public function testFindOpenTag8() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 10;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertFalse($gotOne, 'Starting search after tag should not find tag.');
  }

  public function testFindOpenTag9() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nhere.";
    $tag = 'here';
    $startChar = 2;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag at last char should be found.');
    $this->assertEquals(6, $tagPos,'Tag at last char should be at pos 6.');
  }

  public function testFindOpenTag10() {
    $parser = $this->makeTestableParser();
    $source = "here.\n\nFind\n\nhere.";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag at start of content should be found.');
    $this->assertEquals(0, $tagPos,'Tag at start of content should be at pos 0.');
  }

  public function testFindOpenTag11() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nhere.   \n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag should be found.');
    $this->assertEquals(6, $tagPos,'Tag should be at pos 6.');
  }

  public function testFindOpenTag12() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nhere.   X\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertFalse($gotOne, 'Tag should be not found.');
  }

  public function testFindOpenTag13() {
    $parser = $this->makeTestableParser();
    $source = "Find\n\nhere.   ";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag should be found.');
    $this->assertEquals(6, $tagPos,'Tag should be at pos 6.');
  }

  public function testStripWhitespace1() {
    $parser = $this->makeTestableParser();
    $source = " Find\n\n here.   ";
    $expected = "Find\n\nhere.";
    $result = $parser->trimWhitespace($source);
    $this->assertEquals($expected, $result, 'Whitespace stipped successfully.');

  }

  public function testStripWhitespace2() {
    $parser = $this->makeTestableParser();
    $source = " Find\n\n here.   \n    dog\n  \n  dog   \n";
    $expected = "Find\n\nhere.\ndog\n\ndog\n";
    $result = $parser->trimWhitespace($source);
    $this->assertEquals($expected, $result, 'Whitespace stipped successfully.');

  }

  public function testParamsParse1() {
    $parser = $this->makeTestableParser();
    $source = "t1 = 3";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals(3, $params['t1'], 'Param parsed.');
    $this->assertEquals('', $parseError, 'No param parse error.');
  }

  public function testParamsParse2() {
    $parser = $this->makeTestableParser();
    //Missing space.
    $source = "t1=3";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals(3, $params['t1'], 'Param parsed.');
    $this->assertEquals('', $parseError, 'No param parse error.');
  }

  public function testParamsParse3() {
    $parser = $this->makeTestableParser();
    $source = "t1 = 3\nt2 = 4";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals(3, $params['t1'], 'Param 1 parsed.');
    $this->assertEquals(4, $params['t2'], 'Param 2 parsed.');
    $this->assertEquals('', $parseError, 'No param parse error.');
  }

  public function testParamsParse4() {
    $parser = $this->makeTestableParser();
    $source = "t1 = 3\nt2 = 4\nanimal = duck\n";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals(3, $params['t1'], 'Param 1 parsed.');
    $this->assertEquals(4, $params['t2'], 'Param 2 parsed.');
    $this->assertEquals('duck', $params['animal'], 'Param 3 parsed.');
    $this->assertEquals('', $parseError, 'No param parse error.');
  }

  public function testParamsParse5() {
    $parser = $this->makeTestableParser();
    $source = "t1 = 3\nt2 = 4\nanimal = duck goose\n";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals(3, $params['t1'], 'Param 1 parsed.');
    $this->assertEquals(4, $params['t2'], 'Param 2 parsed.');
    $this->assertEquals('duck goose', $params['animal'], 'Param 3 parsed.');
    $this->assertEquals('', $parseError, 'No param parse error.');
  }

  public function testParamsParse6() {
    $parser = $this->makeTestableParser();
    $source = "t1 = 3\nt2 = 4\nanimal = 'duck goose'\n";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals(3, $params['t1'], 'Param 1 parsed.');
    $this->assertEquals(4, $params['t2'], 'Param 2 parsed.');
    $this->assertEquals('duck goose', $params['animal'], 'Param 3 parsed.');
    $this->assertEquals('', $parseError, 'No param parse error.');
  }

  public function testParamsParse7() {
    $parser = $this->makeTestableParser();
    //Unclosed '.
    $source = "t1 = 3\nt2 = 4\nanimal = 'duck goose\n";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals([], $params, 'Param parse failed.');
    $this->assertTrue(stripos($parseError, 'error') !== FALSE, 'Params malformed.');
  }

  public function testParamsParse8() {
    $parser = $this->makeTestableParser();
    $source = "t1 = 3\nt2 = 4\nanimal = \"duck goose\n";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals([], $params, 'Param parse failed.');
    $this->assertTrue(strlen($parseError) > 0, 'Params malformed.');
  }

  public function testParamsParse9() {
    $parser = $this->makeTestableParser();
    $source = "t1 = 3\nt2 = 4\nanimal = \"duck goose\"\n";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals(4, $params['t2'], 'Param parse nested.');
    $this->assertEquals('duck goose', $params['animal'], 'Param parse nested.');
    $this->assertTrue(strlen($parseError) === 0, 'Params OK.');
  }

  /**
   * Include test expression, valid syntax.
   */
  public function testParamsParse10() {
    $parser = $this->makeTestableParser();
    $source = "t1 = 3\nt2 = 4\nanimal = 'duck goose'\ntest = '2+3>1'";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals(4, $params['t2'], 'Param parse.');
    $this->assertEquals('duck goose', $params['animal'], 'Param parse.');
    $this->assertTrue(strlen($parseError) === 0, 'Params OK.');
  }

    /**
     * Include test expression, missing 's.
     */
  public function testParamsParse11() {
    $parser = $this->makeTestableParser();
    $source = "t1 = 3\nt2 = 4\nanimal = 'duck goose'\ntest = 2+3>1";
    list($params, $parseError) = $parser->parseParams($source);
    $this->assertEquals(4, $params['t2'], 'Param parse.');
    $this->assertEquals('duck goose', $params['animal'], 'Param parse.');
    $this->assertTrue(strlen($parseError) === 0, 'Params OK.');
  }



  public function testParseCustom1() {
    $parser = $this->makeTestableParser();
    $source = "fake1.\n\nDog\n\n/fake1.\n\nThis is the last one.";
    $result = $parser->parseCustomTags($source);
    $expected = "\n\nFake \n\nDog\n\nFake\n\n\n\nThis is the last one.";
    $this->assertEquals($expected, $result, 'Parse results as expected.');
  }

  public function testParseCustom2() {
    $parser = $this->makeTestableParser();
    $source = "fake1.\nparam = 1\n\nDog\n\n/fake1.\n\nThis is the last one.";
    $result = $parser->parseCustomTags($source);
    $expected = "\n\nFake \n\nDog\n\nFake\n\n\n\nThis is the last one.";
    $this->assertEquals($expected, $result, 'Parse results as expected.');
  }

  public function testParseCustom3() {
    $parser = $this->makeTestableParser();
    $source = "fake1.\nparam = 'dog\n\nDog\n\n/fake1.\n\nThis is the last one.";
    $result = $parser->parseCustomTags($source);
    $expected = "\n\nFake \n\nDog\n\nFake\n\n\n\nThis is the last one.";
    $this->assertTrue(
      stripos($result, SkillCourseParser::OPTION_PARSING_ERROR_CLASS) !== FALSE,
      'Parse error message, as expected.'
    );
  }

  public function testParse1() {
    $parser = $this->makeTestableParser();
    $source = "h1. Dog\n\nDog.\n\nThis is the last one.";
    $result = $parser->parse($source);
    $expected = "<h1>Dog</h1>\n\n<p>Dog.</p>\n\n<p>This is the last one.</p>";
    $this->assertEquals($expected, $result, 'Parse results as expected.');
  }

}
