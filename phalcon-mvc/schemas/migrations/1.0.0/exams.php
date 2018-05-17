<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Mvc\Model\Migration;

class ExamsMigration_100 extends Migration {

  public function up() {
    $this->morphTable(
      'exams', array(
        'columns' => array(
          new Column(
            'id', array(
              'type' => Column::TYPE_INTEGER,
              'notNull' => true,
              'autoIncrement' => true,
              'size' => 11,
              'first' => true,
            )
          ),
          new Column(
            'name', array(
              'type' => Column::TYPE_VARCHAR,
              'notNull' => true,
              'size' => 200,
              'after' => 'id',
            )
          ),
          new Column(
            'descr', array(
              'type' => Column::TYPE_TEXT,
              'size' => 1,
              'after' => 'name',
            )
          ),
          new Column(
            'starttime', array(
              'type' => Column::TYPE_DATETIME,
              'notNull' => true,
              'size' => 1,
              'after' => 'descr',
            )
          ),
          new Column(
            'endtime', array(
              'type' => Column::TYPE_DATETIME,
              'notNull' => true,
              'size' => 1,
              'after' => 'starttime',
            )
          ),
          new Column(
            'created', array(
              'type' => Column::TYPE_DATETIME,
              'notNull' => true,
              'size' => 1,
              'after' => 'endtime',
            )
          ),
          new Column(
            'updated', array(
              'type' => Column::TYPE_DATE,
              'notNull' => true,
              'size' => 1,
              'after' => 'created',
            )
          ),
          new Column(
            'creator', array(
              'type' => Column::TYPE_CHAR,
              'notNull' => true,
              'size' => 8,
              'after' => 'updated',
            )
          ),
          new Column(
            'decoded', array(
              'type' => Column::TYPE_CHAR,
              'notNull' => true,
              'size' => 1,
              'after' => 'creator',
            )
          ),
          new Column(
            'orgunit', array(
              'type' => Column::TYPE_VARCHAR,
              'notNull' => true,
              'size' => 150,
              'after' => 'decoded',
            )
          ),
          new Column(
            'grades', array(
              'type' => Column::TYPE_VARCHAR,
              'notNull' => true,
              'size' => 200,
              'after' => 'orgunit',
            )
          ),
          new Column(
            'testcase', array(
              'type' => Column::TYPE_CHAR,
              'notNull' => true,
              'size' => 1,
              'after' => 'grades',
            )
          ),
          new Column(
            'lockdown', array(
              'type' => Column::TYPE_CHAR,
              'notNull' => true,
              'size' => 1,
              'after' => 'testcase',
            )
          ),
        ),
        'indexes' => array(
          new Index('PRIMARY', array('id')),
        ),
        'options' => array(
          'TABLE_TYPE' => 'BASE TABLE',
          'AUTO_INCREMENT' => '1',
          'ENGINE' => 'InnoDB',
          'TABLE_COLLATION' => 'utf8_general_ci',
        ),
      )
    );
  }

}
