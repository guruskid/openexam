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

namespace OpenExam\Library\Globalization\Locale;

use OpenExam\Tests\Phalcon\TestCase;

/**
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class LocaleTester extends Locale {

  public function __construct($locale = null) {
    parent::__construct($locale);
    $this->_sapi = __CLASS__;
  }

  public function setSapi($sapi) {
    $this->_sapi = $sapi;
  }

}

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-23 at 15:49:14.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class LocaleTest extends TestCase {

  /**
   * @var Locale
   */
  private $_object;
  private static $_locales = array(
    'sv_SE.UTF-8' => 'Swedish',
    'en_US' => 'English (US)',
  );

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   * @covers OpenExam\Library\Globalization\Locale\Locale::__construct
   */
  protected function setUp() {
    $this->_object = new LocaleTester();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {

  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::setLocales
   * @group globalization
   */
  public function testSetLocales() {
    self::assertTrue(count($this->_object->getLocales()) == 0);
    $this->_object->setLocales(self::$_locales);
    self::assertTrue(count($this->_object->getLocales()) != 0);
    self::assertTrue(count($this->_object->getLocales()) == count(self::$_locales));
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::getLocales
   * @group globalization
   */
  public function testGetLocales() {
    self::assertTrue(count($this->_object->getLocales()) == 0);
    self::assertTrue(is_array($this->_object->getLocales()));
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::addLocale
   * @group globalization
   */
  public function testAddLocale() {
    self::assertTrue(count($this->_object->getLocales()) == 0);
    $this->_object->addLocale('fr_FR.UTF-8', 'French');
    self::assertTrue(count($this->_object->getLocales()) == 1);
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::setLocale
   * @group globalization
   */
  public function testSetLocale() {
    //
    // Locales are system dependent. We can only guess.
    //
    $locale = setlocale(LC_ALL, "0");

    $expect = null; // should always fail.
    $result = $this->_object->setLocale($expect);
    self::assertTrue($result == false);
    $actual = setlocale(LC_ALL, "0");
    self::assertEquals($actual, $locale);

    $expect = null;
    $result = $this->_object->setLocale('no_LOCALE');
    self::assertTrue($result == false);
    $actual = setlocale(LC_ALL, "0");
    self::assertEquals($actual, $locale);

    $expect = "C"; // should always work.
    $result = $this->_object->setLocale($expect);
    self::assertTrue($result == true);
    $actual = setlocale(LC_ALL, "0");
    self::assertEquals($actual, $expect);

    $expect = "en_US"; // a wild guess
    $result = $this->_object->setLocale($expect);
    self::assertTrue($result == true);
    $actual = setlocale(LC_ALL, "0");
    self::assertEquals($actual, $expect);

    setlocale(LC_ALL, $locale); // restore
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::getLocale
   * @group globalization
   */
  public function testGetLocale() {
    self::assertNotNull($this->_object->getLocale());
    $expect = "en_US";
    $this->_object->setLocale($expect);
    $actual = $this->_object->getLocale();
    self::assertNotNull($this->_object->getLocale());
    self::assertEquals($actual, $expect);
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::hasLocale
   * @group globalization
   */
  public function testHasLocale() {
    self::assertFalse($this->_object->hasLocale(key(self::$_locales)));
    $this->_object->setLocales(null);
    self::assertFalse($this->_object->hasLocale(key(self::$_locales)));
    $this->_object->setLocales(self::$_locales);
    self::assertTrue($this->_object->hasLocale(key(self::$_locales)));
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::getDisplayLanguage
   * @group globalization
   */
  public function testGetDisplayLanguage() {
    if (!extension_loaded('intl')) {
      self::markTestIncomplete("Extension intl is not loaded");
    } else {
      $expect = "Swedish";
      $actual = $this->_object->getDisplayLanguage('sv_SE');
      self::assertEquals($actual, $expect);
    }
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::detect
   * @group globalization
   */
  public function testDetect() {
    $name = 'locale';

    //
    // Make sure request honours "live" mode:
    //
    $expect = 'sv';
    $_REQUEST[$name] = $expect;
    $actual = $this->request->get($name);
    self::assertNotNull($actual);
    self::assertEquals($actual, $expect);

    $this->_object->setLocales(self::$_locales);
    $default = 'C';

    //
    // Test web:
    //
    $this->_object->setSapi('web');
    foreach (array(
      'sv_SE.UTF-8' => 'sv_SE.UTF-8', // locale
      'sv' => 'sv_SE.UTF-8', // lang
      'se' => 'sv_SE.UTF-8', // variant
      'en-us' => 'en_US', // alias
      'en' => 'en_US',
      'fr' => $default) as $lang => $expect) {
      //
      // Match request param:
      //
      $_REQUEST[$name] = $lang;
      $actual = $this->_object->detect($name, $default);
      self::info("(request): lang=%s, expect=%s, actual=%s", $lang, $expect, $actual);
      self::assertNotNull($actual);
      self::assertEquals($expect, $actual);
      unset($_REQUEST[$name]);

      //
      // Match session:
      //
      $this->session->start();
      if ($this->session->isStarted()) {
        $this->session->set($name, $lang);
        $actual = $this->_object->detect($name, $default);
        self::info("(session): lang=%s, expect=%s, actual=%s", $lang, $expect, $actual);
        self::assertNotNull($actual);
        self::assertEquals($expect, $actual);
        $this->session->destroy();
      }

      //
      // Match persistent storage:
      //
      if (isset($this->persistent)) {
        $this->persistent->set($name, $lang);
        $actual = $this->_object->detect($name, $default);
        self::info("(persistent): lang=%s, expect=%s, actual=%s", $lang, $expect, $actual);
        self::assertNotNull($actual);
        self::assertEquals($expect, $actual);
        $this->persistent->remove($name);
      }

      //
      // Match HTTP accept language:
      //
      $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $lang;
      $actual = $this->_object->detect($name, $default);
      self::info("(accept): lang=%s, expect=%s, actual=%s", $lang, $expect, $actual);
      self::assertNotNull($actual);
      self::assertEquals($expect, $actual);
    }

    //
    // Test CLI:
    //
    $this->_object->setSapi('cli');
    foreach (array(
      'sv_SE.UTF-8' => 'sv_SE.UTF-8', // locale
      'sv' => 'sv_SE.UTF-8', // lang
      'se' => 'sv_SE.UTF-8', // variant
      'en-us' => 'en_US', // alias
      'en' => 'en_US',
      'fr' => $default) as $lang => $expect) {
      foreach (array('LANG', 'LC_CTYPE') as $name) {
        putenv("$name=$lang");
        self::assertEquals($lang, getenv($name));
        $actual = $this->_object->detect($name, $default);
        self::info("(cli[$name]): lang=%s, expect=%s, actual=%s", $lang, $expect, $actual);
        self::assertNotNull($actual);
        self::assertEquals($expect, $actual);
      }
    }
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::findLocales
   * @group globalization
   */
  public function testFindLocales() {
    $locales = $this->_object->findLocales($this->config->application->localeDir);
    self::assertNotNull($locales);
    self::assertTrue(count($locales) > 0);
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::getRegion
   * @group globalization
   */
  public function testGetRegion() {
    $expect = "US";
    $actual = $this->_object->getRegion('en_US');
    self::assertNotNull($actual);
    self::assertEquals($expect, $actual);

    $expect = "GB";
    $actual = $this->_object->getRegion('en_GB');
    self::assertNotNull($actual);
    self::assertEquals($expect, $actual);

    $expect = "SE";
    $actual = $this->_object->getRegion('sv_SE.UTF-8');
    self::assertNotNull($actual);
    self::assertEquals($expect, $actual);
  }

  /**
   * @covers OpenExam\Library\Globalization\Locale\Locale::getLanguage
   * @group globalization
   */
  public function testGetLanguage() {
    $expect = "en";
    $actual = $this->_object->getLanguage('en_US');
    self::assertNotNull($actual);
    self::assertEquals($expect, $actual);

    $expect = "en";
    $actual = $this->_object->getLanguage('en_GB');
    self::assertNotNull($actual);
    self::assertEquals($expect, $actual);

    $expect = "sv";
    $actual = $this->_object->getLanguage('sv_SE.UTF-8');
    self::assertNotNull($actual);
    self::assertEquals($expect, $actual);
  }

}
