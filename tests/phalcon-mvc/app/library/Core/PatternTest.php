<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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

namespace OpenExam\Library\Core;

use OpenExam\Tests\Phalcon\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-12-15 at 03:56:58.
 */
class PatternTest extends TestCase {

  //
  // Generic:
  //

  /**
   * @group core
   */
  public function testPatternAny() {
    $string = sprintf("%c%c", chr(10), chr(13)); // telnet newline
    self::assertEquals(1, preg_match(Pattern::REGEX_ANY, $string));
  }

  /**
   * @group core
   */
  public function testPatternNothing() {
    $strings = array(
      "" => 1,
      "1" => 0,
      "a" => 0,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_NOTHING, $string), $string);
    }

    self::assertEquals(1, preg_match(Pattern::REGEX_NOTHING, null));
  }

  /**
   * @group core
   */
  public function testPatternUrl() {
    $strings = array(
      "http://www.example.com" => 1,
      "https://www.example.com" => 1,
      "ftp://www.example.com" => 1,
      "ftps://www.example.com" => 1,
      "ssh://www.example.com" => 1,
      "sftp://www.example.com" => 1,
      "afp://www.example.com" => 0,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_URL, $string), $string);
    }
  }

  //
  // Number:
  //

  /**
   * @group core
   */
  public function testPatternFloat() {
    $strings = array(
      "1.5" => 1,
      "1,5" => 1,
      "1" => 1,
      ".5" => 1,
      ",5" => 1,
      ".025" => 1,
      "1.a" => 0,
      "abc" => 0,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_FLOAT, $string), $string);
    }
  }

  /**
   * @group core
   */
  public function testPatternIndex() {
    $strings = array(
      "0" => 1,
      "1" => 1,
      "-1" => 1,
      "1.5" => 0,
      "a" => 0,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_INDEX, $string), $string);
    }
  }

  /**
   * @group core
   */
  public function testPatternScore() {
    $strings = array(
      "1.5" => 1,
      "1,5" => 1,
      "1" => 1,
      ".5" => 1,
      ",5" => 1,
      ".025" => 1,
      "1.a" => 0,
      "abc" => 0,
      "1.5p" => 1,
      "1,5p" => 1,
      "1.5 p" => 1,
      "1,5 p" => 1,
      "1.5 point" => 1,
      "1.5 poäng" => 1,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_SCORE, $string), $string);
    }
  }

  //
  // Text:
  //

  /**
   * @group core
   */
  public function testPatternMultiLineText() {
    $strings = array(
      "some text" => 1,
      "some\nother\ntext" => 1,
      "\nmore\n\ntext\n\n" => 1,
      "text with number 123" => 1,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_MULTI_LINE_TEXT, $string), $string);
    }
  }

  /**
   * @group core
   */
  public function testPatternSingleLineText() {
    $strings = array(
      "some text" => 1,
      "some\nother\ntext" => 0,
      "text with number 123" => 1,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_SINGLE_LINE_TEXT, $string), $string);
    }
  }

  //
  // User:
  //

  /**
   * @group core
   */
  public function testPatternUser() {
    $strings = array(
      "user" => 1,
      "user@server" => 1,
      "user@server.example.com" => 1,
      "user-name@server.example.com" => 1,
      "user_name@server.example.com" => 1,
      "user\name@server.example.com" => 0,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_USER, $string), $string);
    }
  }

  /**
   * @group core
   */
  public function testPatternCode() {
    $strings = array(
      "1234ABCD" => 1,
      "1234-ABCD" => 1,
      "1234_ABCD" => 1,
      "1234567890ABCDE" => 1,
      "1234567890ABCDEF" => 0, // Max length exceeded
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_CODE, $string), $string);
    }
  }

  /**
   * @group core
   */
  public function testPatternName() {
    $strings = array(
      "Adam" => 1,
      "Adam Bertilsson" => 1,
      "Adam Bertilsson-Götlind" => 1,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_NAME, $string), $string);
    }

    $string = sprintf("Adam Bertilsson-Götlind%c", chr(10));
    self::assertEquals(0, preg_match(Pattern::REGEX_NAME, $string), $string);
  }

  /**
   * @group core
   */
  public function testPatternPersnr() {
    $strings = array(
      "801231-1234" => 1,
      "19801231-1234" => 1,
      "8012311234" => 1,
      "198012311234" => 1,
      "19801231-T234" => 1,
      "19801231-123T" => 1,
      "19801231-1T34" => 0,
      "20051231-1234" => 1,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_PERSNR, $string), $string);
    }
  }

  //
  // Course:
  //

  /**
   * @group core
   */
  public function testPatternCourse() {
    $strings = array(
      "3FV271" => 1,
      "UPPDOK-3FV271" => 1,
      "UPPDOK - 3FV271" => 1,
      "UPPDOK - 3FV271 ABCD" => 1,
      "UPPDOK - 3FV271 ABCDE" => 0, // Max length exceeded
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_COURSE, $string), $string);
    }
  }

  /**
   * @group core
   */
  public function testPatternYear() {
    $strings = array(
      "99" => 1,
      "1999" => 1,
      "00" => 1,
      "01" => 1,
      "2001" => 1,
      "20AB" => 0,
      "AB20" => 0,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_YEAR, $string), $string);
    }
  }

  /**
   * @group core
   */
  public function testPatternTermin() {
    $strings = array(
      "0" => 0,
      "1" => 1,
      "2" => 1,
      "3" => 0,
    );
    foreach ($strings as $string => $expect) {
      self::assertEquals($expect, preg_match(Pattern::REGEX_TERMIN, $string), $string);
    }
  }

  /**
   * Test get function.
   * @group core
   */
  public function testGet() {
    $expect = Pattern::REGEX_USER;
    $actual = Pattern::get(Pattern::MATCH_USER);

    self::assertNotNull($actual);
    self::assertEquals($expect, $actual);
  }

  /**
   * Test set function.
   * @group core
   */
  public function testSet() {
    $expect = "user1";
    Pattern::set(Pattern::MATCH_USER, $expect);
    $actual = Pattern::get(Pattern::MATCH_USER);

    self::assertNotNull($actual);
    self::assertEquals($expect, $actual);
  }

  /**
   * Test match function.
   * @group core
   */
  public function testMatch() {
    $matches = array();

    $expect = false;
    $actual = Pattern::match(Pattern::MATCH_YEAR, 1.0, $matches);

    self::assertNotNull($actual);
    self::assertTrue(is_bool($actual));
    self::assertEquals($expect, $actual);

    $expect = true;
    $actual = Pattern::match(Pattern::MATCH_YEAR, 2017, $matches);

    self::assertNotNull($actual);
    self::assertTrue(is_bool($actual));
    self::assertEquals($expect, $actual);
  }

}
