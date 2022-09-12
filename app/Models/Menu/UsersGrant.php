<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersGrant extends Model
{
    use HasFactory;
    protected $guarded = [];
    // protected $primaryKey = 'permission_id';
    protected $primaryKey = ['permission_id', 'employee_id'];
    public $incrementing = false;
}
