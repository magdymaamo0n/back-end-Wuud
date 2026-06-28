<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Storage;
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
        $validated = $request->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
            'country'  => 'required',
            'city'  => 'required',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|min:6',
            'role'       => 'required'
        ]);

        // باستخدام الموديل، هيرجعلك الـ Object وفيه الـ created_at تلقائي
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'country'  => $request->country,
            'city'  => $request->city,
            'email'      => $request->email,
            'role'       => $request->role,
            'password'   => Hash::make($request->password),
        ]);

        return response()->json([
            'user' => $user, // هنا الـ user راجع كـ object كامل بالبيانات والتاريخ
        ], 200);
    }

    // Edit User
    public function editUser(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'country' => 'required',
            'city' => 'required',
            'email' => 'required|email',
            'role' => 'required',
        ]);
        $user = User::findOrFail($id);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->country = $request->country;
        $user->city = $request->city;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->save();
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required',
        ]);

        $user = Auth::user();

        // 1. لو المستخدم رفع ملف حقيقي من جهازه
        if ($request->file('avatar')) {
            $file = $request->file('avatar');
            $fileName = time() . '.' . $file->getClientOriginalExtension();

            $file->storeAs('public/images', $fileName);
            $user->avatar = $fileName;
        }
        // 2. لو اختار صورة جاهزة (الاسم الصافي اللي جاي من عندك: avatar2.png)
        else {
            $avatarInput = $request->avatar;

            // لو جايلك الاسم الصافي علطول (زي avatar2.png) ومفهوش علامات اللينك
            if (!str_contains($avatarInput, 'http') && !str_contains($avatarInput, '/')) {
                $user->avatar = $avatarInput;
            } else {
                // لو جايلك مسار أو لينك بالصدفة، بنضفه كالأول
                $path = parse_url($avatarInput, PHP_URL_PATH);
                $user->avatar = basename($path);
            }
        }

        $user->save();

        // توليد الرابط الصحيح والمباشر للمتصفح
        $avatarUrl = url(Storage::url('avatars/' . $user->avatar));

        return response()->json([
            'message' => 'Avatar updated successfully!',
            'avatar_url' => $avatarUrl
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // عمل Validation قوي للبيانات
        $request->validate([
            'first_name' => 'required|string|max:55',
            'last_name'  => 'required|string|max:55',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'phone'      => 'nullable|string|unique:users,phone,' . $user->id,
        ]);

        // تحديث الداتا مباشرة
        $user->update([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'phone'      => $request->phone,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user'    => $user
        ], 200);
    }

    public function updateLocation(Request $request)
    {
        $user = Auth::user();

        // الـ Validation الخاص بالبلد والمدينة فقط
        $request->validate([
            'country' => 'required|string|max:100',
            'city'    => 'required|string|max:100',
        ]);

        // تحديث الحقول المطلوبة
        $user->update([
            'country' => $request->country,
            'city'    => $request->city,
        ]);

        return response()->json([
            'message' => 'Location updated successfully!',
            'user'    => [
                'country' => $user->country,
                'city'    => $user->city
            ]
        ], 200);
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
