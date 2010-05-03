<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/teacher/contribute.php
// Author: Anders L�vgren
// Date:   2010-04-29
// 
// This page is used by teacher for contributing questions for an exam.
// 

// 
// Force logon for unauthenticated users:
// 
$GLOBALS['logon'] = true;

// 
// System check:
// 
if(!file_exists("../../conf/database.conf")) {
    header("location: setup.php?reason=database");
}
if(!file_exists("../../conf/config.inc")) {
    header("location: setup.php?reason=config");
}

// 
// Include external libraries:
// 
include "MDB2.php";
include "CAS.php";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include configuration:
// 
include "conf/config.inc";
include "conf/database.conf";

// 
// Include logon and user interface support:
// 
include "include/cas.inc";
include "include/ui.inc";
include "include/error.inc";

// 
// Include database support:
// 
include "include/database.inc";

// 
// Business logic:
// 
include "include/teacher.inc";
include "include/exam.inc";
include "include/teacher/manager.inc";
include "include/teacher/contribute.inc";

// 
// The contribute page:
// 
class ContributePage extends TeacherPage
{
    private $params = array( "exam"     => "/^\d+$/",
			     "action"   => "/^(add|edit|delete)$/",
			     "question" => "/^\d+$/",
			     "mode"     => "/^(save)$/",
			     "score"    => "/^\d(\.\d)*$/",
			     "type"     => "/^(freetext|single|multiple)$/" );
    
    public function __construct()
    {
	parent::__construct(_("Contribute Page"), $this->params);
    }

