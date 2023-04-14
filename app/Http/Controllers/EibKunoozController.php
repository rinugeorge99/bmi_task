<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Eib_kunooz;
use App\Models\Bmi_id;
use App\Models\User_eib_kunooz;
use App\Models\Monthly_sip_details;
use Carbon\Carbon;

class EibKunoozController extends Controller
 {

    public function insert( Request $request ) {
        $c = new NotificationController();
        $validator = Validator::make( request()->all(), array(
            'date' =>  'required',
            'amount' =>  'required',
        ) );
        if ( $validator->fails() ) {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }

        $insert = Eib_kunooz::create( [
            'date'=>$request->date,
            'amount'=>$request->amount,
        ] );
        if ( $insert ) {
            $this->updateEibKunooz( $insert );
            $select = Bmi_id::where( 'status', '=', 0 )->where( 'verify', '=', 1 )->pluck( 'id' );
            $date = Carbon::now()->format( 'Y-m-d' );
            $content = 'eib_kunooz added by treasurer';
            $k = $c->insertNotification( $date, $content, $select, 1 );
            return [ 'code'=>200, 'message'=>'inserted' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }

    }

    public function updateEibKunooz( $data ) {

        $getBmiUsers = Bmi_id::where( 'status', '=', 0 )->where( 'verify', '=', 1 )->get();
        $getTotalMonthlySip = Monthly_sip_details::sum( 'amount' );
        foreach ( $getBmiUsers as $key=>$value ) {
            $getSip = Monthly_sip_details::where( 'bmi_id', '=', $value->id )->sum( 'amount' );

            $percentage = ( $getSip/$getTotalMonthlySip )*100;
            $amt = ( $data->amount*$percentage )/100;
            $addUserExpense = User_eib_kunooz::create( [
                'bmi_id'=>$value->id,
                'eib_kunooz_id'=>$data->id,
                'amount'=>$amt,
                'date_of_payment'=>$data->date
            ] );
        }

    }

    public function update( Request $request ) {
        $insert = Eib_kunooz::where( 'id', '=', $request->id )->update( [
            'date'=>$request->date,
            'amount'=>$request->amount,

        ] );
        if ( $insert ) {
            return [ 'code'=>200, 'message'=>'updated' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
    }

    public function list()
 {
        $select = Eib_kunooz::get();
        return $select;
    }

}
