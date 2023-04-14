<?php

namespace App\Http\Controllers;
use App\Models\User_transaction;
use App\Models\Amount_category;
use App\Models\Fund_Collector;
use App\Models\Bmi_id;
use App\Models\Monthly_sip;
use App\Models\Monthly_sip_details;
use Illuminate\support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserTransactionController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'amount' => 'required',
            'amount_cat_id' => 'required',
            'date' => 'required',
            'fund_collector_id' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials' ];
        }
        $select = Amount_category::where( 'id', '=', $request->amount_cat_id )
        ->get()
        ->first();
        $checkMonthlySip = Monthly_sip::where( 'monthly_sips.bmi_id', '=', $request->bmi_id )->where( 'monthly_sips.amount', '=', 0 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'monthly_sips.bmi_id' )
        ->get( [ 'monthly_sips.*', 'bmi_ids.bmi_id as bmipno' ] );
        if ( count( $checkMonthlySip )>0 ) {
            return [ 'code'=>401, 'message'=>'Please update the monthly sip of member with bmipno'.$checkMonthlySip[ 0 ]->bmipno.' of year '.$checkMonthlySip[ 0 ]->year ];
        }
        $i = 0;
        if ( $select ) {
            if (
                $select->code == 1 ||
                $select->code == 2 ||
                $select->code == 3
            ) {
                $insert = User_transaction::create( [
                    'bmi_id' => $request->bmi_id,
                    'amount' => $request->amount,
                    'amount_cat_id' => $request->amount_cat_id,
                    'date' => $request->date,
                    'fund_collector_id' => $request->fund_collector_id,
                    'transfer' => 0,
                    'transfer_verification' => 0,
                    'transferToBank' => 0,
                    'remarks' => $request->remarks,
                ] );
                if ( $insert ) {
                    if ( $select->code == 1 ) {
                        $i = $this->setMonthlySip(
                            $request->bmi_id,
                            $request->amount,
                            $insert->id,
                            $request->date
                        );
                    }

                    return [
                        'code' => 200,
                        'message' => 'inserted successfully ',
                    ];
                }
            } else {
                return [
                    'code' => 401,
                    'message' => 'Amount type not match',
                ];
            }
        }
    }

    public function setMonthlySip( $bmi_id, $amount, $transaction_id, $date ) {

        while( $amount > 0 ) {

            $lastmonthDetails = DB::table( 'monthly_sip_details' )
            ->where( 'bmi_id', '=', $bmi_id )
            ->get()
            ->last();

            if ( !$lastmonthDetails ) {

                $getBMIDetails = DB::table( 'bmi_ids' )
                ->where( 'id', '=', $bmi_id )
                ->get()
                ->first();
                if ( $getBMIDetails ) {
                    $year = Carbon::createFromFormat( 'd-m-Y', $getBMIDetails->joining_date )->year;
                    $month = Carbon::createFromFormat( 'd-m-Y', $getBMIDetails->joining_date )->month;
                    $getMonthlySip = DB::table( 'monthly_sips' )
                    ->where( 'bmi_id', '=', $bmi_id )
                    ->where( 'year', '=', $year )
                    ->get()
                    ->first();
                    if ( $getMonthlySip ) {
                        $monthly_sip_id = $getMonthlySip->id;
                        if ( $amount>$getMonthlySip->amount ) {
                            $amt = $getMonthlySip->amount;
                            $amount = $amount-$amt;

                        } else {

                            $amt = $amount;
                            $amount = $amount-$amt;

                        }
                    }
                }

            } else {

                $getMonthlySip = DB::table( 'monthly_sips' )
                ->where( 'bmi_id', '=', $bmi_id )
                ->where( 'year', '=', $lastmonthDetails->year )
                ->get()
                ->first();
                $recentPayedAmount = DB::table( 'monthly_sip_details' )
                ->where( 'bmi_id', '=', $bmi_id )
                ->where( 'month', '=', $lastmonthDetails->month )
                ->where( 'year', '=', $lastmonthDetails->year )
                ->sum( 'amount' );
                if ( $getMonthlySip->amount == $recentPayedAmount ) {
                    $month = $lastmonthDetails->month+1;
                    $year = $lastmonthDetails->year;
                    if ( $month == 13 ) {
                        $month = 1;
                        $year = $lastmonthDetails->year+1;
                    }
                    $getMonthlySip1 = DB::table( 'monthly_sips' )
                    ->where( 'bmi_id', '=', $bmi_id )
                    ->where( 'year', '=', $year )
                    ->get()
                    ->first();
                    if ( $getMonthlySip1 ) {
                        $monthly_sip_id = $getMonthlySip1->id;
                        if ( $amount>$getMonthlySip1->amount ) {
                            $amt = $getMonthlySip1->amount;
                            $amount = $amount-$amt;
                        } else {
                            $amt = $amount;
                            $amount = $amount-$amt;
                        }
                    } else {
                        $insert = Monthly_sip::create( [
                            'bmi_id' => $bmi_id,
                            'amount' => $getMonthlySip->amount,
                            'year' => $year,
                        ] );
                        $getInsertedMonthlySip = Monthly_sip::where( 'bmi_id', '=', $bmi_id )
                        ->where( 'year', '=', $year )->get()->first();
                        $monthly_sip_id = $getInsertedMonthlySip->id;
                        if ( $amount>$getInsertedMonthlySip->amount ) {
                            $amt = $getInsertedMonthlySip->amount;
                            $amount = $amount-$amt;
                        } else {
                            $amt = $amount;
                            $amount = $amount-$amt;
                        }
                    }
                } else {
                    $month = $lastmonthDetails->month;
                    $year = $lastmonthDetails->year;
                    $getMonthlySip = DB::table( 'monthly_sips' )
                    ->where( 'bmi_id', '=', $bmi_id )
                    ->where( 'year', '=', $year )
                    ->get()
                    ->first();
                    $recentPayedAmount = DB::table( 'monthly_sip_details' )
                    ->where( 'bmi_id', '=', $bmi_id )
                    ->where( 'month', '=', $month )
                    ->where( 'year', '=', $year )
                    ->sum( 'amount' );
                    if ( $getMonthlySip ) {
                        $monthly_sip_id = $getMonthlySip->id;
                        $amtToPay = $getMonthlySip->amount-$recentPayedAmount;
                        if ( $amount>$amtToPay ) {
                            $amt = $amtToPay;
                            $amount = $amount-$amt;
                        } else {
                            $amt = $amount;
                            $amount = $amount-$amount;
                        }
                    }
                }
            }

            $insertMonthlySipDetails = DB::table( 'monthly_sip_details' )->insert(
                [
                    'bmi_id' => $bmi_id,
                    'year' => $year,
                    'month' => $month,
                    'amount' => $amt,
                    'monthly_sip_id' => $monthly_sip_id,
                    'transaction_id' => $transaction_id,
                    'status' =>0,
                ]
            );

            // return $amount;

        }

    }

    public function update_transaction( Request $request ) {

        $select = Amount_category::where( 'id', '=', $request->amount_cat_id )
        ->get()
        ->first();
        // return $select;
        $i = 0;
        if ( $select ) {
            if (
                $select->code == 1 ||
                $select->code == 2 ||
                $select->code == 3
            ) {
                $update = User_transaction::where( 'id', '=', $request->id )->where( 'transfer_verification', '=', 0 )->update( [
                    'bmi_id' => $request->bmi_id,
                    'amount' => $request->amount,
                    'amount_cat_id' => $request->amount_cat_id,
                    'date' => $request->date,
                    'fund_collector_id' => $request->fund_collector_id,
                    'transfer' => 0,
                    'transfer_verification' => 0,
                    'transferToBank' => 0,
                    'remarks' => $request->remarks,
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
                    'message' => 'Amount type not match',
                ];
            }
        }

    }

    //     public function fundtransferdetails_user()
    // {
    //         $select = DB::table( 'user_transactions' )
    //         // ->where( 'user_transactions.transfer', '=', 1 )
    //         // ->where( 'user_transactions.transfer_verification', '=', 0 )

    //         ->join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )
    //         // ->join( 'fund_collectors' )
    //         ->join(
    //             'amount_categories',
    //             'user_transactions.amount_cat_id',
    //             '=',
    //             'amount_categories.id'
    // ) ->orderby( 'user_transactions.id', 'DESC' )

    //         ->get( [
    //             'user_transactions.id as user_transaction_id',
    //             'bmi_ids.bmi_id',
    //             'bmi_ids.name as member_name',
    //             'user_transactions.date as date_of_payment',
    //             'user_transactions.amount',
    //             'user_transactions.remarks',
    //             'amount_categories.amount_type',
    //             'user_transactions.transfer_verification', //transfer verification 0-pending, 1-approved
    //             'user_transactions.transferToBank',
    //             'user_transactions.transfer',

    // ] );
    //         return $select;
    //     }

    public function fundtransferdetails_user( Request $request )
 {
        $getfundcollector = DB::table( 'fund_collectors' )->where( 'fund_collectors.bmi_id', '=', $request->bmi_id )->where( 'ending_date', '=', null )->get();
        if ( $getfundcollector ) {
            $select = DB::table( 'user_transactions' )
            // ->where( 'user_transactions.transfer', '=', 1 )
            // ->where( 'user_transactions.transfer_verification', '=', 0 )

            ->join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )->where( 'user_transactions.fund_collector_id', '=', $request->bmi_id )
            // ->join( 'fund_collectors' )
            ->join(
                'amount_categories',
                'user_transactions.amount_cat_id',
                '=',
                'amount_categories.id'
            ) ->orderby( 'user_transactions.id', 'DESC' )

            ->get( [
                'user_transactions.id as user_transaction_id',
                'bmi_ids.bmi_id',
                'user_transactions.fund_collector_id',
                'bmi_ids.name as member_name',
                'user_transactions.date as date_of_payment',
                'user_transactions.amount',
                'user_transactions.remarks',
                'amount_categories.amount_type',
                'amount_categories.code as amount_cat_id',
                'user_transactions.transfer_verification', //transfer verification 0-pending, 1-approved
                'user_transactions.transferToBank',
                'user_transactions.transfer',

            ] );
        }
        return $select;
    }

    public function update_transfertobank( Request $request )
 {
        $update = User_transaction::where( 'transferToBank', 0 )->update( [
            'transferToBank' => 1,
            'transferToBank_date' => $request->transferToBank_date,
        ] );
        if ( $update ) {
            return [ 'code' => 200, 'message' => 'updated successfully ' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function list_individual()
 {
        $select = DB::table( 'user_transactions' )

        ->join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )
        ->join(
            'monthly_sip_details',
            'user_transactions.id',
            '=',
            'monthly_sip_details.transaction_id'
        )
        ->join(
            'amount_categories',
            'user_transactions.amount_cat_id',
            '=',
            'amount_categories.code'
        )
        ->get( [
            'bmi_ids.bmi_id',
            'bmi_ids.name as member_name',
            'user_transactions.date as transaction_date',
            'user_transactions.amount',
            'user_transactions.remarks',
            'amount_categories.code',
            'amount_categories.amount_type',
        ] );
        return $select;
    }

    public function fund_received()
 {
        $select = DB::table( 'user_transactions' )
        // ->where( 'fund_collector_id', '=', $fund_collector_id )
        ->join(
            'fund_collectors',
            'user_transactions.fund_collector_id',
            '=',
            'fund_collectors.bmi_id'
        )
        ->join( 'bmi_ids', 'fund_collectors.bmi_id', '=', 'bmi_ids.id' )
        ->get( [
            'bmi_ids.bmi_id',
            'bmi_ids.name',
            'user_transactions.transfer_date',
            'user_transactions.remarks',
            'user_transactions.amount',
        ] );
        return $select;
    }

    public function getUserExpenseDetails( $bmi_id ) {
        $ExpenseDetails = User_transaction::where(
            'user_transactions.bmi_id',
            '=',
            $bmi_id
        )
        ->where( 'amount_categories.code', '=', 2 )
        ->join(
            'amount_categories',
            'amount_categories.id',
            '=',
            'user_transactions.amount_cat_id'
        )
        ->sum( 'user_transactions.amount' );
        return $ExpenseDetails;
    }

    public function getUserExpenseDetails_m( $bmi_id, $year, $month ) {
        $ExpenseDetails = User_transaction::where(
            'user_transactions.bmi_id',
            '=',
            $bmi_id
        )
        ->where( 'amount_categories.code', '=', 2 )
        ->join(
            'amount_categories',
            'amount_categories.id',
            '=',
            'user_transactions.amount_cat_id'
        )
        ->get( [ 'user_transactions.amount', 'user_transactions.date' ] );
        $t = 0;
        if ( count( $ExpenseDetails ) == 0 ) {
            return $t;
        }
        foreach ( $ExpenseDetails as $key => $value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y < $year ) {
                $t += $value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $t += $value->amount;
            }
        }
        return $t;
    }

    public function getUserExpenseDetails_v( $bmi_id, $fromDate, $toDate ) {
        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) != Carbon::now()->format( 'd-m-Y' ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $ExpenseDetails = User_transaction::where(
            'user_transactions.bmi_id',
            '=',
            $bmi_id
        )
        ->where( 'amount_categories.code', '=', 2 )
        ->join(
            'amount_categories',
            'amount_categories.id',
            '=',
            'user_transactions.amount_cat_id'
        )
        ->get( [ 'user_transactions.amount', 'user_transactions.date' ] );
        $t = 0;
        if ( count( $ExpenseDetails ) == 0 ) {
            return $t;
        }
        foreach ( $ExpenseDetails as $key => $value ) {
            $d = Carbon::parse( $value->date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $t += $value->amount;

                }
            } else {
                // if ( $d <= $toDate ) {
                $t += $value->amount;

                // }
            }

        }
        return $t;
    }

    public function updateTransfer_user( Request $request ) {
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
            $updateStatus = User_transaction::where( 'id', '=', $value )
            ->where( 'amount_cat_id', '=', $request->amount_cate_id )
            ->update( [
                'transfer' => 1,
                'transfer_date' => Carbon::now()->format( 'd-m-Y' ),
            ] );
            if ( !$updateStatus ) {
                $i = 1;
            }

        }
        if ( $i ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        } else {
            return [
                'code' => 200,
                'message' => 'Transfer to main fund Collector',
            ];
        }
    }

    public function getTransferCollection_user() {

        $select = User_transaction::where( 'transfer', '=', 1 )
        ->where( 'transfer_verification', '=', 0 )
        ->join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )
        ->get( [ 'user_transactions.*', 'bmi_ids.name as member_name' ] );
        return $select;

    }

    public function ApproveUserTransactionByTreasurer( Request $request ) {

        $validator = Validator::make( $request->all(), [
            'transactionArray' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials' ];
        }
        $i = 0;
        $idArray = $request->transactionArray ? json_decode( $request->transactionArray ) : [];
        foreach ( $idArray as $key=>$value ) {
            $approve = User_Transaction::where( 'id', '=', $value )->update( [
                'transfer_verification' => 1,
                'treasurer_id'=>$request->treasurer_id,
                'transfer_verification_date' => Carbon::now()->format( 'd-m-Y' ),
            ] );
            if ( !$approve ) {
                $i = 1;
            }
        }

        if ( $i == 0 ) {
            $c = new NotificationController();
            $select = Fund_collector::where( 'status', '=', 1 )->where( 'ending_date', '=', null )->pluck( 'bmi_id' );
            $date = Carbon::now()->format( 'Y-m-d' );
            $content = 'User transaction approved by the treasurer ';
            $status = 0;
            $k = $c->insertNotification( $date, $content, $select, $status );
            return [ 'code' => 200, 'message' => 'Transaction approved' ];
        } else {
            return [ 'code' => 401, 'message' => 'something went wrong' ];
        }
    }

    public function getApprovedUserTransaction() {
        $select = User_Transaction::where( 'transfer_verification', '=', 1 )
        // ->where( 'transferToBank', '=', 0 )

        ->join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )
        ->orderby( 'user_transactions.bmi_id', 'DESC' )
        ->get( [ 'user_transactions.*', 'bmi_ids.name as member_name' ] );
        return $select;
    }

    public function transfertobank_user( Request $request ) {
        $validator = Validator::make( $request->all(), [
            // 'amount_cate_id' => 'required',
            'transactionArray' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials' ];
        }
        $i = 0;
        $idArray = $request->transactionArray ? json_decode( $request->transactionArray ) : [];
        // $idArray = $request->idArray;
        foreach ( $idArray as $key => $value ) {
            $updateStatus = User_transaction::where( 'id', '=', $value )
            // ->where( 'amount_cat_id', '=', $request->amount_cate_id )
            ->update( [
                'transferToBank' => 1,
                'transferToBank_date' => Carbon::now()->format( 'Y-m-d' ),
            ] );
            if ( !$updateStatus ) {
                $i = 1;
            }

        }
        if ( $i ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        } else {
            return [
                'code' => 200,
                'message' => 'transfer to bank',
            ];
        }

    }

    public function fundtransferdetails() {
        $select = DB::table( 'user_transactions' )
        ->join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )
        ->join(
            'monthly_sip_details',
            'user_transactions.id',
            '=',
            'monthly_sip_details.transaction_id'
        )
        ->join(
            'amount_categories',
            'user_transactions.amount_cat_id',
            '=',
            'amount_categories.code'
        )
        ->get( [
            'bmi_ids.bmi_id',
            'bmi_ids.name as member_name',
            'user_transactions.date as transaction_date',
            'user_transactions.amount',
            'user_transactions.remarks',
            'amount_categories.code',
            'amount_categories.amount_type',
        ] );
        // return $select;
        $select1 = DB::table( 'company_transactions' )
        ->join(
            'companies',
            'company_transactions.company_id',
            '=',
            'companies.id'
        )
        ->join(
            'amount_categories',
            'company_transactions.amount_cat_id',
            '=',
            'amount_categories.id'
        )
        ->get( [
            'companies.name as company_name',
            'company_transactions.amount',
            'company_transactions.date as date_of_payment',
            'company_transactions.collected_from',
            'company_transactions.remarks',
            'company_transactions.transfer_verification as status',
            'amount_categories.code',
            'amount_categories.amount_type',
        ] );
        $item = $select1->merge( $select );
        return $item;
    }

    public function expense_in() {
        $v = new MonthlySipController;
        $select = DB::table( 'user_transactions' )
        ->where( 'user_transactions.amount_cat_id', '=', 2 )
        ->where( 'user_transactions.transferToBank', '=', 1 )
        ->join( 'bmi_ids as a', 'user_transactions.bmi_id', '=', 'a.id' )
        // ->where( 'a.status', '!=', 3 )
        ->where( 'a.verify', '=', 1 )
        ->join(
            'bmi_ids as b',
            'user_transactions.fund_collector_id',
            '=',
            'b.id'
        )

        ->get( [
            'a.name as member_name',
            'a.bmi_id as bmip_no',
            'a.status',
            'b.name as fund_collector',
            'b.id as fund_collector_id',
            'user_transactions.date',
            'user_transactions.remarks',
            'user_transactions.amount',
            'user_transactions.amount_cat_id',
        ] );

        $l = $v->AmountAvalilableinBank();
        return [ 'amount'=>$l, 'details'=>$select ];
    }

    public function others_in() {
        $v = new MonthlySipController;
        $select = DB::table( 'user_transactions' )
        ->where( 'user_transactions.amount_cat_id', '=', 3 )
        // ->where( 'user_transactions.transferToBank', '=', 1 )
        ->join( 'bmi_ids as a', 'user_transactions.bmi_id', '=', 'a.id' )->where( 'a.verify', '=', 1 )
        ->join(
            'bmi_ids as b',
            'user_transactions.fund_collector_id',
            '=',
            'b.id'
        )

        ->get( [
            'a.name as member_name',
            'a.bmi_id as bmip_no',
            'a.status',
            'b.name as fund_collector',
            'b.id as fund_collector_id',
            'user_transactions.date',
            'user_transactions.remarks',
            'user_transactions.transferToBank',
            'user_transactions.amount',
            'user_transactions.amount_cat_id',
        ] );
        $l = $v->AmountAvalilableinBank();
        return [ 'amount'=>$l, 'details'=>$select ];
    }

    public function monthlysip_in()
 {
        $v = new MonthlySipController;
        $select = DB::table( 'user_transactions' )
        ->where( 'user_transactions.amount_cat_id', '=', 1 )
        ->where( 'user_transactions.transferToBank', '=', 1 )
        ->join( 'bmi_ids as a', 'user_transactions.bmi_id', '=', 'a.id' )
        // ->where( 'a.status', '=', 0 )
        ->where( 'a.verify', '=', 1 )
        ->join(
            'monthly_sips',
            'user_transactions.bmi_id',
            '=',
            'monthly_sips.bmi_id'
        )
        ->join(
            'bmi_ids as b',
            'user_transactions.fund_collector_id',
            '=',
            'b.id'
        )

        ->get( [
            'a.name as member_name',
            'a.bmi_id as bmip_no',
            'a.status',
            'b.name as fund_collector',
            'b.id as fund_collector_id',
            'user_transactions.date',
            'user_transactions.remarks',
            'user_transactions.amount',
            'user_transactions.amount_cat_id'
        ] );
        $l = $v->AmountAvalilableinBank();
        return [ 'amount'=>$l, 'details'=>$select ];
    }

    public function getusertransaction_approvelist()
 {
        $select = DB::table( 'user_transactions' )
        ->where( 'user_transactions.transferToBank', '=', 0 )
        ->where( 'user_transactions.transfer', '=', 1 )
        ->where( 'user_transactions.transfer_verification', '=', 1 )
        ->join(
            'fund_collectors',
            'user_transactions.fund_collector_id',
            '=',
            'fund_collectors.bmi_id'
        )

        ->join( 'bmi_ids', 'fund_collectors.bmi_id', '=', 'bmi_ids.id' )
        ->join(
            'amount_categories',
            'user_transactions.amount_cat_id',
            '=',
            'amount_categories.id'
        )
        ->get( [
            'user_transactions.*',
            'bmi_ids.name as fund_collector_name',
            'amount_categories.*',
        ] );
        // return $select;
        $arr = [];
        foreach ( $select as $key => $value ) {
            $l[ 'fund collector bmi id' ] = $value->bmi_id;
            $l[ 'fund collector id' ] = $value->fund_collector_id;
            $l[ 'transfer date' ] = $value->transfer_date;
            $l[ 'remarks' ] = $value->remarks;
            $l[ 'name' ] = $value->fund_collector_name;
            $l[ 'amount type' ] = $value->code;
            $l[ 'amount' ] = user_transaction::where(
                'user_transactions.fund_collector_id',
                '=',
                $value->fund_collector_id
            )
            ->where(
                'user_transactions.transfer_date',
                '=',
                $value->transfer_date
            )

            ->sum( 'user_transactions.amount' );
            $l[ 'members_list' ] = $this->getMemberList( $value->bmi_id );
            array_push( $arr, $l );
        }
        return $arr;
    }

    public function getMemberList( $bmi_id )
 {
        $getUsers = Bmi_id::get();

        $userArray = [];
        foreach ( $getUsers as $k => $v ) {
            $checkinvested = user_transaction::where( 'bmi_id', '=', $bmi_id )

            ->where( 'bmi_id', '=', $v->id )
            ->get();

            if ( count( $checkinvested ) > 0 ) {
                $li[ 'bmi_id' ] = $v->bmi_id;
                $li[ 'name' ] = $v->name;
                $i = user_transaction::where( 'bmi_id', '=', $bmi_id )
                ->where( 'bmi_id', '=', $v->id )
                ->where( 'user_transactions.transferToBank', '=', 0 )
                ->where( 'user_transactions.transfer', '=', 1 )
                ->where( 'user_transactions.transfer_verification', '=', 1 )
                ->get();
                foreach ( $i as $key => $a ) {
                    $li[ 'amount' ] = user_transaction::where(
                        'bmi_id',
                        '=',
                        $bmi_id
                    )
                    ->where( 'bmi_id', '=', $v->id )
                    ->where( 'transferToBank', '=', $a->transferToBank )
                    ->where( 'transfer', '=', $a->transfer )
                    ->where(
                        'transfer_verification',
                        '=',
                        $a->transfer_verification
                    )
                    ->get();
                }

                array_push( $userArray, $li );
            }
        }
        return $userArray;
    }

    public function getTransferUserTransactionDetails() {
        $getFundCollectorDetails = User_transaction::where( 'user_transactions.transfer', '=', 1 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.fund_collector_id' )
        ->groupby( 'user_transactions.fund_collector_id', 'user_transactions.transfer_date', 'bmi_ids.name', 'user_transactions.transfer_verification', 'amount_cat_id', 'bmi_ids.bmi_id' )
        ->orderby( 'user_transactions.transfer_verification' )
        ->get( [
            'user_transactions.transfer_date',
            'user_transactions.fund_collector_id',
            'bmi_ids.name as fund_collector_name',
            'bmi_ids.bmi_id as fund_collector_bmip',
            'user_transactions.transfer_verification',
            'amount_cat_id'
        ] );
        foreach ( $getFundCollectorDetails as $key=>$value ) {
            $value->amount = User_transaction::where( 'user_transactions.transfer', '=', 1 )->where( 'user_transactions.transfer_verification', '=', $value->transfer_verification )
            ->where( 'fund_collector_id', '=', $value->fund_collector_id )
            ->where( 'transfer_date', '=', $value->transfer_date )
            ->where( 'amount_cat_id', '=', $value->amount_cat_id )
            ->sum( 'amount' );
            $getD = User_transaction::where( 'user_transactions.transfer', '=', 1 )->where( 'user_transactions.transfer_verification', '=', $value->transfer_verification )
            ->where( 'fund_collector_id', '=', $value->fund_collector_id )
            ->where( 'transfer_date', '=', $value->transfer_date )
            ->where( 'amount_cat_id', '=', $value->amount_cat_id )
            ->get( [ 'id' ] );
            $member_list = array();
            $transactionArray = array();
            foreach ( $getD as $k=>$v ) {
                array_push( $transactionArray, $v->id );
                // if ( $v->amount_cat_id == 1 ) {
                $getUsers = User_transaction::where( 'user_transactions.id', '=', $v->id )
                ->where( 'amount_cat_id', '=', $value->amount_cat_id )
                ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.bmi_id' )
                ->get( [
                    'bmi_ids.bmi_id as bmipno',
                    'bmi_ids.name',
                    'user_transactions.date',
                    'user_transactions.amount',
                    'user_transactions.remarks'

                ] );
                if ( count( $getUsers )>0 ) {
                    array_push( $member_list, ...$getUsers );
                }
                // }
            }
            $value->transactionIdArray = $transactionArray;
            $value->members_list = $member_list;
        }
        return $getFundCollectorDetails;
    }

    public function getVerifiedUserTransactionDetails( Request $request ) {
        if ( isset( $request->fromDate ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $request->fromDate );
        $toDate = Carbon::parse( $request->toDate );
        $getFundCollectorDetails = User_transaction::where( 'user_transactions.transfer_verification', '=', 1 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.fund_collector_id' )
        ->join( 'bmi_ids as bmi_tbl', 'bmi_tbl.id', '=', 'user_transactions.treasurer_id' )
        ->groupby(
            'user_transactions.fund_collector_id',
            'user_transactions.transfer_date',
            'bmi_ids.name',
            'user_transactions.transferToBank',
            'amount_cat_id',
            'bmi_tbl.name',
            'bmi_ids.bmi_id'
        )
        ->orderby( 'user_transactions.transferToBank' )
        ->get( [
            'user_transactions.transfer_date',
            'user_transactions.fund_collector_id',
            'bmi_ids.name as fund_collector_name',
            'bmi_ids.bmi_id as fund_collector_bmip',
            'user_transactions.transferToBank',
            'amount_cat_id',
            'bmi_tbl.name as treasurer'
        ] );
        $arr = array();
        foreach ( $getFundCollectorDetails as $key=>$value ) {
            $d = Carbon::parse( $value->transfer_date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $l[ 'transfer_date' ] = $value->transfer_date;
                    $l[ 'fund_collector_id' ] = $value->fund_collector_id;
                    $l[ 'fund_collector_name' ] = $value->fund_collector_name;
                    $l[ 'fund_collector_bmip' ] = $value->fund_collector_bmip;
                    $l[ 'transferToBank' ] = $value->transferToBank;
                    $l[ 'amount_cat_id' ] = $value->amount_cat_id;
                    $l[ 'treasurer' ] = $value->treasurer;
                    $l[ 'amount' ] = User_transaction::where( 'user_transactions.transfer_verification', '=', 1 )->where( 'user_transactions.transferToBank', '=', $value->transferToBank )
                    ->where( 'fund_collector_id', '=', $value->fund_collector_id )
                    ->where( 'transfer_date', '=', $value->transfer_date )
                    ->where( 'amount_cat_id', '=', $value->amount_cat_id )
                    ->sum( 'amount' );
                    $getD = User_transaction::where( 'user_transactions.transfer_verification', '=', 1 )->where( 'user_transactions.transferToBank', '=', $value->transferToBank )
                    ->where( 'fund_collector_id', '=', $value->fund_collector_id )
                    ->where( 'transfer_date', '=', $value->transfer_date )
                    ->where( 'amount_cat_id', '=', $value->amount_cat_id )
                    ->get( [ 'id' ] );
                    $transactionArray = array();
                    $member_list = array();
                    foreach ( $getD as $k=>$v ) {
                        array_push( $transactionArray, $v->id );
                        // if ( $v->amount_cat_id == 1 ) {
                        $getUsers = User_transaction::where( 'user_transactions.id', '=', $v->id )
                        ->where( 'amount_cat_id', '=', $value->amount_cat_id )
                        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.bmi_id' )
                        ->get( [
                            'bmi_ids.bmi_id as bmipno',
                            'bmi_ids.name',
                            'user_transactions.date',
                            'user_transactions.amount',
                            'user_transactions.remarks',
                        ] );
                        if ( count( $getUsers )>0 ) {
                            array_push( $member_list, ...$getUsers );
                        }
                        // }
                    }
                    $l[ 'transactionIdArray' ] = $transactionArray;
                    $l[ 'members_list' ] = $member_list;
                    array_push( $arr, $l );
                }
            } else {
                if ( $d <= $toDate ) {
                    $l[ 'transfer_date' ] = $value->transfer_date;
                    $l[ 'fund_collector_id' ] = $value->fund_collector_id;
                    $l[ 'fund_collector_name' ] = $value->fund_collector_name;
                    $l[ 'fund_collector_bmip' ] = $value->fund_collector_bmip;
                    $l[ 'transferToBank' ] = $value->transferToBank;
                    $l[ 'amount_cat_id' ] = $value->amount_cat_id;
                    $l[ 'treasurer' ] = $value->treasurer;
                    $l[ 'amount' ] = User_transaction::where( 'user_transactions.transfer_verification', '=', 1 )->where( 'user_transactions.transferToBank', '=', $value->transferToBank )
                    ->where( 'fund_collector_id', '=', $value->fund_collector_id )
                    ->where( 'transfer_date', '=', $value->transfer_date )
                    ->where( 'amount_cat_id', '=', $value->amount_cat_id )
                    ->sum( 'amount' );
                    $getD = User_transaction::where( 'user_transactions.transfer_verification', '=', 1 )->where( 'user_transactions.transferToBank', '=', $value->transferToBank )
                    ->where( 'fund_collector_id', '=', $value->fund_collector_id )
                    ->where( 'transfer_date', '=', $value->transfer_date )
                    ->where( 'amount_cat_id', '=', $value->amount_cat_id )
                    ->get( [ 'id' ] );
                    $transactionArray = array();
                    $member_list = array();
                    foreach ( $getD as $k=>$v ) {
                        array_push( $transactionArray, $v->id );
                        $getUsers = User_transaction::where( 'user_transactions.id', '=', $v->id )
                        ->where( 'amount_cat_id', '=', $value->amount_cat_id )
                        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.bmi_id' )
                        ->get( [
                            'bmi_ids.bmi_id as bmipno',
                            'bmi_ids.name',
                            'user_transactions.date',
                            'user_transactions.amount',
                            'user_transactions.remarks',
                        ] );
                        if ( count( $getUsers )>0 ) {
                            array_push( $member_list, ...$getUsers );
                        }
                    }
                    $l[ 'transactionIdArray' ] = $transactionArray;
                    $l[ 'members_list' ] = $member_list;
                    array_push( $arr, $l );
                }
            }

        }
        return $arr;
    }

    public function getothers_v( $bmi_id, $fromDate, $toDate ) {
        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) != Carbon::now()->format( 'd-m-Y' ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $getothers = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )
        ->where( 'user_transactions.transferToBank', '=', 1 )->
        where( 'amount_categories.code', '=', 3 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [ 'user_transactions.amount', 'user_transactions.date' ] );

        $t = 0;
        if ( count( $getothers ) == 0 ) {
            return $t;
        }
        foreach ( $getothers  as $key=>$value ) {
            $d = Carbon::parse( $value->date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $t = $t + $value->amount;
                }
            } else {
                // if ( $d <= $toDate ) {
                $t = $t +$value->amount;
                // }
            }

        }
        return $t;
    }

    public function getothers_m( $bmi_id, $year, $month ) {
        $getothers = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )
        // ->where( 'user_transactions.transferToBank', '=', 1 )
        ->where( 'amount_categories.code', '=', 3 )

        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [ 'user_transactions.amount', 'user_transactions.date' ] );

        $t = 0;
        if ( count( $getothers ) == 0 ) {
            return $t;
        }
        foreach ( $getothers as $key=>$value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $t = $t +$value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $t = $t + $value->amount;
            }
        }
        return $t;
    }

}