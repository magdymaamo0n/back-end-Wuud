<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class socialAuthController extends Controller
{
    public function redirectToProvider()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleCallback(\Illuminate\Http\Request $request)
    {
        try {
            $google_user = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $google_user->email)->first();

            $nameParts = explode(' ', $google_user->getName(), 2);

            $firstName = $nameParts[0] ?? 'Google'; // بياخد الاسم الأول (Magdy)
            $lastName = $nameParts[1] ?? 'User';   // بياخد باقي الاسم (Elbaz)

            $data = [
                'google_id'    => $google_user->id,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'google_token' => substr($google_user->token, 0, 200),
                'password'     => $user ? $user->password : bcrypt(Str::random(24)),
            ];

            // 🎯 التريكة السحرية: حقل الـ role يتحدد "فقط" لو المستخدم لسه جديد ومش موجود في السيستم
            if (!$user) {
                $data['role'] = 'user';
            }

            // 3️⃣ الإنشاء أو التحديث الآمن
            $user = User::updateOrCreate(
                ['email' => $google_user->email],
                $data
            );

            // 4️⃣ توليد التوكن
            $token = $user->createToken('access_token')->accessToken;

            return response()->json([
                'status'       => 'success',
                'user'         => $user,
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Google authentication failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
