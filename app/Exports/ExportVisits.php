<?php

namespace App\Exports;

use App\Models\Visit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;


class ExportVisits implements FromCollection
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
    private $visited;
    private $pending;
    private $missed;

    public function collection()
    {
        $max = max([count($this->visited),count($this->pending),count($this->missed)]);
        $result = [];
        for($i=0;$i < $max;$i++){
            $result[]= [
                count($this->visited) - $i > 0 ? $this->visited[$i]?->client?->name : null,
                count($this->visited) - $i > 0 ? $this->visited[$i]->visit_date->format('Y-m-d') : null,
                count($this->pending) - $i > 0 ? $this->pending[$i]?->client?->name : null,
                count($this->pending) - $i > 0 ? $this->pending[$i]->visit_date->format('Y-m-d') : null,
                count($this->missed) - $i > 0 ? $this->missed[$i]?->client?->name : null,
                count($this->missed) - $i > 0 ? $this->missed[$i]->visit_date->format('Y-m-d') : null,
            ];
        }
        return collect($result);
    }
    public function __construct($visited,$pending,$missed)
    {
        $this->visited = $visited;
        $this->pending = $pending;
        $this->missed = $missed;
    }
}
