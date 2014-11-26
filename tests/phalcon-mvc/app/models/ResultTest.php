<?php

namespace OpenExam\Models;

use OpenExam\Library\Security\User;
use OpenExam\Tests\Phalcon\TestModel;
use OpenExam\Tests\Phalcon\UniqueUser;

/**
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ResultModel extends Result
{

        public function initialize()
        {
                parent::initialize();
        }

}

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-15 at 20:20:03.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ResultTest extends TestModel
{

        /**
         * The model resource name.
         */
        const MODEL = 'result';

        /**
         * @group model
         */
        public function testRelations()
        {
                $result = Result::findFirst();
                self::assertNotNull($result);

                self::assertNotEquals(0, $result->answer->count());
                self::assertTrue(count($result->answer) == 1);
        }

        /**
         * @covers OpenExam\Models\Result::getSource
         * @group model
         */
        public function testGetSource()
        {
                $object = new ResultModel();
                $expect = "results";
                $actual = $object->getSource();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Models\Result::create
         * @group model
         */
        public function testCreate()
        {
                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        self::assertTrue($this->create($model, $user, true));
                        $this->cleanup($model);
                }

                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        self::assertTrue($this->create($model, $user, false));
                        $this->cleanup($model);
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        self::assertTrue($this->create($model, $user, false));
                        $this->cleanup($model);
                }

                $user = $this->getDI()->get('user');
                $roles = $this->capabilities->getRoles(self::MODEL);

                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        if (in_array('create', $actions)) {
                                self::assertTrue($this->create($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->create($model, $user, false));  // action denied
                        }
                        $this->cleanup($model);
                }
        }

        /**
         * @covers OpenExam\Models\Result::update
         * @group model
         */
        public function testUpdate()
        {
                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->update($model, $user, true));
                }

                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->update($model, $user, false));
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->update($model, $user, false));
                }

                $user = $this->getDI()->get('user');
                $roles = $this->capabilities->getRoles(self::MODEL);

                self::info("sample=%s", print_r($this->sample->getSample(self::MODEL), true));
                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL));
                        if (in_array('update', $actions)) {
                                self::assertTrue($this->update($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->update($model, $user, false));  // action denied
                        }
                }
        }

        /**
         * @covers OpenExam\Models\Result::delete
         * @group model
         */
        public function testDelete()
        {
                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        self::assertTrue($this->delete($model, $user, true));
                        $this->cleanup($model);
                }

                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        self::assertTrue($this->delete($model, $user, false));
                        $this->cleanup($model);
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        self::assertTrue($this->delete($model, $user, false));
                        $this->cleanup($model);
                }

                $user = $this->getDI()->get('user');
                $roles = $this->capabilities->getRoles(self::MODEL);

                self::info("sample=%s", print_r($this->sample->getSample(self::MODEL), true));
                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        if (in_array('delete', $actions)) {
                                self::assertTrue($this->delete($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->delete($model, $user, false));  // action denied
                        }
                        $this->cleanup($model);
                }
        }

        /**
         * @covers OpenExam\Models\Result::find
         * @group model
         */
        public function testRead()
        {
                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->read($model, $user, true));
                }

                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->read($model, $user, false));
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->read($model, $user, false));
                }

                $user = $this->getDI()->get('user');
                $roles = $this->capabilities->getRoles(self::MODEL);

                self::info("sample=%s", print_r($this->sample->getSample(self::MODEL), true));
                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Result();
                        $model->assign($this->sample->getSample(self::MODEL));
                        if (in_array('read', $actions)) {
                                self::assertTrue($this->read($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->read($model, $user, false));  // action denied
                        }
                }
        }

}
