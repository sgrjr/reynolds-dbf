<?php namespace Sreynoldsjr\ReynoldsDbf\QueryBuilder;

use Illuminate\Database\PDO\MySqlDriver;
use Illuminate\Database\Query\Grammars\MySqlGrammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Schema\MySqlSchemaState;
use Illuminate\Filesystem\Filesystem;
use PDO;
use Sreynoldsjr\ReynoldsDbf\QueryBuilder\DbfProcessor;
use Sreynoldsjr\ReynoldsDbf\QueryBuilder\DbfBuilder as QueryBuilder;

class DbfConnection
{

    public $queryGrammar;
    public $postProcessor;
        /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Determine if the connected database is a MariaDB database.
     *
     * @return bool
     */
    public function isMaria()
    {
        return str_contains($this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION), 'MariaDB');
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\MySqlBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new DbfBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\MySqlGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the schema state for the connection.
     *
     * @param  \Illuminate\Filesystem\Filesystem|null  $files
     * @param  callable|null  $processFactory
     * @return \Illuminate\Database\Schema\MySqlSchemaState
     */
    public function getSchemaState(Filesystem $files = null, callable $processFactory = null)
    {
        return new MySqlSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\MySqlProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new DbfProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Illuminate\Database\PDO\MySqlDriver
     */
    protected function getDoctrineDriver()
    {
        return new MySqlDriver;
    }

     /**
     * Get the query grammar used by the connection.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

     /**
     * Get the query post processor used by the connection.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }
}
