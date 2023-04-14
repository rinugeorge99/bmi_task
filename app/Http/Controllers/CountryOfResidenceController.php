<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country_of_residence;
use Illuminate\Support\Facades\Validator;

class CountryOfResidenceController extends Controller
 {
    public function insert( Request $request ) {

        $validator = Validator::make( request()->all(), array(
            'type' =>  'required',

        ) );
        if ( $validator->fails() ) {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }

        $insert = Country_of_residence::create( [

            'type'=>$request->type,
            'bank_status'=>$request->bank_status,
        ] );
        if ( $insert ) {
            return [ 'code'=>200, 'message'=>'inserted' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
    }

    public function getAll() {
        $select = Country_of_residence::get();
        return $select;
    }
}
