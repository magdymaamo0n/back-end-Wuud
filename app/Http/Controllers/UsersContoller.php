<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersContoller extends Controller
{
    public function GetUsers(Request $request)
    {
        $users = User::paginate($request->input('limit', 10));
        return $users;
    }
    // Get Auth User
    public function authUser()
    {
        return Auth::user();
    }

    // Get Specific User
    public function getUser($id)
    {
        return User::findOrFail($id);
    }

    // Add User

    public function addUser(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);
        $user =  DB::table('users')->insert([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);
        return response()->json([
            'user' => $user,
        ], 200);
    }

    // Edit User
    public function editUser(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'role' => 'required',
        ]);
        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->save();
    }

    public function editProfile(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $user = Auth::user();
        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/avatars', $fileName);
            $user->avatar = $fileName;
        }

        $user->save();
        return response()->json($user);
    }


    // Search On Users
    public function search(Request $request)
    {
        // بنجيب كل البيانات اللي مبعوتة عشان نعرف الـ Key اسمه إيه
        $data = $request->all();

        // بنشيل التاريخ عشان نعرف نجيب الكلمة اللي بنبحث بيها (سواء اسمها name أو title أو غيره)
        $date = $request->input('date');
        $searchWord = collect($data)->except(['date'])->first();

        $query = User::query(); // أو Product حسب الصفحة

        // الشرط الحاسم:
        // لو فيه كلمة بحث، طبق الفلترة فوراً
        if (!empty($searchWord)) {
            $query->where('name', 'LIKE', '%' . $searchWord . '%');
        }

        // لو فيه تاريخ، طبق فلترة التاريخ
        if (!empty($date)) {
            $query->whereDate('created_at', $date);
        }

        // لو الخانتين فاضيين، رجع مصفوفة فاضية عشان ميعرضش كل البيانات
        if (empty($searchWord) && empty($date)) {
            return response()->json([]);
        }

        $results = $query->get();

        return response()->json($results);
    }

    // Delete User
    public function destroy($id)
    {
        return  User::findOrFail($id)->delete();
    }
}
