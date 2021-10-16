<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $itemInCart = Cart::where('user_id', Auth::id())
        ->where('product_id', $request->product_id)->first(); //カートに商品があるか確認
        if($itemInCart){
        $itemInCart->quantity += $request->quantity; //あれば数量を追加
        $itemInCart->save();
        } else {
        Cart::create([ // なければ新規作成
        'user_id' => Auth::id(),
        'product_id' => $request->product_id,
        'quantity' => $request->quantity
        ]);
        }
        return redirect()->route('user.cart.index');
    }
}
