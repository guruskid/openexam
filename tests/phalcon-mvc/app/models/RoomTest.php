<?php

namespace OpenExam\Models;

use OpenExam\Models\Room;
use OpenExam\Tests\Phalcon\TestModel;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-15 at 08:53:37.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class RoomTest extends TestModel
{

        public function __construct()
        {
                parent::__construct(new Room());
        }

        /**
         * @group model
         */
        public function testRelations()
        {
                $room = Computer::findFirst()->room;
                self::assertNotNull($room);

                self::assertNotEquals($room->computers->count(), 0);
                self::assertTrue(count($room->computers) > 0);
        }

        /**
         * @group model
         */
        public function testProperties()
        {
                $values = array(
                        'name'        => 'Name1',
                        'description' => 'Description1'
                );

                try {
                        $helper = new TestModel(new Room());
                        $helper->tryPersist();
                        self::fail("Excepted constraint violation exception");
                } catch (\Exception $exception) {
                        // Expected exception
                }

                try {
                        $helper = new TestModel(new Room());
                        $helper->tryPersist($values);
                } catch (\Exception $exception) {
                        self::fail($exception);
                }
        }

        /**
         * @covers OpenExam\Models\Room::getSource
         * @group model
         */
        public function testGetSource()
        {
                $expect = "rooms";
                $actual = $this->object->getSource();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

}
