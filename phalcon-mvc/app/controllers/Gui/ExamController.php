<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ExamController.php
// Created: 2014-09-18 18:11:50
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Controllers\Gui;

use Exception;
use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Core\Exam\Archive;
use OpenExam\Library\Core\Exam\Staff;
use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Gui\Component\DateTime\Range as DateTime;
use OpenExam\Library\Gui\Component\Exam\Check;
use OpenExam\Library\Gui\Component\Exam\Phase;
use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Corrector;
use OpenExam\Models\Exam;
use OpenExam\Models\Lock;
use OpenExam\Models\Question;
use OpenExam\Models\Topic;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use Phalcon\Mvc\Model\Transaction\Failed as TransactionFailed;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
use Phalcon\Mvc\View;
use Phalcon\Paginator\Adapter\NativeArray as PaginatorArray;

/**
 * Controller for loading Exam pages
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class ExamController extends GuiController
{

        /**
         * Exams to list per page on exam/index
         */
        const EXAMS_PER_PAGE = 3;

        /**
         * List all exams sections where caller has role.
         */
        public function indexAction()
        {
                // 
                // Try to acquire all roles:
                // 
                $this->user->acquire(array(
                        Roles::ADMIN,
                        Roles::CONTRIBUTOR,
                        Roles::CORRECTOR,
                        Roles::CREATOR,
                        Roles::DECODER,
                        Roles::INVIGILATOR,
                        Roles::STUDENT,
                        Roles::TEACHER
                ));

                // 
                // Special handling for student pseudo roles that differentiate
                // between having upcoming and alredy finished exams:
                // 
                if ($this->user->roles->acquire(Roles::STUDENT)) {
                        // 
                        // Get all published exams using the student role:
                        // 
                        $this->user->setPrimaryRole(Roles::STUDENT);
                        if (!($exams = Exam::find(array(
                                    'conditions' => "published = 'Y'"
                            )))) {
                                throw new Exception("Failed query student exams");
                        }

                        // 
                        // Filter student exams on upcoming and ongoing:
                        // 
                        $found = $exams->filter(function($exam) {
                                if ($exam->state & State::UPCOMING || $exam->state & State::RUNNING) {
                                        return $exam;
                                }
                        });
                        if (count($found) > 0) {
                                $this->user->roles->addRole('student-upcoming');
                        }

                        // 
                        // Filter student exams on finished:
                        // 
                        $found = $exams->filter(function($exam) {
                                if ($exam->state & State::FINISHED) {
                                        return $exam;
                                }
                        });
                        if (count($found) > 0) {
                                $this->user->roles->addRole('student-finished');
                        }

                        unset($exams);
                        unset($found);
                }

                // 
                // Show creator tab if admin and teacher:
                // 
                if ($this->user->roles->hasRole(Roles::ADMIN) ||
                    $this->user->roles->hasRole(Roles::TEACHER)) {
                        $this->user->roles->addRole(Roles::CREATOR);
                }

                // 
                // Get list of all acquired exams:
                // 
                $roles = $this->user->roles->getRoles();

                // 
                // Set state for sections:
                // 
                $state = array(
                        'student-finished' => State::FINISHED,
                        'student-upcoming' => State::UPCOMING | State::RUNNING
                );

                // 
                // Use these filter options:
                // 
                $filter = array(
                        'sort'   => 'desc',
                        'order'  => 'created',
                        'first'  => 1,
                        'limit'  => 5,
                        'search' => '',
                        'state'  => 0,
                        'match'  => array()
                );

                // 
                // Set data for view:
                // 
                $this->view->setVars(array(
                        'state'  => $state,
                        'roles'  => $roles,
                        'expand' => array(),
                        'filter' => $filter
                ));
        }

        /**
         * Display exam listing.
         * 
         * Call this method to display listing of exams in exam index. The 
         * role correspond to one of the accordion tabs. Accepts these post 
         * parameters: sort, order, first and limit.
         * 
         * The first parameter is the page to show (pagination). The limit
         * should be 5, but can actually be unlimited. The first and limit
         * is passed along to view.
         * 
         * @param string $sect The requesting index section (i.e. creator).
         * @throws Exception
         */
        public function sectionAction($sect)
        {
                // 
                // Explore combined roles (currently only students):
                // 
                if (strstr($sect, '-')) {
                        list($role, $part) = explode('-', $sect);
                } else {
                        list($role, $part) = array($sect, false);
                }

                // 
                // Get request parameters:
                // 
                $order = $this->request->getPost('order', 'string', 'created');
                $sort = $this->request->getPost('sort', 'string', 'desc');
                $first = $this->request->getPost('first', 'int', 1);
                $limit = $this->request->getPost('limit', 'int', 5);
                $search = $this->request->getPost('search', 'string', '');
                $state = $this->request->getPost('state', 'int', 0);
                $match = $this->request->getPost('match', 'string', array());

                // 
                // Convert boolean strings to enum:
                // 
                foreach ($match as $key => $val) {
                        if ($val == 'true') {
                                $match[$key] = 'Y';
                        } elseif ($val == 'false') {
                                $match[$key] = 'N';
                        }
                }

                // 
                // Set primary role for model access:
                // 
                $this->user->setPrimaryRole($role);

                // 
                // Use QueryBuilder to get exams. Don't use model result for pagination 
                // as adviced by phalcon docs.
                // 
                // See: https://olddocs.phalconphp.com/en/3.0.0/reference/pagination.html
                //      http://phalcon.io/cheat-sheet/#section-17
                //      
                $builder = $this->modelsManager->createBuilder()
                    ->from("Exam")
                    ->orderBy("Exam.$order $sort")
                    ->groupBy("Exam.id");

                if (strlen($search)) {
                        $builder->andWhere("Exam.name LIKE '%$search%'");
                }
                foreach ($match as $key => $val) {
                        $builder->andWhere("$key = '$val'");
                }
                
                // 
                // Execute PHQL query statement:
                // 
                $string = Exam::getQuery($builder->getPhql());
                $result = $this->modelsManager->executeQuery($string);

                // 
                // The exam array to paginate:
                // 
                $exams = array();

                if ($state > 0) {
                        // 
                        // Filter on exam state:
                        // 
                        $exams = $result->filter(function($exam) use($state) {
                                if ($exam->state & $state) {
                                        return $exam;
                                }
                        });
                } else {
                        // 
                        // Have to extract each model from result set:
                        // 
                        foreach ($result as $exam) {
                                $exams[] = $exam;
                        }
                }

                // 
                // Create paginator for result set:
                // 
                $paginator = new PaginatorArray(array(
                        "data"  => $exams,
                        "limit" => $limit,
                        "page"  => $first
                ));

                // 
                // Pass data to view:
                // 
                $this->view->setVars(array(
                        'role'   => $role,
                        'sect'   => $sect,
                        'page'   => $paginator->getPaginate(),
                        'sort'   => $sort,
                        'order'  => $order,
                        'first'  => $first,
                        'limit'  => $limit,
                        'search' => $search
                ));
        }

        /**
         * Show create view for exam. 
         * 
         * On exam create request, new records are inserted 
         */
        public function createAction()
        {
                $exam = new Exam();

                if ($exam->save(array(
                            'name'    => ' ',
                            'descr'   => ' ',
                            'creator' => $this->user->getPrincipalName(),
                            'grades'  => 'U:0&#13;&#10;G:50&#13;&#10;VG:75'
                    )) == false) {
                        throw new Exception(
                        sprintf("Failed to initialize exam (%s)", $exam->getMessages()[0])
                        );
                }

                return $this->response->redirect('exam/update/' . $exam->id . '/creator/new-exam');
        }

        /**
         * Update view for exam
         * exam/update/{exam-id}
         * 
         * Allowed for roles: creator, contributor
         * 
         * @param int $eid Exam ID
         * @param string $role The exam role.
         * @param string $mode Optional mode (i.e. new-exam).
         */
        public function updateAction($eid, $role, $mode = null)
        {
                // 
                // Sanitize:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($role = $this->filter->sanitize($role, "string"))) {
                        throw new Exception("Missing required role", Error::PRECONDITION_FAILED);
                }

                // 
                // Check if minimum required access for requested role pass:
                // 
                if ($this->capabilities->hasPermission($role, 'exam', ObjectAccess::READ)) {
                        $this->user->setPrimaryRole($role);
                } else {
                        throw new Exception("Invalid URL.", Error::BAD_REQUEST);
                }

                // 
                // Fetch data:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Exam status check:
                // 
                $check = new Check($exam);
                $staff = new Staff($exam);

                // 
                // Set view data:
                // 
                $this->view->setVars(array(
                        'exam'  => $exam,
                        'mode'  => $mode,
                        'role'  => $role,
                        'check' => $check,
                        'staff' => $staff
                ));
        }

        /**
         * Allows exam creator to replicate his exam.
         * 
         * @param int $eid The exam ID.
         */
        public function replicateAction($eid)
        {
                // 
                // Notice: 
                // All roles have a default behavior. We need to filter out 
                // caller from being assigned twice.
                // 

                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                if (!$this->user->roles->acquire(Roles::CREATOR, $eid) &&
                    !$this->user->roles->acquire(Roles::ADMIN, $eid)) {
                        throw new Exception("Only creator or admins can replicate exams", Error::FORBIDDEN);
                }

                if (!($oldExam = Exam::findFirst($eid))) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Start transaction with rollback on error:
                // 
                $transactionManager = new TransactionManager();
                $transactionManager->setDbService('dbwrite');

                $transaction = $transactionManager->get();

                try {
                        // 
                        // Create new exam with inherited properties:
                        // 
                        $newExam = new Exam();
                        $newExam->setTransaction($transaction);

                        if ($newExam->save(array(
                                    "name"    => $oldExam->name,
                                    "descr"   => $oldExam->descr,
                                    "creator" => $oldExam->creator,
                                    "details" => $oldExam->details,
                                    "orgunit" => $oldExam->orgunit,
                                    "grades"  => $oldExam->grades
                            )) == false) {
                                throw new Exception(
                                sprintf("Failed save new exam (%s)", $newExam->getMessages()[0])
                                );
                        }

                        // 
                        // Replicate other data if options are provided for replication.
                        // 

                        if ($this->request->hasPost('options')) {
                                $replicateOpts = $this->request->getPost('options');

                                // 
                                // Map between old and new topics:
                                // 
                                $topicsMap = array();

                                // 
                                // Keep track of used question and topics names and slots:
                                // 
                                $idmap = array(
                                        'q' => array(
                                                'n' => 0,
                                                's' => 0
                                        ),
                                        't' => array(
                                                'n' => 0,
                                                's' => 0
                                        )
                                );

                                // 
                                // Replicate topics if selected:
                                // 
                                if (in_array('topics', $replicateOpts)) {
                                        // 
                                        // Replicate topics. Keep track on new topics by
                                        // adding them to the topics map.
                                        // 
                                        foreach ($oldExam->getTopics(array('order' => 'slot,name')) as $oldTopic) {

                                                // 
                                                // Remap name and slot order:
                                                // 
                                                if (is_numeric($oldTopic->name)) {
                                                        $oldTopic->name = ++$idmap['t']['n'];
                                                }
                                                if (is_numeric($oldTopic->slot)) {
                                                        $oldTopic->slot = ++$idmap['t']['s'];
                                                }

                                                // 
                                                // Don't insert default topic. It will be added
                                                // by model behavior instead.
                                                // 
                                                if ($oldTopic->name == 'default') {
                                                        $topicsMap[$oldTopic->id] = $newExam->topics[0]->id;
                                                        continue;
                                                }

                                                $newTopic = new Topic();
                                                $newTopic->setTransaction($transaction);

                                                if ($newTopic->save(array(
                                                            "exam_id"   => $newExam->id,
                                                            "name"      => $oldTopic->name,
                                                            "randomize" => $oldTopic->randomize,
                                                            "grades"    => $oldTopic->grades,
                                                            "depend"    => $oldTopic->depend
                                                    )) == false) {
                                                        throw new Exception(
                                                        sprintf("Failed duplicate topic (%s)", $newTopic->getMessages()[0])
                                                        );
                                                }
                                                $topicsMap[$oldTopic->id] = $newTopic->id;

                                                unset($oldTopic);
                                                unset($newTopic);
                                        }
                                } else {
                                        // 
                                        // Map all topics to default topic:
                                        // 
                                        foreach ($oldExam->topics as $oldTopic) {
                                                $topicsMap[$oldTopic->id] = $newExam->topics[0]->id;
                                        }
                                }

                                // 
                                // Replicate questions and correctors if selected:
                                // 
                                if (in_array('questions', $replicateOpts)) {
                                        foreach ($oldExam->getQuestions(array('order' => 'slot,name')) as $oldQuest) {

                                                // 
                                                // Remap name and slot order:
                                                // 
                                                if (is_numeric($oldQuest->name)) {
                                                        $oldQuest->name = ++$idmap['q']['n'];
                                                }
                                                if (is_numeric($oldQuest->slot)) {
                                                        $oldQuest->slot = ++$idmap['q']['s'];
                                                }

                                                // 
                                                // Replicate question:
                                                // 
                                                $newQuest = new Question();
                                                $newQuest->setTransaction($transaction);

                                                if ($newQuest->save(array(
                                                            "exam_id"  => $newExam->id,
                                                            "topic_id" => $topicsMap[$oldQuest->topic_id],
                                                            "score"    => $oldQuest->score,
                                                            "name"     => $oldQuest->name,
                                                            "quest"    => $oldQuest->quest,
                                                            "status"   => $oldQuest->status,
                                                            "comment"  => $oldQuest->comment,
                                                            "grades"   => $oldQuest->grades
                                                    )) == false) {
                                                        throw new Exception(
                                                        sprintf("Failed duplicate question (%s)", $newQuest->getMessages()[0])
                                                        );
                                                }

                                                // 
                                                // Replicate question correctors:
                                                // 
                                                foreach ($oldQuest->correctors as $oldCorrector) {
                                                        if ($oldCorrector->user == $this->user->getPrincipalName()) {
                                                                continue;
                                                        }

                                                        $newCorrector = new Corrector();
                                                        $newCorrector->setTransaction($transaction);

                                                        if ($newCorrector->save(array(
                                                                    "question_id" => $newQuest->id,
                                                                    "user"        => $oldCorrector->user
                                                            )) == false) {
                                                                throw new Exception(
                                                                sprintf("Failed duplicate corrector (%s)", $newCorrector->getMessages()[0])
                                                                );
                                                        }

                                                        unset($newCorrector);
                                                        unset($oldCorrector);
                                                }

                                                unset($newQuest);
                                                unset($oldQuest);
                                        }
                                }

                                // 
                                // Replicate roles if selected.
                                // 
                                if (in_array('roles', $replicateOpts)) {
                                        // 
                                        // The roles to be replicated:
                                        // 
                                        $roles = array(
                                                'contributors' => '\OpenExam\Models\Contributor',
                                                'decoders'     => '\OpenExam\Models\Decoder',
                                                'invigilators' => '\OpenExam\Models\Invigilator',
                                        );

                                        foreach ($roles as $role => $class) {
                                                foreach ($oldExam->$role as $member) {

                                                        // 
                                                        // Skip user added by behavior.
                                                        // 
                                                        if ($member->user == $this->user->getPrincipalName()) {
                                                                continue;
                                                        }

                                                        $newRole = new $class();
                                                        $newRole->setTransaction($transaction);

                                                        if ($newRole->save(array(
                                                                    "exam_id" => $newExam->id,
                                                                    "user"    => $member->user
                                                            )) == false) {
                                                                throw new Exception(
                                                                sprintf("Failed duplicate role (%s)", $newRole->getMessages()[0])
                                                                );
                                                        }

                                                        unset($newRole);
                                                }
                                        }
                                }

                                unset($replicateOpts);
                                unset($topicsMap);
                        }
                } catch (TransactionFailed $exception) {
                        throw $exception;
                } catch (Exception $exception) {
                        $transaction->rollback($exception->getMessage());
                } finally {
                        if ($transactionManager->has()) {
                                $transaction->commit();
                        }
                }

                $this->session->set('draft-exam-id', $newExam->id);

                $this->view->disable();
                $this->response->setJsonContent(array(
                        "status"  => "success",
                        "exam_id" => $newExam->id
                ));
                $this->response->send();
        }

        /**
         * Shows exam instructions for student and for test exam.
         * 
         * @param int $eid The exam ID.
         */
        public function instructionAction($eid)
        {
                // 
                // Sanitize:
                // 
                if (!($eid = $this->dispatcher->getParam("examId", "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Fetch exam data either as creator or student:
                // 
                if ($this->user->roles->acquire(Roles::CREATOR, $eid)) {
                        $this->user->setPrimaryRole(Roles::CREATOR);
                } else {
                        $this->user->setPrimaryRole(Roles::STUDENT);
                }

                // 
                // Redirect to start page if exam is missing:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        return $this->response->redirect($this->config->session->startPage);
                }

                // 
                // Check that exam has not finished:
                // 
                if ($this->user->getPrimaryRole() == Roles::STUDENT) {
                        if ($exam->state & State::FINISHED) {
                                return $this->response->redirect($this->config->session->startPage);
                        }
                }

                // 
                // Set test mode if exam creator:
                // 
                if ($this->user->getPrimaryRole() == Roles::CREATOR) {
                        $this->view->setVar('testMode', true);
                } else {
                        $this->view->setVar('testMode', false);
                }

                // 
                // Set exam and layout for instructions view:
                // 
                $this->view->setVar('exam', $exam);
                $this->view->setLayout('thin-layout');

                // 
                // Check for location aware documentation:
                // 
                if (($location = $this->location->getEntry())) {
                        $this->view->setVar('location', $location);
                }
        }

        /**
         * Load popup for student management under the exam
         * exam/students
         * 
         * Allowed to Roles: invigilator
         */
        public function studentsAction()
        {
                // 
                // Render view as popup page:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Sanitize:
                // 
                if (!($eid = $this->request->get('exam_id', "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (!($exam = Exam::findFirst(array(
                            'conditions' => 'id = :exam:',
                            'bind'       => array(
                                    'exam' => $eid
                            )
                    )))) {
                        throw new Exception("Failed find target exam", Error::PRECONDITION_FAILED);
                }

                // 
                // Set read-only if students can't be added:
                // 
                if (!$exam->getState()->has(State::EXAMINATABLE)) {
                        $this->view->setVar('readonly', true);
                } else {
                        $this->view->setVar('readonly', false);
                }

                // 
                // Set data for view:
                // 
                $this->view->setVars(array(
                        "exam"    => $exam,
                        "domains" => $this->catalog->getDomains()
                ));
        }

        /**
         * Load popup for exam settings
         * exam/settings
         * 
         * Allowed to Roles: creator
         */
        public function settingsAction()
        {
                // 
                // Render view as popup page:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Sanitize:
                // 
                if (!($eid = $this->request->get('exam_id', "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Need department information:
                // 
                $departments = $this->user->departments->getDepartments();

                // 
                // Set data for view:
                // 
                $this->view->setVar("exam", $exam);
                $this->view->setVar("departments", $departments);
        }

        /**
         * Load popup for exam security
         * exam/security
         * 
         * Allowed to Roles: creator
         */
        public function securityAction()
        {
                // 
                // Render view as popup page:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Sanitize:
                // 
                if (($eid = $this->request->getPost("exam_id", "int")) == null) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (($exam = Exam::findFirst($eid)) == false) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Set data for view:
                // 
                $this->view->setVars(array(
                        "active" => $this->location->getActive($eid),
                        "system" => $this->location->getSystem(),
                        "recent" => $this->location->getRecent(),
                        "exam"   => $exam
                ));
        }

        /**
         * Action for pending exam access.
         */
        public function pendingAction()
        {
                // 
                // Get forwarded exam:
                // 
                $exam = $this->dispatcher->getParam('exam');

                // 
                // Set view data:
                // 
                $this->view->setVars(array(
                        'exam'  => $exam,
                        'icon'  => $this->url->get('/img/clock.png'),
                        'retry' => $this->url->get('/exam/' . $exam->id . '/question/1')
                ));
        }

        /**
         * View for displaying exam details (i.e. state).
         * 
         * @param int $eid The exam ID.
         * @throws Exception
         */
        public function detailsAction($eid)
        {
                // 
                // Sanitize:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (($exam = Exam::findFirst($eid)) == false) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Set view data:
                // 
                $this->view->setVars(array(
                        'exam'     => $exam,
                        'phase'    => new Phase($exam->getState()),
                        'datetime' => new DateTime($exam->starttime, $exam->endtime)
                ));
        }

        /**
         * Start taking an exam.
         */
        public function startAction()
        {
                // 
                // Get exams since one week ago:
                // 
                $this->user->setPrimaryRole(Roles::STUDENT);
                if (!($exams = Exam::find(array(
                            'conditions' => "published = 'Y' AND OpenExam\Models\Exam.endtime > :endtime:",
                            'order'      => 'starttime DESC',
                            'bind'       => array(
                                    'endtime' => strftime("%F", time() - 604800)
                            )
                    )))) {
                        throw new Exception("Failed query student exams");
                }

                // 
                // Filter exams on upcoming and ongoing state:
                // 
                $exams = $exams->filter(function($exam) {
                        if ($exam->state & State::UPCOMING || $exam->state & State::RUNNING) {
                                return $exam;
                        }
                });

                // 
                // Bypass exam selection if only one active exam.
                // 
                if (count($exams) == 1) {
                        $this->dispatcher->forward(array(
                                'action' => 'instruction',
                                'params' => array(
                                        'examId' => $exams[0]->id
                                )
                        ));
                        return true;
                }

                // 
                // Let caller select exam. Pick index view and tell it to 
                // expand the upcoming exam tab.
                // 
                $this->view->setVars(array(
                        'roleBasedExamList' => array('student-upcoming' => $exams),
                        'expandExamTabs'    => array('student-upcoming')
                ));
                $this->view->pick(array('exam/index'));
        }

        /**
         * Exam status check.
         * 
         * @param int $eid The exam ID.
         */
        public function checkAction()
        {
                // 
                // Render view as popup page:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Sanitize:
                // 
                if (($eid = $this->request->getPost("exam_id", "int")) == null) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if ($this->request->hasPost('readonly')) {
                        if (($readonly = $this->request->getPost("readonly", "int")) == null) {
                                throw new Exception("Missing readonly parameter", Error::PRECONDITION_FAILED);
                        }
                }

                // 
                // Dialog is readonly:
                // 
                if (isset($readonly)) {
                        $this->view->setVar('readonly', $readonly);
                } else {
                        $this->view->setVar('readonly', false);
                }

                // 
                // Set dailog role:
                // 
                if (($roles = $this->user->acquire(array(Roles::CREATOR, Roles::INVIGILATOR), $eid))) {
                        $this->view->setVar('role', $roles[0]);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (($exam = Exam::findFirst($eid)) == false) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                $this->view->setVar('check', new Check($exam));
                $this->view->setVar('exam_id', $eid);
        }

        /**
         * Show exam lock status/manager panel.
         */
        public function lockAction()
        {
                // 
                // Render view as popup page:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Load exam lock (if any) from request parameters:
                // 
                if (($lid = $this->request->getPost("id", "int"))) {
                        $lock = Lock::findFirst("id = $lid");
                }
                if (($sid = $this->request->getPost("student_id", "int"))) {
                        $lock = Lock::findFirst("student_id = $sid");
                }

                // 
                // Set variables for view:
                // 
                $this->view->setVars(array(
                        'lock' => $lock,
                        'stud' => $sid
                ));
        }

        /**
         * Create PDF for archive or paper exam.
         * 
         * @param int $eid The exam ID.
         * @param bool $download Should archive be downloaded?
         */
        public function archiveAction($eid, $download = false)
        {
                $data = array();

                // 
                // Sanitize request parameters:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check required parameters:
                // 
                if (empty($eid)) {
                        throw new Exception("The exam ID is missing", Error::PRECONDITION_FAILED);
                }

                // 
                // Get exam data:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new ModelException("Failed find exam", Error::PRECONDITION_FAILED);
                }

                // 
                // Check access.
                // 
                $handler = new Archive($exam);
                if (!$handler->accessable()) {
                        throw new ModelException("You are not allowed to access exam archive.", Error::FORBIDDEN);
                }

                // 
                // Send archive if requested.
                // 
                if ($download) {
                        if (!$handler->exists()) {
                                $handler->create();
                        }
                        if (!$handler->verify()) {
                                $handler->create();
                        }
                        if (!$handler->verify()) {
                                throw new Exception("Failed generate/verify PDF archive");
                        } else {
                                $handler->send();
                                exit(0);
                        }
                }

                // 
                // Fetch question data:
                // 
                $data['examScore'] = 0;
                $questions = $exam->getQuestions(array("order" => "slot", 'conditions' => "status = 'active'"));

                foreach ($questions as $question) {

                        $qScore = 0;
                        $qParts = json_decode($question->quest, true);
                        foreach ($qParts as $qPart) {
                                $qScore += $qPart['q_points'];
                        }
                        $data['questions'][] = $question;

                        // 
                        // Question parts data to be passed to view:
                        // 
                        $data['qData'][$question->id]['qParts'] = $qParts;

                        // 
                        // Question correctors data:
                        // 
                        foreach ($question->getCorrectors() as $corrector) {
                                $data['qCorrectors'][$corrector->user][] = $question->name;
                        }

                        // 
                        // Exam score:
                        // 
                        $data['examScore'] += $qScore;

                        unset($question);
                        unset($qParts);
                }

                // 
                // Calculate exam grades:
                // 
                $grades = preg_split('/[\r\n]+/', $exam->grades);
                foreach ($grades as $grade) {
                        $t = explode(":", $grade);
                        $data['examGrades'][$t[0]] = $t[1];
                }
                arsort($data['examGrades']);

                // 
                // Configure URL service to return full domain if called back from a render task.
                // 
                if ($this->request->has('token') &&
                    $this->request->has('render') &&
                    $this->request->get('render') == 'archive') {
                        $this->url->setBaseUri(
                            sprintf("%s://%s/%s/", $this->request->getScheme(), $this->request->getServerName(), trim($this->config->application->baseUri, '/'))
                        );
                }

                $this->view->setVars(array(
                        'exam' => $exam,
                        'data' => $data
                    )
                );
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        }

}
