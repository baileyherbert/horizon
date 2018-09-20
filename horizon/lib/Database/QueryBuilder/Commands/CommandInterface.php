<?php

namespace Horizon\Database\QueryBuilder\Commands;

use Horizon\Database\QueryBuilder;

interface CommandInterface
{

    /**
     * Constructs a new command instance.
     *
     * @param QueryBuilder $builder
     */
    public function __construct(QueryBuilder $builder);

}