<?php

namespace App\Http\Controllers;

use App\Models\Employee\Employee;
use App\Models\Menu\Menu;
use App\Models\Menu\UsersGrant;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function getAll(Request $request)
    {
        $query = Employee::query();
        if ($request->input('keyword') != 'null') {
            $keyword = $request->input('keyword');
            $query->whereRaw("name LIKE '%" . $keyword . "%'")
                ->orWhereRaw("address_line_1 LIKE '%" . $keyword . "%'")
                ->orWhereRaw("email LIKE '%" . $keyword . "%'")
                ->orWhereRaw("username LIKE '%" . $keyword . "%'")
                ->orWhereRaw("mobile LIKE '%" . $keyword . "%'");
        }

        if ($request->input('sortitem') != 'null') {
            $query->orderBy($request->input('sortitem'), $request->input('sortdir'));
        }

        $page = $request->input('page', 1);
        $per_page = $request->input('size') ? $request->input('size') : 10;

        // $total = $query->count();
        $result = $query->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'data' => $result,
        ], 200);
    }

    public function get_all_employees_list(Request $request)
    {
        $result = Employee::select('name', 'email', 'employee_id as account_id')->get()->makeVisible('employee_id')->toArray();

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //search_employees by  name
    public function search_employees(Request $request)
    {
        $query = Employee::query();
        $keyword = $request->input('query');
        $query->whereRaw("name LIKE '%" . $keyword . "%'")
            ->orWhereRaw("mobile LIKE '%" . $keyword . "%'")
            ->orWhereRaw("email LIKE '%" . $keyword . "%'");

        $result = $query->get();
        $result->makeVisible('employee_id');

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //get employee by id
    public function get_employee_by_id(Request $request)
    {
        $employee = Employee::find(decrypt($request->input('employee')));
        $employee->makeVisible('username')->toArray();
        //$employee['status'] = ($employee['status'] === 1) ? true : false;
        $employee['permissions'] = $this->convert_permissions_for_edit(decrypt($request->input('employee')));

        return response()->json([
            'data' => $employee,
        ], 200);
    }

    //get new User Permissions
    public function get_new_employee()
    {
        $permissions = $this->convert_permissions_for_edit();
        return response()->json([
            'data' => $permissions,
        ], 200);
    }

    public function save_employee(Request $request)
    {
        $values = $request->all();
        $employee_id = isset($values['employeeId']) ? decrypt($values['employeeId']) : null;
        try {
            $data = [
                'name' => $request->input('name'),
                'mobile' => $request->input('mobile'),
                'email' => $request->input('email'),
                'address_line_1' => $request->input('address_line_1'),
                'username' => $request->input('username'),
                'status' => 1,
                'lang' => $request->input('lang'),
                'comments' => $request->input('comments'),
            ];

            if ($request->input('password')) {
                $data['password'] = bcrypt($request->input('password'));
            }

            $saved_employee = Employee::updateOrCreate([
                'employee_id' => $employee_id,
            ], $data);

            $permissions = $this->convert_edit_to_permissions($values, $saved_employee->employee_id);

            if ($saved_employee->employee_id) {
                UsersGrant::where('employee_id', $saved_employee->employee_id)->delete();
                UsersGrant::insert($permissions);
            }

            return response()->json([
                'error' => false,
                'message' => "customers.new_customer_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => true,
                'message' => "customers.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    private function convert_edit_to_permissions($values, $employee_id)
    {
        //$employee_id = decrypt($employee_id);
        $permissions = array();
        foreach ($values['permissions'] as $item) {
            if ($item['active']) {
                $permissions[] = [
                    "permission_id" => $item['permission_id'],
                    "employee_id" => $employee_id,
                ];
            }

            if (isset($item['submenus'])) {
                if (count($item['submenus']) > 0) {
                    foreach ($item['submenus'] as $sub_item) {
                        if ($sub_item['active']) {
                            $permissions[] = [
                                "permission_id" => $sub_item['item'],
                                "employee_id" => $employee_id,
                            ];
                        }
                    }

                }
            }

            if (count($item['basic']) > 0) {
                foreach ($item['basic'] as $basic_item) {
                    if ($basic_item['active']) {
                        $permissions[] = [
                            "permission_id" => $basic_item['item'],
                            "employee_id" => $employee_id,
                        ];
                    }
                }

            }

        }

        return $permissions;
    }

    private function convert_permissions_for_edit($employee_id = null)
    {
        //fetch all menu item from database
        $app_menu = Menu::with('submenu', 'basicmenu')->orderBy('editorder')->get()->toArray();

        if ($employee_id) {
            //fetch all allowed permissions as array from database then convert natural array to laravel collection
            $user_grants = collect(UsersGrant::where('employee_id', $employee_id)->select('permission_id')->pluck('permission_id')->all());
        }

        //creating new menu items
        $generated_menu = array();
        foreach ($app_menu as $menu) {
            $tmp_menu = [
                'item' => $menu['menu_id'],
                'permission_id' => $menu['permission_id'],
                'active' => $employee_id ? ($user_grants->search($menu['permission_id']) ? true : false) : true,
            ];
            if (count($menu['submenu']) > 0) {
                $tmp_submenu = array();
                foreach ($menu['submenu'] as $submenu) {
                    $tmp_submenu[] = [
                        'item' => $submenu['permission_id'],
                        'active' => $employee_id ? ($user_grants->search($submenu['permission_id']) ? true : false) : true,

                    ];
                }
                $tmp_menu['submenus'] = $tmp_submenu;
            }
            if (count($menu['basicmenu']) > 0) {
                $tmp_basicmenu = array();
                foreach ($menu['basicmenu'] as $basicmenu) {
                    $tmp_basicmenu[] = [
                        'item' => $basicmenu['permission_id'],
                        'active' => $employee_id ? ($user_grants->search($basicmenu['permission_id']) ? true : false) : true,
                    ];
                }
                $tmp_menu['basic'] = $tmp_basicmenu;
            }
            $generated_menu[] = $tmp_menu;
        };
        return $generated_menu;
    }

}
