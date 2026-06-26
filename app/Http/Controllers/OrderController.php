<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;

class OrderController extends Controller
{
    public function export()
    {
        if (ob_get_level() > 0) ob_end_clean();

        // غيرنا اسم الملف لـ approved_orders عشان الإدارة تعرف الفرق
        return Excel::download(new OrdersExport, 'approved_orders.xlsx');
    }

    public function changeStatus(Request $request, $id)
    {
        try {
            // 1. البحث عن الأوردر
            $order = \App\Models\Order::with('products')->findOrFail($id);

            // 2. لو الحالة الجديدة "canceled" والأوردر مكنش ملغي أصلاً
            // كدة بننادي الدالة اللي إنت بعتها عشان نرجع البضاعة للمخزن
            if ($request->status === 'canceled' && $order->status !== 'canceled') {
                $this->restoreStock($order);
            }

            // 3. تحديث الحالة (سواء confirmed أو canceled أو غيره)
            $order->status = $request->status;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة بنجاح، والمبيعات هتتحدث في الداشبورد'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function myOrders(Request $request)
    {
        $user = auth()->user();

        // 1. استلام الحالة (ممكن تكون all أو pending أو confirmed)
        $status = $request->input('status');
        $limit = $request->input('limit', 5);

        // 2. نبدأ الاستعلام الأساسي (هات أوردرات اليوزر ده)
        $query = Order::where('user_id', $user->id);

        // 3. الشرط اللي إنت عايزه:
        // لو الـ status مبعوت "و" مش قيمته "all"
        if ($request->has('status') && $status !== 'All') {
            $query->where('status', $status);
        }

        // 4. جلب البيانات النهائية مع التقسيم (Pagination)
        $orders = $query->with('products.images')
            ->latest()
            ->paginate($limit);

        // 5. الـ Transform عشان الصورة تكون "مباشرة"
        $orders->getCollection()->transform(function ($order) {
            $order->image = $order->products->first()?->images->first()?->image;
            unset($order->products);
            return $order;
        });

        return response()->json($orders);
    }

    public function userCancelOrder($id)
    {
        $user = auth()->user();
        $order = Order::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'عذراً، الطلب قيد التنفيذ ولا يمكن إلغاؤه'], 403);
        }

        // ننادي الدالة قبل ما نغير الحالة
        $this->restoreStock($order);

        $order->status = 'canceled';
        $order->save();

