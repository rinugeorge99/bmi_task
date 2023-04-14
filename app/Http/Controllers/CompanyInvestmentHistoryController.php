<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Company_investment_history;
use App\Models\User_investment;
use App\Models\Company;
use Carbon\Carbon;

class CompanyInvestmentHistoryController extends Controller
 {

    public function getInvestedDetails( Request $request ) {
        $getTotalInvestment = Company_investment_history::where( 'company_id', '=', $request->company_id )->sum( 'invested_amount' );
        $getTotalInvestmentReturn = Company_investment_history::where( 'company_id', '=', $request->company_id )->sum( 'investment_return' );
        $getCompanyName = Company::where( 'id', '=', $request->company_id )->get()->first();
        if ( $getCompanyName ) {
            $p[ 'company_name' ] = $getCompanyName->name;
        }
        $p[ 'totalInvestment' ] = $getTotalInvestment-$getTotalInvestmentReturn;
        $getUserInvestment = User_investment::where( 'bmi_id', '=', $request->bmi_id )
        ->where( 'company_id', '=', $request->company_id )
        ->sum( 'invested_amount' );
        $getUserInvestmentReturn = User_investment::where( 'bmi_id', '=', $request->bmi_id )
        ->where( 'company_id', '=', $request->company_id )
        ->sum( 'investment_return' );
        $p[ 'individualInvestment' ] = $getUserInvestment-$getUserInvestmentReturn;
        $getCompanyInvestmentDetails = Company_investment_history::where( 'company_id', '=', $request->company_id )->get();
        $getUserInvestmentDetails = User_investment::where( 'company_id', '=', $request->company_id )->where( 'bmi_id', '=', $request->bmi_id )->get();
        $arr = array();
        $m = 1;
        $i = new BmiIdController();
        while( $m <= 12 ) {
            $l[ 'month' ] = $i->getMonthName( $m );
            $company_invested_amount = 0;
            $company_invested_return = 0;
            foreach ( $getCompanyInvestmentDetails as $key=>$value ) {
                $y = Carbon::parse( $value->date )->year;
                $m1 = Carbon::parse( $value->date )->month;
                if ( $y<$request->year ) {
                    $company_invested_amount = $company_invested_amount+$value->invested_amount;
                    $company_invested_return = $company_invested_return+$value->investment_return;
                }
                if ( $y == $request->year && $m1 <= $m ) {
                    $company_invested_amount = $company_invested_amount+$value->invested_amount;
                    $company_invested_return = $company_invested_return+$value->investment_return;
                }

            }
            $l[ 'totalInvestment' ] = $company_invested_amount-$company_invested_return;
            $individual_invested_amount = 0;
            $individual_investment_return = 0;
            $l[ 'percentage' ] = 0;
            foreach ( $getUserInvestmentDetails as $key1=>$value1 ) {
                $y = Carbon::parse( $value1->date )->year;
                $m1 = Carbon::parse( $value1->date )->month;
                if ( $y<$request->year ) {
                    $l[ 'percentage' ] = $value1->percentage;
                }
                if ( $y == $request->year && $m1 <= $m ) {
                    $l[ 'percentage' ] = $value1->percentage;

                }

            }
            $l[ 'individualInvestment' ] = $l[ 'totalInvestment' ]*( $l[ 'percentage' ]/100 );
            array_push( $arr, $l );
            $m = $m+1;
        }
        $p[ 'data' ] = $arr;
        return $p;
    }

}
