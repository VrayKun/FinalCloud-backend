<?php

namespace App\Http\Controllers;
use App\Models\Banner;
use App\Models\Product;
use App\Models\Category;
use App\Models\PostTag;
use App\Models\PostCategory;
use App\Models\Post;
use App\Models\Cart;
use App\Models\Brand;
use App\User;
use Auth;
use Session;
use Newsletter;
use DB;
use Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
class FrontendController extends Controller
{
   
    public function index(Request $request){
        $role = $request->user()->role;
        if($role=='admin'){
            return redirect()->route('admin');
        }
        else{
            return redirect()->with('error','You dont have permission to access this page!');
        }
        // return redirect()->route($request->user()->role);
    }

    public function home(){
        $featured=Product::where('status','active')->where('is_featured',1)->orderBy('price','DESC')->limit(2)->get();
        $posts=Post::where('status','active')->orderBy('id','DESC')->limit(3)->get();
        $banners=Banner::where('status','active')->limit(3)->orderBy('id','DESC')->get();
        // return $banner;
        $products=Product::where('status','active')->orderBy('id','DESC')->limit(8)->get();
        $category=Category::where('status','active')->where('is_parent',1)->orderBy('title','ASC')->get();
        // return $category;
        // return view('frontend.index')
        //         ->with('featured',$featured)
        //         ->with('posts',$posts)
        //         ->with('banners',$banners)
        //         ->with('product_lists',$products)
        //         ->with('category_lists',$category);
        return view('frontend.pages.login');
    }   

    // Login
    public function login(){
        return view('frontend.pages.login');
    }

    public function loginSubmit(Request $request){
        $data= $request->all();
        $user = User::where('email', $data['email'])->first();
    
        // Check if user exists and is active
        if($user && $user->status == 'active') {
            // Attempt to log the user in
            if(Auth::attempt(['email' => $data['email'], 'password' => $data['password']])){
                Session::put('user',$data['email']);
                $role = $request->user()->role;
                if($role=='admin'){
                    request()->session()->flash('success','Logged in successfully!');
                    return redirect()->route('admin');
                }
                else{
                    return redirect()->back()->with('error','You dont have permission to access this page!');
                }
            }
            else{
                request()->session()->flash('error','Invalid email and password pleas try again!');
                return redirect()->back();
            }
        } else {
            request()->session()->flash('error','Your account is not active!');
            return redirect()->back();
        }
    }
    // public function loginSubmit(Request $request){
    //     $data= $request->all();
    //     if(Auth::attempt(['email' => $data['email'], 'password' => $data['password'],'status'=>'active'])){
    //         Session::put('user',$data['email']);
    //         $role = $request->user()->role;
    //         if($role=='admin'){
    //             request()->session()->flash('success','Logged in successfully!');
    //             return redirect()->route('admin');
    //         }
    //         else{
    //             return redirect()->with('error','You dont have permission to access this page!');
    //         }
    //     }
    //     else{
    //         request()->session()->flash('error','Invalid email and password pleas try again!');
    //         return redirect()->back();
    //     }
    // }

    public function logout(){
        Session::forget('user');
        Auth::logout();
        request()->session()->flash('success','Logged out successfully');
        return back();
    }

    public function register(){
        return view('frontend.pages.register');
    }
    public function registerSubmit(Request $request){
        // return $request->all();
        $this->validate($request,[
            'name'=>'string|required|min:2',
            'email'=>'string|required|unique:users,email',
            'password'=>'required|min:6|confirmed',
        ]);
        $data=$request->all();
        // dd($data);
        $check=$this->create($data);
        Session::put('user',$data['email']);
        if($check){
            request()->session()->flash('success','Registered successfully');
            return redirect()->route('home');
        }
        else{
            request()->session()->flash('error','Please try again!');
            return back();
        }
    }
    public function create(array $data){
        return User::create([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=>Hash::make($data['password']),
            'status'=>'active'
            ]);
    }
    // Reset password
    public function showResetForm(){
        return view('auth.passwords.old-reset');
    }

    public function subscribe(Request $request){
        if(! Newsletter::isSubscribed($request->email)){
                Newsletter::subscribePending($request->email);
                if(Newsletter::lastActionSucceeded()){
                    request()->session()->flash('success','Subscribed! Please check your email');
                    return redirect()->route('home');
                }
                else{
                    Newsletter::getLastError();
                    return back()->with('error','Something went wrong! please try again');
                }
            }
            else{
                request()->session()->flash('error','Already Subscribed');
                return back();
            }
    }
    
}
