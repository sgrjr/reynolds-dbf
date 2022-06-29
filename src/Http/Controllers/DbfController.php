<?php namespace Sreynoldsjr\ReynoldsDbf\Http\Controllers;


use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class DbfController extends Controller
{

  protected function index(Request $request){
    return view('reynolds-dbf::dashboard',["message" => "This is a message."]);
  }

}
