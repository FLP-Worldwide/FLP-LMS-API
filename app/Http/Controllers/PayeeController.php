<?php

namespace App\Http\Controllers;

use App\Models\Payee;
use Illuminate\Http\Request;

class PayeeController extends Controller
{
    public function index(){ return Payee::all(); }

    public function store(Request $request){
        $request->validate([
            'display_name'=>'required',
            'contact_no'=>'required'
        ]);
        return Payee::create($request->all());
    }

    public function show($id){ return Payee::findOrFail($id); }

    public function update(Request $request,$id){
        $data=Payee::findOrFail($id);
        $data->update($request->all());
        return $data;
    }

    public function destroy($id){
        Payee::findOrFail($id)->delete();
        return response()->json(['message'=>'Payee deleted']);
    }
}
