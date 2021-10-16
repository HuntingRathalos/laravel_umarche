<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\User;

class CartController extends Controller
{
    public function index()
    {
        $user = User::findOrFail(Auth::id());
        $products = $user->products; // 多対多のリレーション
        $totalPrice = 0;
        foreach($products as $product){
        $totalPrice += $product->price * $product->pivot->quantity;
        }

        return view('user.cart', compact('products', 'totalPrice'));
    }

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

    public function delete($id)
    {
        Cart::where('product_id', $id)
        ->where('user_id', Auth::id())->delete();
        return redirect()->route('user.cart.index');
    }

    public function checkout()
    {
        $user = User::findOrFail(Auth::id());
        $products = $user->products;

        $lineItems = [];
        foreach($products as $product) {
            $lineItem = [
                'name' => $product->name,
                'description' => $product->description,
                'amount' => $product->price,
                'currency' => 'jpy',
                'quantity' => $product->pivot->quantity,
                ];
                array_push($lineItems, $lineItem);
        }
        // dd($lineItems);

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [$lineItems],
            'modeʼ => ʻpayment',
            'success_url' => route('user.items.index'),
            'cancel_url' => route('cart.cart.index'),
        ]);

        $publicKey = env('STRIPE_PUBRIC_KEY');
        return view('user.checkout', compact('session', 'publicKey'));
    }
}
