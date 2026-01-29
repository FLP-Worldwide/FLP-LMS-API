<?php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * 🔹 LIST ROLES (Institute-wise)
     */
    public function index()
    {
        $roles = Role::
            latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $roles,
        ]);
    }

    /**
     * 🔹 CREATE ROLE
     */
    public function store(Request $request)
    {

        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Role created successfully',
            'data' => $role,
        ], 201);
    }

    /**
     * 🔹 UPDATE ROLE
     */
    public function update(Request $request, $id)
    {

        $role = Role::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $role->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'is_active' => $data['is_active'] ?? $role->is_active,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Role updated successfully',
            'data' => $role,
        ]);
    }

    /**
     * 🔹 DELETE ROLE
     */
    public function destroy($id)
    {

        $role = Role::findOrFail($id);

        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully',
        ]);
    }
}
