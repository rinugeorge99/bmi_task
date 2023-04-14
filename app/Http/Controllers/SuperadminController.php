<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Superadmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class SuperadminController extends Controller
 {
    public function insert( Request $request ) {

        $validator = Validator::make( request()->all(), array(
            'username' =>  'required',
            'email' =>  'required|email',
            'password' =>  'required'
        ) );
        if ( $validator->fails() ) {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }

        $insert = Superadmin::get()->first();
        if ( is_null( $insert ) ) {
            $insert = Superadmin::create( [

                'username'=>$request->username,
                'email'=>$request->email,
                'password' => Hash::make( $request->password ),
                'userid' =>Str::uuid(),

            ] );
            if ( $insert ) {
                return [ 'code'=>200, 'message'=>'inserted' ];
            } else {
                return [ 'code'=>401, 'message'=>'Something went wrong' ];
            }

        } else {
            return [ 'code'=>401, 'message'=>'value already exist' ];

        }
    }

    public function update( Request $request ) {
        $insert = Superadmin::where( 'id', '=', $request->id )->update( [
            'username'=>$request->username,
            'email'=>$request->email,

        ] );
        if ( $insert ) {
            return [ 'code'=>200, 'message'=>'updated' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
    }

    public function delete( $id ) {
        $delete = Superadmin::where( 'id', '=', $id )->delete( [] );

        if ( $delete ) {
            return [ 'code'=>200, 'message'=>'deleted' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
    }

    public function passwordupdate( Request $request ) {
        $insert = Superadmin::where( 'id', '=', $request->id )->update( [
            'password' => Hash::make( $request->password ),
        ] );
        if ( $insert ) {
            return [ 'code'=>200, 'message'=>'updated' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
    }

    public function select( Request $request ) {
        $select = Superadmin::where( 'userid', '=', $request->userid )->get( [
            'id',
            'username as name',
            'email',
            'userid',
            'created_at',
            'updated_at'
        ] );
        return $select;
    }

    public function loginSuperAdmin( Request $request ) {
        $email = $request->input( 'email' );
        $password = $request->input( 'password' );

        $user = Superadmin::where( 'email', '=', $email )->get()->first();

        if ( !$user ) {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
        if ( !Hash::check( $password, $user->password ) ) {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
        $user = Superadmin::get( [
            'id',
            'username as name',
            'email',
            'userid',
            'created_at',
            'updated_at'
        ] );
        return [ 'code'=>200, 'message'=>'login', 'data' => $user ];
    }

}

