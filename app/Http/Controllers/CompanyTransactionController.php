<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company_transaction;
use App\Models\Company_investment_history;
use App\Models\Amount_category;
use App\Models\Company;
use Illuminate\support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Company_Profit;
use App\Models\Bmi_id;
use App\Models\User_profit;
use App\Models\User_investment;
use App\Models\Fund_collector;
// use App\Models\Treasurer;

class CompanyTransactionController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( $request->all(), [
            'company_id' => 'required',
            'amount' => 'required',
            'amount_cat_id' => 'required',
            'date' => 'required',
            'fund_collector_id' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials' ];
        }
        $selectCompany = Company::where( 'id', '=', $request->company_id )
        ->get()
        ->first();
        if ( !$selectCompany ) {
            return [ 'code' => 401, 'message' => 'Company is not exist' ];
        }
        $investedAmount = $selectCompany->invested_amount;
        $investmentReturn = $selectCompany->investment_return ?: 0;
        $tInvested = $investedAmount - $investmentReturn;
       
        $select = Amount_category::where( 'code', '=', $request->amount_cat_id )
        ->get()
        ->first();
        // return $select;
        $i = 0;
        if ( $select ) {
            if($select->code==4 &&   $request->amount > $tInvested ) {
                return [
                    'code' => 401,
                    'message' => 'Paying amount is greater than invested amount.',
                ];
            }
            if ( $select->code == 4 || $select->code == 5 ) {
                $insert = Company_transaction::create( [
                    'company_id' => $request->company_id,
                    'amount' => $request->amount,
                    'amount_cat_id' => $request->amount_cat_id,
                    'date' => $request->date,
                    'fund_collector_id' => $request->fund_collector_id,
                    'transfer' => $request->transfer ?: 0,
                    'transfer_date' => $request->transfer_date,
                    'transfer_verification' =>
                    $request->transfer_verification ?: 0,
                    'transferToBank' => $request->transferToBank ?: 0,
                    'transferToBank_date' => $request->transferToBank_date,
                    'remarks' => $request->remarks,
                    'transfer_verification_date' =>
                    $request->transfer_verification_date,
                    'collected_from' => $request->collected_from,
                    'profit_status' => 0,

                ] );
                if ( $insert ) {
                    return [
                        'code' => 200,
                        'message' => 'inserted successfully ',
                    ];
                }
            } else {
                return [
                    'code' => 401,
                    'message' => 'amount type not match',
                ];
            }
        }
    }

    public function update_transaction( Request $request ) {
        $select = Amount_category::where(
            'code',
            '=',
            $request->amount_cat_id
        )->get();
        $i = 0;
        if ( count( $select ) > 0 ) {
            foreach ( $select as $key => $value ) {
                if ( $value->code == 4 || $value->code == 5 ) {
                    $update = Company_transaction::where(
                        'id',
                        '=',
                        $request->id
                    )->where( 'transfer', '=', 0 )->update( [
                        'company_id' => $request->company_id,
                        'amount' => $request->amount,
                        'amount_cat_id' => $request->amount_cat_id,
                        'date' => $request->date,
                        'fund_collector_id' => $request->fund_collector_id,
                        'transfer' => $request->transfer ?: 0,
                        'transfer_date' => $request->transfer_date,
                        'transfer_verification' =>
                        $request->transfer_verification ?: 0,
                        'transferToBank' => $request->transferToBank ?: 0,
                        'transferToBank_date' => $request->transferToBank_date,
                        'remarks' => $request->remarks,
                        'transfer_verification_date' =>
                        $request->transfer_verification_date,
                        'collected_from' => $request->collected_from,
                    ] );
                    if ( $update ) {
                        return [
                            'code' => 200,
                            'message' => 'updated successfully ',
                        ];
                    }
                } else {
                    return [
                        'code' => 401,
                        'message' => 'amount type not match ',
                    ];
                }
                if ( Company_transaction::where( 'transfer', $request->transfer )->doesntExist() ) {
                    return [
                        'code' => 401,
                        'message' => 'no data  ',
                    ];
                }

            }

        }
    }

    public function fundtransferdetails_company(Request $request) {
        $getfundcollector = DB::table( 'fund_collectors' )->where( 'fund_collectors.bmi_id', '=', $request->bmi_id )->where( 'ending_date', '=', null )->get();
        if( $getfundcollector){
        $select = DB::table( 'company_transactions' )
        ->join(
            'amount_categories',
            'company_transactions.amount_cat_id',
            '=',
            'amount_categories.id'
        )->where('company_transactions.fund_collector_id','=',$request->bmi_id)
        ->join( 'companies', 'companies.id', '=', 'company_transactions.company_id' )
        ->leftjoin( 'bmi_ids', 'bmi_ids.id', '=', 'company_transactions.collected_from' )
        ->orderby( 'company_transactions.id', 'DESC' )
        ->get( [
            'company_transactions.id',
            'company_transactions.amount_cat_id',
            'company_transactions.fund_collector_id',
            'company_transactions.date',
            'companies.name',
            'companies.id as company_id',
            'company_transactions.amount',
            'bmi_ids.bmi_id as collected_from',
            'company_transactions.remarks',
            'company_transactions.transfer',
            'company_transactions.transfer_verification',
        ] );
        $arr = array();
        foreach ( $select as $key=>$value ) {
            $getUserInvestmentDetails = User_investment::where( 'user_investments.company_transaction_id', '=', $value->id )
            ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_investments.bmi_id' )
            ->get( [
                'bmi_ids.bmi_id as bmipno',
                'bmi_ids.name',
                'user_investments.bmi_id'
            ] );
            foreach ( $getUserInvestmentDetails as $k=>$v ) {
                $l[ 'bmipno' ] = $v->bmipno;
                $l[ 'name' ] = $v->name;
                $l[ 'invested_amount' ] = User_investment::where( 'bmi_id', '=', $v->bmi_id )->where( 'company_transaction_id', '=', $v->company_transaction_id )->sum( 'invested_amount' );
                $l[ 'investment_return' ] = User_investment::where( 'bmi_id', '=', $v->bmi_id )->where( 'company_transaction_id', '=', $v->company_transaction_id )->sum( 'investment_return' );
                array_push( $arr, $l );
            }
            $value->member_list = $arr;
        }
    }
        return $select;
    }

    public function getMemberList( $company_id ) {
        $getUsers = Bmi_id::get();
        $userArray = [];
        foreach ( $getUsers as $k => $v ) {
            $checkinvested = User_investment::where( 'company_id', '=', $company_id )
            ->where( 'bmi_id', '=', $v->id )
            ->get();

            if ( count( $checkinvested ) > 0 ) {
                $li[ 'bmi_id' ] = $v->bmi_id;
                $li[ 'name' ] = $v->name;
                $i = User_investment::where( 'company_id', '=', $company_id )
                ->where( 'bmi_id', '=', $v->id )
                ->sum( 'invested_amount' );
                $j = User_investment::where( 'company_id', '=', $company_id )
                ->where( 'bmi_id', '=', $v->id )
                ->sum( 'investment_return' );
                $li[ 'invested_amount' ] = $i - ( $j ?: 0 );
                $li[ 'profit' ] = User_profit::where( 'company_id', '=', $company_id
            )
            ->where( 'bmi_id', '=', $v->id )
            ->sum( 'amount' );
            array_push( $userArray, $li );
        }
    }
    return $userArray;
}

