<?php

declare(strict_types=1);

namespace Commerce\Reports\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ReportsHubController extends Controller
{
    public function index(): View
    {
        return view('reports::admin.reports.index', [
            'reports' => [
                [
                    'title' => 'ยอดขายรายวัน',
                    'description' => 'สรุปยอดขายและจำนวนออเดอร์แยกตามวัน พร้อมเปรียบเทียบช่องทาง',
                    'route' => 'admin.reports.sales.index',
                    'icon' => 'chart-bar',
                ],
                [
                    'title' => 'รายการคำสั่งซื้อ',
                    'description' => 'รายละเอียดคำสั่งซื้อทั้งหมดในช่วงเวลาที่เลือก',
                    'route' => 'admin.reports.orders.index',
                    'icon' => 'shopping-cart',
                ],
                [
                    'title' => 'สินค้าที่ขายได้',
                    'description' => 'สรุปสินค้า จำนวน และยอดขายตาม SKU',
                    'route' => 'admin.reports.products.index',
                    'icon' => 'cube',
                ],
            ],
        ]);
    }
}
