<?php

namespace App\Http\Controllers;

use DB;
use Hash;
use App\Models\User;
use App\Models\Customer;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    
    function __construct()
    {
         $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','store']]);
         $this->middleware('permission:user-create', ['only' => ['create','store']]);
         $this->middleware('permission:user-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }

   
    public function index(Request $request)
    {
        $data = User::orderBy('id', 'desc')->get();        
        return view('users.index', compact('data'));
    }

    
    public function create()
    {
        $roles = Role::pluck('name','name')->all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
            'roles' => 'required'
        ]);
    
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
    
        $user = User::create($input);
        $user->assignRole($request->input('roles'));
    
        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    
    public function show($id)
    {
        $user = User::find($id);

        return view('users.show', compact('user'));
    }

 
    public function edit($id)
    {
        $post = User::find($id);
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $post->roles->pluck('name', 'name')->all();
        return view('users.edit', compact('post', 'roles', 'userRole'));
    }

    
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'confirmed',
            'roles' => 'required'
        ]);
    
        $input = $request->all();
        
        if(!empty($input['password'])) { 
            $input['password'] = Hash::make($input['password']);
        } else {
            $input = Arr::except($input, array('password'));    
        }
    
        $user = User::find($id);
        $user->update($input);

        DB::table('model_has_roles')
            ->where('model_id', $id)
            ->delete();
    
        $user->assignRole($request->input('roles'));
    
        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        User::find($id)->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function statuschange(Request $request){
        $dt = $request->all();
        
        if($dt['status'] == 1){
            $status = 0;
        }else{
            $status = 1;   
        }
        
        User::where('id',$dt['id'])->update(array('status' => $status));
       
    }

  
     public function customers(){      
        return view('customer.index');
     }
     public function customerlist() {
        $industry = Customer::get();
        return datatables()->of($industry)
                        ->editColumn('created_at', '{{ date("d-m-Y", strtotime($created_at)) }}')
                       
                        ->rawColumns([
                            'business' => 'business',
                        ])
                        ->make(true);
    }
}