public function updateTransferStatus( Request $request ) {
    $validator = Validator::make( $request->all(), [
        'amount_cate_id' => 'required',
        'idArray' => 'required',
    ] );
    if ( $validator->fails() ) {
        return [ 'code' => 401, 'message' => 'invalid credentials' ];
    }
    $i = 0;
    $idArray = $request->idArray ? json_decode( $request->idArray ) : [];
    // $idArray = $request->idArray;
    foreach ( $idArray as $key => $value ) {
        $updateStatus = Company_transaction::where( 'id', '=', $value )
        ->where( 'amount_cat_id', '=', $request->amount_cate_id )
        ->update( [
            'transfer' => 1,
            'transfer_date' => Carbon::now()->format( 'd-m-Y' ),
        ] );
        if ( !$updateStatus ) {
            $i = 1;
        }

    }
    if ( $i = 0 ) {
        return [ 'code' => 401, 'message' => 'Something went wrong' ];
    } else {
        return [
            'code' => 200,
            'message' => 'Transfer to main fund Collector',
        ];
    }
}

public function getTransferCollectionDetails()
 {
    $select = Company_transaction::where( 'transfer', '=', 1 )
    ->where( 'transfer_verification', '=', 0 )
    ->join(
        'companies',
        'companies.id',
        '=',
        'company_transactions.company_id'
    )
    ->get( [ 'company_transactions.*', 'companies.name as company_name' ] );
    return $select;
}

