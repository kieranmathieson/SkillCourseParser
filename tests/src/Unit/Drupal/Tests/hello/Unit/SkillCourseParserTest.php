<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 3/16/2018
 * Time: 9:42 AM
 */

namespace Drupal\Tests\hello\Unit;

use Drupal\hello\SkillCourseParser;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Utility\Token;

class ExposeFindOpenTag extends SkillCourseParser {
  public function __construct(Token $token) {
    parent::__construct($token);
  }

  public function findOpenTag($textToSearch, $tag, $searchPosStart) {
    return parent::findOpenTag($textToSearch, $tag, $searchPosStart);
  }

  public function isTagTextOnLineByItself(string $textToSearch, string $openTagText, integer $tagPos) {
    return parent::isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
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
    $parser = new ExposeFindOpenTag($tokenMock);
    $foo = true;
    $this->assertTrue($foo);
  }

  public function makeParserWithOpenTag() {
    $tokenMock = $this->getMockBuilder('Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()
      ->getMock();
    $parser = new ExposeFindOpenTag($tokenMock);
    return $parser;
  }

  public function testIsTagTextOnLineByItself() {
    $parser = $this->makeParserWithOpenTag();
    $textToSearch = "Find\n\nhere.\n\nThe end\n";
    $openTagText = 'here';
    $tagPos = 6;
    $tagOnLineByItself = $parser->isTagTextOnLineByItself($textToSearch, $openTagText, $tagPos);
    $this->assertTrue($tagOnLineByItself, 'Tag is on a line by itself.');
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

}
