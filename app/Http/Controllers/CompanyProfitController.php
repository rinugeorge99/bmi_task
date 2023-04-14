<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use App\Models\Company_Profit;
use App\Models\Company_transaction;

use App\Models\User_investment;
use App\Models\User_profit;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyProfitController extends Controller {
    public function insert( Request $request ) {
        $arr = json_decode( $request->profit_details );

        foreach ( $arr as $key=>$value ) {
            $insert = Company_Profit::create( [
                'company_id'=>$request->company_id,
                'profit'=>$value->profit,
                'month'=>$value->month,
                'year'=>$value->year,
                'company_transaction_id'=>$request->company_transaction_id,

            ] );
        }
        if ( $insert ) {
            $update = Company_transaction::where( 'id', '=', $request->company_transaction_id )->where( 'profit_status', '=', 0 )
            ->update( [
                'profit_status'=>1,
            ] );

            return [ 'code' => 200, 'message' => 'inserted' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function getProfitDetails( Request $request )
 {
        $getTotalProfit = Company_profit::where( 'company_id', '=', $request->company_id )
        ->sum( 'profit' );
        $getIndividualProfit = User_profit::where( 'company_id', '=', $request->company_id )->where( 'bmi_id', '=', $request->bmi_id )->sum( 'amount' );
        $getCompanyName = Company::where( 'id', '=', $request->company_id )->get()->first();
        if ( $getCompanyName ) {
            $p[ 'company_name' ] = $getCompanyName->name;
        }
        $p[ 'totalProfit' ] = $getTotalProfit;
        $p[ 'individualProfit' ] = $getIndividualProfit;
        $m = 1;
        $arr = array();
        $i = new BmiIdController();
        while( $m <= 12 ) {
            $l[ 'month' ] = $i->getMonthName( $m );
            $l[ 'totalProfit' ] = Company_profit::where( 'company_id', '=', $request->company_id )
            ->where( 'year', '=', $request->year )
            ->where( 'month', '=', $m )
            ->sum( 'profit' );
            $getProfitPercentage = User_investment::where( 'company_id', '=', $request->company_id )
            ->where( 'bmi_id', '=', $request->bmi_id )->get();
            if ( count( $getProfitPercentage )>0 ) {
                $l[ 'percentage' ] = $getProfitPercentage[ 0 ]->percentage;
                $l[ 'individualProfit' ] = $l[ 'totalProfit' ]*( $l[ 'percentage' ]/100 );
            }
            array_push( $arr, $l );
            $m = $m+1;
        }
        $p[ 'data' ] = $arr;
        return $p;
    }

}
