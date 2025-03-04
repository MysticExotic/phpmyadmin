<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ListDatabase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ListDatabase::class)]
class ListDatabaseTest extends AbstractTestCase
{
    /**
     * ListDatabase instance
     */
    private ListDatabase $object;

    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['only_db'] = ['single\\_db'];
        $this->object = new ListDatabase();
    }

    /**
     * Test for ListDatabase::getDefault
     */
    public function testEmpty(): void
    {
        $arr = new ListDatabase();
        $this->assertEquals('', $arr->getDefault());
    }

    /**
     * Test for ListDatabase::exists
     */
    public function testExists(): void
    {
        $arr = new ListDatabase();
        $this->assertTrue($arr->exists('single_db'));
    }

    public function testGetList(): void
    {
        $arr = new ListDatabase();

        $GLOBALS['db'] = 'db';
        $this->assertEquals(
            [['name' => 'single_db', 'is_selected' => false]],
            $arr->getList(),
        );

        $GLOBALS['db'] = 'single_db';
        $this->assertEquals(
            [['name' => 'single_db', 'is_selected' => true]],
            $arr->getList(),
        );
    }

    /**
     * Test for checkHideDatabase
     */
    public function testCheckHideDatabase(): void
    {
        $GLOBALS['cfg']['Server']['hide_db'] = 'single\\_db';
        $this->assertEquals(
            $this->callFunction(
                $this->object,
                ListDatabase::class,
                'checkHideDatabase',
                [],
            ),
            '',
        );
    }

    /**
     * Test for getDefault
     */
    public function testGetDefault(): void
    {
        $GLOBALS['db'] = '';
        $this->assertEquals(
            $this->object->getDefault(),
            '',
        );

        $GLOBALS['db'] = 'mysql';
        $this->assertEquals(
            $this->object->getDefault(),
            'mysql',
        );
    }
}