public function ApproveCompanyTransactionByTreasurer( Request $request )
 {
    $validator = Validator::make( $request->all(), [
        'id' => 'required',
    ] );
    if ( $validator->fails() ) {
        return [ 'code' => 401, 'message' => 'invalid credentials' ];
    }
    $approve = Company_transaction::where( 'id', '=', $request->id )->update( [
        'transfer_verification' => 1,
        'transfer_verification_date' => Carbon::now()->format( 'd-m-Y' ),
        'treasurer_id'=>$request->treasurer_id
    ] );
    if ( $approve ) {
        $c = new NotificationController();
        $select = Fund_collector::where( 'status', '=', 1 )->where( 'ending_date', '=', null )->pluck( 'bmi_id' );
        $date = Carbon::now()->format( 'Y-m-d' );
        //$getDetailstreasurer = DB::table( 'company_transactions' )
        //     ->where( 'company_transactions.id', '=', $request->id )
        //     ->join( 'treasurers', 'company_transactions.treasurer_id', '=', 'treasurers.bmi_id' )
        //     ->where( 'treasurers.ending_date', '=', null )
        //     ->where( 'treasurers.treasurer', '=', 1 )
        //     ->join( 'bmi_ids', 'treasurers.bmi_id', '=', 'bmi_ids.id' )
        //     ->get( [ 'bmi_ids.*' ] )->first();

        $content = 'Company transaction approved by the treasurer ';
        $status=0;
        //.$getDetailstreasurer->name.'('.$getDetailstreasurer->bmi_id.')';
        $k = $c->insertNotification( $date, $content, $select,$status );
        return [ 'code' => 200, 'message' => 'Transaction approved' ];
    } else {
        return [ 'code' => 401, 'message' => 'something went wrong' ];
    }
}

//     public function getApprovedCompanyTransaction()
// {
//         $select = Company_transaction::where( 'transfer_verification', '=', 1 )
//         // ->where( 'transferToBank', '=', 0 )

//         ->join(
//             'companies',
//             'companies.id',
//             '=',
//             'company_transactions.company_id'
// )
//         ->orderby( 'company_transactions.id', 'DESC' )
//         ->get( [ 'company_transactions.*', 'companies.name as company_name' ] );
//         return $select;
//     }

public function getApprovedCompanyTransaction() {
    $select = DB::table( 'companies' )
    ->join( 'bmi_ids', 'companies.treasure_id', '=', 'bmi_ids.id' )
    ->where( 'companies.status', '1' )
    ->get( [ 'companies.*', 'bmi_ids.name as treasurer_name' ] );
    // return $select;
    if ( $select->isEmpty() ) {
        return [ 'code' => 401, 'message' => 'no result found ' ];
    } else {
        return $select;
    }
}

