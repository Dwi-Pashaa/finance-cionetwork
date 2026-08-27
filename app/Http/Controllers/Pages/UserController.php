<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sort = $request->sort ?? 10;
        $search = $request->search ?? null;

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('username', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            })
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Investor');
            })
            ->orderBy('id', 'DESC')
            ->paginate($sort)
            ->withQueryString();


        return view("pages.user.index", compact("users"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $role = Role::where('name', '!=', 'Investor')->get();
        return view("pages.user.create", compact("role"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            "username" => "required|unique:users,username",
            "name" => "required|string",
            "email" => "required|unique:users,email",
            "role" => "required",
            "password" => "required|string|min:8|confirmed",
            "password_confirmation" => "required|string"
        ]);

        $post = $request->except('password_confirmation', 'role');
        $post['password'] = Hash::make($request->password);

        $user = User::create($post);
        $user->assignRole($request->role);

        return redirect()->route('user.index')->with('success', 'Berhasil menambahkan user baru.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $role = Role::where('name', '!=', 'Investor')->get();
        return view("pages.user.edit", compact("user", "role"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            "username" => "required|unique:users,username," . $user->id,
            "name" => "required|string",
            "email" => "required|unique:users,email," . $user->id,
            "role" => "required",
            "password" => "nullable|string|min:8|confirmed",
        ]);
    
        $updateData = $request->except('password', 'password_confirmation', 'role');
    
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }
    
        $user->update($updateData);
    
        $user->syncRoles([$request->role]);
    
        return redirect()->route('user.index')->with('success', 'Berhasil memperbarui user.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['code' => 400, 'status' => 'error', 'message' => 'Data Not Found.']);
        }

        $user->delete();

        return response()->json(['code' => 200, 'status' => 'success', 'message' => 'Berhasil menghapus data.']);
    }
}
