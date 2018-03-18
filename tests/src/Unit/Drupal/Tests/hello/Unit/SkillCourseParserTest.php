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
  public function __construct(Token $token) {
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

}

/**
 * Tests stuff.
 *
 * @group hello
 */
class SkillCourseParserTest extends UnitTestCase  {
  public function testTrueIsTrue()  {
    $foo = true;
    $this->assertTrue($foo);
  }

  public function testMockTokenService() {
    $tokenMock = $this->getMockBuilder('Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()
      ->getMock();
    $parser = new TestableParser($tokenMock);
    $foo = true;
    $this->assertTrue($foo);
  }

  public function makeParserWithOpenTag() {
    $tokenMock = $this->getMockBuilder('Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()
      ->getMock();
    $parser = new TestableParser($tokenMock);
    return $parser;
  }

  public function testIsTagTextOnLineByItself1() {
    $parser = $this->makeParserWithOpenTag();
    $textToSearch = "Find\n\nhere.\n\nThe end\n";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is on a line by itself.');
  }

  public function testIsTagTextOnLineByItself2() {
    $parser = $this->makeParserWithOpenTag();
    $textToSearch = "Find\n\nhere.\nThe end\n";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is also on a line by itself.');
  }

  public function testIsTagTextOnLineByItself3() {
    $parser = $this->makeParserWithOpenTag();
    $textToSearch = "Find\n\nhere.    \nThe end\n";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is still on a line by itself.');
  }

  public function testIsTagTextOnLineByItself4() {
    $parser = $this->makeParserWithOpenTag();
    $textToSearch = "Find\n\nis here.\n\nThe end\n";
    $openTagText = 'here';
    $tagPos = 9;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertfalse($tagOnLineByItself, 'Tag is not on a line by itself.');
  }

  public function testIsTagTextOnLineByItself5() {
    $parser = $this->makeParserWithOpenTag();
    $textToSearch = "Find\n\nhere.";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is on a line by itself at EOF.');
  }

  public function testIsTagTextOnLineByItself6() {
    $parser = $this->makeParserWithOpenTag();
    $textToSearch = "Find\n\nhere. ";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is on a line by itself at EOF with spaces.');
  }

  public function testIsTagTextOnLineByItself7() {
    $parser = $this->makeParserWithOpenTag();
    $textToSearch = "Find\n\nhere. Aye!";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertFalse($tagOnLineByItself, 'Tag is not on a line by itself at EOF.');
  }

  public function testIsTagTextOnLineByItself8() {
    $parser = $this->makeParserWithOpenTag();
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
    $parser = $this->makeParserWithOpenTag();
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
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag should be found.');
  }

  public function testFindOpenTag2() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nHere is the thing.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertFalse($gotOne, 'Tag should not be found.');
  }

  public function testFindOpenTag3() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag should be found.');
    $this->assertEquals(6, $tagPos,'Tag should be at pos 6.');
  }

  public function testFindOpenTag4() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nHere is the here.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertFalse($gotOne, 'Thing that looks like a tag but i\'snt should not be found.');
  }

  public function testFindOpenTag5() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nHere is the here.\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag after distractors should be found.');
  }

  public function testFindOpenTag6() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nHere is the here.\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag after distractors should be found.');
    $this->assertEquals(25, $tagPos,'Tag after distractors should be at pos 25.');
  }

  public function testFindOpenTag7() {
    $parser = $this->makeParserWithOpenTag();
    $source = "here.\n\nFind\n\nHere is the here.\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag at front should be found.');
    $this->assertEquals(0, $tagPos,'Tag at front should be at pos 0.');
  }

  public function testFindOpenTag8() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nhere.\n\nThe end\n";
    $tag = 'here';
    $startChar = 10;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertFalse($gotOne, 'Starting search after tag should not find tag.');
  }

  public function testFindOpenTag9() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nhere.";
    $tag = 'here';
    $startChar = 2;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag at last char should be found.');
    $this->assertEquals(6, $tagPos,'Tag at last char should be at pos 6.');
  }

  public function testFindOpenTag10() {
    $parser = $this->makeParserWithOpenTag();
    $source = "here.\n\nFind\n\nhere.";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag at start of content should be found.');
    $this->assertEquals(0, $tagPos,'Tag at start of content should be at pos 0.');
  }

  public function testFindOpenTag11() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nhere.   \n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag should be found.');
    $this->assertEquals(6, $tagPos,'Tag should be at pos 6.');
  }

  public function testFindOpenTag12() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nhere.   X\n\nThe end\n";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertFalse($gotOne, 'Tag should be not found.');
  }

  public function testFindOpenTag13() {
    $parser = $this->makeParserWithOpenTag();
    $source = "Find\n\nhere.   ";
    $tag = 'here';
    $startChar = 0;
    list($gotOne, $tagPos) = $parser->findOpenTag($source, $tag, $startChar);
    $this->assertTrue($gotOne, 'Tag should be found.');
    $this->assertEquals(6, $tagPos,'Tag should be at pos 6.');
  }

  public function testStripWhitespace1() {
    $parser = $this->makeParserWithOpenTag();
    $source = " Find\n\n here.   ";
    $expected = "Find\n\nhere.";
    $result = $parser->trimWhitespace($source);
    $this->assertEquals($expected, $result, 'Tag should be found.');

  }

}