public function transferToBank( Request $request )
 {

    $select = Company_transaction::where(
        'company_transactions.id',
        '=',
        $request->id
    )
    ->join(
        'amount_categories',
        'amount_categories.id',
        '=',
        'company_transactions.amount_cat_id'
    )
    ->get( [ 'company_transactions.*', 'amount_categories.code' ] )
    ->first();

    if ( !$select ) {
        return [
            'code' => 401,
            'message' => 'This transaction is not exist',
        ];
    }

    $code = $select->code;
    $company_id = $select->company_id;
    $amount = $select->amount;

    $update = Company_transaction::where( 'id', '=', $request->id )->update( [
        'transferToBank' => 1,
        'transferToBank_date' => Carbon::now()->format( 'd-m-Y' ),
    ] );

    if ( $update ) {
        if ( $code == 4 ) {
            $this->updateInvestmentReturn(
                $company_id,
                $amount,
                $request->id
            );
        }
        if ( $code == 5 ) {
            $this->updateUserProfit( $amount, $company_id, $request->id );
        }
        return [ 'code' => 200, 'message' => 'Transfer to bank' ];
    } else {
        return [ 'code' => 401, 'message' => 'Something went wrong' ];
    }
}

public function updateInvestmentReturn(
    $company_id,
    $amount,
    $company_transaction_id
) {
    // update table company
    $select = Company::where( 'id', '=', $company_id )
    ->get()
    ->first();

    if ( !$select ) {
        return [ 'code' => 401, 'message' => 'This company is not exist' ];
    }
    $investmentRt = $select->investment_return ?: 0;
    $investmentRt += $amount;
    if ( $investmentRt > $select->invested_amount ) {
        return [
            'code' => 200,
            'message' =>
            'Investment return is greater than invested amount',
        ];
    }
    $update = Company::where( 'id', '=', $company_id )->update( [
        'investment_return' => $investmentRt,
    ] );
    if ( !$update ) {
        return [ 'code' => 401, 'message' => 'Company table is not updated' ];
    }
   
    // add record into company investment history
    $getCompanyTransaction_date=Company_transaction::where('id','=',$company_transaction_id)->get('date')->first();
    $insert = Company_investment_history::create( [
        'company_id' => $company_id,
        'date' =>$getCompanyTransaction_date->date,
        'investment_return' => $amount,
        'company_transaction_id' => $company_transaction_id,
    ] );

    // add record into user_investment
    $getBmiUsers = Bmi_id::where('status','=',0)->get();
    foreach ( $getBmiUsers as $key => $value ) {
        $selectUserInvestment = User_investment::where(
            'company_id',
            '=',
            $company_id
        )
        ->where( 'bmi_id', '=', $value->id )
        ->get()
        ->last();
        if($selectUserInvestment){
            $investmentRtAmt =
            $amount * ( $selectUserInvestment->percentage / 100 );
            $addInvestmentReturn = User_investment::create( [
                'bmi_id' => $value->id,
                'company_id' => $company_id,
                'date' => $getCompanyTransaction_date->date,
                'percentage' => $selectUserInvestment->percentage,
                'investment_return' => $investmentRtAmt,
                'company_transaction_id' => $company_transaction_id,
            ] );
        }
       
    }
}
public function updateUserProfit(
    $amount,
    $company_id,
    $company_transaction_id
) {
    $getBmiUsers = Bmi_id::where('status','=',0)->get();

    foreach ( $getBmiUsers as $key => $value ) {
        $selectUserInvestment = User_investment::where(
            'company_id',
            '=',
            $company_id
        )
        ->where( 'bmi_id', '=', $value->id )
        ->get()
        ->last();
       if($selectUserInvestment){
        $profit = $amount * ( $selectUserInvestment->percentage / 100 );
       
        $getCompanyTransaction_date = Company_transaction::where( 'id', '=', $company_transaction_id )->get( 'date' )->first();
        $addInvestmentReturn = User_profit::create( [
            'bmi_id' => $value->id,
            'company_id' => $company_id,
            'amount' => $profit,
            'date_of_payment' =>  $getCompanyTransaction_date->date,
            'company_transaction_id' => $company_transaction_id,
        ] );
       }
           
    }
}

