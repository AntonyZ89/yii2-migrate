<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace antonyz89\migrate;

use yii\base\NotSupportedException;
use yii\console\ExitCode;
use antonyz89\seeder\SeederController;

/**
 * Class MigrateController
 * @package console\controllers
 *
 * @property boolean $skipConfirm
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    public $generatorTemplateFiles = [
        'create_table'    => '@antonyz89/migrate/views/createTableMigration.php',
        'drop_table'      => '@antonyz89/migrate/views/dropTableMigration.php',
        'add_column'      => '@antonyz89/migrate/views/addColumnMigration.php',
        'drop_column'     => '@antonyz89/migrate/views/dropColumnMigration.php',
        'create_junction' => '@antonyz89/migrate/views/createTableMigration.php',
    ];

    /**
     * {@inheritdoc}
     */
    public $templateFile = '@antonyz89/migrate/views/migration.php';

    public $skipConfirm = false;

    /**
     * Run migrate/fresh && yii seeder
     *
     * @return int
     * @throws NotSupportedException
     */
    public function actionFull()
    {
        if (!class_exists("antonyz89\\seeder\\SeederController")) {
            $message = "You must install 'antonyz89/yii2-seeder' extension for `migrate/full` support. " .
                "To resolve, you must add 'antonyz89/yii2-seeder' to the 'require' section of your application's" .
                " composer.json file and then run 'composer update'.";
            $this->stdout($message);
            return ExitCode::UNAVAILABLE;
        }

        if (YII_ENV_PROD) {
            $this->stdout("YII_ENV is set to 'prod'.\nRefreshing migrations is not possible on production systems.\n");
            return ExitCode::OK;
        }

        $this->skipConfirm = true;

        $this->truncateDatabase();

        if ($this->actionUp() == ExitCode::OK) {
            (new SeederController(null, null))->actionSeed();
        }

        $this->skipConfirm = false;
        return ExitCode::OK;
    }

    public function actionTruncateDatabase()
    {
        if (YII_ENV_PROD) {
            $this->stdout("YII_ENV is set to 'prod'.\rTruncate database is not possible on production systems.\n");
            return ExitCode::OK;
        }

        $confirm = $this->confirm("Are you sure you want to truncate database?\nAll data will be lost.");

        if ($confirm) {
            $this->stdout("\nTruncating database...\n");
            $this->truncateDatabase();
            $this->stdout("Database truncated.\n");
        } else {
            $this->stdout("\nTruncate database aborted.\n");
        }
    }

    public function actionTruncate($table = null)
    {
        if (YII_ENV_PROD) {
            $this->stdout("YII_ENV is set to 'prod'.\rTruncate table is not possible on production systems.\n");
            return ExitCode::OK;
        }

        if ($table === null) {
            $confirm = $this->confirm("Are you sure you want to truncate all tables?");
        } else {
            $confirm = $this->confirm("Are you sure you want to truncate table '{$table}'?");
        }

        if (!$confirm) {
            return ExitCode::OK;
        }

        $this->db->createCommand()->truncateTable($table)->execute();
    }

    public function confirm($message, $default = false)
    {
        if ($this->skipConfirm)
            return true;

        return parent::confirm($message, $default);
    }
}
