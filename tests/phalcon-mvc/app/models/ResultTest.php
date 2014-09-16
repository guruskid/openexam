<?php

namespace OpenExam\Models;

use Exception;
use OpenExam\Tests\Phalcon\TestModel;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-15 at 20:20:03.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ResultTest extends TestModel
{

        public function __construct()
        {
                parent::__construct(new Result());
        }

        /**
         * @group model
         */
        public function testRelations()
        {
                $result = Result::findFirst();

                self::assertNotEquals($result->answer->count(), 0);
                self::assertTrue(count($result->answer) == 1);
        }

        /**
         * @covers OpenExam\Models\Result::properties
         * @group model
         */
        public function testProperties()
        {
                $values = array(
                        'answer_id' => Answer::findFirst()->id,
                        'score'     => 3.5
                );

                try {
                        $helper = new TestModel(new Result());
                        $helper->tryPersist();
                        self::fail("Excepted constraint violation exception");
                } catch (Exception $exception) {
                        // Expected exception
                }

                try {
                        $helper = new TestModel(new Result());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail($exception);
                }

                $values = array(
                        'answer_id' => Answer::findFirst()->id,
                        'score'     => 3.5,
                        'comment'   => 'Comment1'
                );
                try {
                        $helper = new TestModel(new Result());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail("Unexcepted constraint violation exception");
                }

                $values = array(
                        'answer_id'    => Answer::findFirst()->id,
                        'score'        => 3.5,
                        'comment'      => 'Comment1',
                        'non_existing' => 666
                );
                try {
                        $helper = new TestModel(new Result());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail("Unexcepted constraint violation exception");
                }
        }

        /**
         * @covers OpenExam\Models\Result::getSource
         * @group model
         */
        public function testGetSource()
        {
                $expect = "results";
                $actual = $this->object->getSource();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

}
