<?php

namespace Groundwork\Migration;

use Exception;
use Groundwork\Database\Db;
use Groundwork\Exceptions\Database\QueryException;
use Groundwork\Utils\Str;
use Groundwork\Utils\Table;
use Groundwork\Database\Query;
use Groundwork\Migration\Builders\Blueprint;
use Groundwork\Migration\Builders\Schema;

class Migrator {

    const migratorTable = 'migrations';

    const migrationFolder = '/database/migrations';
    
    const seedersFolder = '/database/seeders';

    /**
     * Runs all migrations that do not exist in the database already.
     */
    public function migrate()
    {
        echo "<pre>";
        // This method needs to do a few things:
        // 1. Check which migrations have occurred
        // 2. Check which migration files exist
        // 3. Run the migrations that haven't been processed
        $this->checkDatabase();

        $files = $this->checkMigrationFiles();

        $processed = $this->getProcessedMigrations();

        // find the differences
        $files->diff($processed)
            ->each(function(string $file) {

                echo "Executing migration $file...";

                $class = $this->buildMigration($file);

                if ($this->executeMigration($class)) {

                    (new Query($this::migratorTable))
                        ->insert([
                            'class' => get_class($class),
                            'filename' => $file,
                        ]);

                    echo "Ok!\n";

                } else {
                    echo "Error!\n";
                    exit;
                }
            });

        echo "\nAll migrations have been executed.\n</pre>";
        echo "<a href='" . route('migrator-seed') . "'>Seed</a>";
    }

    /**
     * Rolls back a given amount of recent migrations.
     *
     * @param int|null $steps [optional]
     *
     * @throws QueryException
     */
    public function rollback(int $steps = null)
    {
        echo "<pre>";

        $this->checkDatabase();

        $migrations = $this->getProcessedMigrations();

        if (is_null($steps) || $steps > $migrations->count()) {
            $steps = $migrations->count();
        }

        for($i = 0; $i < $steps; $i++) {
            $filename = $migrations->pop();
            if (is_null($filename)) {
                echo "No more migrations to roll back!\n</pre>";
                exit;
            }

            echo "Reverting migration $filename...";

            $class = $this->buildMigration($filename);

            if ($this->revertMigration($class)) {

                (new Query($this::migratorTable))
                    ->where('filename', $filename)
                    ->delete();

                echo "Ok!\n";

            } else {
                echo "Error!\n</pre>";
                exit;
            }
        }

        echo "\n$i migration(s) have been rolled back.\n</pre>";
        echo "<a href='" . route('migrator-migrate') . "'>Migrate</a>";
    }

    /**
     * Runs all seeders found in the seeders' folder.
     */
    public function seed()
    {
        $seeders = $this->getSeeders();

        echo "<pre>";
        echo "Running {$seeders->count()} seeders...\n\n";

        $seeders->each(function(string $file) {
            echo "Seeder $file...";
            require root() . $this::seedersFolder . '/' . $file;
            echo "Done.\n";
        });

        echo "All seeders have been executed.<br>\n";
        
        echo "</pre>";
    }

    /**
     * Shows what is about to happen and shows a confirm link.
     */
    public function queryPurge()
    {
        try {
            $db = Db::getInstance();
        } 
        catch (Exception $ex) {
            dd("Unable to connect to db." . $ex->getMessage());
        }

        $tables = $this->getAllTables($db);

        echo "<h1>Warning!</h1><p>You are about to delete " . $tables->count() . " tables. Are you sure you want to continue?</p><br><br>";

        echo "<a href='" . route('migrator-purge') . "'>Purge</a>";
    }

