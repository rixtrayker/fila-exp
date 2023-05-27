<?php

namespace App\Helpers;

use Doctrine\DBAL\Schema\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\SimpleExcel\SimpleExcelReader;
use Str;
class ImportHelper {

    protected Collection $data;
    protected $modelClass;

    public function importMultipleFiles($modelClass, $files): bool
    {
        $this->modelClass = $modelClass;

        $this->readMultipleFiles($files);

        return $this->insertData($this->data);
    }
    
    public function importFile($modelClass, $file): bool
    {
        $this->modelClass = $modelClass;

        $this->readFile($file);

        return $this->insertData($this->data);
    }

    private function readMultipleFiles($files)
    {
        foreach($files as $file){
            $this->readFile($file);
        }
    }

    private function readFile($file)
    {
        $fileName = 'app/public/'.$file;
        $filePath = storage_path($fileName);
        $model = app($this->modelClass);

        $dataCollection = new Collection();

        SimpleExcelReader::create($filePath)
            ->headersToSnakeCase()
            ->getRows()
            ->filter(function($rowProperties)use($model){ return $model::importCriteria($rowProperties);})
            ->each(function (array $rowProperties) use ($model, $dataCollection){
                $rowProperties = $model::importCast($rowProperties);
                $dataCollection->push($rowProperties);
            });

        $this->data = $dataCollection;
    }

    private function insertData(Collection $data): bool
    {
        return app($this->modelClass)::insert($data->toArray());
    }
}