public function getProfitSummary( Request $request ) {
    $fromDate = Carbon::parse( $request->fromDate );
    $toDate = Carbon::parse( $request->toDate );
    $getUsers = Bmi_id::where('verify','=',1)->get();
    $companyList = Company::where( 'status', '!=', 0 )->get( [ 'id', 'name','status' ] );
    if ( count( $getUsers ) == 0 ) {
        return [
            'companyList' => $companyList,
            'all' => [],
            'monthly' => [],
        ];
    }
    $arr = [];
    $arr1 = [];
    $p = new MonthlySipController();
    $w = new UserEibKunoozController();

    foreach ( $getUsers as $key => $value ) {
        $a[ 'bmi_id' ] = $value->bmi_id;
        $a[ 'name' ] = $value->name;
        $a[ 'status' ] = $value->status;
        $a[ 'totalProfit' ] = $p->getUserTotalProfit_v( $value->id, $fromDate, $toDate );
        $a[ 'eib_kunooz' ] = $w->getUserEibKunooz_v( $value->id, $fromDate, $toDate );
        if ( count( $companyList ) > 0 ) {

            foreach ( $companyList as $key1 => $value1 ) {
               $g = User_profit::where( 'bmi_id', '=', $value->id )
                ->where( 'company_id', '=', $value1->id )
                ->get( [ 'date_of_payment', 'amount' ] );
                $gt = 0;
                foreach ( $g as $key2=>$value2 ) {
                  
                    $d = Carbon::parse( $value2->date_of_payment );
                    if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) == Carbon::now()->format( 'd-m-Y' ) ) {
                        // if ( $d <= $toDate ) {
                           $gt = $gt+$value2->amount;
                        // }
                    } else {
                        if ( $fromDate <= $d && $d <= $toDate ) {
                            $gt = $gt+$value2->amount;
                        }
                    }
                }
                $a[ $value1->id ] = $gt;
            }
        }
        array_push( $arr, $a );
    }

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
    ->orderby( 'company_transactions.id', 'DESC' )
    ->get( 'date' );
    $largestYear = Carbon::parse( $toDate )->year;
    if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) == Carbon::now()->format( 'd-m-Y' ) ) {
        $smallestYear = $largestYear;
        if ( count( $companyTransaction ) > 0 ) {
            foreach ( $companyTransaction as $kk => $vv ) {
                $d = $vv->date;
                $year = Carbon::parse( $d )->year;

                if ( $year < $smallestYear ) {
                    $smallestYear = $year;
                }
            }
        }
    } else {
        $smallestYear = Carbon::parse( $fromDate )->year;
    }

    $uu = new BmiIdController();
    while ( $smallestYear <= $largestYear ) {
        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) == Carbon::now()->format( 'd-m-Y' ) ) {
            $currentMonth = 1;
            if ( $smallestYear<$largestYear ) {
                $lastMonth = 12;
            }
            if ( $smallestYear == $largestYear ) {
                $lastMonth = Carbon::parse( $toDate )->month;
            }
        } else {
            if ( $smallestYear<$largestYear ) {
                $currentMonth = Carbon::parse( $fromDate )->month;
                $lastMonth = 12;
            }
            if ( $smallestYear == $largestYear ) {
                if ( Carbon::parse( $fromDate )->year == $smallestYear ) {
                    $currentMonth = Carbon::parse( $fromDate )->month;
                } else {
                    $currentMonth = 1;
                }
                $lastMonth = Carbon::parse( $toDate )->month;
            }
        }
        while ( $currentMonth <= $lastMonth ) {
            foreach ( $getUsers as $key => $value ) {
                $a1[ 'bmi_id' ] = $value->bmi_id;
                $a1[ 'name' ] = $value->name;
                $a1[ 'status' ] = $value->status;
                $a1[ 'month_year' ] =
                $uu->getMonthName( $currentMonth ) . ' ' . $smallestYear;
                $a1[ 'totalProfit' ] = $p->getUserTotalProfit( $value->id );
                $a1[ 'eib_kunooz' ] = $w->getUserEibKunooz( $value->id );
                if ( count( $companyList ) > 0 ) {
                    foreach ( $companyList as $key1 => $value1 ) {
                        $g = User_profit::where( 'bmi_id', '=', $value->id )
                        ->where( 'company_id', '=', $value1->id )
                        ->get( [ 'amount', 'date_of_payment' ] );
                        $amt = 0;
                        foreach ( $g as $k => $v ) {
                            $y = Carbon::parse( $v->date )->year;
                            $m = Carbon::parse( $v->date )->month;
                            if ( $y < $largestYear ) {
                                $amt += $v->amount;
                            }
                            if ( $y == $largestYear && $m <= $currentMonth ) {
                                $amt += $v->amount;
                            }
                        }
                       $a1[ $value1->id ] = $amt;
                    }
                }
                array_push( $arr1, $a1 );
            }
            $currentMonth++;
        }
        $smallestYear++;
    }
