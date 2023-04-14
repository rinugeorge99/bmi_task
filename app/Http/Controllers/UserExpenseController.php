<?php

namespace App\Http\Controllers;
use App\Models\User_transaction;
use Illuminate\Http\Request;
use App\Models\User_expense;
use Carbon\Carbon;

class UserExpenseController extends Controller
 {
    public function insert( Request $request ) {

        $insert = User_expense::create( [

            'amount'=>$request->amount,
            'bmi_id'=>$request->bmi_id,
            'expense_id'=>$request->expense_id,

        ] );
        if ( $insert ) {
            return [ 'code'=>200, 'message'=>'inserted' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }

    }

    public function update( Request $request ) {
        $insert = User_expense::where( 'id', '=', $request->id )->update( [
            'amount'=>$request->amount,
            'bmi_id'=>$request->bmi_id,
            'expense_id'=>$request->expense_id,
        ] );
        if ( $insert ) {
            return [ 'code'=>200, 'message'=>'updated' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
    }

    public function getExpenseFund() {
        $ExpenseDetails = User_transaction::where( 'amount_categories.code', '=', 2 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        -> join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )->where( 'bmi_ids.status', '!=', 3 )
        ->sum( 'user_transactions.amount' );
        $totalExpense = User_expense::where( 'bmi_ids.status', '!=', 3 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_expenses.bmi_id' )
        ->sum( 'user_expenses.amount' );
        return $ExpenseDetails - $totalExpense;
    }

    public function listExpenseFund() {
        $ExpenseDetails = User_transaction::where( 'amount_categories.code', '=', 2 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->sum( 'user_transactions.amount' );
        $totalExpense = User_expense::where( 'bmi_ids.status', '!=', 3 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_expenses.bmi_id' )
        ->sum( 'user_expenses.amount' );
        $balance = $ExpenseDetails - $totalExpense;
        $select = $ExpenseDetails->merge( $totalExpense );

        return  [ 'code'=>$ExpenseDetails, 'message'=>'Something went wrong' ];
    }

    public function getUserExpense( $bmi_id ) {
        return $getExpenseDetails = User_expense::where( 'bmi_id', '=', $bmi_id )->sum( 'amount' );
    }

    public function getUserExpense_m( $bmi_id, $year, $month ) {
        $getExpenseDetails = User_expense::where( 'bmi_id', '=', $bmi_id )
        // ->where( 'expenses.transferfromBank', '=', 1 )
        ->join( 'expenses', 'expenses.id', '=', 'user_expenses.expense_id' )
        ->get( [ 'user_expenses.amount', 'expenses.date' ] );
        $t = 0;
        if ( count( $getExpenseDetails ) == 0 ) {
            return $t;
        }
        foreach ( $getExpenseDetails as $key=>$value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $t += $value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $t += $value->amount;
            }
        }
        return $t;
    }

    public function getUserExpense_v( $bmi_id, $fromDate, $toDate ) {
        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) != Carbon::now()->format( 'd-m-Y' ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $getExpenseDetails = User_expense::where( 'bmi_id', '=', $bmi_id )
        ->where( 'expenses.transferfromBank', '=', 1 )
        ->join( 'expenses', 'expenses.id', '=', 'user_expenses.expense_id' )
        ->get( [ 'user_expenses.amount', 'expenses.date' ] );
        $t = 0;
        if ( count( $getExpenseDetails ) == 0 ) {
            return $t;
        }
        foreach ( $getExpenseDetails as $key=>$value ) {
            $d = Carbon::parse( $value->date );
            if ( $temp = 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $t += $value->amount;
                }
            } else {
                if ( $d <= $toDate ) {
                    $t += $value->amount;
                }
            }
        }
        return $t;
    }

    // public function get_expense_list( $bmi_id ) {
    //     $ExpenseDetails = User_transaction::where( 'amount_categories.code', '=', 2 )
    //     ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
    //     ->sum( 'user_transactions.amount' );
    //      $totalExpense = User_expense::where( 'bmi_ids.status', '!=', 3 )
    //     ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_expenses.bmi_id' )

    // }
}
