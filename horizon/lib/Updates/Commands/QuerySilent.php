<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;
use Horizon\Database\Exception\DatabaseException;

class QuerySilent extends Command
{

    /**
     * @var string
     */
    protected $query;

    /**
     * @var array
     */
    protected $bindings;

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {
        if (count($args) == 0) {
            throw new CommandException(sprintf('Expected at least %d arguments, got %d in Query()', 1, 0));
        }

        $this->query = array_shift($args);
        $this->bindings = $args;
    }

    /**
     * Validates the data parsed from arguments.
     */
    protected function validate()
    {

    }

    /**
     * Executes the command.
     */
    public function execute()
    {
        $this->getLogger()->info('Executing query:', $this->query);
        $this->getLogger()->info('Using prepared statement:', (empty($this->bindings) ? 'No' : 'Yes'));

        if (!empty($this->bindings)) {
            $this->getLogger()->info('Prepared binding(s):', implode(', ', $this->bindings));
        }

        try {
            \Database::query($this->query, $this->bindings);
        }
        catch (DatabaseException $e) {
            $this->getLogger()->error('Query encountered an error:', $e->getMessage());
            $this->getLogger()->error('File:', $e->getFile(), 'Line:', $e->getLine());
            $this->getLogger()->error('Trace:', $e->getTraceAsString());
            $this->getLogger()->error('Ignoring error and continuing update.');
        }
    }

}