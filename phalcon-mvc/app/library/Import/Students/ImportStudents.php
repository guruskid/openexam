<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudents.php
// Created: 2015-04-15 00:21:13
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Import\ImportBase;
use OpenExam\Library\Import\ImportData;
use stdClass;

/**
 * Base class for student import classes.
 * 
 * The $students member contains an associative array whose key are the
 * user ID and the value are the optional assigned tag.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudents extends ImportBase
{

        const XMLDOC = '<openexam/>';
        // Constant for setMapping():
        const TAG = 'tag';
        const USER = 'user';
        const CODE = 'code';
        const PNR = 'pnr';
        const ROW = 'row';

        private static $pnrhpatt = "/^personnummer|pers.?nr|p.?nr|pnum$/";
        private static $pnrvpatt = "/^\d{6,8}-?(\d{4}|[a-zA-Z]\d{3}|\d{3}[a-zA-Z])$/";
        protected $students = array();
        protected $opts;
        protected $reader;
        private $excel;         // the excel object
        private $sheet;         // active sheet
        private $cols;          // columns in active sheet
        private $rows;          // rows in active sheet
        private $sdat;          // php array (0-based) of sheet data
        private $first = 0;     // first row

        public function __construct($accept)
        {
                parent::__construct($accept);
                $this->data = new ImportData(self::XMLDOC);
                $this->opts = new stdClass();
        }

        /**
         * Set tagging.
         * 
         * A numeric value defines the column containing some generic string
         * to be associated with the added student. It the tag value is a
         * string, then it applies to all imported accounts.
         * 
         * @param int|string $tag The column or tag string.
         */
        public function setTagging($tag)
        {
                if (is_numeric($tag)) {
                        $this->opts->coltag = $tag;
                } elseif (is_string($tag) && strlen($tag) == 1) {
                        $this->opts->coltag = $tag - ord('A');
                } else {
                        $this->opts->tagstr = $tag;
                }
        }

        /**
         * Set column mapping.
         * 
         * Possible values for the $type aregument are:
         * <ul>
         * <li>'tag'    -> Define the tag column.</li>
         * <li>'user'   -> Define the account column.</li>
         * <li>'code'   -> Define the anonymous code column.</li>
         * <li>'persnr' -> Define the person number column.</li>
         * </ul>
         * 
         * @param string $type The type identifier.
         * @param int $index The column index.
         */
        public function setMapping($type, $index)
        {
                switch ($type) {
                        case self::TAG:
                                $this->opts->coltag = $index;
                                break;
                        case self::USER:
                                $this->opts->coluser = $index;
                                break;
                        case self::PNR:
                                $this->opts->colpnr = $index;
                                break;
                        case self::CODE:
                                $this->opts->colcode = $index;
                }
        }

        /**
         * Set first row to import from.
         * @param int $row The row number.
         */
        public function setStartRow($row)
        {
                $this->first = $row;
        }

        public function read()
        {
                $this->excel = $this->reader->load($this->file);
                $this->sheet = $this->excel->setActiveSheetIndex(0);

                $this->cols = ord($this->sheet->getHighestColumn()) - ord('A');
                $this->rows = $this->sheet->getHighestRow();
                $this->sdat = $this->sheet->toArray();

                if (isset($this->opts->coluser)) {
                        $this->readAccounts($this->opts->coluser);
                } elseif (isset($this->opts->colpnr)) {
                        $this->readPersNr($this->opts->colpnr);
                        $this->lookupPersNr();
                } else {
                        $this->readDetect();    // try to detect pers.nr.
                        $this->lookupPersNr();
                }

                if (count($this->students) == 0) {
                        throw new ImportException(_("No account information in import file.", Error::PRECONDITION_FAILED));
                }

                if (isset($this->opts->coltag)) {
                        $this->readTag($this->opts->coltag);
                }
                if (isset($this->opts->tagstr)) {
                        $this->assignTag($this->opts->tagstr);
                }
                if (isset($this->opts->colcode)) {
                        $this->readCode($this->opts->colcode);
                }

                $pnode = $this->data->addChild('students');
                foreach ($this->students as $user => $val) {
                        $snode = $pnode->addChild('student');
                        $snode->addChild('user', $user);
                        $snode->addChild('code', $val[self::CODE]);
                        $snode->addChild('tag', $val[self::TAG]);
                }
        }

        /**
         * Get associative array of student data.
         * 
         * The user name is the key. Requires calling setMapping() for 
         * successful extracting username, tag, code from input data.
         * 
         * @return array
         */
        public function getStudents()
        {
                return $this->students;
        }

        /**
         * Get raw sheet data.
         * @return array
         */
        public function getSheet()
        {
                return $this->sdat;
        }

        // 
        // Read accounts from named column.
        // 
        private function readAccounts($column)
        {
                for ($r = $this->first; $r < $this->rows; ++$r) {
                        $value = $this->sdat[$r][$column];
                        $this->setValue(null, self::USER, $value);
                        $this->setValue($value, self::ROW, $r);
                }
        }

        // 
        // Read personal numbers from named column.
        // 
        private function readPersNr($column)
        {
                for ($r = $this->first; $r < $this->rows; ++$r) {
                        $value = trim($this->sdat[$r][$column]);
                        if (empty($value)) {
                                continue;
                        } elseif (preg_match(self::$pnrvpatt, $value)) {
                                $this->setValue(null, self::USER, $value);
                                $this->setValue($value, self::ROW, $r);
                        } elseif ($r != $this->first) {
                                throw new ImportException(sprintf("Unmatched personal number in cell '%d,%d' (%s)", $r, $column, $value), Error::NOT_ACCEPTABLE);
                        }
                }
        }

        // 
        // Try to detect the column containing the personal number. If it
        // fails, scan each row trying to match a cell with a personal number.
        // 
        private function readDetect()
        {
                // 
                // Try to detect an header matching one of the hpattern.
                // 
                for ($c = 0; $c < $this->cols; ++$c) {
                        $value = $this->sdat[0][$c];
                        if (preg_match(self::$pnrhpatt, strtolower($value))) {
                                // 
                                // Check if row below actually contains a 
                                // personal number.
                                // 
                                $value = $this->sdat[1][$c];
                                if (preg_match(self::$pnrvpatt, $value)) {
                                        $this->first = 1;
                                        $this->readPersNr($c);
                                        return;
                                }
                        }
                }

                // 
                // Try to detect an personal number in each cell.
                // 
                for ($r = $this->first; $r < $this->rows; ++$r) {
                        for ($c = 0; $c < $this->cols; ++$c) {
                                $value = $this->sdat[$r][$c];
                                if (preg_match(self::$pnrvpatt, $value)) {
                                        $this->setValue(null, self::USER, $value);
                                        $this->setValue($value, self::ROW, $r);
                                }
                        }
                }
        }

        // 
        // Read code from column index.
        // 
        private function readCode($column)
        {
                foreach (array_keys($this->students) as $user) {
                        $value = $this->sdat[$this->getValue($user, self::ROW)][$column];
                        $this->setValue($user, self::CODE, $value);
                }
        }

        // 
        // Read tag from column index.
        // 
        private function readTag($column)
        {
                foreach (array_keys($this->students) as $user) {
                        $value = $this->sdat[$this->getValue($user, self::ROW)][$column];
                        $this->setValue($user, self::TAG, $value);
                }
        }

        // 
        // Assign the same tag to all students.
        // 
        private function assignTag($tag)
        {
                foreach (array_keys($this->students) as $user) {
                        $this->setValue($user, self::TAG, $tag);
                }
        }

        // 
        // Resolve any personal numbers.
        // 
        private function lookupPersNr()
        {
                foreach ($this->students as $key => $val) {
                        if (is_numeric($key[0])) {
                                $principal = $this->catalog->getPrincipal($key, Principal::ATTR_PNR, array('attr' => Principal::ATTR_PN));
                                if (count($principal) != 0) {
                                        $user = $principal[0]->principal;
                                        $val = $this->students[$key];
                                        unset($this->students[$key]);
                                        $this->students[$user] = $val;
                                        $this->setvalue($user, self::PNR, $key);
                                }
                        }
                }
        }

        private function setValue($key, $name, $val)
        {
                if ($name == self::USER) {
                        if (!isset($this->students[$val])) {
                                $this->students[$val] = array(
                                        self::TAG  => '', self::CODE => ''
                                );
                        }
                } else {
                        $this->students[$key][$name] = $val;
                }
        }

        private function getValue($key, $name)
        {
                return $this->students[$key][$name];
        }

}
