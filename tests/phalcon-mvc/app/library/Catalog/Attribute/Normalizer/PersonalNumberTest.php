<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace OpenExam\Library\Catalog\Attribute\Normalizer;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2017-04-06 at 13:59:42.
 */
class PersonalNumberTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var PersonalNumber
   */
  protected $object;
  /**
   * The input string.
   * @var string
   */
  private $_persnr = '841231-1234';
  /**
   * The normalized string.
   * @var string
   */
  private $_fullnr = '198412311234';

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    $this->object = new PersonalNumber($this->_persnr);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {

  }

  /**
   * @covers OpenExam\Library\Catalog\Attribute\Normalizer\PersonalNumber::__toString
   * @group catalog
   */
  public function test__toString() {
    $expect = $this->_persnr;
    $actual = (string) $this->object;

    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);
  }

  /**
   * @covers OpenExam\Library\Catalog\Attribute\Normalizer\PersonalNumber::__get
   * @group catalog
   */
  public function test__get() {
    $expect = substr($this->_fullnr, 0, 4);
    $actual = $this->object->year;
    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);

    $expect = substr($this->_fullnr, 4, 2);
    $actual = $this->object->month;
    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);

    $expect = substr($this->_fullnr, 6, 2);
    $actual = $this->object->day;
    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);

    $expect = substr($this->_fullnr, 0, 8);
    $actual = $this->object->birth;
    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);

    $expect = substr($this->_fullnr, 11, 1);
    $actual = $this->object->checksum;
    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);

    $expect = substr($this->_fullnr, 8, 3);
    $actual = $this->object->serial;
    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);

    $expect = false;
    $actual = $this->object->foreign;
    self::assertNotNull($actual);
    self::assertTrue(is_bool($actual));
    self::assertEquals($expect, $actual);

    $expect = false;
    $actual = $this->object->male;
    self::assertNotNull($actual);
    self::assertTrue(is_bool($actual));
    self::assertEquals($expect, $actual);
  }

  /**
   * @covers OpenExam\Library\Catalog\Attribute\Normalizer\PersonalNumber::isMale
   * @group catalog
   */
  public function testIsMale() {
    $expect = $this->object->male;
    $actual = $this->object->isMale();
    self::assertNotNull($actual);
    self::assertTrue(is_bool($actual));
    self::assertEquals($expect, $actual);
  }

  /**
   * @covers OpenExam\Library\Catalog\Attribute\Normalizer\PersonalNumber::isFemale
   * @group catalog
   */
  public function testIsFemale() {
    $expect = $this->object->male === false;
    $actual = $this->object->isFemale();
    self::assertNotNull($actual);
    self::assertTrue(is_bool($actual));
    self::assertEquals($expect, $actual);
  }

  /**
   * @covers OpenExam\Library\Catalog\Attribute\Normalizer\PersonalNumber::isForeign
   * @group catalog
   */
  public function testIsForeign() {
    $expect = $this->object->foreign;
    $actual = $this->object->isForeign();
    self::assertNotNull($actual);
    self::assertTrue(is_bool($actual));
    self::assertEquals($expect, $actual);
  }

  /**
   * @covers OpenExam\Library\Catalog\Attribute\Normalizer\PersonalNumber::getFormatted
   * @group catalog
   */
  public function testGetFormatted() {
    $expect = $this->_persnr;
    $actual = $this->object->getFormatted();
    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);
  }

  /**
   * @covers OpenExam\Library\Catalog\Attribute\Normalizer\PersonalNumber::getNormalized()
   * @group catalog
   */
  public function testGetNormalized() {
    $expect = $this->_fullnr;
    $actual = $this->object->getNormalized();
    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);
  }

  /**
   * @covers OpenExam\Library\Catalog\Attribute\Normalizer\PersonalNumber::format($persnr)
   * @group catalog
   */
  public function testFormat() {
    $expect = $this->_persnr;
    $actual = $this->object->format($this->_fullnr);
    self::assertNotNull($actual);
    self::assertTrue(is_string($actual));
    self::assertEquals($expect, $actual);
  }

}
