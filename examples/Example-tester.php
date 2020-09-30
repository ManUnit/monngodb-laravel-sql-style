<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Products ;

class Tester extends Controller
{
    //
    public function index() {
    

         // @@  1 test all()
         // $test = Products::collection()->all();
         // @@  2 test get()
         
         // $test = Products::collection()->select('*')->get();
         // @@  3  test where()

         // $test = Products::collection()->select('*')->where('id','=',7)->get();
         // @@  4  test like()
         // $test4 = Products::collection()->select('*')->where('image','like','%head%')->get();

         // @@  5 test first()
        //  $test = Products::collection()->select('*')->where('image','like','%หัว%')->first();
        // @@  6  test where andwhere
        //  $test = Products::collection()->select('*')->where('image','like','%หัว%')
        //                                              ->andwhere('code','=','CHT')
        //                                              ->get();
        // @@  7  test where andwhere orwhere
        // $test = Products::collection()->select('*')->where('image','like','%หัว%')
        //                                              ->andwhere('code','=','CHT')
        //                                              ->orwhere('price','<',100)
        //                                              ->get();
        // @@  8  test AS 
        // $test = Products::collection()->select('id as pid','image as im')->where('image','like','%หัว%')
        //                                              ->andwhere('code','=','CHT')
        //                                              ->orwhere('price','<',100)
        //                                              ->get();
        // @@  9 test orderby () ASC and DESC
        // $test = Products::collection()->select('id as pid','image as im')->where('image','like','%หัว%')
        //                                               ->andwhere('code','=','CHT')
        //                                               ->orwhere('price','<',100)
        //                                               ->orderby('id','desc')
        //                                               ->get();
        //    
        // @@ 10 test groupby()
        // $test = Products::collection('reviews')->select('user_id as uid')
        //                                               ->where('ratting','>',3)
        //                                               ->groupby('user_id')
        //                                               ->orderby('user_id')
        //                                               ->get();
        //
        // @@ 11 test follow selecting  groupby('$selected')
        // $test = Products::collection('reviews')->select('user_id as uid' , 'id  as pid')
        //                                               ->where('ratting','>',3)
        //                                               ->groupby('$selected')
        //                                               ->orderby('user_id')
        //                                               ->get() ; 
        //
        // @ 12 test limit()
        // $test = Products::collection('reviews')->select('user_id as uid' , 'id  as pid')
        //                                               ->where('ratting','>',3)
        //                                               ->groupby('$selected')
        //                                               ->orderby('user_id')
        //                                               ->limit(2)
        //                                               ->get() ;
        //
        //  @@ 13 test paginate()
        // $test = Products::collection('products')->select('id as pid','image as im')->where('image','like','%หัว%')
        //                                               ->andwhere('code','=','CHT')
        //                                               ->orwhere('price','<',100)
        //                                               ->orderby('id','desc')
        //                                               ->paginate(5);
        //
        //  @@ 14 test leftjoin()
        // $test = Products::collection('reviews')->select('products.id as pid','products.image as pim','reviews.id as rid','reviews.product_id as rpid')
        //                                               ->leftjoin('products','reviews.product_id','products.id')
        //                                               ->where('reviews.product_id','=',15)
        //                                               ->orderby('reviews.id','desc')
        //                                               ->groupby('$selected')
        //                                               ->get();