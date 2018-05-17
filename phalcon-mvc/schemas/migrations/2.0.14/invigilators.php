<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class InvigilatorsMigration_2014
 */
class InvigilatorsMigration_2014 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('invigilators', array(
      'columns' => array(
        new Column(
          'id',
          array(
            'type' => Column::TYPE_INTEGER,
            'notNull' => true,
            'autoIncrement' => true,
            'size' => 11,
            'first' => true,
          )
        ),
        new Column(
          'exam_id',
          array(
            'type' => Column::TYPE_INTEGER,
            'notNull' => true,
            'size' => 11,
            'after' => 'id',
          )
        ),
        new Column(
          'user',
          array(
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 60,
            'after' => 'exam_id',
          )
        ),
      ),
      'indexes' => array(
        new Index('PRIMARY', array('id'), 'PRIMARY'),
        new Index('exam_id', array('exam_id'), null),
      ),
      'references' => array(
        new Reference(
          'invigilators_ibfk_1',
          array(
            'referencedSchema' => 'openexam2prod',
            'referencedTable' => 'exams',
            'columns' => array('exam_id', 'exam_id'),
            'referencedColumns' => array('id', 'id'),
            'onUpdate' => 'RESTRICT',
            'onDelete' => 'CASCADE',
          )
        ),
      ),
      'options' => array(
        'TABLE_TYPE' => 'BASE TABLE',
        'AUTO_INCREMENT' => '30448',
        'ENGINE' => 'InnoDB',
        'TABLE_COLLATION' => 'utf8_general_ci',
      ),
    )
    );
  }

  /**
   * Run the migrations
   *
   * @return void
   */
  public function up() {

  }

  /**
   * Reverse the migrations
   *
   * @return void
   */
  public function down() {

  }

}