// return $arr1;
    return [
        'companyList' => $companyList,
        'all' => $arr,
        'monthly' => $arr1,
    ];
}

public function getBankInOfProfit() {
    $v = new MonthlySipController;
    $getCompanyTransaction = Company_transaction::where(
        'amount_categories.code',
        '=',
        5
    )
    ->where( 'company_transactions.transferToBank', '=', 1 )
    ->join(
        'amount_categories',
        'amount_categories.id',
        '=',
        'company_transactions.amount_cat_id'
    )
    ->join(
        'companies',
        'companies.id',
        '=',
        'company_transactions.company_id'
    )
    ->leftJoin( 'company_profits', 'company_transactions.id', '=', 'company_profits.company_transaction_id' )
    ->join(
        'bmi_ids as fund_collector',
        'fund_collector.id',
        '=',
        'company_transactions.fund_collector_id'
    )->leftJoin( 'bmi_ids as collected_from', 'collected_from.id', '=', 'company_transactions.collected_from' )
    ->get( [
        'company_transactions.transferToBank_date',
        'companies.name',
        'fund_collector.bmi_id as fund_collector_id',
        'company_transactions.collected_from',
        'collected_from.bmi_id as collected_from',
        'company_transactions.remarks',
        'company_transactions.amount as collected_amount',
        'company_profits.profit as amount',
        'company_profits.month'
    ] );
    
    $l = $v->AmountAvalilableinBank();
    return [ 'amount'=>$l, 'details'=>$getCompanyTransaction ];
}

public function investmentreturnIn(){
    $v=new MonthlySipController;
     $getCompanyTransaction = Company_transaction::where(
        'amount_categories.code',
        '=',
        4
    )
    ->where( 'company_transactions.transferToBank', '=', 1 )
    ->join(
        'amount_categories',
        'amount_categories.id',
        '=',
        'company_transactions.amount_cat_id'
    )
    ->join(
        'companies',
        'companies.id',
        '=',
        'company_transactions.company_id'
    )
    ->join(
        'bmi_ids as fund_collector',
        'fund_collector.id',
        '=',
        'company_transactions.fund_collector_id'
    )->leftJoin('bmi_ids as collected_from','collected_from.id','=','company_transactions.collected_from')
    ->get( [
        'company_transactions.transferToBank_date',
        'companies.name',
        'fund_collector.bmi_id as fund_collector_id',
        'company_transactions.collected_from',
        'collected_from.bmi_id as collected_from',
        'company_transactions.remarks',
        'company_transactions.amount',
    ] );
    $l=$v->AmountAvalilableinBank();
    return ['amount'=>$l,'details'=>$getCompanyTransaction];
}
public function list_profit() {
    $select = DB::table( 'company_transactions' )
    ->where( 'company_transactions.transferToBank', '=', 1 )
    ->get();
    return $select;
}

