<?php

namespace App\Http\Controllers;
use App\Models\Expense;
use App\Models\Bmi_id;
use App\Models\User_expense;
use Illuminate\Http\Request;
use App\Models\User_transaction;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( request()->all(), [
            'date' => 'required',
            'amount' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
        $getusertransactionexpense = User_transaction::join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )
        ->where( 'amount_categories.code', '=', 2 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->where( 'bmi_ids.status', '=', 0 )
        ->where( 'bmi_ids.verify', '=', 1 )
        ->sum( 'user_transactions.amount' );
        $getuserexpense = User_expense::join( 'bmi_ids', 'user_expenses.bmi_id', '=', 'bmi_ids.id' )
        ->where( 'bmi_ids.status', '=', 0 )
        ->where( 'bmi_ids.verify', '=', 1 )
        ->sum( 'user_expenses.amount' );
        $expense =  $getusertransactionexpense - $getuserexpense;
        if ( $expense<$request->amount ) {

            return[ 'code' => 401, 'message' => 'Insuffient balance in expense' ];
        } else {

            $insert = Expense::create( [
                'date' => $request->date,
                'amount' => $request->amount,
                'purpose' => $request->purpose,
                'transferfromBank' => 0,
                'treasurer' => $request->treasurer,
            ] );
            if ( $insert ) {
                return [ 'code' => 200, 'message' => 'inserted' ];
            } else {
                return [ 'code' => 401, 'message' => 'Something went wrong' ];
            }
        }
    }

    public function update( Request $request )
 {
        $insert = Expense::where( 'id', '=', $request->id )->update( [
            'date' => $request->date,
            'amount' => $request->amount,
            'purpose' => $request->purpose,
            //   'transferfromBank' => 0,
            'transferfromBank_date' => Carbon::now()->format( 'd-m-Y' ),
            'treasurer' => $request->treasurer,
        ] );
        if ( $insert ) {
            return [ 'code' => 200, 'message' => 'updated' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function list_expense()
 {
        $select = DB::table( 'expenses' )
        ->join( 'treasurers', 'expenses.treasurer', '=', 'treasurers.bmi_id' )
        ->join( 'bmi_ids', 'treasurers.bmi_id', '=', 'bmi_ids.id' )
        ->where( 'treasurers.ending_date', '=', null )

        ->where( 'expenses.transferFromBank', 0 )

        ->get( [
            'expenses.id',
            'expenses.date',
            'expenses.amount',
            'expenses.purpose',
            'bmi_ids.name as treasurer_name',
            'bmi_ids.bmi_id',
            'treasurers.treasurer',
        ] );
        return $select;
        if ( $select->isEmpty() ) {
            return [ 'code' => 401, 'message' => 'no result found ' ];
        } else {
            return $select;
        }
    }

    public function transfer_expense( Request $request )
 {
        $c = new NotificationController();
        $selectExpense = Expense::where( 'id', '=', $request->id )
        ->get()
        ->first();
        if ( !$selectExpense ) {
            return [
                'code' => 200,
                'message' => 'This expense details is not exist',
            ];
        }
        $update = Expense::where( 'id', $request->id )->update( [
            'transferFromBank' => 1,
            'transferFromBank_date' => Carbon::now()->format( 'd-m-Y' ),
        ] );
        if ( $update ) {
            $this->updateUserExpense( $request->id, $selectExpense->amount );
            $select = Bmi_id::where( 'status', '=', 0 )->where( 'verify', '=', 1 )->pluck( 'id' );
            $date = Carbon::now()->format( 'Y-m-d' );
            // $getDetails = User_expense::where( 'bmi_id', '=', $request->bmi_id )->get()->first();
            $content = 'expense debited from the account ';

            $k = $c->insertNotification( $date, $content, $select, 1 );
            return [ 'code' => 200, 'message' => 'updated successfully ' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function get_expenseout()
 {
        $v = new MonthlySipController;
        $select = Expense::get( [ 'id', 'date', 'amount', 'purpose' ] );

        $l = $v->AmountAvalilableinBank();
        if ( $select->isEmpty() ) {
            return [ 'code' => 401, 'message' => 'no result found ' ];
        } else {

            return [ 'amount'=>$l, 'details'=>$select ];
        }
    }

    public function updateUserExpense( $expense_id, $amount )
 {
        $getBmiUsers = Bmi_id::where( 'status', '=', 0 )->where( 'verify', '=', 1 )->get();
        $count = count( $getBmiUsers );
        $amt = $amount / $count;
        foreach ( $getBmiUsers as $key => $value ) {
            $addUserExpense = User_expense::create( [
                'bmi_id' => $value->id,
                'expense_id' => $expense_id,
                'amount' => $amt,
            ] );
        }
    }

    public function getExpenseSummary( Request $request ) {
        if ( isset( $request->fromDate ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $request->fromDate );
        $toDate = Carbon::parse( $request->toDate );
        $getUsers = Bmi_id::where( 'verify', '=', 1 )->get( [
            'id',
            'bmi_id',
            'name',
            'status'
        ] );
        $arr = [];
        if ( count( $getUsers ) == 0 ) {
            return [ 'code' => 401, 'message' => 'No users yet.' ];
        }
        foreach ( $getUsers as $key => $value ) {
            $l[ 'bmi_id' ] = $value->bmi_id;
            $l[ 'name' ] = $value->name;
            $l[ 'status' ] = $value->status;
            $getFundcollector = User_transaction::where( 'user_transactions.bmi_id', '=', $value->id )->where( 'user_transactions.transferToBank', '=', 1 )
            ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.fund_collector_id' )
            ->get( [ 'bmi_ids.bmi_id', 'bmi_ids.name' ] )
            ->last();
            // return $getFundcollector->bmi_id.' '.$getFundcollector->name;
            if ( $getFundcollector ) {
                $l[ 'fundcollector' ] =
                $getFundcollector->bmi_id . ' ' . $getFundcollector->name;
            }
            $expCollected = User_transaction::where(
                'amount_categories.code',
                '=',
                2
            )
            // ->where( 'user_transactions.transferToBank', '=', 1 )
            ->where( 'user_transactions.bmi_id', '=', $value->id )
            ->join(
                'amount_categories',
                'amount_categories.id',
                '=',
                'user_transactions.amount_cat_id'
            )
            ->get( [ 'date', 'amount' ] );
            $l[ 'totalExpenseCollected' ] = 0;
            foreach ( $expCollected as $k=>$v ) {

                $d = Carbon::parse( $v->date );
                if ( $temp == 0 ) {
                    if ( $d <= $toDate ) {
                        $l[ 'totalExpenseCollected' ] = $l[ 'totalExpenseCollected' ]+$v->amount;
                    }
                } else {
                    if ( $fromDate <= $d && $d <= $toDate ) {
                        $l[ 'totalExpenseCollected' ] = $l[ 'totalExpenseCollected' ]+$v->amount;
                    }
                }
            }

            $expDebited = User_expense::where(
                'user_expenses.bmi_id',
                '=',
                $value->id
            )
            ->join( 'expenses', 'expenses.id', '=', 'user_expenses.expense_id' )
            ->get( [ 'expenses.date', 'user_expenses.amount' ] );
            $l[ 'totalExpenseDebited' ] = 0;
            foreach ( $expDebited as $k1=>$v1 ) {
                $d = Carbon::parse( $v1->date );
                if ( $temp == 0 ) {
                    if ( $d <= $toDate ) {
                        $l[ 'totalExpenseDebited' ] = $l[ 'totalExpenseDebited' ] +$v1->amount;
                    }
                } else {
                    if ( $fromDate <= $d && $d <= $toDate ) {
                        $l[ 'totalExpenseDebited' ] = $l[ 'totalExpenseDebited' ]+$v1->amount;
                    }
                }
            }
            $getPurpose = User_expense::where( 'bmi_id', '=', $value->id )
            ->join(
                'expenses',
                'expenses.id',
                '=',
                'user_expenses.expense_id'
            )
            ->get( 'purpose' )
            ->last();
            if ( $getPurpose ) {
                $l[ 'purpose' ] = $getPurpose->purpose;
            }

            $l[ 'balance' ] =
            $l[ 'totalExpenseCollected' ] - $l[ 'totalExpenseDebited' ];
            // if ( $l[ 'totalExpenseCollected' ]>0 ) {
            array_push( $arr, $l );
            // }

        }
        return $arr;
    }

    public function getUserExpenseDetails( $bmi_id ) {
        $selectexpcollected = User_transaction::where( 'amount_categories.code', '=', 2 )
        ->where( 'user_transactions.bmi_id', '=', $bmi_id )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [
            'user_transactions.date',
            'user_transactions.amount as deposit',
            'user_transactions.remarks',
        ] );
        foreach ( $selectexpcollected as $key => $value ) {
            $value->expense = null;
        }

        $selectexpUsed = User_expense::where( 'user_expenses.bmi_id', '=', $bmi_id )
        ->join( 'expenses', 'expenses.id', '=', 'user_expenses.expense_id' )
        ->get( [
            'expenses.date',
            'user_expenses.amount as expense',
            'expenses.purpose as remarks',
        ] );
        foreach ( $selectexpUsed as $key => $value ) {
            $value->deposit = null;
        }
        $res = $selectexpcollected->concat( $selectexpUsed );
        $key = 0;
        while( $key <= count( $res )-1 ) {
            $key1 = $key+1;
            while( $key1 <= count( $res )-1 ) {
                $date = Carbon::parse( $res[ $key ]->date );
                $nextDate = Carbon::parse( $res[ $key1 ]->date );
                if ( $date>$nextDate ) {
                    $temp = $res[ $key ];
                    $res[ $key ] = $res[ $key1 ];
                    $res[ $key1 ] = $temp;
                }
                $key1 = $key1+1;
            }
            $key = $key+1;
        }
        $i = new BmiIdController();
        foreach ( $res as $key=>$value ) {
            $d = Carbon::parse( $value->date )->day;
            $m = $i->getMonthName( Carbon::parse( $value->date )->month );
            $y = Carbon::parse( $value->date )->year;
            $value->date = $m.' '.$d;
            $k = 0;
            $dd = 0;
            $ee = 0;
            while( $k <= $key ) {
                $dd = $dd+( $res[ $k ]->deposit?:0 );
                $ee = $ee+( $res[ $k ]->expense?:0 );
                $k = $k+1;
            }
            $value->balance = $dd-$ee;
        }
        return $res;
    }
}
