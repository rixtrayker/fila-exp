<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
trait CanImport {

    private static function getTableColumns(): array
    {
        $tableName = (new self())->getTable();
        return DB::getSchemaBuilder()->getColumnListing($tableName);
    }

    public static function getImportColumns(): array
    {
        return static::getTableColumns();
    }
    public static function importCast($rowProperties): array
    {
        foreach($rowProperties as $key => $value){
            if(static::importCasts($key) == 'date'){
                $carbonDate = Carbon::parse($value);
                $rowProperties[$key] = $carbonDate->format('Y-m-d');
            }
        }

        return $rowProperties;
    }
    protected static function importRequired($rowProperties): array
    {
        return [];
    }
    public static function importCriteria($rowProperties): bool
    {
        $requiredFields = static::importRequired();

        foreach ($requiredFields as $field) {
            if (!isset($rowProperties[$field])) {
                return false;
            }
        }

        return true;
    }
}
