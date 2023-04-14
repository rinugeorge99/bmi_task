<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Zakat;
use App\Models\Bmi_id;
use App\Models\User_zakat;
use Carbon\Carbon;
use App\Models\Company_transaction;
use App\Models\User_profit;
use Illuminate\Support\Facades\DB;

class ZakatController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( request()->all(), [
            'date' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
            'profit' => 'required',
            'zakat' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }

        $insert = Zakat::create( [
            'date' => $request->date,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'profit' => $request->profit,
            'zakat' => $request->zakat,
            'zakat_details' => $request->zakat_details,
            'transferFromBank' => 0,
            'treasurer' => $request->treasurer,
        ] );
        if ( $insert ) {
            return [ 'code' => 200, 'message' => 'inserted' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function update( Request $request )
 {
        $insert = Zakat::where( 'id', '=', $request->id )->update( [
            'date' => $request->date,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'profit' => $request->profit,
            'zakat' => $request->zakat,
            'zakat_details' => $request->zakat_details,
            // 'transferFromBank' =>0,
            // 'transferFromBank_date' => $request->transferFromBank_date,
            'treasurer' => $request->treasurer,
        ] );
        if ( $insert ) {
            return [ 'code' => 200, 'message' => 'updated' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function list_zakat()
 {
        $v = new MonthlySipController;
        $select = DB::table( 'zakats' )
        ->join( 'treasurers', 'zakats.treasurer', '=', 'treasurers.bmi_id' )
        // ->where( 'treasurers.ending_date', '=', null )
        ->join( 'bmi_ids', 'treasurers.bmi_id', '=', 'bmi_ids.id' )

        ->get( [
            'zakats.id',
            'zakats.date',
            'zakats.from_date',
            'zakats.to_date',
            'zakats.profit',
            'zakats.zakat',
            'zakats.zakat_details',
            'zakats.treasurer',
            'treasurers.treasurer',
            'zakats.transferFromBank',
            'bmi_ids.name',
        ] );
        $l = $v->AmountAvalilableinBank();
        if ( $select->isEmpty() ) {
            return [ 'code' => 401, 'message' => 'no result found ' ];
        } else {

            return [ 'amount'=>$l, 'details'=>$select ];
        }
    }

    public function approving_zakat( Request $request )
 {

        $c = new NotificationController();
        $select = Zakat::where( 'id', $request->id )
        ->get()
        ->first();
        // return $select;
        if ( !$select ) {
            return [
                'code' => 401,
                'message' => 'This zakat transaction is not exist',
            ];
        }
        $update = Zakat::where( 'id', $request->id )->update( [
            'transferFromBank' => 1,
            'transferFromBank_date' => Carbon::now()->format( 'd-m-Y' ),

        ] );
        if ( $update ) {
            $this->updateZakat( $request->id );
            $select = Bmi_id::where( 'status', '=', 0 )->where( 'verify', '=', 1 )->pluck( 'id' );
            $date = Carbon::now()->format( 'Y-m-d' );
            $content = 'Zakat 2.5% debited from the account';
            $k = $c->insertNotification( $date, $content, $select, 1 );

            return [ 'code' => 200, 'message' => 'updated successfully ' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    //     public function updateZakat( $zakat_id )
    // {
    //         $getZakat = Zakat::where( 'id', '=', $zakat_id )
    //         ->get()
    //         ->first();
    //         $fromDate = Carbon::parse( $getZakat->from_date );
    //         $toDate = Carbon::parse( $getZakat->to_date );
    //         $getBmiUsers = Bmi_id::where( 'status', '!=', 3 )->where( 'verify', '=', 1 )->get();
    //         $companyTransaction = Company_transaction::where(
    //             'amount_categories.code',
    //             '=',
    //             5
    // )
    //         ->join(
    //             'amount_categories',
    //             'amount_categories.id',
    //             '=',
    //             'company_transactions.amount_cat_id'
    // )
    //         ->get( [ 'company_transactions.*' ] );
    //         $arr = [];
    //         if ( count( $companyTransaction ) > 0 ) {
    //             foreach ( $companyTransaction as $key => $value ) {
    //                 $date = Carbon::parse( $value->date );
    //                 if ( $fromDate <= $date && $date <= $toDate ) {
    //                     array_push( $arr, $value->id );
    //                 }
    //             }
    //         }

    //         if ( count( $arr ) > 0 ) {
    //             foreach ( $getBmiUsers as $key => $value ) {
    //                 $getUserProfitDetails = User_profit::whereIn(
    //                     'company_transaction_id',
    //                     $arr
    // )
    //                 ->where( 'bmi_id', '=', $value->id )
    //                 ->sum( 'amount' );
    //                 $userZakat = $getUserProfitDetails * 0.025;
    //                 $addUserZakat = User_zakat::create( [
    //                     'bmi_id' => $value->id,
    //                     'zakat_id' => $zakat_id,
    //                     'profit' => $getUserProfitDetails,
    //                     'amount' => $userZakat,
    // ] );
    //             }
    //         }
    //     }

    public function getZakat( Request $request ) {

        $s = Company_transaction::where( 'amount_categories.code', '=', 5 )
        ->where( 'companies.status', '=', 1 )->where( 'company_transactions.transferToBank', '=', 1 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
        ->join( 'companies', 'companies.id', '=', 'company_transactions.company_id' )
        ->get( [ 'company_transactions.amount', 'company_transactions.date' ] );
        $arr = array();
        $b = 0;
        foreach ( $s as $key=>$value ) {
            // return $value;
            $fromDate = Carbon::parse( $request->fromDate )->format( 'Y-m-d' );
            $toDate = Carbon::parse( $request->toDate )->format( 'Y-m-d' );

            $n = Carbon::parse( $value->date )->format( 'Y-m-d' );
            if ( $fromDate <= $n && $n <= $toDate ) {

                $b = $b+$value->amount;

            }
            $t = ( $b*2.5 )/100;
        }
        $w = ( [ 'profit'=>$b, 'zakat'=>$t ] );
        return $w;
    }

    public function updateZakat( $zakat_id )
 {
        $getZakat = Zakat::where( 'id', '=', $zakat_id )
        ->get()
        ->first();
        $fromDate = Carbon::parse( $getZakat->from_date );
        $toDate = Carbon::parse( $getZakat->to_date );
        $getBmiUsers = Bmi_id::where( 'status', '!=', 3 )->where( 'verify', '=', 1 )->get();
        $companyTransaction = Company_transaction::where(
            'amount_categories.code',
            '=',
            5
        )
        ->join(
            'amount_categories',
            'amount_categories.id',
            '=',
            'company_transactions.amount_cat_id'
        )
        ->get( [ 'company_transactions.*' ] );
        $arr = [];
        if ( count( $companyTransaction ) > 0 ) {
            foreach ( $companyTransaction as $key => $value ) {
                $date = Carbon::parse( $value->date );
                if ( $fromDate <= $date && $date <= $toDate ) {
                    array_push( $arr, $value->id );
                }
            }
        }

        if ( count( $arr ) > 0 ) {
            foreach ( $getBmiUsers as $key => $value ) {
                $getUserProfitDetails = User_profit::whereIn(
                    'company_transaction_id',
                    $arr
                )
                ->where( 'bmi_id', '=', $value->id )
                ->sum( 'amount' );
                $userZakat = $getUserProfitDetails * 0.025;
                $addUserZakat = User_zakat::create( [
                    'bmi_id' => $value1->id,
                    'zakat_id' => $zakat_id,
                    'profit' => $getUserProfitDetails,
                    'amount' => $userZakat,
                ] );
            }
        }
    }

}