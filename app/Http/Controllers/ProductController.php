<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = [
        ['id' => 1, 'name' => '手机壳', 'sales' => '1000', 'store' => 'TikTok Store', 'price' => 9.99, 'reviews' => '120', 'rating' => 4.8],
        ['id' => 2, 'name' => '耳机', 'sales' => '500', 'store' => 'TEMU', 'price' => 19.99, 'reviews' => '80', 'rating' => 4.5],
    ];

        return view('product.list', compact('products'));
    }
}
