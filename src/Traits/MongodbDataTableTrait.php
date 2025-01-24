<?php
namespace DiegoDrese\DataTables\Traits;

use DiegoDrese\DataTables\MongodbDataTable;

trait MongodbDataTableTrait
{
    /**
     * Get Mongodb DataTable instance for a model.
     *
     * @return MongodbDataTable
     */
    public static function dataTable()
    {
        return new MongodbDataTable(new static);
    }
}
