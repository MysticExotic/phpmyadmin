<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Identifiers\TriggerName;
use PhpMyAdmin\Plugins\Export\ExportHtmlword;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Triggers\Event;
use PhpMyAdmin\Triggers\Timing;
use PhpMyAdmin\Triggers\Trigger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use ReflectionProperty;

use function __;
use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportHtmlword::class)]
#[Group('medium')]
class ExportHtmlwordTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected ExportHtmlword $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $GLOBALS['server'] = 0;
        $this->object = new ExportHtmlword(
            new Relation($this->dbi),
            new Export($this->dbi),
            new Transformations(),
        );
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['lang'] = '';
        $GLOBALS['text_dir'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportHtmlword::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportHtmlword::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(ExportPluginProperties::class, $properties);

        $this->assertEquals(
            'Microsoft Word 2000',
            $properties->getText(),
        );

        $this->assertEquals(
            'doc',
            $properties->getExtension(),
        );

        $this->assertEquals(
            'application/vnd.ms-word',
            $properties->getMimeType(),
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText(),
        );

        $this->assertTrue(
            $properties->getForceFile(),
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        $this->assertEquals(
            'Format Specific Options',
            $options->getName(),
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'dump_what',
            $generalOptions->getName(),
        );

        $this->assertEquals(
            'Dump table',
            $generalOptions->getText(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        $this->assertInstanceOf(RadioPropertyItem::class, $property);

        $this->assertEquals(
            'structure_or_data',
            $property->getName(),
        );

        $this->assertEquals(
            ['structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')],
            $property->getValues(),
        );

        $generalOptions = $generalOptionsArray->current();

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'dump_what',
            $generalOptions->getName(),
        );

        $this->assertEquals(
            'Data dump options',
            $generalOptions->getText(),
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getForce(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'null',
            $property->getName(),
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText(),
        );

        $property = $generalProperties->current();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'columns',
            $property->getName(),
        );

        $this->assertEquals(
            'Put columns names in the first row',
            $property->getText(),
        );
    }

    public function testExportHeader(): void
    {
        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        $expected = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . 'utf-8" />
            </head>
            <body>';

        $this->assertEquals($expected, $result);

        // case 2

        $GLOBALS['charset'] = 'ISO-8859-1';
        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        $expected = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . 'ISO-8859-1" />
            </head>
            <body>';

        $this->assertEquals($expected, $result);
    }

    public function testExportFooter(): void
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportFooter(),
        );
        $result = ob_get_clean();

        $this->assertEquals('</body></html>', $result);
    }

    public function testExportDBHeader(): void
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportDBHeader('d"b'),
        );
        $result = ob_get_clean();

        $this->assertEquals('<h1>Database d&quot;b</h1>', $result);
    }

    public function testExportDBFooter(): void
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB'),
        );
    }

    public function testExportDBCreate(): void
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database'),
        );
    }

    public function testExportData(): void
    {
        // case 1
        $GLOBALS['htmlword_columns'] = true;
        $GLOBALS['what'] = 'UT';
        $GLOBALS['UT_null'] = 'customNull';
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;

        ob_start();
        $this->assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Dumping data for table test_table</h2>'
            . '<table width="100%" cellspacing="1"><tr class="print-category">'
            . '<td class="print"><strong>id</strong></td>'
            . '<td class="print"><strong>name</strong></td>'
            . '<td class="print"><strong>datetimefield</strong></td>'
            . '</tr><tr class="print-category">'
            . '<td class="print">1</td><td class="print">abcd</td><td class="print">2011-01-20 02:00:02</td>'
            . '</tr><tr class="print-category">'
            . '<td class="print">2</td><td class="print">foo</td><td class="print">2010-01-20 02:00:02</td>'
            . '</tr><tr class="print-category">'
            . '<td class="print">3</td><td class="print">Abcd</td><td class="print">2012-01-20 02:00:02</td>'
            . '</tr></table>',
            $result,
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $this->object = $this->getMockBuilder(ExportHtmlword::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->disableOriginalConstructor()
            ->getMock();

        // case 1

        $keys = [['Non_unique' => 0, 'Column_name' => 'name1'], ['Non_unique' => 1, 'Column_name' => 'name2']];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', 'view')
            ->willReturn($keys);

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', 'view')
            ->willReturn([['Field' => 'column']]);

        DatabaseInterface::$instance = $dbi;

        $this->object->expects($this->once())
            ->method('formatOneColumnDefinition')
            ->with(['Field' => 'column'], ['name1'], 'column')
            ->willReturn('1');

        $this->assertEquals(
            '<table width="100%" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>' .
            '1</tr></table>',
            $this->object->getTableDefStandIn('database', 'view'),
        );
    }

    public function testGetTableDef(): void
    {
        $this->object = $this->getMockBuilder(ExportHtmlword::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->setConstructorArgs([new Relation($this->dbi), new Export($this->dbi), new Transformations()])
            ->getMock();

        $keys = [['Non_unique' => 0, 'Column_name' => 'name1'], ['Non_unique' => 1, 'Column_name' => 'name2']];

        // case 1

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturn(
                [],
                ['fieldname' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
            );

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->willReturn($keys);

        $columns = ['Field' => 'fieldname'];
        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->willReturn([$columns]);

        $dbi->expects($this->once())
            ->method('tryQueryAsControlUser')
            ->willReturn($resultStub);

        $resultStub->expects($this->once())
            ->method('numRows')
            ->willReturn(1);

        $resultStub->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn(['comment' => 'testComment']);

        $dbi->expects($this->any())->method('escapeString')
            ->willReturnArgument(0);

        DatabaseInterface::$instance = $dbi;
        $this->object->relation = new Relation($dbi);

        $this->object->expects($this->exactly(3))
            ->method('formatOneColumnDefinition')
            ->with($columns, ['name1'])
            ->willReturn('1');

        $relationParameters = RelationParameters::fromArray([
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $result = $this->object->getTableDef('database', '', true, true, true);

        $this->assertEquals(
            '<table width="100%" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td>' .
            '<td class="print"><strong>Comments</strong></td>' .
            '<td class="print"><strong>Media type</strong></td></tr>' .
            '1<td class="print"></td><td class="print">Test&lt;</td></tr></table>',
            $result,
        );

        // case 2

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturn(
                ['fieldname' => ['foreign_table' => 'ftable', 'foreign_field' => 'ffield']],
                ['field' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
            );

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->willReturn($keys);

        $columns = ['Field' => 'fieldname'];

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->willReturn([$columns]);

        $dbi->expects($this->once())
            ->method('tryQueryAsControlUser')
            ->willReturn($resultStub);

        $resultStub->expects($this->once())
            ->method('numRows')
            ->willReturn(1);

        $resultStub->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn(['comment' => 'testComment']);

        $dbi->expects($this->any())->method('escapeString')
            ->willReturnArgument(0);

        DatabaseInterface::$instance = $dbi;
        $this->object->relation = new Relation($dbi);

        $relationParameters = RelationParameters::fromArray([
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $result = $this->object->getTableDef('database', '', true, true, true);

        $this->assertStringContainsString('<td class="print">ftable (ffield)</td>', $result);

        $this->assertStringContainsString('<td class="print"></td><td class="print"></td>', $result);

        // case 3

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->willReturn($keys);

        $columns = ['Field' => 'fieldname'];

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->willReturn([$columns]);

        $dbi->expects($this->never())
            ->method('tryQuery');

        $dbi->expects($this->any())->method('escapeString')
            ->willReturnArgument(0);

        DatabaseInterface::$instance = $dbi;

        $relationParameters = RelationParameters::fromArray([
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $result = $this->object->getTableDef('database', '', false, false, false);

        $this->assertEquals(
            '<table width="100%" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>1</tr></table>',
            $result,
        );
    }

    public function testGetTriggers(): void
    {
        $triggers = [
            new Trigger(
                TriggerName::from('tna"me'),
                Timing::Before,
                Event::Update,
                TableName::from('table'),
                'def',
                'test_user@localhost',
            ),
        ];

        $method = new ReflectionMethod(ExportHtmlword::class, 'getTriggers');
        $result = $method->invoke($this->object, 'database', 'table', $triggers);

        $this->assertStringContainsString(
            '<td class="print">tna&quot;me</td>' .
            '<td class="print">BEFORE</td>' .
            '<td class="print">UPDATE</td>' .
            '<td class="print">def</td>',
            $result,
        );
    }

    public function testExportStructure(): void
    {
        ob_start();
        $this->dummyDbi->addSelectDb('test_db');
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                'localhost',
                'create_table',
                'test',
            ),
        );
        $this->dummyDbi->assertAllSelectsConsumed();
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Table structure for table test_table</h2>'
            . '<table width="100%" cellspacing="1"><tr class="print-category">'
            . '<th class="print">Column</th><td class="print"><strong>Type</strong></td>'
            . '<td class="print"><strong>Null</strong></td><td class="print"><strong>Default</strong></td></tr>'
            . '<tr class="print-category"><td class="print"><em><strong>id</strong></em></td>'
            . '<td class="print">int(11)</td><td class="print">No</td><td class="print">NULL</td></tr>'
            . '<tr class="print-category"><td class="print">name</td><td class="print">varchar(20)</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">datetimefield</td><td class="print">datetime</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr></table>',
            $result,
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                'localhost',
                'triggers',
                'test',
            ),
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Triggers test_table</h2><table width="100%" cellspacing="1">'
            . '<tr class="print-category"><th class="print">Name</th>'
            . '<td class="print"><strong>Time</strong></td><td class="print"><strong>Event</strong></td>'
            . '<td class="print"><strong>Definition</strong></td></tr><tr class="print-category">'
            . '<td class="print">test_trigger</td><td class="print">AFTER</td>'
            . '<td class="print">INSERT</td><td class="print">BEGIN END</td></tr></table>',
            $result,
        );

        ob_start();
        $this->dummyDbi->addSelectDb('test_db');
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                'localhost',
                'create_view',
                'test',
            ),
        );
        $this->dummyDbi->assertAllSelectsConsumed();
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Structure for view test_table</h2>'
            . '<table width="100%" cellspacing="1"><tr class="print-category">'
            . '<th class="print">Column</th><td class="print"><strong>Type</strong></td>'
            . '<td class="print"><strong>Null</strong></td><td class="print"><strong>Default</strong>'
            . '</td></tr><tr class="print-category"><td class="print"><em><strong>id</strong></em></td>'
            . '<td class="print">int(11)</td><td class="print">No</td><td class="print">NULL</td></tr>'
            . '<tr class="print-category"><td class="print">name</td><td class="print">varchar(20)</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">datetimefield</td><td class="print">datetime</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr></table>',
            $result,
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                'localhost',
                'stand_in',
                'test',
            ),
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Stand-in structure for view test_table</h2>'
            . '<table width="100%" cellspacing="1"><tr class="print-category">'
            . '<th class="print">Column</th><td class="print"><strong>Type</strong></td>'
            . '<td class="print"><strong>Null</strong></td><td class="print"><strong>Default</strong></td>'
            . '</tr><tr class="print-category">'
            . '<td class="print"><em><strong>id</strong></em></td><td class="print">int(11)</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">name</td><td class="print">varchar(20)</td><td class="print">No</td>'
            . '<td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">datetimefield</td><td class="print">datetime</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr></table>',
            $result,
        );
    }

    public function testFormatOneColumnDefinition(): void
    {
        $method = new ReflectionMethod(ExportHtmlword::class, 'formatOneColumnDefinition');

        $cols = ['Null' => 'Yes', 'Field' => 'field', 'Key' => 'PRI', 'Type' => 'set(abc)enum123'];

        $uniqueKeys = ['field'];

        $this->assertEquals(
            '<tr class="print-category"><td class="print"><em>' .
            '<strong>field</strong></em></td><td class="print">set(abc)</td>' .
            '<td class="print">Yes</td><td class="print">NULL</td>',
            $method->invoke($this->object, $cols, $uniqueKeys),
        );

        $cols = ['Null' => 'NO', 'Field' => 'fields', 'Key' => 'COMP', 'Type' => '', 'Default' => 'def'];

        $uniqueKeys = ['field'];

        $this->assertEquals(
            '<tr class="print-category"><td class="print">fields</td>' .
            '<td class="print">&amp;nbsp;</td><td class="print">No</td>' .
            '<td class="print">def</td>',
            $method->invoke($this->object, $cols, $uniqueKeys),
        );
    }
}
