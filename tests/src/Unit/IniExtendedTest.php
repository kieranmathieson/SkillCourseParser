<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 4/2/18
 * Time: 3:37 PM
 */

namespace Drupal\Tests\hello\Unit;


use Drupal\hello\IniExtended;
use Drupal\Tests\UnitTestCase;

class IniExtendedTest extends UnitTestCase {

  public function testSimpleParam1() {
    $source = 'elf=5';
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['elf']), 'elf exists');
    $this->assertEquals(5, $result['default']['elf'], 'Got a 5');
  }

  public function testSimpleParam2() {
    $source = 'elfy="thing"';
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['elfy']), 'elfy exists');
    $this->assertEquals("thing", $result['default']['elfy'], 'Got a "thing"');
  }


  public function testSimpleParam3() {
    $source = 'elfy=thing';
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['elfy']), 'elfy exists');
    $this->assertEquals("thing", $result['default']['elfy'], 'Got a "thing"');
  }

  public function testSimpleParam4() {
    $source = 'elfy=true';
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['elfy']), 'elfy exists');
    $this->assertTrue($result['default']['elfy'], 'Got truth');
  }

  public function testSimpleParam5() {
    $source = 'elfy=false';
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['elfy']), 'elfy exists');
    $this->assertFalse($result['default']['elfy'], 'Got lies');
  }

  public function testSimpleParam6() {
    $source = 'elfy="mouse.ear"';
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['elfy']), 'elfy exists');
    $this->assertEquals("mouse.ear", $result['default']['elfy'], 'Got mouse ear');
  }

  public function testSimpleParam7() {
    $source = 'elfy=mouse.ear';
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['elfy']), 'elfy exists');
    $this->assertEquals("mouse.ear", $result['default']['elfy'], 'Got mouse ear');
  }

  public function testMultiParam1() {
    $source = "zim=1\ngir=2";
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['zim']), 'zim exists');
    $this->assertTrue(isset($result['default']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['default']), 'Got pair');
    $this->assertEquals(2, $result['default']['gir'], 'Got gir');
    $this->assertEquals(1, $result['default']['zim'], 'Got zim');
  }

  public function testMultiParam2() {
    $source = "zim='moose'\ngir=squirrel";
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['zim']), 'zim exists');
    $this->assertTrue(isset($result['default']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['default']), 'Got pair');
    $this->assertEquals('squirrel', $result['default']['gir'], 'Got gir');
    $this->assertEquals('moose', $result['default']['zim'], 'Got zim');
  }

  public function testMultiParam3() {
    $source = "zim=true\ngir=false";
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['zim']), 'zim exists');
    $this->assertTrue(isset($result['default']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['default']), 'Got pair');
    $this->assertFalse($result['default']['gir'], 'Got gir');
    $this->assertTrue($result['default']['zim'], 'Got zim');
  }

  public function testNestedParam1() {
    $source = "zim.toes=4\ngir.fingers=6";
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['zim']), 'zim exists');
    $this->assertTrue(isset($result['default']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['default']), 'Got pair');
    $this->assertTrue(isset($result['default']['zim']['toes']), 'zim toes exists');
    $this->assertTrue(isset($result['default']['gir']['fingers']), 'gir fingers exists');
    $this->assertEquals(1, count($result['default']['zim']), 'Got one for zim');
    $this->assertEquals(1, count($result['default']['gir']), 'Got one for gir');
    $this->assertEquals(4, $result['default']['zim']['toes'], 'Got zim toes');
    $this->assertEquals(6, $result['default']['gir']['fingers'], 'Got gir fingers');
  }

  public function testNestedParam2() {
    $source = "zim.toes=turkey\ngir.fingers='all along'";
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['zim']), 'zim exists');
    $this->assertTrue(isset($result['default']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['default']), 'Got pair');
    $this->assertTrue(isset($result['default']['zim']['toes']), 'zim toes exists');
    $this->assertTrue(isset($result['default']['gir']['fingers']), 'gir fingers exists');
    $this->assertEquals(1, count($result['default']['zim']), 'Got one for zim');
    $this->assertEquals(1, count($result['default']['gir']), 'Got one for gir');
    $this->assertEquals('turkey', $result['default']['zim']['toes'], 'Got zim toes');
    $this->assertEquals('all along', $result['default']['gir']['fingers'], 'Got gir fingers');
  }

  public function testNestedParam3() {
    $source = "zim.toes=turkey\ngir.fingers=all along";
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['zim']), 'zim exists');
    $this->assertTrue(isset($result['default']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['default']), 'Got pair');
    $this->assertTrue(isset($result['default']['zim']['toes']), 'zim toes exists');
    $this->assertTrue(isset($result['default']['gir']['fingers']), 'gir fingers exists');
    $this->assertEquals(1, count($result['default']['zim']), 'Got one for zim');
    $this->assertEquals(1, count($result['default']['gir']), 'Got one for gir');
    $this->assertEquals('turkey', $result['default']['zim']['toes'], 'Got zim toes');
    $this->assertEquals('all along', $result['default']['gir']['fingers'], 'Got gir fingers');
  }

  public function testNestedParam4() {
    $source = "zim.toes=true\ngir.fingers=false";
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['zim']), 'zim exists');
    $this->assertTrue(isset($result['default']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['default']), 'Got pair');
    $this->assertTrue(isset($result['default']['zim']['toes']), 'zim toes exists');
    $this->assertTrue(isset($result['default']['gir']['fingers']), 'gir fingers exists');
    $this->assertEquals(1, count($result['default']['zim']), 'Got one for zim');
    $this->assertEquals(1, count($result['default']['gir']), 'Got one for gir');
    $this->assertTrue($result['default']['zim']['toes'], 'Got zim toes');
    $this->assertFalse($result['default']['gir']['fingers'], 'Got gir fingers');
  }

  /**
   * Test for missing name component after dot.
   */
  public function testNestedParam5() {
    $source = "zim.toes.=4\ngir.fingers=6";
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['default']), 'default section');
    $this->assertTrue(isset($result['default']['zim']), 'zim exists');
    $this->assertTrue(isset($result['default']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['default']), 'Got pair');
    $this->assertTrue(isset($result['default']['zim']['toes']), 'zim toes exists');
    $this->assertTrue(isset($result['default']['gir']['fingers']), 'gir fingers exists');
    $this->assertEquals(1, count($result['default']['zim']), 'Got one for zim');
    $this->assertEquals(1, count($result['default']['gir']), 'Got one for gir');
    $this->assertEquals(4, $result['default']['zim']['toes'][''], 'Got zim toes');
    $this->assertEquals(6, $result['default']['gir']['fingers'], 'Got gir fingers');
  }

  public function testSection1() {
    $source = "[invader]\nzim=1\ngir=2";
    $result = IniExtended::parse($source);
    $this->assertEquals(1, count($result), 'One element returned');
    $this->assertTrue(isset($result['invader']), 'invader section');
    $this->assertTrue(isset($result['invader']['zim']), 'zim exists');
    $this->assertTrue(isset($result['invader']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['invader']), 'Got pair');
    $this->assertEquals(2, $result['invader']['gir'], 'Got gir');
    $this->assertEquals(1, $result['invader']['zim'], 'Got zim');
  }

  public function testSection2() {
    $source = "[invader]\nzim=1\ngir=2\n[bosses]\nteacher=Miss Bitters\ninvaders=Tallest";
    $result = IniExtended::parse($source);
    $this->assertEquals(2, count($result), 'One element returned');
    $this->assertTrue(isset($result['invader']), 'invader section');
    $this->assertTrue(isset($result['invader']['zim']), 'zim exists');
    $this->assertTrue(isset($result['invader']['gir']), 'gir exists');
    $this->assertEquals(2, count($result['invader']), 'Got pair');
    $this->assertEquals(2, $result['invader']['gir'], 'Got gir');
    $this->assertEquals(1, $result['invader']['zim'], 'Got zim');
    $this->assertTrue(isset($result['bosses']), 'bosses section');
    $this->assertTrue(isset($result['bosses']['teacher']), 'teacher exists');
    $this->assertTrue(isset($result['bosses']['invaders']), 'invaders exists');
    $this->assertEquals(2, count($result['bosses']), 'Got pair');
    $this->assertEquals('Miss Bitters', $result['bosses']['teacher'], 'Got teacher');
    $this->assertEquals('Tallest', $result['bosses']['invaders'], 'Got invaders');
  }


}