public function getTransferCompanyTransactionDetails() {
    $getList = Company_transaction::
    // where( 'company_transactions.transfer_verification', '=', 0)
    // ->where( 'company_transactions.transferToBank', '=', 0 )
    where( 'company_transactions.transfer', '=', 1 )
    ->join( 'bmi_ids', 'bmi_ids.id', '=', 'company_transactions.fund_collector_id' )
    ->leftJoin( 'bmi_ids as bmi_tbl1', 'bmi_tbl1.id', '=', 'company_transactions.treasurer_id' )
    ->join( 'companies', 'companies.id', '=', 'company_transactions.company_id' )
    // ->orderby( 'company_transactions.transfer_verification', 'ASC' )
    ->orderby( 'company_transactions.id', 'ASC' )
    ->get( [
        'company_transactions.transfer_date',
        'bmi_ids.bmi_id as fund_collector_bmipno',
        'bmi_ids.name as fund_collector_name',
        'company_transactions.amount',
        'companies.id as company_id',
        'companies.name',
        'company_transactions.remarks',
        'bmi_tbl1.name as treasurer',
        'company_transactions.transfer_verification',
        'company_transactions.transferToBank',
        'company_transactions.transfer',
        'company_transactions.amount_cat_id',
        'company_transactions.id',
        'company_transactions.profit_status'
    ] );
    return $getList;
}

