<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
    //protected $primaryKey = 'permission_id';
    protected $keyType = 'string';

    public function submenu()
    {
        return $this->hasMany(SubMenu::class, 'menu_id', 'menu_id');
    }

    public function basicmenu()
    {
        return $this->hasMany(BasicMenu::class, 'menu_id', 'menu_id');
    }

}