    /**
     * Purges the database, dropping ALL tables.
     * 
     * **Warning! This WILL clear the database! Make SURE this is correct!!!!!!!**
     */
    public function purge()
    {
        echo "<pre>";

        try {
            $db = Db::getInstance();
        }
        catch (Exception $ex) {
            echo "Unable to connect to db." . $ex->getMessage() . "</pre>";
            exit;
        }

        $tables = $this->getAllTables($db);

        echo "Dropping " . $tables->count() . " Tables...\n\n";

        $db->raw('SET FOREIGN_KEY_CHECKS = 0');

        $tables->each(function($table) use($db) {
            echo "Dropping table $table...";
            $result = $db->raw('DROP TABLE IF EXISTS ' . $table);
    
            if (!$result) {
                echo $db->error() . "</pre>";
                dd($result);
            }
            echo " Ok!\n";
        });

        $db->raw('SET FOREIGN_KEY_CHECKS = 1');

        echo "Successfully dropped all tables and re-enabled foreign key checks!\n</pre>";
        
        echo "<a href='" . route('migrator-migrate') . "'>Migrate</a>";
    }

    /**
     * Checks the database, if it's available and if the migrations table is there. 
     * 
     * If not, make it.
     */
    private function checkDatabase()
    {
        try {
            $db = Db::getInstance();
        } 
        catch (Exception $ex) {
            dd("Unable to connect to db." . $ex->getMessage());
        }

        $result = $db->raw("SHOW TABLES LIKE '" . $this::migratorTable . "'");

        if ($result->num_rows === 1) {
            return;
        }

        // make the table since it doesn't exist.
        Schema::create($this::migratorTable, function(Blueprint $blueprint) {
            $blueprint->id();

            $blueprint->string('class');

            $blueprint->string('filename');

            $blueprint->timestamps();
        });

        echo "Migrations table has been created.\n\n";
    }

    /**
     * Returns a table with all database table names.
     * 
     * @param Db $db
     * 
     * @return Table
     */
    private function getAllTables(Db $db) : Table
    {
        $tables = table();

        $result = $db->raw('SHOW TABLES');

        if (!$result) {
            dd($result, 'Error checking tables!', $db->error());
        }

        while ($row = $db->row()) {
            foreach ($row as $table) {
                $tables->push($table);
            }
        }

        return $tables;
    }

    /**
     * This method looks into the /migrations folder and makes a list of all migrations
     *
     * @return Table
     */
    private function checkMigrationFiles() : Table
    {
        $migrations = table();

        // Get a list of the migration files folder
        $files = scandir(root() . $this::migrationFolder);

        foreach ($files as $file) {
            if (Str::endsWith($file, '.php')) {
                $migrations->push(substr($file, 0, -4));
            }
        }

        return $migrations;
    }

    /**
     * This method looks into the migrator table and returns a list of all run migrations.
     *
     * @return Table With all the migrations in the database.
     * @throws QueryException
     */
    private function getProcessedMigrations() : Table
    {
        $query = new Query($this::migratorTable);

        // this returns a base table
        return $query->order('created_at')
            ->order('id')
            ->get()
            ->pluck('filename');
    }

    /**
     * This method takes the migration file and builds a query
     * 
     * @param string $file
     * 
     * @return Migration
     */
    private function buildMigration(string $file) : Migration
    {
        // get the class from this file
        $parts = explode('_', $file);

        $class = Str::studly(implode("_", array_slice($parts, 2)));

        require_once root() . $this::migrationFolder . '/' . $file . '.php';

        return new $class;
    }

    /**
     * This method executes a migration query
     * 
     * @param Migration $migration
     * 
     * @return bool
     */
    private function executeMigration(Migration $migration) : bool
    {
        return $migration->up();
    }

    /**
     * This method executes a migration query
     * 
     * @param Migration $migration
     * 
     * @return bool
     */
    private function revertMigration(Migration $migration) : bool
    {
        return $migration->down();
    }

    /**
     * Gets a list of all seeder php files.
     */
    private function getSeeders() : Table
    {
        $files = scandir(root() . $this::seedersFolder);

        $seeders = table();

        foreach ($files as $file) {
            if (Str::endsWith($file, '.php')) {
                $seeders->push($file);
            }
        }

        return $seeders;
    }
}