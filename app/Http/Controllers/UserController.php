<?php
namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $users;

    public function __construct(UserService $users)
    {
        $this->users = $users;
    }

    public function index()
    {
        return response()->json($this->users->all());
    }

    public function store(Request $request)
    {
        $user = $this->users->create($request->all());
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = $this->users->update($id, $request->all());

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function destroy($id)
    {
        $deleted = $this->users->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['message' => 'Deleted']);
    }
}