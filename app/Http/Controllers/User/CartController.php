<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\User;
use App\Models\Stock;
use Stripe;
use App\Services\CartService;
use App\Jobs\SendThanksMail;
use App\Jobs\SendOrderedMail;


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
            $quantity = '';
            $quantity = Stock::where('product_id', $product->id)->sum('quantity');;
            if($product->pivot->quantity > $quantity ){
                return redirect()->route('user.cart.index');
                } else {
                    $lineItem = [
                        'name' => $product->name,
                        'description' => $product->description,
                        'amount' => $product->price,
                        'currency' => 'jpy',
                        'quantity' => $product->pivot->quantity,
                        ];
                        array_push($lineItems, $lineItem);
                }


        }
        // dd($lineItems);
        foreach($products as $product) {
            Stock::create([
                'product_id' =>$product->id,
                'type' =>\Constant::PRODUCT_LIST['reduce'],
                'quantity' =>$product->pivot->quantity * -1,
            ]);
        }

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [$lineItems],
            'mode' => 'payment',
            'success_url' => route('user.cart.success'),
            'cancel_url' => route('user.cart.cancel'),
        ]);

        // $publicKey = env('STRIPE_PUBRIC_KEY');
        // return view('user.checkout', compact('session', 'publicKey'));
        return redirect($session->url, 303);
    }

    public function success()
    {
        $items = Cart::where('user_id', Auth::id())->get();
        $products = CartService::getItemsInCart($items);
        $user = User::findOrFail(Auth::id());
        SendThanksMail::dispatch($products,$user);
        foreach($products as $product)
        {
        SendOrderedMail::dispatch($product, $user);
        }

        Cart::where('user_id' ,Auth::id())->delete();

        return redirect()->route('user.items.index');
    }

    public function cancel()
    {
        $user = User::findOrFail(Auth::id());
        foreach($user->products as $product){
            Stock::create([
            'product_id' => $product->id,
            'type' => \Constant::PRODUCT_LIST['add'],
            'quantity' => $product->pivot->quantity
            ]);
        }
        return redirect()->route('user.cart.index');

    }
}
