<?php

namespace App\Http\Controllers;
use App\Models\User_profit;
use App\Models\Company;
use App\Models\User_investment;
use App\Models\Company_transaction;
use App\Models\User_eib_kunooz;
use Illuminate\support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserProfitController extends Controller
 {
    public function insert_profit( Request $request )
 {
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'company_id' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials' ];
        }
        $insert = User_profit::create( [
            'bmi_id' => $request->bmi_id,
            'company_id' => $request->company_id,
            'amount' => $request->amount,
            'date_of_payment' => $request->date_of_payment,
        ] );
        if ( $insert ) {
            return [ 'code' => 200, 'message' => 'inserted successfully ' ];
        } else {
            return [ 'code' => 401, 'message' => 'something went wrong' ];
        }
    }

    public function update_profit( Request $request )
 {
        $update = User_profit::where( 'id', '=', $request->id )->update( [
            'bmi_id' => $request->bmi_id,
            'company_id' => $request->company_id,
            'amount' => $request->amount,
            'date_of_payment' => $request->date_of_payment,
        ] );
        if ( $update ) {
            return [ 'code' => 200, 'message' => 'updated successfully' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function delete_profit( $id )
 {
        $delete = User_profit::where( 'id', '=', $id )->delete( [] );

        if ( $delete ) {
            return [ 'code' => 200, 'message' => 'deleted' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function getUserProfitDetails( Request $request ) {
        $bmi_id = $request->bmi_id;
        if ( isset( $request->fromDate ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $request->fromDate );
        $toDate = Carbon::parse( $request->toDate );
        $getProfitDetails = Company_transaction::where( 'amount_categories.code', '=', 5 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
        ->sum( 'amount' );
        $userProfit = User_profit::where( 'bmi_id', '=', $bmi_id )->sum( 'amount' );
        $i[ 'totalProfitPercentage' ] = $userProfit?( ( $userProfit/$getProfitDetails )*100 ):0;
        $i[ 'totalProfit' ] = $userProfit;
        $getCompany = Company::where( 'status', '!=', 0 )->orderby( 'status', 'ASC' )->get();
        $arr = array();
        foreach ( $getCompany as $key=>$value ) {
            $getinvestedCompany = User_investment::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->get();
            if ( count( $getinvestedCompany )>0 ) {
                $l[ 'company_id' ] = $value->id;
                $l[ 'company_name' ] = $value->name;
                if ( $fromDate || $toDate ) {

                    $l[ 'totalProfit' ] = Company_transaction::where( 'amount_categories.code', '=', 5 )
                    ->where( 'company_id', '=', $value->id )
                    ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
                    ->get( [ 'date', 'amount' ] );
                    $p = 0;
                    foreach ( $l[ 'totalProfit' ] as $k=>$v ) {
                        $d = Carbon::parse( $v->date );
                        if ( $temp == 0 ) {
                            // if ( $d <= $toDate ) {
                            $p = $p+$v->amount;
                            // }
                        } else {
                            if ( $fromDate <= $d && $d <= $toDate ) {
                                $p = $p+$v->amount;
                            }
                        }
                    }
                    $l[ 'totalProfit' ] = $p;

                    $l[ 'individualProfit' ] = User_profit::where( 'user_profits.bmi_id', '=', $bmi_id )
                    ->where( 'user_profits.company_id', '=', $value->id )
                    ->join( 'company_transactions', 'company_transactions.id', '=', 'user_profits.company_transaction_id' )->get( [ 'company_transactions.date', 'user_profits.amount' ] );
                    $q = 0;
                    foreach ( $l[ 'individualProfit' ] as $k1=>$v1 ) {
                        $d = Carbon::parse( $v1->date );
                        if ( $temp == 0 ) {
                            // if ( $d <= $toDate ) {
                            $q = $q+$v1->amount;
                            // }
                        } else {
                            if ( $fromDate <= $d && $d <= $toDate ) {
                                $q = $q+$v1->amount;
                            }
                        }
                    }
                    $l[ 'individualProfit' ] = $q;
                    $l[ 'percentage' ] = $l[ 'individualProfit' ]?( ( $l[ 'individualProfit' ]/$l[ 'totalProfit' ] )*100 ):0;
                } else {
                    $l[ 'totalProfit' ] = Company_transaction::where( 'amount_categories.code', '=', 5 )
                    ->where( 'company_id', '=', $value->id )
                    ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
                    ->sum( 'amount' );
                    $l[ 'individualProfit' ] = User_profit::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->sum( 'amount' );
                    $l[ 'percentage' ] = $l[ 'individualProfit' ]?( ( $l[ 'individualProfit' ]/$l[ 'totalProfit' ] )*100 ):0;
                }
                $l[ 'status' ] = $value->status == 1?'Active':'Closed';
                array_push( $arr, $l );

            }

        }

        $l[ 'company_id' ] = 0;
        $l[ 'company_name' ] = 'Eib Kunooz';
        if ( $fromDate || $toDate ) {
            $l[ 'totalProfit' ] =  User_eib_kunooz::join( 'bmi_ids', 'user_eib_kunoozs.bmi_id', '=', 'bmi_ids.id' )
            // ->where( 'bmi_ids.status', '=', '0' )
            ->where( 'bmi_ids.verify', '=', '1' )->get( [ 'user_eib_kunoozs.date_of_payment as date', 'user_eib_kunoozs.amount' ] );
            $p = 0;
            foreach ( $l[ 'totalProfit' ] as $k=>$v ) {
                $d = Carbon::parse( $v->date );
                if ( $temp == 0 ) {
                    if ( $d <= $toDate ) {
                        $p = $p+$v->amount;
                    }
                } else {
                    if ( $fromDate <= $d && $d <= $toDate ) {
                        $p = $p+$v->amount;
                    }
                }
            }
            $l[ 'totalProfit' ] = $p;

            $l[ 'individualProfit' ] = User_eib_kunooz::where( 'user_eib_kunoozs.bmi_id', '=', $request->bmi_id )->join( 'bmi_ids', 'user_eib_kunoozs.bmi_id', '=', 'bmi_ids.id' )
            ->where( 'bmi_ids.status', '=', '0' )
            ->where( 'bmi_ids.verify', '=', '1' )->get( [ 'user_eib_kunoozs.amount', 'user_eib_kunoozs.date_of_payment as date' ] );
            $q = 0;
            foreach ( $l[ 'individualProfit' ] as $k1=>$v1 ) {

                $d = Carbon::parse( $v1->date );
                if ( $temp == 0 ) {
                    if ( $d <= $toDate ) {
                        $q = $q+$v1->amount;
                    }
                } else {
                    if ( $fromDate <= $d && $d <= $toDate ) {
                        $q = $q+$v1->amount;
                    }
                }
            }
            $l[ 'individualProfit' ] = $q;
            $l[ 'percentage' ] = $l[ 'individualProfit' ]?( ( $l[ 'individualProfit' ]/$l[ 'totalProfit' ] )*100 ):0;
        }
        //  else {
        //     $l[ 'totalProfit' ] = Company_transaction::where( 'amount_categories.code', '=', 5 )
        //     ->where( 'company_id', '=', $value->id )
        //     ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
        //     ->sum( 'amount' );
        //     $l[ 'individualProfit' ] = User_profit::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->sum( 'amount' );
        //     $l[ 'percentage' ] = $l[ 'individualProfit' ]?( ( $l[ 'individualProfit' ]/$l[ 'totalProfit' ] )*100 ):0;
        // }
        // $l[ 'status' ] = $value->status == 1?'Active':'Closed';
        array_push( $arr, $l );
        $i[ 'profitDetails_data' ] = $arr;
        return $i;
    }

    public function profit_in()
 {
        $select = DB::table( 'user_profits' )
        ->join(
            'company_transactions',
            'user_profits.company_transaction_id',
            '=',
            'company_transactions.id'
        )
        ->join(
            'companies',
            'company_transactions.company_id',
            '=',
            'companies.id'
        )
        ->get( [
            'user_profits.invested_amount',
            'company_investment_histories.investment_return',
            'companies.name as company_name',
            'companies.location',
            'company_transactions.transfer_date',
            'company_transactions.fund_collector_id',
            'company_transactions.collected_from',
        ] );

        return $select;
    }

    //     public function profit_company_app( $bmi_id, $company_id )
    // {

    //         $select = User_profit::where( 'user_profits.company_id', '=', $company_id )->where( 'user_profits.bmi_id', '=', $bmi_id )
    //         ->join( 'companies', 'companies.id', '=', 'user_profits.company_id' )-> get();
    //         $arr = array();
    //         foreach ( $select as $key=>$value )
    // {
    //             $p = Carbon::parse( $value->date )->year;
    //             $k = Carbon::parse( $value->date )->month;
    //             $l[ 'Total profit' ] =  Company_transaction::where( 'company_transactions.company_id', '=', $value->id )
    //             ->where( 'amount_categories.code', '=', 5 )
    //             ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
    //             ->sum( 'company_transactions.amount' );

    //             $l[ 'individual profit' ] = $value->amount;
    //             $m = Company_transaction::
    //             where( 'company_transactions.company_id', '=', $value->id )
    //             ->where( 'amount_categories.code', '=', 5 )
    //             ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
    //             ->sum( 'company_transactions.amount' );

    //             $n = $value->amount;
    // {
    //                 if ( $m == 0 || $n == 0 ) {
    //                     $l [ '%' ] = null;
    //                 } else {
    //                     $l[ '%' ] = ( $n/$m )*100;
    //                 }
    //             }

    //             $l[ 'month' ] = $k;
    //             $l[ 'year' ] = $p;

    //             array_push( $arr, $l );

    //         }

    //         // $arr1 = json_encode( $arr1 );
    //         // $select1 = ( object ) $arr;
    //         return $arr;

    //     }

    //     public function profit_company_list_app( $bmi_id, $company_id ) {
    //         $i = $this->profit_company_app( $bmi_id, $company_id );
    //         // $select = User_profit::where( 'user_profits.company_id', '=', $company_id )->where( 'user_profits.bmi_id', '=', $bmi_id )
    //         // ->join( 'companies', 'companies.id', '=', 'user_profits.company_id' )-> get();
    //         // $select1 = Company_transaction::where( 'company_transactions.company_id', '=',  'companies.id' )
    //         // ->where( 'amount_categories.code', '=', 5 )
    //         // ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )->get();

    //         // $arr = array();
    //         // foreach ( $select as $key=>$value ) {
    //             $l = User_profit::where( 'user_profits.company_id', '=', $company_id )->where( 'user_profits.bmi_id', '=', $bmi_id )
    //             ->join( 'companies', 'companies.id', '=', 'user_profits.company_id' )-> get( 'companies.name as company_name' )->first();

    //              $l[ 'profits' ] = $i;
    //             // array_push( $arr, $l );

    //         return $l;
    //     }

    public function profit_company_list_app( $bmi_id, $company_id ) {
        $select = User_profit::where( 'user_profits.company_id', '=', $company_id )->where( 'user_profits.bmi_id', '=', $bmi_id )
        ->join( 'companies', 'companies.id', '=', 'user_profits.company_id' )-> get();
        $arr = array();
        return $select;
    }
}