public function getVerifiedCompanyTransactionDetails(Request $request) {
    if(isset($request->fromDate)){
     $temp=1;
    }
    else{
     $temp=0;
    }
    $fromDate=Carbon::parse($request->fromDate);
    $toDate=Carbon::parse($request->toDate);
   $getFundCollectorDetails = Company_transaction::where( 'company_transactions.transfer_verification', '=', 1 )

     ->join( 'bmi_ids', 'bmi_ids.id', '=', 'company_transactions.fund_collector_id' )
     ->leftJoin( 'bmi_ids as bmi_tbl', 'bmi_tbl.id', '=', 'company_transactions.collected_from' )
     ->join( 'bmi_ids as bmi_tbl1', 'bmi_tbl1.id', '=', 'company_transactions.treasurer_id' )
     ->join( 'companies', 'companies.id', '=', 'company_transactions.company_id' )
     ->groupby(
         'company_transactions.fund_collector_id',
         'company_transactions.transfer_date',
         'bmi_ids.name',
         'company_transactions.transfer_verification',
         'amount_cat_id',
         'bmi_tbl1.name',
         'bmi_ids.bmi_id',
         'company_transactions.remarks',
         'companies.name', 'bmi_tbl.name','companies.id',
         'company_transactions.id', 'company_transactions.transferToBank',
     )
     ->orderby( 'company_transactions.transferToBank' )
     ->orderby( 'company_transactions.id', 'DESC' )
     ->get( [
         'company_transactions.transfer_date',
         'company_transactions.fund_collector_id',
         'bmi_ids.name as fund_collector_name',
         'bmi_ids.bmi_id as fund_collector_bmip',
         'company_transactions.transfer_verification',
         'amount_cat_id',
         'bmi_tbl1.name as treasurer_name',
         'bmi_tbl.name as collected_from',
         'company_transactions.remarks',
 'companies.id as company_id',
         'companies.name as company_name',
         'company_transactions.id as company_transactionid',
         'company_transactions.transferToBank',
     ] );
     $arr = [];
     foreach ( $getFundCollectorDetails as $key=>$value ) {
         $d=Carbon::parse($value->transfer_date);
         if($temp==1){
             if($fromDate<=$d&&$d<=$toDate){
                 $t = Company_transaction::where( 'company_transactions.transfer_verification', '=', 1 )->where( 'company_transactions.transferToBank', '=', $value->transferToBank )
                 ->where( 'fund_collector_id', '=', $value->fund_collector_id )
                 ->where( 'transfer_date', '=', $value->transfer_date )
                 ->where( 'amount_cat_id', '=', $value->amount_cat_id )
                 ->where('company_id','=',$value->company_id)
                 ->sum( 'company_transactions.amount' );
                

                 $l[ 'fund_collector_id' ] = $value->fund_collector_bmip;
                 $l[ 'fund_collector_name' ] = $value->fund_collector_name;
                 $l[ 'transfer_date' ] = $value->transfer_date;
                 $l[ 'remarks' ] = $value->remarks;
                 $l[ 'amount_cat_id' ] = $value->amount_cat_id;
                 $l[ 'company_name' ] = $value->company_name;
                 $l[ 'collected_from' ] = $value->collected_from;
                 $l[ 'treasurer_name' ] = $value->treasurer_name;
                 $l['company_transaction_id']=$value->company_transactionid;
                 $l['transferToBank']=$value->transferToBank;
                 $l[ 'total_amount' ] = $t;
                 
                 $m= Company_transaction::where('company_transactions.id','=',$value->company_transactionid)->join('company_profits','company_transactions.id','=','company_profits.company_transaction_id')
                 ->get(['company_profits.month']);
                 $a=[];
                 $ii=new BmiIdController();
                 foreach($m as $key=>$value){
                     $monthName=$ii->getMonthName($value->month);
                     array_push($a,$monthName);
                 }
                 $l['month']=$a;
                 array_push( $arr, $l );
             }   
         } 
         else{
             if($d<=$toDate){
                 $t = Company_transaction::where( 'company_transactions.transfer_verification', '=', 1 )->where( 'company_transactions.transferToBank', '=', $value->transferToBank )
                 ->where( 'fund_collector_id', '=', $value->fund_collector_id )
                 ->where( 'transfer_date', '=', $value->transfer_date )
                 ->where( 'amount_cat_id', '=', $value->amount_cat_id )
                 ->where('company_id','=',$value->company_id)
                 ->sum( 'company_transactions.amount' );
                 $l[ 'fund_collector_id' ] = $value->fund_collector_bmip;
                 $l[ 'fund_collector_name' ] = $value->fund_collector_name;
                 $l[ 'transfer_date' ] = $value->transfer_date;
                 $l[ 'remarks' ] = $value->remarks;
                 $l[ 'amount_cat_id' ] = $value->amount_cat_id;
                 $l[ 'company_name' ] = $value->company_name;
                 $l[ 'collected_from' ] = $value->collected_from;
                 $l[ 'treasurer_name' ] = $value->treasurer_name;
                 $l['company_transaction_id']=$value->company_transactionid;
                 $l['transferToBank']=$value->transferToBank;
                 $l[ 'total_amount' ] = $t;
                $m= Company_transaction::where('company_transactions.id','=',$value->company_transactionid)->join('company_profits','company_transactions.id','=','company_profits.company_transaction_id')
                 ->get(['company_profits.month']);
               
                 $l['details']= Company_transaction::where('company_transactions.id','=',$value->company_transactionid)->join('company_profits','company_transactions.id','=','company_profits.company_transaction_id')
                 ->join('companies','company_profits.company_id','=','companies.id')
                 ->get(['company_profits.id',
                 'company_profits.company_id',
                 'company_profits.month',
                 'company_profits.year',
                 'company_profits.profit',
                 'companies.name as company_name'
                ]);
                 $a=[];
                 $ii=new BmiIdController();
                 foreach($m as $key=>$value){
                     $monthName=$ii->getMonthName($value->month);
                     array_push($a,$monthName);
                 }
              
                $l['month']=$a;
                
              
               
               
                 array_push( $arr, $l );
             }
         }  
     }
     return $arr;
 }
 
    }
 