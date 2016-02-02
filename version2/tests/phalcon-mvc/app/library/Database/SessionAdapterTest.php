<?php

namespace OpenExam\Library\Database;

use OpenExam\Models\Session;
use OpenExam\Tests\Phalcon\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-06-08 at 22:36:06.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SessionAdapterTest extends TestCase
{

        /**
         * @var SessionAdapter
         */
        protected $object;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                $this->object = new SessionAdapter();
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown()
        {
                
        }

        /**
         * @covers OpenExam\Library\Database\SessionAdapter::open
         * @group database
         */
        public function testOpen()
        {
                self::assertTrue($this->object->open());
        }

        /**
         * @covers OpenExam\Library\Database\SessionAdapter::close
         * @group database
         */
        public function testClose()
        {
                self::assertFalse($this->object->close());
        }

        /**
         * @covers OpenExam\Library\Database\SessionAdapter::read
         * @group database
         */
        public function testRead()
        {
                $session = new Session();
                $session->data = "data";
                $session->created = time();
                $session->session_id = md5(time());

                self::assertTrue($session->save());

                $expect = "";
                $actual = $this->object->read(null);
                self::assertEquals($actual, $expect);

                $expect = "";
                $actual = $this->object->read("");
                self::assertEquals($actual, $expect);
                
                $expect = $session->data;
                $actual = $this->object->read($session->session_id);
                self::assertEquals($actual, $expect);

                $expect = "";
                $actual = $this->object->read(null);
                self::assertEquals($actual, $expect);

                self::assertTrue($session->delete());    // cleanup
        }

        /**
         * @covers OpenExam\Library\Database\SessionAdapter::write
         * @group database
         */
        public function testWrite()
        {
                $session = new Session();
                $session->data = "";
                $session->session_id = md5(time());

                self::assertFalse($this->object->write($session->session_id, $session->data));

                $session->data = "data1";       // insert
                $session->created = time();
                $session->updated = null;
                self::assertTrue($this->object->write($session->session_id, $session->data));
                $actual = Session::findFirstBySessionId($session->session_id);
                self::assertEquals($actual->created, $session->created);
                self::assertEquals($actual->updated, $session->updated);

                sleep(1);

                $session->data = "data2";       // update
                $session->updated = time();
                self::assertTrue($this->object->write($session->session_id, $session->data));
                $actual = Session::findFirstBySessionId($session->session_id);
                self::assertEquals($actual->created, $session->created);
                self::assertEquals($actual->updated, $session->updated);

                sleep(1);

                $session->data = "data2";       // noop
                self::assertTrue($this->object->write($session->session_id, $session->data));
                $actual = Session::findFirstBySessionId($session->session_id);
                self::assertEquals($actual->created, $session->created);
                self::assertEquals($actual->updated, $session->updated);

                self::assertTrue($actual->delete());    // cleanup
        }

        /**
         * @covers OpenExam\Library\Database\SessionAdapter::destroy
         * @group database
         */
        public function testDestroy()
        {
                self::markTestIncomplete("Requires browser session");
        }

        /**
         * @covers OpenExam\Library\Database\SessionAdapter::gc
         * @group database
         */
        public function testGc()
        {
                $maxlifetime = $this->config->session->expires;

                $session = new Session();
                $session->data = "data_gc";
                $session->created = time() - $maxlifetime;
                $session->session_id = md5(time());

                self::assertFalse($this->object->gc(0));        // no gc
                self::assertTrue($session->save());             // insert                
                self::assertFalse($this->object->gc(0));        // no gc

                // 
                // Fetch session object onto $this->object:
                // 
                self::assertEquals($session->data, $this->object->read($session->session_id));

                // 
                // Cleanup old stale sessions:
                // 
                self::assertTrue($this->object->gc($maxlifetime));

                $count = Session::count();
                self::assertTrue($this->object->gc($maxlifetime + 1));  // not yet...
                self::assertEquals($count, (int) Session::count());

                self::assertTrue($this->object->gc($maxlifetime - 1));  // ...but now
                self::assertEquals($count - 1, (int) Session::count());

                self::assertTrue($session->delete());   // cleanup
        }

}