        return response()->json(['success' => true, 'message' => 'تم إلغاء الطلب بنجاح']);
    }

    public function store(Request $request)
    {
        try {
            // 1. عمل Validation للتأكد إن البيانات دي مبعوتة ومظبوطة
            $request->validate([
                'customer_name' => 'required|string|max:255',
                'total_price'   => 'required|numeric',
                'phone'         => 'required|string|max:20', // إجباري للأوردر
                'country'       => 'required|string|max:100', // إجباري للأوردر
                'city'          => 'required|string|max:100', // إجباري للأوردر
                'items'         => 'required|array',
            ]);

            $order = new Order();
            $order->customer_name = $request->customer_name;
            $order->total_price = $request->total_price;
            $order->status = 'pending';
            $order->user_id = auth()->id() ?? 1;

            // 🔥 الخطوة الجديدة: حفظ بيانات الشحن والتواصل جوه الأوردر
            $order->phone = $request->phone;
            $order->country = $request->country;
            $order->city = $request->city;

            // قيمة فاضية مؤقتاً للـ Validation
            $order->product_names = "";
            $order->save();

            $allNames = "";
            foreach ($request->items as $item) {
                $product = Product::find($item['id']);
                if ($product) {
                    $allNames .= $product->title . " (x" . $item['count'] . "), ";

                    // ربط المنتجات في الـ Pivot Table
                    $order->products()->attach($product->id, ['quantity' => $item['count']]);

                    // تنقيص المخزن
                    $product->decrement('stock', $item['count']);
                }
            }

            // تحديث الأوردر بالأسامي الحقيقية
            $order->update([
                'product_names' => rtrim($allNames, ", ")
            ]);

            return response()->json(['success' => true, 'order_id' => $order->id]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDashboardStats()
    {
        try {
            // 1. إجمالي المبيعات
            $totalSales = \App\Models\Order::where('status', 'confirmed')->sum('total_price');

            // 2. عدد الطلبات الكلي
            $ordersCount = \App\Models\Order::count();

            // 3. عدد المستخدمين
            $usersCount = \App\Models\User::count();

            // 4. طلبات اليوم
            $todayOrders = \App\Models\Order::where('status', 'confirmed')
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->count();

            // 5. طلبات قيد الانتظار
            $pendingOrders = \App\Models\Order::where('status', 'pending')->count();

            // 6. آخر 5 طلبات
            $recentOrders = \App\Models\Order::with('products.images') // لازم نحمل الصور
                ->latest()
                ->take(5)
                ->get();

            // تحويل الـ recentOrders عشان نطلع مفتاح image مباشر
            $recentOrders->transform(function ($order) {
                // سحب أول صورة من أول منتج في الأوردر
                $firstImage = $order->products->first()?->images->first()?->image;

                $order->image = $firstImage;

                // بنمسح مصفوفة المنتجات عشان الـ JSON يفضل خفيف زي ما طلبت
                unset($order->products);

                return $order;
            });

            // حساب الطلبات الملغية
            $canceledOrders = \App\Models\Order::where('status', 'canceled')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_sales'     => number_format($totalSales, 2),
                    'orders_count'    => $ordersCount,
                    'users_count'     => $usersCount,
                    'today_confirmed' => $todayOrders,
                    'pending_orders'  => $pendingOrders,
                    'recent_orders'   => $recentOrders, // دي دلوقتي فيها الـ image مباشر
                    'canceled_count'  => $canceledOrders,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function allOrdersAdmin(Request $request)
    {
        $status = $request->query('status');
        $limit  = $request->query('limit', 10);
        $search = $request->query('search');

        // 1. نبدأ بالاستعلام ونثبت الـ Eager Loading من الأول
        $query = \App\Models\Order::query()->with(['user', 'products.images']);

        // 2. فلتر الحالة
        if ($request->filled('status') && strtolower($status) !== 'all') {
            $query->where('status', $status);
        }

        // 3. السيرش (تعديل مهم: استخدمنا whereHas للسيرش في اليوزر)
        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('product_names', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // 4. الترتيب والـ Pagination
        $orders = $query->latest()->paginate($limit);

        // 5. الـ Transform (هنا بنثبت الداتا للـ Front)
        $orders->getCollection()->transform(function ($order) {
            // بنمسك الصورة بإيدنا
            $firstProduct = $order->products->first();
            $imagePath = null;

            if ($firstProduct && $firstProduct->images->isNotEmpty()) {
                $imagePath = $firstProduct->images->first()->image;
            }

            return [
                'id' => $order->id,
                'status' => $order->status,
                'total_price' => $order->total_price,
                'product_names' => $order->product_names,
                'customer_name' => $order->user ? $order->user->first_name . " " . $order->user->last_name : 'عميل غير معروف',
                'image' => $imagePath, // دي اللي الـ Front بينادي عليها
                'created_at' => $order->created_at,
            ];
        });

        return response()->json($orders, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $ids = $request->input('ids');
        $status = $request->input('status'); // هنستقبل هنا 'confirmed' أو 'canceled'

        // التأكد إن الحالة مبعوتة ومن ضمن الحالات المسموح بيها
        $allowedStatuses = ['confirmed', 'canceled', 'pending'];

        if (is_array($ids) && in_array($status, $allowedStatuses)) {
            Order::whereIn('id', $ids)->update(['status' => $status]);
            return response()->json(['status' => 'success', 'message' => "Orders updated to $status"]);
        }

        return response()->json(['status' => 'error', 'message' => 'Invalid data'], 400);
    }

    public function search(Request $request)
    {
        $customerName = trim($request->input('name'));
        $date = $request->input('date');

        if (!$customerName && !$date) {
            return response()->json([]);
        }

        // 1. تحميل كل العلاقات اللازمة (اليوزر + المنتجات وصورها)
        $query = Order::query()->with(['user', 'products.images']);

        if ($customerName) {
            $query->whereHas('user', function ($q) use ($customerName) {
                $q->where('name', 'LIKE', '%' . $customerName . '%');
            });
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $results = $query->latest()->get();

        // 2. نفس الـ Transform اللي عملناه في الدالة التانية عشان الصور تظهر
        $results->transform(function ($order) {
            $firstProduct = $order->products->first();
            $order->image = $firstProduct && $firstProduct->images->first()
                ? $firstProduct->images->first()->image
                : null;

            $order->customer_name = $order->user ? $order->user->name : 'عميل غير معروف';

            // مسح العلاقات عشان الـ JSON يكون خفيف
            unset($order->products);
            unset($order->user);

            return $order;
        });

        return response()->json($results, 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function restoreStock($order)
    {
        // لارفيل دلوقتي هيروح لجدول order_product ويشوف كل منتج وكميته كام ويرجعها
        foreach ($order->products as $product) {
            $product->increment('stock', $product->pivot->quantity);
        }
    }
}
