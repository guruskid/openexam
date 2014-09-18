<?php

namespace OpenExam\Models;

use OpenExam\Tests\Phalcon\TestModel;

/**
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class InvigilatorModel extends Invigilator
{

        public function initialize()
        {
                parent::initialize();
        }

}

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-15 at 18:28:47.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class InvigilatorTest extends TestModel
{

        public function __construct()
        {
                parent::__construct(new InvigilatorModel());
        }

        /**
         * @group model
         */
        public function testRelations()
        {
                $invigilator = Invigilator::findFirst();
                self::assertNotNull($invigilator);

                self::assertNotEquals($invigilator->exam->count(), 0);
                self::assertTrue(count($invigilator->exam) == 1);
        }

        /**
         * @group model
         */
        public function testProperties()
        {
                $values = array(
                        'exam_id' => Exam::findFirst()->id,
                        'user'    => 'user1'
                );

                try {
                        $helper = new TestModel(new Invigilator());
                        $helper->tryPersist();
                        self::fail("Excepted constraint violation exception");
                } catch (\Exception $exception) {
                        // Expected exception
                }

                try {
                        $helper = new TestModel(new Invigilator());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail($exception);
                }

                $values = array(
                        'exam_id'      => Exam::findFirst()->id,
                        'user'         => 'user1',
                        'non_existing' => 666   // ignored wihout error
                );
                try {
                        $helper = new TestModel(new Invigilator());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail("Unexcepted constraint violation exception");
                }
        }

        /**
         * @covers OpenExam\Models\Invigilator::getSource
         * @group model
         */
        public function testGetSource()
        {
                $expect = "invigilators";
                $actual = $this->object->getSource();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

}
