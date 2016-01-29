<?php namespace App\Modules\Roles\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Modules\Roles\Models\Role;
use App\Modules\Roles\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Laracasts\Flash\Flash;
use Illuminate\Support\Facades\Auth;
use App\User;
use Validator;

class RolesController extends Controller {

    public function __construct()
    {
        $this->middleWare('auth', ['except' => ['generateRoles']]);
        $this->middleWare('role:root', ['except' => ['generateRoles']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {

        $roles      = Role::orderBy('name', 'asc')->get();
        return view("Roles::index", compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('Roles::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $validator      = Validator::make($request->all(), [
            'name' => 'required|unique:roles|max:50'
        ]);

        if($validator->fails()){
            flash()->error($validator->errors()->first());
            return redirect()->route('roles.create')->withInput()->withErrors($validator);
        }else{
            $role                   = new Role();
            $role->name             = $request->name;
            $role->display_name     = $request->display_name;
            $role->description      = $request->description;
            $role->save();

            flash()->success('Your data has been saved');
            return redirect()->route('roles.index');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id, Request $request)
    {
        $role       = Role::find($id);
        $role_users = $role->users;
        $users      = User::paginate(200);

        if($request->ajax()) {
            return response()->json(['data' => 'json']);
        }else{
            // $u  = User::where('name', '=', '+6285640427774')->first();
            // $u->attachRole($role);
            $title      = 'Detail Roles';
            return view('Roles::show')->with(compact('users', 'role_users', 'role', 'title'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $role       = Role::find($id);
        $title      = 'Edit Role';
        return view('Roles::edit')->with(compact('role', 'title'))->withInput($role);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id, Request $request)
    {
        $role       = Role::find($id);
        $role->name             = $role->name;
        $role->display_name     = $request->display_name;
        $role->description      = $request->description;

        if(!$role->save()){
            flash()->error('Error Occured when saving your data');
            return redirect()->route('roles.edit', ['id' => $id])->withInput();
        }else{
            flash()->success('Your data has been saved');
            return redirect()->route('roles.index');
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function generateRoles()
    {
        // find admin users
        $admin_user     = User::where('name', '=', 'administrator');

        $root           = new Role();
        $root->where('name', '=', 'root');
        if(!$root->exists()){
            $root->name                 = 'root';
            $root->display_name         = 'Super User'; // optional
            $root->description          = 'User is allowed to do everything'; // optional
            $root->save();
        }else{
            $root   = $root->where('name', '=', 'root')->first();
        }

        $admin      = new Role();
        $admin->where('name', '=', 'admin');
        if(!$admin->exists()){
            $admin->name                = 'admin';
            $admin->display_name        = 'User Administrator'; // optional
            $admin->description         = 'user is allowed to manage and edit other users data'; // optional
            $admin->save();
        }else{
            $admin      = $admin->where('name', '=', 'admin')->first();
        }

        $operator   = new Role();
        $operator->where('name', '=', 'operator');
        if(!$operator->exists()){
            $operator->name             = 'operator';
            $operator->display_name     = 'Operator'; // optional
            $operator->description      = 'User Is Only Allowed To Manage And Edit Their Data'; // optional
            $operator->save();
        }else{
            $operator       = $admin->where('name', '=', 'operator')->first();
        }

        $users      = new Role();
        $users->where('name', '=', 'users');
        if(!$users->exists()){
            $users->name             = 'users';
            $users->display_name     = 'End User'; // optional
            $users->description      = 'User is only allowed to manage and edit their data'; // optional
            $users->save();
        }else{
            $users      = $admin->where('name', '=', 'users')->first();
        }

        if($admin_user->exists() AND false === $admin_user->first()->hasRole([$admin->name], true)){
            $admin_user->first()->attachRole($admin);
        }

        if(Auth::check()){
            if(Auth::user()->hasRole('root')){
                flash()->success('Your Roles has been generated!!');
                return redirect()->route('roles.index');
            }else{
                Auth::logout();
                return redirect()->route('login');
            }
        }else{
            return redirect()->route('login');
        }
    }

    public function attachRole(Request $request)
    {
        $role       = Role::find($request->role_id);
        $user       = User::find($request->user_id);
        $user->attachRole($role);
        return response()->json(['data' => $request->all()]);
    }
}
