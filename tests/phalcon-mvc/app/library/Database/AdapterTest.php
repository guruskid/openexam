<?php

namespace OpenExam\Library\Database;

use OpenExam\Library\Database\Adapter;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-04 at 15:00:28.
 */
class AdapterTest extends \OpenExam\Tests\Phalcon\TestCase
{

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown()
        {
                
        }

        /**
         * @covers OpenExam\Library\Database\Adapter::create
         */
        public function testCreate()
        {
                $config = $this->config->database;

                try {
                        $config->adapter = Adapter::MySQL;
                        $adapter = Adapter::create($config);
                        self::assertNotNull($adapter);
                        self::assertInstanceOf('\Phalcon\Db\Adapter\Pdo\Mysql', $adapter);
                } catch (\PDOException $exception) {
                        // ignore expected exception
                        printf("Adapter %s: %s\n", Adapter::MySQL, $exception->getMessage());
                } catch (\Exception $exception) {
                        self::fail($exception);
                }

                try {
                        $config->adapter = Adapter::PostgreSQL;
                        $adapter = Adapter::create($config);
                        self::assertNotNull($adapter);
                        self::assertInstanceOf('\Phalcon\Db\Adapter\Pdo\Postgresql', $adapter);
                } catch (\PDOException $exception) {
                        // ignore expected exception
                        printf("Adapter %s: %s\n", Adapter::PostgreSQL, $exception->getMessage());
                } catch (\Exception $exception) {
                        self::fail($exception);
                }

                try {
                        $config->adapter = Adapter::Oracle;
                        $adapter = Adapter::create($config);
                        self::assertNotNull($adapter);
                        self::assertInstanceOf('\Phalcon\Db\Adapter\Pdo\Oracle', $adapter);
                } catch (\PDOException $exception) {
                        // ignore expected exception
                        printf("Adapter %s: %s\n", Adapter::Oracle, $exception->getMessage());
                } catch (\Exception $exception) {
                        self::fail($exception);
                }
                
                try {
                        $config->adapter = Adapter::SQLite;
                        $adapter = Adapter::create($config);
                        self::assertNotNull($adapter);
                        self::assertInstanceOf('\Phalcon\Db\Adapter\Pdo\Sqlite', $adapter);
                } catch (\PDOException $exception) {
                        // ignore expected exception
                        printf("Adapter %s: %s\n", Adapter::SQLite, $exception->getMessage());
                } catch (\Exception $exception) {
                        self::fail($exception);
                }
        }

}
