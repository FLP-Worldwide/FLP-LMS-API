<?php

namespace App\Http\Controllers;

use App\Models\Payer;
use Illuminate\Http\Request;

class PayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    { return Payer::all(); }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $request->validate([
            'display_name'=>'required',
            'contact_no'=>'required'
        ]);
        return Payer::create($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show($id){ return Payer::findOrFail($id); }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id){
        $data=Payer::findOrFail($id);
        $data->update($request->all());
        return $data;
    }

    public function destroy($id){
        Payer::findOrFail($id)->delete();
        return response()->json(['message'=>'Payer deleted']);
    }
}
