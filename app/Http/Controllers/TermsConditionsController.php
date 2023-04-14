<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Terms_conditions;
use App\Models\Bmi_id;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TermsConditionsController extends Controller
 {
    public function insert( Request $request ) {

        $validator = Validator::make( request()->all(), array(
            'description' =>  'required',

        ) );
        if ( $validator->fails() ) {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
        $insert = Terms_conditions::get()->first();
        if ( is_null( $insert ) ) {
            $insert = Terms_conditions::create( [

                'description'=>$request->description,

            ] );
            if ( $insert ) {
                return [ 'code'=>200, 'message'=>'inserted' ];
            } else {
                return [ 'code'=>401, 'message'=>'Something went wrong' ];
            }

        } else {
            return [ 'code'=>401, 'message'=>'value allready exist' ];

        }
    }

    public function update( Request $request ) {
        $c = new NotificationController();
        $update = Terms_conditions::where( 'id', '=', $request->id )->update( [
            'description'=>$request->description,

        ] );
        if ( $update ) {

            $update1 = Bmi_id::where( 'accept_terms_condition', '=', 1 )->update( [
                'accept_terms_condition'=>0, ] );

                if ( $update1 ) {
                    $select = Bmi_id::where( 'status', '=', 0 )->where( 'verify', '=', 1 )->pluck( 'id' );
                    $date = Carbon::now()->format( 'Y-m-d' );

                    $content = 'Terms and Conditions updated';
                    $k = $c->insertNotification( $date, $content, $select, 1 );
                    return [ 'code'=>200, 'message'=>'updated successfully' ];
                }

            } else {
                return [ 'code'=>401, 'message'=>'Something went wrong' ];
            }
        }

        public function select() {
            $select = Terms_conditions::orderby( 'id', 'desc' )->get();
            return $select;
        }
    }
