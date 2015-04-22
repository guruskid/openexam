<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentsTextTab.php
// Created: 2015-04-15 00:33:07
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_IOFactory;

/**
 * Import students from TAB-separated values file.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsTextTab extends ImportStudents
{

        private static $mimedef = "text/tab-separated-values";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = PHPExcel_IOFactory::createReader('CSV');
                $this->reader->setDelimiter("\t");
                $this->reader->setReadDataOnly(true);
        }

}