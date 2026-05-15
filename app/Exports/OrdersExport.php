<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping; // عشان نتحكم في شكل التاريخ
use Maatwebsite\Excel\Concerns\WithStyles;  // عشان التوسيط والشياكة
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class OrdersExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithStyles
{

    public function query()
{
    // هات فقط الطلبات اللي الحالة بتاعتها 'approved'
    // تأكد أن كلمة 'approved' مطابقة لما هو مخزن في قاعدة بياناتك
    return Order::query()->where('status', 'approved');
}

    public function collection()
    {
        return Order::where('status', 'confirmed')->get();
    }

    // 1. تنسيق التاريخ (بناخد الداتا ونعدلها قبل ما تنزل الشيت)
    public function map($order): array
    {
        return [
            $order->customer_name,
            $order->product_names,
            $order->total_price . ' EGP',
            $order->created_at->format('Y-m-d H:i'), // بيخلي التاريخ (سنة-شهر-يوم ساعة:دقيقة)
        ];
    }

    public function headings(): array
    {
        return ["اسم العميل", "المنتجات", "الإجمالي", "تاريخ الطلب"];
    }

    // 2. التوسيط وتظبط شكل المربعات
    public function styles(Worksheet $sheet)
    {
        return [
            // السطر الأول (العناوين) يبقا Bold وخلفية رمادي خفيفة
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EEEEEE']
                ]
            ],
            // تنسيق كل الخلايا في الشيت (التوسيط)
            'A1:Z1000' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER, // توسيط بالعرض
                    'vertical' => Alignment::VERTICAL_CENTER,     // توسيط بالطول
                ],
            ],
        ];
    }
}
