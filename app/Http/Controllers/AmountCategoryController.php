<?php

namespace App\Http\Controllers;
use App\Models\Amount_category;
use Illuminate\support\Facades\Validator;
use Illuminate\Http\Request;

class AmountCategoryController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( request()->all(), [
            'amount_type' => 'required',
            'code' => 'required',

        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials ' ];
        }
        $insert = Amount_category::create( [
            'amount_type' => $request->amount_type,
            'description' => $request->description,
            'code' => $request->code,
        ] );
        if ( $insert ) {
            return [
                'code' => 200,
                'message' => 'inserted successfully ',
            ];
        } else {
            return [ 'code' => 401, 'message' => 'something went wrong' ];
        }
    }

    public function update( Request $request )
 {
        $update = Amount_category::where( 'id', '=', $request->id )->update( [
            'amount_type' => $request->amount_type,
            'description' => $request->description,
            'code' => $request->code,
        ] );
        if ( $update ) {
            return [ 'code' => 200, 'message' => 'updated successfully' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function list()
 {
        $select = Amount_category::get();
        return $select;
    }

    public function delete( $id )
 {
        $delete = Amount_category::where( 'id', '=', $id )->delete( [] );

        if ( $delete ) {
            return [ 'code' => 200, 'message' => 'deleted' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }
}
