<?php

namespace OpenExam\Library\Render;

use OpenExam\Tests\Phalcon\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-11-27 at 12:17:40.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class RenderImageTest extends TestCase
{

        /**
         * Checksum and file size properties:
         */
        private static $output = array(
                'png'    => array(
                        'md5'  => 'b1242ce26730c72c91725e06adeb6cdc',
                        'size' => 1863147
                ),
                'jpeg'   => array(
                        'md5'  => 'ed78c0ca0257a6a64c51c6b6e2ba7e5d',
                        'size' => 44197
                ),
                'bmp'    => array(
                        'md5'  => 'ef46e21c679eb9d63d62368f13eae714',
                        'size' => 1394742
                ),
                'svg'    => array(
                        'md5'  => '153131d44c1e4aaa94801bce150ff2ab',
                        'size' => 117052
                ),
                'unlink' => false,
                'check'  => false
        );
        /**
         * @var RenderImage
         */
        protected $object;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                $this->object = new RenderImage;
                $this->cwd = getcwd();
                chdir(__DIR__);
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown()
        {
                chdir($this->cwd);
        }

        /**
         * @covers OpenExam\Library\Render\RenderImage::save
         * @group render
         */
        public function testSave()
        {
                // 
                // Test image rendering:
                // 

                $globals = array(
                        'in'           => 'index.html',
                        'imageQuality' => '95'
                );


                foreach (array('png', 'jpeg', 'svg', 'bmp') as $type) {
                        $filename = sprintf("%s/render-image-test.%s", sys_get_temp_dir(), $type);

                        self::assertTrue($this->object->save($filename, $globals));
                        self::assertTrue(file_exists($filename));
                        self::assertTrue(filesize($filename) != 0);

                        if (self::$output['check']) {
                                self::assertEquals(self::$output[$type]['size'], filesize($filename));
                                self::assertEquals(self::$output[$type]['md5'], md5_file($filename));
                        }
                        if (self::$output['unlink'] && file_exists($filename)) {
                                unlink($filename);
                        }
                }
        }

}