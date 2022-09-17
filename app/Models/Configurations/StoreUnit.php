<?php

namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreUnit extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $primaryKey = 'unit_id';
    public $timestamps = false;

    public function getLabelAttribute()
    {
        return "{$this->unit_name_en} - {$this->unit_name_ar}";
    }

    public function getValueAttribute()
    {
        return $this->unit_id;
    }

    protected $appends = ['label', 'value'];
}
