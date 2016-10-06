<?php
namespace sky\yii\console;

use yii\helpers\Console;
use yii\db\Query;

class MigrateController extends \yii\console\controllers\MigrateController
{
    public $migrationTable = '{{%migration}}';
    /**
     * Creates the migration history table.
     */
    protected function createMigrationHistoryTable()
    {
        $tableName = $this->db->schema->getRawTableName($this->migrationTable);
        $this->stdout("Creating migration history table \"$tableName\"...", Console::FG_YELLOW);
        $this->db->createCommand()->createTable($this->migrationTable, [
            'version' => 'varchar(180) NOT NULL PRIMARY KEY',
            'path' => 'varchar(255) NOT NULL',
            'apply_time' => 'integer',
        ])->execute();
        $this->db->createCommand()->insert($this->migrationTable, [
            'version' => self::BASE_MIGRATION,
            'path' => self::className(),
            'apply_time' => time(),
        ])->execute();
        $this->stdout("Done.\n", Console::FG_GREEN);
    }
    
    /**
     * @inheritdoc
     */
    protected function addMigrationHistory($version)
    {
        $command = $this->db->createCommand();
        $command->insert($this->migrationTable, [
            'version' => $version,
            'path' => $this->migrationPath . DIRECTORY_SEPARATOR,
            'apply_time' => time(),
        ])->execute();
    }
    
    /**
     * @inheritdoc
     */
    protected function removeMigrationHistory($version)
    {
        $command = $this->db->createCommand();
        $command->delete($this->migrationTable, [
            'version' => $version,
        ])->execute();
    }
    
    /**
     * Creates a new migration instance.
     * @param string $class the migration class name
     * @return \yii\db\Migration the migration instance
     */
    protected function createMigration($class)
    {
        $query = new Query;
        $migrate = $query->select(['path'])
                ->from($this->migrationTable)
                ->where(['version' => $class])
                ->one();
        $filename = DIRECTORY_SEPARATOR . $class . '.php';
        if ($migrate && file_exists($migrate['path'] . $filename)) {
            $this->migrationPath = $migrate['path'];
        }

        $file = $this->migrationPath . $filename;
        require_once($file);

        return new $class(['db' => $this->db]);
    }
}