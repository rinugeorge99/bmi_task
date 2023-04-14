<?php
namespace App\Http\Controllers;
use App\Models\Monthly_sip_details;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Monthly_sip;
use App\Models\Bmi_id;

class MonthlySipDetailsController extends Controller
 {
    public function insert( Request $request ) {
        $validator = Validator::make( request()->all(), [
            'year' => 'required',
            'month' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }

        $insert = Monthly_sip_details::create( [
            'bmi_id' => $request->bmi_id,
            'year' => $request->year,
            'month' => $request->month,
            'monthly_sip_id' => $request->monthly_sip_id,
            'transaction_id' => $request->transaction_id,
            'status' => $request->status,
        ] );
        if ( $insert ) {
            return [ 'code' => 200, 'message' => 'inserted' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function pending_advance() {
        $currentYear = Carbon::now()->year;
        $currentmonth = Carbon::now()->month;
        $getUsers = Bmi_id::where( 'status', '=', 0 )->get();
        $pending = array();
        $advance = array();
        $ii = new MonthlySipController();

        foreach ( $getUsers as $key=>$value ) {
            $p[ 'bmi_id' ] = $value->bmi_id;
            $p[ 'name' ] = $value->name;
            $p[ 'pending' ] = $this->getPendingMonthAndYear( $value->id );
            $p[ 'advance' ] = $this->getAdvanceMonthAndYear( $value->id );
            $getMonthlySipDetails = Monthly_sip_details::where( 'monthly_sip_details.bmi_id', '=', $value->id )
            ->join( 'user_transactions', 'user_transactions.id', '=', 'monthly_sip_details.transaction_id' )
            ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.fund_collector_id' )
            ->get()->last();

            $p[ 'date_of_payment' ] = $getMonthlySipDetails?$getMonthlySipDetails->date:null;
            $p[ 'fund_collector' ] = $getMonthlySipDetails?$getMonthlySipDetails->bmi_id:null;
            $p[ 'totalamount_pending' ] = 0;
            foreach ( $p[ 'pending' ] as $key=>$value ) {
                $p[ 'totalamount_pending' ] = $p[ 'totalamount_pending' ]+$value[ 'amount' ];
            }
            $p[ 'totalamount_advance' ] = 0;
            foreach ( $p[ 'advance' ] as $key=>$value ) {
                $p[ 'totalamount_advance' ] = $p[ 'totalamount_advance' ]+$value[ 'amount' ];
            }
            array_push( $pending, $p );
        }
        return $pending;
    }

    public function getAdvanceMonthAndYear( $bmi_id ) {
        $currentYear = Carbon::now()->year;
        $currentmonth = Carbon::now()->month;
        $ii = new BmiIdController();
        // find out any monthly transaction which are already paid from this current month and year
        $getMonthlySipDetails1 = Monthly_sip_details::where( 'bmi_id', '=', $bmi_id )->get();

        $a11 = array();

        foreach ( $getMonthlySipDetails1 as $key=>$value ) {
            if ( $value->year>$currentYear ) {
                $l[ 'month' ] = $value->month;
                $l[ 'year' ] = $value->year;
                $l[ 'amount' ] = $value->amount;
                array_push( $a11, $l );

            }
            if ( $value->year == $currentYear && $value->month>$currentmonth ) {
                $l[ 'month' ] = $value->month;
                $l[ 'year' ] = $value->year;
                $l[ 'amount' ] = $value->amount;
                array_push( $a11, $l );

            }
        }
        return $a11;

    }

    public function getPendingMonthAndYear( $bmi_id ) {
        $currentYear = Carbon::now()->year;
        $currentmonth = Carbon::now()->month;
        $ii = new BmiIdController();
        $p1 = array();
        $getMonthlySipDetails = Monthly_sip_details::where( 'bmi_id', '=', $bmi_id )->get()->last();
        if ( $getMonthlySipDetails ) {
            $m1 = $getMonthlySipDetails->month;
            $y1 = $getMonthlySipDetails->year;
            $getLastMonthDetails = Monthly_sip_details::where( 'month', '=', $m1 )
            ->where( 'year', '=', $y1 )->sum( 'amount' );
            $monthlySipAmount = Monthly_sip::where( 'bmi_id', '=', $bmi_id )
            ->where( 'year', '=', $y1 )
            ->get( 'amount' )->first();
            if ( $getLastMonthDetails < ( $monthlySipAmount?$monthlySipAmount->amount:0 ) ) {
                $y = $y1;
                $m = $m1;
                $amt = $monthlySipAmount->amount-$getLastMonthDetails;
            } else {
                $y = $y1;
                $m = $m1+1;
                $amt = $monthlySipAmount->amount;
            }
            while ( $y <= $currentYear ) {
                if ( $y == $currentYear ) {
                    $m1 = $currentmonth;
                } else {
                    $m1 = 12;
                }
                while( $m <= $m1 ) {
                    $pp[ 'month' ] = $m;
                    $pp[ 'year' ] = $y;
                    $pp[ 'amount' ] = $amt;
                    array_push( $p1, $pp );
                    $m = $m+1;
                    $amt = $monthlySipAmount->amount;
                }
                $y = $y+1;
                $m = 1;
            }
        }

        return $p1;
    }

    public function list_sip( $bmi_id ) {
        $select = Monthly_sip_details::where( 'monthly_sip_details.bmi_id', '=', $bmi_id )
        ->groupby( 'monthly_sip_details.year', 'monthly_sip_details.month' )
        ->orderby( 'monthly_sip_details.id', 'DESC' )
        ->get( [ 'monthly_sip_details.year', 'monthly_sip_details.month' ] );
        foreach ( $select as $key=>$value ) {
            $value->amountType = 'SIP';
            $value->amount = Monthly_sip_details::where( 'bmi_id', '=', $bmi_id )->where( 'year', '=', $value->year )->where( 'month', '=', $value->month )->sum( 'amount' );
            $getMonthlySip = Monthly_sip::where( 'year', '=', $value->year )->where( 'bmi_id', '=', $bmi_id )->get()->first();
            if ( $getMonthlySip ) {
                $amount = $getMonthlySip->amount;
                if ( $value->amount<$amount ) {
                    $value->status = 'Unpaid';
                } else {
                    $value->status = 'Paid';
                }
            }
        }
        return $select;
    }

    public function list_monthly_sip_app( Request $request ) {
        $np[ 'totalSipPaid' ] = Monthly_sip_details::where( 'bmi_id', '=', $request->bmi_id )->sum( 'amount' );
        $np[ 'totalPending' ] = 0;
        $getpendingmonthandyear = $this->getPendingMonthAndYear( $request->bmi_id );
        foreach ( $getpendingmonthandyear as $key=>$value ) {
            $np[ 'totalPending' ] = $np[ 'totalPending' ] + $value[ 'amount' ];
        }
        $getMonthlySipDetails = Monthly_sip_details::where( 'bmi_id', '=', $request->bmi_id )->where( 'year', '=', $request->year )
        ->orderby( 'month', 'DESC' )
        ->groupby( 'month' )
        ->get( 'month' );
        $i = new BmiIdController();
        $arr = array();
        foreach ( $getMonthlySipDetails as $key=>$value ) {
            $l[ 'month' ] = $i->getMonthName( $value->month );
            $t = Monthly_sip_details::where( 'monthly_sip_details.year', '=', $request->year )
            ->where( 'monthly_sip_details.bmi_id', '=', $request->bmi_id )
            ->where( 'monthly_sip_details.month', '=', $value->month )
            ->join( 'user_transactions', 'user_transactions.id', '=', 'monthly_sip_details.transaction_id' )
            ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.fund_collector_id' )
            ->get( [ 'bmi_ids.bmi_id as bmipno', 'bmi_ids.name', 'date' ] )->last();
            $l[ 'fund_collector_bmipno' ] = $t->bmipno;
            $l[ 'fund_collector_name' ] = $t->name;
            $l[ 'paid_date' ] = $t->date;
            $l[ 'amount' ] = Monthly_sip_details::where( 'year', '=', $request->year )->where( 'month', '=', $value->month )->where( 'bmi_id', '=', $request->bmi_id )->sum( 'amount' );

            $currentSip = Monthly_sip::where( 'bmi_id', '=', $request->bmi_id )->where( 'year', '=', $request->year )->get( 'amount' );
            if ( $request->year<Carbon::now()->year ) {
                if ( $currentSip->amount = $l[ 'amount' ] ) {
                    $l[ 'status' ] = 'Paid';

                } else {
                    $l[ 'status' ] = 'Pending';
                }
            } else if ( $request->year == Carbon::now()->year && $value->month <= Carbon::now()->month ) {
                if ( $currentSip->amount = $l[ 'amount' ] ) {
                    $l[ 'status' ] = 'Paid';
                } else {
                    $l[ 'status' ] = 'Pending';
                }
            } else {
                $l[ 'status' ] = 'Advance';
            }

            array_push( $arr, $l );
        }
        $np[ 'data' ] = $arr;
        return $np;

    }

    public function getCurrentSIPStatus( $bmi_id ) {
        $currentYear = Carbon::now()->year;
        $currentmonth = Carbon::now()->month;
        $details = Monthly_sip_details::where( 'bmi_id', '=', $bmi_id )->where( 'year', '=', $currentYear )->where( 'month', '=', $currentmonth )
        ->get()
        ->first();

        $s = 0;
        if ( $details ) {
            $monthlysip = Monthly_sip::where( 'bmi_id', '=', $bmi_id )->where( 'year', '=', $currentYear )->get()->first();
            if ( $monthlysip->amount == $details->amount ) {
                return 'Paid';
            } else {
                return 'pending';
            }
        } else {
            return 'unpaid';
        }
        // if ( $s == 1 ) {
        //     return 'Paid';
        // } else {
        //     return 'unpaid';
        // }
        // return $details;
    }

}
