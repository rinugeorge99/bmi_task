<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Treasurer;
use App\Models\Bmi_id;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TreasurerController extends Controller
 {
    public function insert( Request $request )
 {
        // $c = new NotificationController();
        $select = Treasurer::where( 'bmi_id', '=', $request->bmi_id )
        ->where( 'ending_date', '=', null )
        ->get();

        if ( count( $select ) > 0 ) {
            foreach ( $select as $key => $value ) {
                if ( $value->treasurer == 1 ) {
                    return [
                        'code' => 401,
                        'message' => 'Already a treasurer',
                    ];
                }

                if ( $value->treasurer == 0 ) {
                    return [
                        'code' => 401,
                        'message' => 'Already a joint treasurer',
                    ];
                }
            }
        }
        // $f = new FundCollectorController();
        // $isFundCollector = $f->checkFundCollector( $request->bmi_id );
        // if ( $isFundCollector ) {
        //     return [ 'code' => 401, 'message' => 'Already a Fund Collector' ];
        // }

        $updateStatus = Treasurer::where( 'treasurer', '=', $request->treasurer )
        ->where( 'status', '=', 0 )
        ->update( [
            'ending_date' => $request->starting_date,
            'status' => 1,
        ] );

        $insert = Treasurer::create( [
            'bmi_id' => $request->bmi_id,
            'starting_date' => $request->starting_date,
            // 'ending_date'=>$request->ending_date,

            'treasurer' => $request->treasurer,
            'status' => 0,
        ] );
        if ( $insert ) {

            // $select = Bmi_id::where( 'status', '=', 0 )->pluck( 'id' );
            // $date = Carbon::now()->format( 'Y-m-d' );
            // $getTreasurerDetails = Bmi_id::where( 'id', '=', $request->bmi_id )->get()->first();
            // $content = 'The Treasurer of the Organization changed by super admin.New treasurer is '.$getTreasurerDetails->name.'('.$getTreasurerDetails->bmi_id.')';
            // $k = $c->insertNotification( $date, $content, $select );
            if ( $request->previous_bmi_id ) {
            }
            return [
                'code' => 200,
                'message' => 'inserted successfully ',
            ];
        } else {
            return [
                'code' => 401,
                'message' => 'something went wrong',
            ];
        }
    }

    public function loginTreasurer( Request $request )
 {
        $bmi_id = $request->input( 'bmi_id' );
        $password = $request->input( 'password' );

        $user = DB::table( 'treasurers' )
        ->join( 'bmi_ids', 'treasurers.bmi_id', '=', 'bmi_ids.id' )
        ->where( 'bmi_ids.bmi_id', '=', $bmi_id )
        ->where( 'treasurers.ending_date', '=', null )
        ->where( 'treasurers.status', '=', 0 )
        ->get()
        ->first();

        if ( !$user ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
        if ( !Hash::check( $password, $user->password ) ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }

        return [ 'code' => 200, 'message' => 'login', 'data' => $user ];
    }

    public function getTreasurer()
 {
        $select = Treasurer::where( 'treasurers.status', '=', 0 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'treasurers.bmi_id' )
        ->get( [
            'bmi_ids.id as bmi_id',
            'bmi_ids.bmi_id as bmipno',
            'bmi_ids.name',
            'treasurers.starting_date',
            'treasurers.treasurer',
        ] );
        return $select;
    }

    public function checkTreasurer( $bmi_id )
 {
        $select = Treasurer::where( 'bmi_id', '=', $bmi_id )
        ->where( 'status', '=', 0 )
        ->get();
        if ( count( $select ) > 0 ) {
            return 1;
        } else {
            return 0;
        }
    }

    public function select( $userid )
 {
        $select = DB::table( 'treasurers' )
        ->join( 'bmi_ids', 'treasurers.bmi_id', '=', 'bmi_ids.id' )
        ->where( 'userid', '=', $userid )
        ->where( 'treasurers.ending_date', '=', null )
        ->get( [
            'bmi_ids.id',
            'bmi_ids.bmi_id',
            'treasurers.starting_date',
            'treasurers.ending_date',
            'treasurers.treasurer',
            'bmi_ids.status',

            'bmi_ids.name',
            'bmi_ids.email',
            'bmi_ids.contact',

            'bmi_ids.userid',
            'bmi_ids.joining_date',
        ] );
        return $select;
    }

}
