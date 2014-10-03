<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    TestModelAccess.php
// Created: 2014-09-24 05:52:46
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Tests\Phalcon;

use Exception;
use OpenExam\Library\Security\User;
use OpenExam\Models\Access\AuthorizationInterface;
use OpenExam\Models\Admin;
use OpenExam\Models\Contributor;
use OpenExam\Models\Corrector;
use OpenExam\Models\Decoder;
use OpenExam\Models\Exam;
use OpenExam\Models\Invigilator;
use OpenExam\Models\Student;
use OpenExam\Models\Teacher;

/**
 * Helper class for testing model access.
 * 
 * Essential, this class creates a truth table of role -> resource -> action 
 * and uses it to validate that the outcome holds (permitted actions succeed 
 * and disallowed action fails).
 * 
 * Tests are performed by injecting a user object. The first test check that
 * a unauthenticated user don't have access to any 
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class TestModelAccess extends TestModelBasic
{

        protected $sample;
        protected $values;
        protected $access;
        protected static $role = 'dummy';
        protected static $roles;
        protected static $resources;
        protected static $actions;
        protected $user;

        /**
         * Constructor
         * @param AuthorizationInterface $object An not yet persisted model object.
         * @param User $user Optional user for access test.
         */
        public function __construct($object)
        {
                parent::__construct($object);

                if (!isset(self::$roles)) {
                        self::$roles = array(
                                'admin', 'contributor', 'corrector', 'creator',
                                'decoder', 'invigilator', 'student', 'teacher'
                        );
                }

                if (!isset(self::$resources)) {
                        self::$resources = array(
                                'admin', 'answer', 'computer', 'contributor',
                                'corrector', 'decoder', 'exam', 'file',
                                'invigilator', 'lock', 'question', 'resource',
                                'result', 'room', 'student', 'teacher', 'topic'
                        );
                }

                if (!isset(self::$actions)) {
                        self::$actions = array(
                                'create', 'read', 'update', 'delete'
                        );
                }

                // 
                // Setup access list:
                // 
                $this->access = self::getAccessList(require(CONFIG_DIR . '/access.def'));

                // 
                // Load sample data:
                //                
                $this->sample = sprintf("%s/unittest/sample.dat", $this->config->application->cacheDir);
                if (!file_exists($this->sample)) {
                        self::error("Sample data is missing, please run 'php phalcon-mvc/script/unittest.php --setup'");
                }
                $this->sample = unserialize(file_get_contents($this->sample));
                self::info("sample=%s", print_r($this->sample, true));

                // 
                // Set test values from sample data:
                // 
                $this->values = $this->sample[$object->getName()];
                $this->object->assign($this->values);
        }

        /**
         * Build role -> resource -> actions tree:
         * @param array $system The system configured access list.
         */
        private static function getAccessList($system)
        {
                $access = array();

                // 
                // All actions are disallow by default:
                // 
                foreach (self::$roles as $role) {
                        foreach (self::$resources as $resource) {
                                foreach (self::$actions as $action) {
                                        $access[$role][$resource][$action] = false;
                                }
                        }
                }

                // 
                // Merge in $system:
                // 
                foreach ($system['roles'] as $role => $resources) {
                        if (is_string($resources) && $resources == '*') {
                                foreach (self::$resources as $resource) {
                                        foreach (self::$actions as $action) {
                                                $access[$role][$resource][$action] = true;
                                        }
                                }
                                continue;
                        }
                        foreach ($resources as $resource => $action) {
                                $actions = $system['permissions'][$action];
                                if (is_string($actions) && $actions == '*') {
                                        foreach (self::$actions as $action) {
                                                $access[$role][$resource][$action] = true;
                                        }
                                } elseif (is_array($actions)) {
                                        foreach ($actions as $action) {
                                                $access[$role][$resource][$action] = true;
                                        }
                                } else {
                                        $access[$role][$resource][$actions] = true;
                                }
                        }
                }

                return $access;
        }

        /**
         * Create new model object using supplied values.
         */
        private function createModelObject()
        {
                $class = get_class($this->object);
                $this->object = new $class();
                $this->object->assign($this->values);
        }

        /**
         * Delete model object.
         */
        private function deleteModelObject()
        {
                if ($this->object->id != 0) {
                        $this->object->delete();
                }
        }

        /**
         * Check role access on this model.
         * 
         * Checking that allowed actions succeed is equal important that
         * checking that disallowed action fails.
         * 
         * @group model
         * @group security
         */
        public function testModelAccess()
        {
                self::info("name=%s", $this->object->getName());

                // 
                // Use sample exam:
                // 
                $roles = array();
                $roles['admin'] = Admin::findFirst($this->sample['admin']['id']);
                $roles['teacher'] = Teacher::findFirst($this->sample['teacher']['id']);
                $roles['contributor'] = Contributor::findFirst($this->sample['contributor']['id']);
                $roles['corrector'] = Corrector::findFirst($this->sample['corrector']['id']);
                $roles['decoder'] = Decoder::findFirst($this->sample['decoder']['id']);
                $roles['invigilator'] = Invigilator::findFirst($this->sample['invigilator']['id']);
                $roles['student'] = Student::findFirst($this->sample['student']['id']);
                $roles['creator'] = Exam::findFirst($this->sample['exam']['id']);

                // 
                // Inject unauthenticated user:
                // 
                $this->user = new User();
                $this->getDI()->set('user', $this->user);

                // 
                // Should pass (unchecked access):
                // 
                foreach ($roles as $role => $object) {
                        $this->user->setPrimaryRole(null);

                        if ($role == 'creator') {
                                $object->creator = $this->user->getPrincipalName();
                                $object->update();
                        } else {
                                $object->user = $this->user->getPrincipalName();
                                $object->update();
                        }
                        try {
                                $this->checkModelAccess($role);
                        } catch (\Exception $exception) {
                                self::error($exception, "Unexpected exception (%s)", get_class($object));
                        }
                }

                // 
                // Should fail (unauthenticated user):
                // 
                foreach ($roles as $role => $object) {
                        $this->user->setPrimaryRole($role);

                        if ($role == 'creator') {
                                $object->creator = $this->user->getPrincipalName();
                                $object->update();
                        } else {
                                $object->user = $this->user->getPrincipalName();
                                $object->update();
                        }
                        try {
                                $this->checkModelAccess($role);
                        } catch (\Exception $exception) {
                                self::error($exception, "Unexpected exception (%s)", get_class($object));
                        }
                }

                // 
                // Inject a fake authenticated user:
                // 
                $this->user = new User((new UniqueUser())->user);
                $this->getDI()->set('user', $this->user);

                // 
                // Should succeed:
                // 
                foreach ($roles as $role => $object) {

                        $this->user->setPrimaryRole(null);

                        if ($role == 'creator') {
                                $object->creator = $this->user->getPrincipalName();
                                $object->update();
                        } else {
                                $object->user = $this->user->getPrincipalName();
                                $object->update();
                        }

                        $this->user->setPrimaryRole($role);

                        try {
                                $this->checkModelAccess($role);
                        } catch (\Exception $exception) {
                                self::error($exception, "Unexpected exception (%s)", get_class($object));
                        }
                }
                // 
                // Keep phpunit happy:
                // 
                self::assertTrue(true);
        }

        private function checkModelAccess($role)
        {
                self::info("role=%s, user=%s", $role, $this->user->getPrincipalName());

                foreach ($this->access[$role] as $resource => $actions) {
                        if ($resource == $this->object->getName()) {
                                $this->createModelObject();
                                foreach ($actions as $action => $permit) {
                                        $this->checkModelAction($role, $resource, $action, $permit);
                                }
                                $this->deleteModelObject();
                        }
                }
        }

        /**
         * Check performing action on resource having given role.
         * 
         * @param string $role The role.
         * @param string $resource The resource.
         * @param string $action The action to perform.
         * @param bool $permit Should model action be permitted?
         * @group model
         * @group security
         */
        private function checkModelAction($role, $resource, $action, $permit)
        {
                self::info("role=%s, resource=%s, action=%s, permit=%s", $role, $resource, $action, $permit ? "yes" : "no");

                try {
                        switch ($action) {
                                case 'create':
                                        $this->checkModelCreate($role, $resource, $permit);
                                        break;
                                case 'read':
                                        $this->checkModelRead($role, $resource, $permit);
                                        break;
                                case 'update':
                                        $this->checkModelUpdate($role, $resource, $permit);
                                        break;
                                case 'delete':
                                        $this->checkModelDelete($role, $resource, $permit);
                                        break;
                                default:
                                        self::error("Unknown action $action");
                        }
                } catch (\Exception $exception) {
                        if ($exception->getMessage() == 'auth') {
                                self::success("User not authenticated");
                        } elseif ($exception->getMessage() == 'user' || $exception->getMessage() == 'acl') {
                                self::error($exception, "Service not configured (%s)", $exception->getMessage());
                        } elseif ($permit) {
                                self::error("Permitted action failed: role=%s, resource=%s, action=%s", $role, $resource, $action);
                        } else {
                                self::success("Disallowed action failed: role=%s, resource=%s, action=%s", $role, $resource, $action);
                        }
                }
        }

        /**
         * Test create on resource having given role.
         * 
         * @param string $role The role.
         * @param string $resource The resource.
         * @param bool $permit Should model action be permitted?
         * @group model
         * @group security
         */
        private function checkModelCreate($role, $resource, $permit)
        {
                if ($this->object->id != 0) {
                        $this->object->id = 0;
                }
                if ($this->object->create() == false) {
                        throw new Exception(print_r($this->object->getMessages(), true));
                }
                if (!$permit) {
                        self::error("Expected exception not thrown (not permitted).");
                }
        }

        /**
         * Test read on resource having given role.
         * 
         * @param string $role The role.
         * @param string $resource The resource.
         * @param bool $permit Should model action be permitted?
         * @group model
         * @group security
         */
        private function checkModelRead($role, $resource, $permit)
        {
                $class = get_class($this->object);

                if ($class::findFirst() == false) {
                        throw new Exception(print_r($this->object->getMessages(), true));
                }
                if (!$permit) {
                        self::error("Expected exception not thrown (not permitted).");
                }
        }

        /**
         * Test update on resource having given role.
         * 
         * @param string $role The role.
         * @param string $resource The resource.
         * @param bool $permit Should model action be permitted?
         * @group model
         * @group security
         */
        private function checkModelUpdate($role, $resource, $permit)
        {
                if ($this->object->id == 0) {
                        self::warn("Object id == 0 (skipped)");
                        return;
                }
                if ($this->object->update() == false) {
                        throw new Exception(print_r($this->object->getMessages(), true));
                }
                if (!$permit) {
                        self::error("Expected exception not thrown (not permitted).");
                }
        }

        /**
         * Test delete on resource having given role.
         * 
         * @param string $role The role.
         * @param string $resource The resource.
         * @param bool $expect Expected result.
         * @group model
         * @group security
         */
        private function checkModelDelete($role, $resource, $permit)
        {
                if ($this->object->id == 0) {
                        self::warn("Object id == 0 (skipped)");
                        return;
                }
                if ($this->object->delete() == false) {
                        throw new Exception(print_r($this->object->getMessages(), true));
                }
                if (!$permit) {
                        self::error("Expected exception not thrown (not permitted).");
                }
        }

}