    // 
    // The main entry point. This is where all processing begins.
    // 
    public function printBody()
    {
        //
	// Authorization first:
	//
	if(isset($_REQUEST['exam'])) {
	    self::checkAccess($_REQUEST['exam']);
	}
	
	//
	// Bussiness logic:
	//
	if(isset($_REQUEST['exam'])) {
	    if(isset($_REQUEST['action'])) {
		if($_REQUEST['action'] == "add") {
		    if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
			self::assert(array('score', 'name', 'quest', 'type'));
			self::saveAddQuestion($_REQUEST['exam'], $_REQUEST['score'], 
					      $_REQUEST['name'], $_REQUEST['quest'],
					      $_REQUEST['type']);
		    } else {
			self::formAddQuestion($_REQUEST['exam']);
		    }
		} elseif($_REQUEST['action'] == "edit") {
		    if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
			self::assert(array('score', 'name', 'quest', 'type', 'question', 'user'));
			self::saveEditQuestion($_REQUEST['exam'], $_REQUEST['score'], 
					       $_REQUEST['name'], $_REQUEST['quest'],
					       $_REQUEST['type'], $_REQUEST['question'],
					       $_REQUEST['user']);
		    } else {
			self::assert('question');
			self::formEditQuestion($_REQUEST['exam'], $_REQUEST['question']);
		    }
		} elseif($_REQUEST['action'] == "delete") {
		    self::assert('question');
		    self::saveDeleteQuestion($_REQUEST['exam'], $_REQUEST['question']);
		}
	    } else {
		self::showQuestions($_REQUEST['exam']);
	    }
	} else {
	    self::showAvailableExams();
	}
    }

    // 
    // Verify that the caller has been granted the required role on this exam.
    // 
    private function checkAccess($exam)
    {
	$role = "contributor";
	
	if(!Teacher::userHasRole($exam, $role, phpCAS::getUser())) {
	    ErrorPage::show(_("Access denied!"),
			    sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), $role));
	    exit(1);
	}
    }

    // 
    // Delete this question.
    // 
    private function saveDeleteQuestion($exam, $question)
    {
	$contrib = new Contribute($exam);
	$contrib->deleteQuestion($question);
	
	header(sprintf("location: contribute.php?exam=%d", $exam));
    }
    
    // 
    // Save answers posted by form.
    // 
    private function saveAddQuestion($exam, $score, $name, $quest, $type)
    {
	$video = isset($_REQUEST['video']) ? $_REQUEST['video'] : "";
	$audio = isset($_REQUEST['audio']) ? $_REQUEST['audio'] : "";
	$image = isset($_REQUEST['image']) ? $_REQUEST['image'] : "";
	
	$contrib = new Contribute($exam);
	$contrib->addQuestion($exam, $score, utf8_encode($name), utf8_encode($quest), $type, phpCAS::getUser(), $video, $audio, $image);
	
	header(sprintf("location: contribute.php?exam=%d", $exam));
    }

    // 
    // Save answers posted by form.
    // 
    private function saveEditQuestion($exam, $score, $name, $quest, $type, $question, $user)
    {
	$video = isset($_REQUEST['video']) ? $_REQUEST['video'] : "";
	$audio = isset($_REQUEST['audio']) ? $_REQUEST['audio'] : "";
	$image = isset($_REQUEST['image']) ? $_REQUEST['image'] : "";
	
	$contrib = new Contribute($exam);
	$contrib->editQuestion($question, $exam, $score, utf8_encode($name), utf8_encode($quest), $type, $user, $video, $audio, $image);

	header(sprintf("location: contribute.php?exam=%d", $exam));
    }
    
    // 
    // Helper function for adding a new or editing an existing question.
    // 
    private function formPostQuestion(&$data, $action, &$exam)
    {
	$options = array( "freetext" => _("Freeform text question"),
			  "single"   => _("Single choice question"),
			  "multiple" => _("Multiple choice question")
			  );
	
	printf("<form action=\"contribute.php\" method=\"GET\">\n");
 	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $data->getExamID());
	printf("<input type=\"hidden\" name=\"mode\" value=\"save\" />\n");
	printf("<input type=\"hidden\" name=\"action\" value=\"%s\" />\n", $action);
	if($action == "edit") {
	    printf("<input type=\"hidden\" name=\"question\" value=\"%d\" />\n",
		   $data->getQuestionID());
	}
	
	printf("<p><u>%s:</u></p>\n", _("Required fields"));
	printf("<label for=\"name\">%s:</label>\n", _("Name"));
	printf("<input type=\"text\" name=\"name\" size=\"60\" value=\"%s\" />\n", 
	       $data->hasQuestionName() ? utf8_decode($data->getQuestionName()) : "");
	printf("<br />\n");
	printf("<label for=\"quest\">%s:</label>\n", _("Question"));
	printf("<textarea name=\"quest\" cols=\"70\" rows=\"15\">%s</textarea>\n",
	       $data->hasQuestionText() ? utf8_decode($data->getQuestionText()) : "");
	printf("<br />\n");
	printf("<label for=\"score\">%s:</label>\n", _("Score"));
	printf("<input type=\"text\" name=\"score\" size=\"10\" value=\"%.01f\" />\n",
	       $data->hasQuestionScore() ? $data->getQuestionScore() : 0.0);
	printf("<br />\n");
	printf("<label for=\"type\">%s:</label>\n", _("Type"));
	printf("<select name=\"type\">\n");
	foreach($options as $value => $text) {
	    printf("<option value=\"%s\" %s>%s</option>\n", 
		   $value, $data->getQuestionType() == $value ? "selected" : "", $text);
	}
	printf("</select>\n");
	
	printf("<p><u>%s:</u></p>\n", _("Optional fields"));
	printf("<label for=\"video\">%s:</label>\n", _("Video URL"));
	printf("<input type=\"text\" name=\"video\" value=\"%s\" size=\"80\" title=\"%s\" />\n",
	       $data->hasQuestionVideo() ? $data->getQuestionVideo() : "",
	       _("An URL address (like http://www.example.com/xxx) linking to an web resource related to this question."));
	printf("<br />\n");
	printf("<label for=\"audio\">%s:</label>\n", _("Audio URL"));
	printf("<input type=\"text\" name=\"audio\" value=\"%s\" size=\"80\" title=\"%s\" />\n",
	       $data->hasQuestionAudio() ? $data->getQuestionAudio() : "",
	       _("An URL address (like http://www.example.com/xxx) linking to an web resource related to this question."));
	printf("<br />\n");
	printf("<label for=\"image\">%s:</label>\n", _("Image URL"));
	printf("<input type=\"text\" name=\"image\" value=\"%s\" size=\"80\" title=\"%s\" />\n",
	       $data->hasQuestionImage() ? $data->getQuestionImage() : "",
	       _("An URL address (like http://www.example.com/xxx) linking to an web resource related to this question."));

	// 
	// Only allow the creator of the exam to change the publisher of an question. 
	// This is because the exam creator is the only person who can grant the required
	// contribute role to the target user.
	// 
	if($exam->getExamCreator() == phpCAS::getUser()) {
	    printf("<p><u>%s:</u></p>\n", _("Accounting"));
	    printf("<label for=\"name\">%s:</label>\n", _("Publisher"));
	    printf("<input type=\"text\" name=\"user\" size=\"60\" value=\"%s\" title=\"%s\" />\n", 
		   $data->hasQuestionPublisher() ? utf8_decode($data->getQuestionPublisher()) : phpCAS::getUser(),
		   _("This field sets the UU-ID (CAS-ID) of the person who's responsible for correcting answers for this question.\n\nIf you modify the value in this field, make sure that this person has been granted contribute privileges on this exam!"));
	}
	
	printf("<br /><br />\n");	
	printf("<label for=\"submit\">&nbsp;</label>\n");
	printf("<input type=\"submit\" name=\"submit\" value=\"%s\" />\n", _("Submit"));
	printf("</form>\n");
    }
	
    // 
    // Show the form for adding a new question.
    // 
    private function formAddQuestion($exam)
    {
	$manager = new Manager($exam);
	$mandata = $manager->getData();
	$qrecord = new DataRecord(array("examid" => $exam, "questiontype" => "freetext" ));
	
	printf("<h3>" . _("Add Question") . "</h3>\n");
	printf("<p>" . _("This page let you add a new question in the examination '%s'") . "</p>\n", 
	       utf8_decode($mandata->getExamName()));
	
	self::formPostQuestion($qrecord, "add", $mandata);
    }
    
    // 
    // Show the form for editing an existing question.
    // 
    private function formEditQuestion($exam, $question)
    {
	$manager = new Manager($exam);
	$mandata = $manager->getData();
	$qrecord = Exam::getQuestionData($question);
	
	printf("<h3>" . _("Edit Question") . "</h3>\n");
	printf("<p>" . _("This page let you edit the existing question in the examination '%s'") . "</p>\n", 
	       utf8_decode($mandata->getExamName()));
	
	self::formPostQuestion($qrecord, "edit", $mandata);
    }
	
    // 
    // Show all questions for this exam.
    // 
    private function showQuestions($exam)
    {
	$manager = new Manager($exam);
	
	$data = $manager->getData();
	$info = $manager->getInfo();
	
	$questions = $manager->getQuestions();
	
	printf("<h3>" . _("Manage Questions") . "</h3>\n");
	printf("<p>" . 
	       _("This page let you add, edit and remove questions in the examination '%s'. ") . 
	       _("You can only edit or remove a question if you are the publisher of the question or the creator of this examination.") . 
	       "</p>\n", 
	       utf8_decode($data->getExamName()));

	if(!$info->isContributable()) {
	    printf("<p>" . _("Notice: It's no longer possible to contribute or modify questions for this examination.") . "</p>\n");
	}
	
	$tree = new TreeBuilder(_("Questions"));
	$root = $tree->getRoot();
	if($info->isContributable()) {
	    $root->addLink(_("Add"), sprintf("?exam=%d&amp;action=add", $data->getExamID()));
	}
	foreach($questions as $question) {
	    $child = $root->addChild(sprintf("%s %s", _("Question"), utf8_decode($question->getQuestionName())));
	    if($info->isContributable()) {
		if($question->getQuestionPublisher() == phpCAS::getUser() || $data->getExamCreator() == phpCAS::getUser()) {		
		    $child->AddLink(_("Edit"), sprintf("?exam=%d&amp;action=edit&amp;question=%d", 
						       $question->getExamID(),
						       $question->getQuestionID()));
		    $child->AddLink(_("Edit"), sprintf("?exam=%d&amp;action=delete&amp;question=%d", 
						       $question->getExamID(),
						       $question->getQuestionID()));
		}
	    }
	    $child->addChild(sprintf("%s: %.01f", _("Score"), $question->getQuestionScore()));
	    $child->addChild(sprintf("%s: %s", _("Publisher"), $question->getQuestionPublisher()));
	    $child->addChild(sprintf("%s: %s", _("Video"), $question->hasQuestionVideo() ? $question->getQuestionVideo() : _("No")));
	    $child->addChild(sprintf("%s: %s", _("Audio"), $question->hasQuestionAudio() ? $question->getQuestionAudio() : _("No")));
	    $child->addChild(sprintf("%s: %s", _("Image"), $question->hasQuestionImage() ? $question->getQuestionImage() : _("No")));
	    $subobj = $child->addChild(sprintf("%s:", _("Question Text")));
	    $subobj->addText(utf8_decode(str_replace("\n", "<br>", $question->getQuestionText())));
	}
	$tree->output();
    }
        
    // 
    // Show all exams where caller has been granted the contribute role.
    // 
    private function showAvailableExams()
    {
	printf("<p>"  . _("Select the examination you wish to contribute questions for (applies only to contributable examinations).") . "</p>\n");

	$tree = new TreeBuilder(_("Examinations"));
	$root = $tree->getRoot();
	
	$exams = Contribute::getExams(phpCAS::getUser());	
	foreach($exams as $exam) {
	    $manager = new Manager($exam->getExamID());
	    
	    $child = $root->addChild(utf8_decode($exam->getExamName()));
	    if($manager->getInfo()->isContributable()) {
		$child->setLink(sprintf("?exam=%d", $exam->getExamID()));
	    }
	    // TODO: add title to links!!
	    $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($exam->getExamStartTime()))));
	    $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($exam->getExamEndTime()))));
	}
	$tree->output();
    }
    
}

$page = new ContributePage();
$page->render();

?>
