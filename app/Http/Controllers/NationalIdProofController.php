<?php

namespace App\Http\Controllers;
use App\Models\National_id_proof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class NationalIdProofController extends Controller
{
    public function insert( Request $request ) {


        $validator = Validator::make(request()->all(),array(
            'type' =>  'required',
            
        ));
        if($validator->fails()){
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
     
        $insert = National_id_proof::create( [

            'type'=>$request->type,
            'expiry_status'=>$request->expiry_status,
        ] );
        if ( $insert ) {
            return [ 'code'=>200, 'message'=>'inserted' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
    }
    public function get($id){
        $select=National_id_proof::where('id','=',$id)->get()->first();
        return $select;
    }
    public function getAll(){
        $select=National_id_proof::get();
        return $select;
    }
}
