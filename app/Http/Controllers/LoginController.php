<?php

namespace App\Http\Controllers;

use App\Models\Configurations\StockLocation;
use App\Models\Employee\Employee;
use App\Models\Menu\Menu;
use App\Models\Menu\UsersGrant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

//find
//where
//all
//where_in get 2 or more person (['1', '2'])
//->latest() //call last created item
//->oldes() //call oldest created item
//->skip(5) //skip items

class LoginController extends Controller
{
    public function doLogin()
    {
        //find spesified user from employee tabel
        $employee = Employee::where('username', request('username'))->first();
        if (Hash::check(request('password'), $employee->password)) {
            $token = $employee->createToken('for-api')->plainTextToken;
            //get selected data from store with store_id

            $store = StockLocation::limit(50)->orderBy('location_name_en', 'ASC')->find(request('store'));

            //fetch all menu item from database
            $collected_menu = Menu::with('submenu')->orderBy('order')->get()->toArray();

            //fetch all allowed permissions as array from database
            $user_grants = UsersGrant::where('employee_id', $employee->employee_id)->select('permission_id')->pluck('permission_id')->all();
            //convert natural array to laravel collection
            $collection = collect($user_grants);

            //Generate new menu items
            $generated_menu = array();
            foreach ($collected_menu as $menu) {
                if ($collection->search($menu['permission_id']) > -1) {
                    $tmp_menu = $menu;
                    if (count($tmp_menu['submenu']) > 0) {
                        $tmp_submenu = array();
                        foreach ($tmp_menu['submenu'] as $submenu) {
                            if ($collection->search($submenu['permission_id'])) {
                                $tmp_submenu[] = $submenu;
                            }
                        }

                        if (count($tmp_submenu) > 0) {
                            $tmp_menu['submenu'] = $tmp_submenu;
                            $generated_menu[] = $tmp_menu;
                        }
                    } else {
                        $generated_menu[] = $tmp_menu;
                    }
                }
            };

            $resp = [
                "storeID" => request('store'),
                "store" => $store,
                "display_name" => $employee->name,
                "Permissions" => $generated_menu,
            ];

            return response()->json([
                'auth' => true,
                'token' => $token,
                'info' => $resp,
                'message' => 'login Successfully',
            ], 200);
        } else {
            return response()->json([
                'auth' => false,
                'message' => 'login Failed',
            ], 401);
        }
    }

    public function getAllStores()
    {
        try {
            $stores = StockLocation::all('location_id', 'location_name_en', 'location_name_ar');
            return response()->json([
                'status' => 200,
                'stores' => $stores,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'status' => 200,
                'message' => 'logout successfully',
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }
}
