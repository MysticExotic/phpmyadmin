<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\ExportController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\UserPreferences;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ExportController::class)]
class ExportControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadContainerBuilder();
        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        DatabaseInterface::$instance = null;
    }

    public function testExportController(): void
    {
        parent::loadDbiIntoContainerBuilder();

        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['cfg']['Server'] = Config::getInstance()->getSettings()->Servers[1]->asArray();
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['single_table'] = '1';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT COUNT(*) FROM `test_db`.`test_table`', [['3']]);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseRenderer();
        $pageSettings = new PageSettings(
            new UserPreferences($dbi, new Relation($dbi), new Template()),
        );
        $pageSettings->init('Export');
        $template = new Template();
        $exportList = Plugins::getExport('table', true);

        $expected = $template->render('table/export/index', [
            'export_type' => 'table',
            'db' => 'test_db',
            'table' => 'test_table',
            'templates' => ['is_enabled' => false, 'templates' => [], 'selected' => null],
            'sql_query' => '',
            'hidden_inputs' => [
                'db' => 'test_db',
                'table' => 'test_table',
                'export_type' => 'table',
                'export_method' => 'quick',
                'template_id' => '',
                'single_table' => true,
            ],
            'export_method' => 'quick',
            'plugins_choice' => Plugins::getChoice($exportList, 'sql'),
            'options' => Plugins::getOptions('Export', $exportList),
            'can_convert_kanji' => false,
            'exec_time_limit' => 300,
            'rows' => [
                'allrows' => null,
                'limit_to' => null,
                'limit_from' => null,
                'unlim_num_rows' => 0,
                'number_of_rows' => '3',
            ],
            'has_save_dir' => false,
            'save_dir' => '/',
            'export_is_checked' => false,
            'export_overwrite_is_checked' => false,
            'has_aliases' => false,
            'aliases' => [],
            'is_checked_lock_tables' => false,
            'is_checked_asfile' => true,
            'is_checked_as_separate_files' => false,
            'is_checked_export' => false,
            'is_checked_export_overwrite' => false,
            'is_checked_remember_file_template' => true,
            'repopulate' => false,
            'lock_tables' => false,
            'is_encoding_supported' => true,
            'encodings' => Encoding::listEncodings(),
            'export_charset' => '',
            'export_asfile' => true,
            'has_zip' => true,
            'has_gzip' => true,
            'selected_compression' => 'none',
            'filename_template' => '@TABLE@',
            'page_settings_error_html' => $pageSettings->getErrorHTML(),
            'page_settings_html' => $pageSettings->getHTML(),
        ]);

        (new ExportController(
            $response,
            $template,
            new Options(new Relation($dbi), new TemplateModel($dbi)),
            $pageSettings,
        ))($this->createStub(ServerRequest::class));
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
