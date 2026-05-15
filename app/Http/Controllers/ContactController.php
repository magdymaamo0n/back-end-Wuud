<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Validator;
use App\Mail\ContactReplyMail;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // 1. التاكد من البيانات (Validation)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. حفظ في قاعدة البيانات
        $contact = Contact::create($request->all());

        return response()->json([
            'message' => 'Your message has been received successfully, thank you for contacting us!',
            'data' => $contact
        ], 201);
    }

    public function index(Request $request)
    {
        // بياخد الـ limit من الـ URL، ولو مش موجود بيخليها 10
        $limit = $request->query('limit', 10);

        // لارفيل ذكي كفاية إنه بياخد رقم الـ page لوحده من الـ URL
        $messages = Contact::orderBy('created_at', 'desc')->paginate($limit);

        return response()->json($messages);
    }

    public function sendReply(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);

        $request->validate([
            'reply' => 'required|string|min:5'
        ]);

        $contact = Contact::findOrFail($id);

        try {
            // 1. إرسال الإيميل
            Mail::to($contact->email)->send(new ContactReplyMail($contact->name, $request->reply));

            // 2. تحديث حالة الرسالة في الداتابيز (اختياري بس مهم)
            $contact->update(['status' => 'replied']);

            return response()->json(['message' => "The reply was successfully sent to the customer's email!"]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Email sending failed: ' . $e->getMessage()], 500);
        }

        $contact->status = 'replied';
        $contact->save();

        return response()->json([
            'message' => 'تم الرد بنجاح',
            'contact' => $contact // بنرجع البيانات الجديدة
        ]);

        $contact->save();
    }

    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->json(['message' => 'تم حذف الرسالة بنجاح']);
    }
}
