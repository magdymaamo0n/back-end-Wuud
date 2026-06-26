<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrdersExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithStyles
{
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
            '+' . $order->phone . ' ',
            $order->country,
            $order->city
        ];
    }

    public function headings(): array
    {
        return ["Customer name", "Products", "Total", "Order date", "Phone Numer", "Customer country", "Customer City"];
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
