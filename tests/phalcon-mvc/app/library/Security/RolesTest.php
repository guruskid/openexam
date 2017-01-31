<?php

namespace OpenExam\Library\Security;

use OpenExam\Models\Corrector;
use OpenExam\Models\Exam;
use OpenExam\Models\Question;
use OpenExam\Models\Topic;
use OpenExam\Tests\Phalcon\TestCase;
use OpenExam\Tests\Phalcon\UniqueUser;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-08-31 at 16:13:35.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class RolesTest extends TestCase
{

        /**
         * @var Roles
         */
        private $_object;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                parent::setUp();
                $this->_object = new Roles();
                $this->_object->clear();         // No cached roles
                print_r($this->_object->user, true);
        }

        /**
         * @covers OpenExam\Library\Security\Roles::__construct
         * @group security
         */
        public function testConstruct()
        {
                $this->_object = new Roles();
                self::assertTrue(count($this->_object->getRoles(0)) == 0);
                self::assertTrue(count($this->_object->getAllRoles()) == 0);

                // Test alternative roles array format:
                $roles = array(
                        0 => array(Roles::ADMIN, Roles::TEACHER),
                        1 => array(Roles::CONTRIBUTOR)
                );
                $this->_object = new Roles($roles);
                self::assertTrue(count($this->_object->getRoles(0)) == 3);
                self::assertTrue(count($this->_object->getRoles(1)) == 1);
                self::assertTrue(count($this->_object->getAllRoles()) == 2);

                // Test native roles array format:
                $roles = array(
                        0 => array(Roles::ADMIN => true, Roles::TEACHER => true),
                        1 => array(Roles::CONTRIBUTOR => true)
                );
                $this->_object = new Roles($roles);
                self::assertTrue(count($this->_object->getRoles(0)) == 3);
                self::assertTrue(count($this->_object->getRoles(1)) == 1);
                self::assertTrue(count($this->_object->getAllRoles()) == 2);

                // Test idempotence:
                $roles = array(
                        0 => array(Roles::ADMIN => true, Roles::TEACHER => true),
                        1 => array(Roles::CONTRIBUTOR => true),
                        1 => array(Roles::CONTRIBUTOR => true),
                        2 => array(Roles::CONTRIBUTOR => true)
                );
                $this->_object = new Roles($roles);
                self::assertTrue(count($this->_object->getRoles(0)) == 3);
                self::assertTrue(count($this->_object->getRoles(1)) == 1);
                self::assertTrue(count($this->_object->getAllRoles()) == 3);

                // An more complex test:
                $roles = array(
                        0 => array(Roles::ADMIN, Roles::TEACHER),
                        1 => array(Roles::CONTRIBUTOR),
                        2 => array(Roles::DECODER, Roles::INVIGILATOR),
                        3 => array(Roles::CONTRIBUTOR)
                );
                $this->_object = new Roles($roles);
                self::assertTrue(count($this->_object->getRoles(0)) == 5);
                self::assertTrue(count($this->_object->getRoles(1)) == 1);
                self::assertTrue(count($this->_object->getRoles(2)) == 2);
                self::assertTrue(count($this->_object->getRoles(3)) == 1);
                self::assertTrue(count($this->_object->getRoles(4)) == 0);
                self::assertTrue(count($this->_object->getAllRoles()) == 4);
                self::assertTrue(count($this->_object->getAllRoles()[1]) == 1);
        }

        /**
         * @covers OpenExam\Library\Security\Roles::addRole
         * @group security
         */
        public function testAddRole()
        {
                $role = 'role';

                self::assertFalse($this->_object->hasRole($role));
                self::assertFalse($this->_object->hasRole($role, 0));
                self::assertFalse($this->_object->hasRole($role, 1));

                $this->_object->addRole($role);
                self::assertTrue($this->_object->hasRole($role));
                self::assertTrue($this->_object->hasRole($role, 0));
                self::assertFalse($this->_object->hasRole($role, 1));

                $this->_object = new Roles;
                self::assertFalse($this->_object->hasRole($role));
                $this->_object->addRole($role, 1);
                self::assertTrue($this->_object->hasRole($role));
                self::assertTrue($this->_object->hasRole($role, 0));
                self::assertTrue($this->_object->hasRole($role, 1));
                self::assertFalse($this->_object->hasRole($role, 2));
        }

        /**
         * @covers OpenExam\Library\Security\Roles::removeRole
         * @group security
         */
        public function testRemoveRole()
        {
                $role = 'role';

                $this->_object->removeRole($role);
                self::assertFalse($this->_object->hasRole($role));

                $this->_object->addRole($role);
                self::assertTrue($this->_object->hasRole($role));
                $this->_object->removeRole($role);
                self::assertFalse($this->_object->hasRole($role));

                $this->_object = new Roles;
                self::assertFalse($this->_object->hasRole($role, 1));
                $this->_object->addRole($role, 1);
                self::assertTrue($this->_object->hasRole($role, 1));
                $this->_object->removeRole($role, 1);
                self::assertFalse($this->_object->hasRole($role, 1));
                self::assertTrue($this->_object->hasRole($role));
        }

        /**
         * @covers OpenExam\Library\Security\Roles::hasRole
         * @group security
         */
        public function testHasRole()
        {
                $role1 = 'role1';
                $role2 = 'role2';

                self::assertFalse($this->_object->hasRole($role1));
                $this->_object->addRole($role1);
                self::assertTrue($this->_object->hasRole($role1));
                self::assertFalse($this->_object->hasRole($role2));

                $this->_object->addRole($role2);
                self::assertTrue($this->_object->hasRole($role2));

                self::assertFalse($this->_object->hasRole($role1, 1));
                $this->_object->addRole($role1, 1);
                self::assertTrue($this->_object->hasRole($role1, 1));
                self::assertFalse($this->_object->hasRole($role2, 1));

                $this->_object->addRole($role2, 2);
                self::assertTrue($this->_object->hasRole($role2, 2));

                self::assertFalse($this->_object->hasRole($role1, 2));
                self::assertFalse($this->_object->hasRole($role2, 1));
        }

        /**
         * @covers OpenExam\Library\Security\Roles::getRoles
         * @group security
         */
        public function testGetRoles()
        {
                $role = 'role';

                self::assertTrue(is_array($this->_object->getRoles()));
                self::assertTrue(is_array($this->_object->getRoles(1)));

                self::assertTrue(count($this->_object->getRoles()) == 0);
                self::assertTrue(count($this->_object->getRoles(1)) == 0);

                $this->_object->addRole($role);

                self::assertTrue(is_array($this->_object->getRoles()));
                self::assertTrue(is_array($this->_object->getRoles(1)));

                self::assertTrue(count($this->_object->getRoles()) == 1);
                self::assertTrue(count($this->_object->getRoles(1)) == 0);

                $this->_object->addRole($role, 1);

                self::assertTrue(is_array($this->_object->getRoles()));
                self::assertTrue(is_array($this->_object->getRoles(1)));

                self::assertTrue(count($this->_object->getRoles()) == 1);
                self::assertTrue(count($this->_object->getRoles(1)) == 1);
        }

        /**
         * @covers OpenExam\Library\Security\Roles::getAllRoles
         * @group security
         */
        public function testGetAllRoles()
        {
                self::assertTrue(is_array($this->_object->getAllRoles()));
                self::assertTrue(count($this->_object->getAllRoles()) == 0);

                $this->_object->addRole(Roles::ADMIN);
                $this->_object->addRole(Roles::TEACHER);

                self::assertTrue(is_array($this->_object->getAllRoles()));
                self::assertTrue(count($this->_object->getAllRoles()) == 1);
                self::assertTrue(count($this->_object->getAllRoles()[0]) == 2);

                $this->_object = new Roles;

                $this->_object->addRole(Roles::CONTRIBUTOR, 1);
                $this->_object->addRole(Roles::DECODER, 1);
                $this->_object->addRole(Roles::INVIGILATOR, 2);

                self::assertTrue(is_array($this->_object->getAllRoles()));
                self::assertTrue(count($this->_object->getAllRoles()) == 3);
                self::assertTrue(count($this->_object->getAllRoles()[0]) == 3);
                self::assertTrue(count($this->_object->getAllRoles()[1]) == 2);
                self::assertTrue(count($this->_object->getAllRoles()[2]) == 1);

                // Test idempotence:
                $this->_object->addRole(Roles::CONTRIBUTOR, 1);
                self::assertTrue(count($this->_object->getAllRoles()[0]) == 3);
                self::assertTrue(count($this->_object->getAllRoles()[1]) == 2);

                self::info("[roles: '%s']\n", print_r($this->_object->getAllRoles(), true));
        }

        /**
         * @covers OpenExam\Library\Security\Roles::clear
         * @group security
         */
        public function testClear()
        {
                self::assertTrue(count($this->_object->getAllRoles()) == 0);
                $this->_object->addRole('test');
                self::assertTrue(count($this->_object->getAllRoles()) != 0);
                $this->_object->clear();
                self::assertTrue(count($this->_object->getAllRoles()) == 0);
        }

        /**
         * @covers OpenExam\Library\Security\Roles::acquire
         * @group security
         */
        public function testAcquire()
        {
                // 
                // Domain for unique users:
                // 
                if (isset($this->config['user']['domain'])) {
                        $domain = $this->config['user']['domain'];
                } else {
                        $domain = 'test.com';
                }

                // 
                // Fake current unauthenticated user:
                // 
                $user = new User();
                $this->_di->set('user', $user);

                // 
                // Test corner case:
                // 
                self::assertNull($this->user->getPrincipalName());
                foreach (array(
                    Roles::CONTRIBUTOR,
                    Roles::CORRECTOR,
                    Roles::CREATOR,
                    Roles::DECODER,
                    Roles::INVIGILATOR,
                    Roles::TEACHER,
                    Roles::ADMIN,
                    Roles::STUDENT
                ) as $role) {
                        self::assertFalse($this->_object->acquire($role), "role: $role");
                        self::assertFalse($this->_object->acquire($role, 1), "role: $role");
                }
                $this->_object->clear();

                // 
                // User principal guarantied to not exists in any model.
                // 
                $principal = (new UniqueUser($domain))->user;

                // 
                // Fake current authenticated user:
                // 
                $user = new User($principal);
                $this->_di->set('user', $user);

                self::info("[principal name: '%s'] (local)\n", $user);
                self::info("[principal name: '%s'] (magic)\n", $this->user);
                self::assertNotNull($this->user);
                self::assertNotNull($this->_di->get('user'));
                self::assertSame($user, $this->user);
                self::assertSame($user, $this->_di->get('user'));
                self::assertSame($user, $this->_object->getDI()->get('user'));
                self::assertEquals($user, $principal);
                self::assertEquals($user, $this->user);
                self::assertEquals($user, $this->_di->get('user'));

                // 
                // Test acquire system wide roles:
                // 
                $this->checkAcquireSystemRole($principal, Roles::ADMIN, '\OpenExam\Models\Admin');
                $this->checkAcquireSystemRole($principal, Roles::TEACHER, '\OpenExam\Models\Teacher');

                // 
                // Test acquire object specific roles:
                // 
                $exam = new Exam();
                $exam->name = "Name";
                $exam->orgunit = "Orgunit";
                $exam->creator = $principal;
                $exam->grades = json_encode(array('data' => array('U' => 0, 'G' => 20, 'VG' => 30)));

                if ($exam->create() === false) {
                        self::error(print_r($exam->getMessages(), true));
                }

                if ($principal != $exam->creator) {
                        $principal = $exam->creator;
                        $this->_di->set('user', new User($principal));
                }

                self::assertEquals($principal, $exam->creator);

                $this->checkAcquireExamRole($principal, $exam, Roles::CREATOR, null);
                $this->checkAcquireExamRole($principal, $exam, Roles::CONTRIBUTOR, '\OpenExam\Models\Contributor');
                $this->checkAcquireExamRole($principal, $exam, Roles::DECODER, '\OpenExam\Models\Decoder');
                $this->checkAcquireExamRole($principal, $exam, Roles::INVIGILATOR, '\OpenExam\Models\Invigilator');
                $this->checkAcquireExamRole($principal, $exam, Roles::STUDENT, '\OpenExam\Models\Student');

                $this->checkAcquireCorrectorRoles($principal, $exam);

                $exam->delete();
        }

        /**
         * Test acquire system role.
         * 
         * System roles are global and not connected with an object, like 
         * an exam (e.g. decoder) or question (corrector).
         * 
         * @param string $user The user principal name.
         * @param string $role The wanted role.
         * @param string $class The class name related to wanted role.
         */
        private function checkAcquireSystemRole($user, $role, $class)
        {
                self::info("[class: '%s', role: '%s']", $class, $role);
                self::info("[roles: '%s']", $this->_object);

                self::assertFalse($this->_object->acquire($role));
                self::assertFalse($this->_object->acquire($role, 0));
                self::assertFalse($this->_object->acquire($role, 1));
                self::info("[roles: '%s']", $this->_object);

                $model = new $class();
                $model->user = $user;
                $model->create();
                self::dump($model);

                self::assertTrue($this->_object->acquire($role));
                self::assertTrue($this->_object->acquire($role, 0));
                self::assertTrue($this->_object->acquire($role, 1));
                self::info("[roles: '%s']", $this->_object);
                $model->delete();       // cleanup                
        }

        /**
         * Test acquire exam role.
         * 
         * Try to acquire the requested role on the exam. The role can for
         * example be decoder of an exam.
         * 
         * @param string $user The user principal name.
         * @param Exam $exam The exam object.
         * @param string $role The wanted role.
         * @param string $class The class name related to wanted role.
         */
        private function checkAcquireExamRole($user, $exam, $role, $class)
        {
                self::info("[class: '%s', role: '%s']\n", $class, $role);
                self::info("[roles: '%s']\n", $this->_object);

                // 
                // The contributor, invigilator and decoder role are automatic 
                // granted creator by behavior. The creator role is implicit 
                // conneted with exam.
                // 
                if ($role != Roles::CREATOR &&
                    $role != Roles::CONTRIBUTOR &&
                    $role != Roles::INVIGILATOR &&
                    $role != Roles::DECODER) {
                        self::assertFalse($this->_object->acquire($role));
                        self::assertFalse($this->_object->acquire($role, 0));
                        self::assertFalse($this->_object->acquire($role, $exam->id));
                        self::info("[roles: '%s']\n", $this->_object);

                        $model = new $class();
                        $model->exam_id = $exam->id;
                        $model->user = $user;
                        if ($role == Roles::STUDENT) {
                                $model->code = '1234ABCD';
                        }
                        if ($model->create() == false) {
                                self::error(implode("\n", $model->getMessages()));
                        }
                        self::dump($model);
                }

                self::assertTrue($this->_object->acquire($role));
                self::assertTrue($this->_object->acquire($role, 0));
                self::assertTrue($this->_object->acquire($role, $exam->id));
                self::assertFalse($this->_object->acquire($role, $exam->id + 1));
                self::info("[roles: '%s']\n", $this->_object);

                if (isset($model)) {
                        $model->delete();       // cleanup                
                }
        }

        /**
         * Test acquire corrector role.
         * 
         * The corrector role is connected with an question in contrary to
         * most other roles that are connected with an exam. This makes the
         * preparation a bit more complex.
         * 
         * @param string $user The user principal name.
         * @param Exam $exam The exam object.
         */
        private function checkAcquireCorrectorRoles($user, $exam)
        {
                $role = Roles::CORRECTOR;

                self::info("[exam: '%s', role: '%s']\n", $exam->id, $role);
                self::info("[roles: '%s']\n", $this->_object);

                self::assertFalse($this->_object->acquire($role));
                self::assertFalse($this->_object->acquire($role, 0));
                self::info("[roles: '%s']\n", $this->_object);

                $tmodel = new Topic();
                $tmodel->exam_id = $exam->id;
                $tmodel->name = "Topic 1";
                if ($tmodel->create() == false) {
                        self::error(implode("\n", $tmodel->getMessages()));
                }
                self::dump($tmodel);

                $qmodel = new Question();
                $qmodel->exam_id = $exam->id;
                $qmodel->topic_id = $tmodel->id;
                $qmodel->score = 0.0;
                $qmodel->user = $user;
                $qmodel->name = "Question 1";
                $qmodel->quest = "Body";
                if ($qmodel->create() == false) {
                        self::error(implode("\n", $qmodel->getMessages()));
                }
                self::dump($qmodel);

                $cmodel = new Corrector();
                $cmodel->question_id = $qmodel->id;
                $cmodel->user = $user;
                if ($cmodel->create() == false) {
                        self::error(implode("\n", $cmodel->getMessages()));
                }
                self::dump($cmodel);

                self::assertEquals($user, $qmodel->user);
                self::assertTrue($this->_object->acquire($role));
                self::assertTrue($this->_object->acquire($role, 0));
                self::assertTrue($this->_object->acquire($role, $qmodel->id));
                self::assertTrue($this->_object->acquire($role, $exam->id));
                self::assertFalse($this->_object->acquire($role, $qmodel->id + 1));
                self::assertFalse($this->_object->acquire($role, $exam->id + 1));
                self::info("[roles: '%s']\n", $this->_object);

                $qmodel->delete();
                $tmodel->delete();
                $cmodel->delete();
        }

        /**
         * @covers OpenExam\Library\Security\Roles::isAdmin
         * @group security
         */
        public function testIsAdmin()
        {
                self::assertFalse($this->_object->isAdmin());
                $this->_object->addRole(Roles::ADMIN);
                self::assertTrue($this->_object->isAdmin());
                $this->_object->clear();
                $this->_object->addRole('custom');
                self::assertFalse($this->_object->isAdmin());
        }

        /**
         * @covers OpenExam\Library\Security\Roles::isStudent
         * @group security
         */
        public function testIsStudent()
        {
                // 
                // Test both globally (0) and exam specific (1):
                // 
                for ($id = 0; $id < 2; $id++) {
                        self::assertFalse($this->_object->isStudent($id));
                        $this->_object->addRole(Roles::STUDENT, $id);
                        self::assertTrue($this->_object->isStudent($id));
                        $this->_object->clear();
                        $this->_object->addRole('custom', $id);
                        self::assertFalse($this->_object->isStudent($id));
                }
        }

        /**
         * @covers OpenExam\Library\Security\Roles::isStaff
         * @group security
         */
        public function testIsStaff()
        {
                // 
                // Should succeed:
                // 
                foreach (array(
                    Roles::CONTRIBUTOR,
                    Roles::CORRECTOR,
                    Roles::CREATOR,
                    Roles::DECODER,
                    Roles::INVIGILATOR,
                    Roles::TEACHER
                ) as $role) {
                        for ($id = 0; $id < 2; $id++) {
                                $this->_object->clear();
                                self::assertFalse($this->_object->isStaff($id));
                                $this->_object->addRole($role, $id);
                                self::assertTrue($this->_object->isStaff($id));
                        }
                }

                // 
                // Should fail:
                // 
                foreach (array(
                    Roles::ADMIN,
                    Roles::STUDENT,
                    'service'
                ) as $role) {
                        for ($id = 0; $id < 2; $id++) {
                                $this->_object->clear();
                                self::assertFalse($this->_object->isStaff($id));
                                $this->_object->addRole($role, $id);
                                self::assertFalse($this->_object->isStaff($id));
                        }
                }
        }

        /**
         * @covers OpenExam\Library\Security\Roles::isCustom
         * @group security
         */
        public function testIsCustom()
        {
                self::assertTrue(Roles::isCustom('service'));
                self::assertFalse(Roles::isCustom(Roles::ADMIN));
        }

        /**
         * @covers OpenExam\Library\Security\Roles::isGlobal($role)
         * @group security
         */
        public function testIsGlobal()
        {
                self::assertTrue(Roles::isGlobal('service'));
                self::assertTrue(Roles::isGlobal(Roles::ADMIN));
                self::assertTrue(Roles::isGlobal(Roles::TEACHER));
                self::assertFalse(Roles::isCustom(Roles::CONTRIBUTOR));
        }

        private static function dump($model)
        {
                self::info("%s: ", get_class($model));
                self::info(print_r($model->toArray(), true));
        }

}
