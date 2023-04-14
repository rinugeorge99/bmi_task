<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Monthly_sip;
use App\Models\Monthly_sip_details;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User_transaction;
use App\Models\Company_transaction;
use App\Models\Company_investment_history;
use App\Models\Amount_category;
use App\Models\Bmi_id;
use App\Models\User_profit;
use App\Models\Company;
use App\Models\User_investment;
use App\Models\User_eib_kunooz;
use App\Models\User_expense;
use App\Models\User_zakat;
use App\Models\Expense;
use App\Models\Eib_kunooz;
use App\Models\Zakat;

class MonthlySipController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( request()->all(), [
            'bmi_id' => 'required',
            // 'amount' =>  'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
        $insert = Monthly_sip::create( [
            'bmi_id' => $request->bmi_id,
            'year' => $request->year,
            'amount' => $request->amount,
            'percentage' => $request->percentage,
        ] );
        if ( $insert ) {
            return [ 'code' => 200, 'message' => 'inserted' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function update( Request $request )
 {
        $c = new NotificationController();
        $insert = Monthly_sip::where( 'id', '=', $request->id )->update( [
            'bmi_id' => $request->bmi_id,
            'year' => $request->year,
            'amount' => $request->amount,
            'percentage' => $request->percentage,
        ] );

        if ( $insert ) {
            $select = Monthly_sip_details::where(
                'bmi_id',
                '=',
                $request->bmi_id
            )
            ->where( 'year', '=', $request->year )
            ->get();
            if ( count( $select ) > 0 ) {
                return $this->updateMonthlySipDetails(
                    $request->bmi_id,
                    $request->year
                );
            }
            $currentyear = Carbon::now()->year;
            $select = Bmi_id::where( 'status', '=', 0 )->where( 'verify', '=', 1 )->where( 'id', '=', $request->bmi_id )->pluck( 'id' );
            $date = Carbon::now()->format( 'Y-m-d' );
            $getDetails = Monthly_sip::where( 'bmi_id', '=', $request->bmi_id )->where( 'year', '=', $currentyear )->get()->first();
            $content = 'Updated Monthly SIP amount is '.$getDetails->amount;
            $k = $c->insertNotification( $date, $content, $select, 1 );
            return [ 'code' => 200, 'message' => 'updated' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function updateMonthlySipDetails( $bmi_id, $year ) {
        $selectUpdatedSip = Monthly_sip::where( 'bmi_id', '=', $bmi_id )->where( 'year', '=', $year )->get()->first();
        $getMonthlySipDetailsOfUpdatedYear = Monthly_sip_details::where( 'bmi_id', '=', $bmi_id )->where( 'year', '=', $year )->get();
        $totalAmountpayed = Monthly_sip_details::where( 'bmi_id', '=', $bmi_id )->where( 'year', '=', $year )->sum( 'amount' );
        $arr1 = array();
        $transactionIdArray = array();
        if ( count( $getMonthlySipDetailsOfUpdatedYear ) > 0 ) {
            foreach ( $getMonthlySipDetailsOfUpdatedYear as $key=>$value ) {
                $checkTransactionIdpresentInPreviousYear = Monthly_sip_details::where( 'transaction_id', '=', $value->transaction_id )
                ->where( 'bmi_id', '=', $bmi_id )->where( 'year', '=', $year-1 )->get();
                if ( count( $checkTransactionIdpresentInPreviousYear )>0 ) {
                    $totalAmountpayed = $totalAmountpayed-$value->amount;
                } else {
                    if ( !in_array( $value->transaction_id, $transactionIdArray ) ) {
                        array_push( $transactionIdArray, $value->transaction_id );
                    }
                    $deleteRecord = Monthly_sip_details::where( 'id', '=', $value->id )->delete();
                }

            }
            $ii = new UserTransactionController();

            foreach ( $transactionIdArray as $key=>$value ) {
                $getDetail = User_transaction::where( 'id', '=', $value )->get()->first();
                if ( $getDetail ) {
                    $ii->setMonthlySip( $bmi_id, $getDetail->amount, $getDetail->id, $getDetail->date );
                }
            }
        }
        return $totalAmountpayed;
    }

    public function select( Request $request ) {
        if ( isset( $request->fromDate ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromYear = Carbon::parse( $request->fromDate )->year;
        $toYear = Carbon::parse( $request->toDate )->year;
        $select = Monthly_sip::
        join(
            'bmi_ids',
            'monthly_sips.bmi_id',
            '=',
            'bmi_ids.id'
        )
        ->orderby( 'year', 'DESC' )
        ->get( [
            'monthly_sips.*',
            'bmi_ids.bmi_id as bmipno',
            'bmi_ids.name',
        ] );
        $arr = [];
        foreach ( $select as $key => $value ) {
            $total = Monthly_sip::where( 'year', '=', $value->year )->sum(
                'amount'
            );
            $value->percentage = $value->amount
            ? ( $value->amount / $total ) * 100
            : 0;
            $l[ 'id' ] = $value->id;
            $l[ 'bmi_id' ] = $value->bmi_id;
            $l[ 'bmipno' ] = $value->bmipno;
            $l[ 'Member_Name' ] = $value->name;
            $l[ 'Year' ] = $value->year;
            $l[ 'Monthly_Sip' ] = $value->amount;
            $l[ 'Percentage' ] = $value->percentage;
            if ( $temp == 1 ) {
                if ( $fromYear <= $value->year && $value->year <= $toYear ) {
                    array_push( $arr, $l );
                }
            } else {
                array_push( $arr, $l );
            }
        }
        return $arr;
    }

    public function getTotalFund() {
        $currentDate = Carbon::parse( Carbon::now() );

        $totalProfit = 0;
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        // $getTotalMonthlySIP = Monthly_sip_details::where(
        //     'monthly_sip_details.year',
        //     '<=',
        //     $currentYear
        // )
        // ->where( 'bmi_ids.status', '=', 0 )
        // ->where( 'bmi_ids.verify', '=', 1 )
        // ->join( 'bmi_ids', 'bmi_ids.id', '=', 'monthly_sip_details.bmi_id' )
        // ->join(
        //     'user_transactions',
        //     'user_transactions.id',
        //     '=',
        //     'monthly_sip_details.transaction_id'
        // )
        // // ->where( 'user_transactions.transferToBank', '=', 1 )
        // ->get( [
        //     'monthly_sip_details.amount',
        //     'monthly_sip_details.month',
        //     'monthly_sip_details.year',
        // ] );
        $getTotalMonthlySIP = User_transaction::where( 'amount_categories.code', '=', 1 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )->where( 'bmi_ids.status', '!=', 3 )
        ->sum( 'user_transactions.amount' );
        // $totalMonthlySIP = 0;
        // foreach ( $getTotalMonthlySIP as $key => $value ) {
        //     if ( $value->year < $currentYear ) {
        //         $totalMonthlySIP += $value->amount;
        //     }
        //     if (
        //         $value->year == $currentYear &&
        //         $value->month <= $currentMonth
        // ) {
        //         $totalMonthlySIP += $value->amount;
        //     }
        // }

        // }
        // get total profit
        // get all user transaction of profit of active user
        // $totalProfitamount = User_profit::where( 'bmi_ids.status', '=', 0 ) ->where( 'bmi_ids.verify', '=', 1 )
        // // ->where( 'company_transactions.transferToBank', '=', 1 )
        // ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_profits.bmi_id' )
        // ->get( [ 'user_profits.*' ] );
        // foreach ( $totalProfitamount as $key => $value ) {
        //     // compare the date with the payment date
        //     $date = Carbon::parse( $value->date );
        //     if ( $currentDate >= $date ) {
        //         $totalProfit = $totalProfit+ $value->amount;
        //     }
        // }
        $totalProfitamount = User_profit::where( 'bmi_ids.status', '!=', 3 ) ->where( 'bmi_ids.verify', '=', 1 )
        // ->where( 'company_transactions.transferToBank', '=', 1 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_profits.bmi_id' )
        ->sum( 'user_profits.amount' );

        // $investedAmount = $this->getTotalInvestedAmount();
        return $getTotalMonthlySIP + $totalProfitamount;
    }

    public function getTotalFund_m( $year, $month ) {
        $getTotalMonthlySip = User_transaction::where( 'amount_categories.code', '=', 1 )
        ->where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.bmi_id' )
        ->get( [ 'date', 'amount' ] );
        $totalMonthlySIP = 0;
        $totalProfit = 0;

        foreach ( $getTotalMonthlySip as $key=>$value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $totalMonthlySIP = $totalMonthlySIP+$value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $totalMonthlySIP = $totalMonthlySIP+$value->amount;
            }
        }

        $getTotalProfit = Company_transaction::where( 'amount_categories.code', '=', 5 )
        ->where( 'companies.status', '=', 1 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
        ->join( 'companies', 'companies.id', '=', 'company_transactions.company_id' )
        ->get( [ 'date', 'amount' ] );
        foreach ( $getTotalProfit as $key=>$value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $totalProfit = $totalProfit+$value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $totalProfit = $totalProfit+$value->amount;
            }
        }
        // return $totalProfit;
        return $totalMonthlySIP+$totalProfit;
    }

    public function getTotalInvestedAmount() {
        $currentDate = Carbon::parse( Carbon::now()->format( 'd-m-Y' ) );

        $investableAmount = Company::where( 'status', '=', 1 )
        ->sum( 'invested_amount' );
        $investmentReturnAmount = Company::where( 'status', '=', 1 )
        ->sum( 'investment_return' );
        return $investedAmount = $investableAmount - $investmentReturnAmount;
    }

    public function getTotalInvestedAmount_m( $year, $month ) {
        $getCompanyInvestmentHistory = Company_investment_history::where( 'companies.status', '=', 1 )
        ->join( 'companies', 'companies.id', '=', 'company_investment_histories.company_id' )
        ->get( [
            'company_investment_histories.invested_amount',
            'company_investment_histories.investment_return',
            'company_investment_histories.date',
        ] );
        $investedAmount = 0;
        $investmentReturn = 0;
        foreach ( $getCompanyInvestmentHistory as $key=>$value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $investedAmount = $investedAmount+$value->invested_amount;
                $investmentReturn = $investmentReturn+$value->investment_return;
            }
            if ( $y == $year && $m <= $month ) {
                $investedAmount = $investedAmount+$value->invested_amount;
                $investmentReturn = $investmentReturn+$value->investment_return;
            }
        }
        return $tInvested = $investedAmount-$investmentReturn;
    }

    public function getExpenseFund( $year, $month ) {
        $getExpenseCollected = User_transaction::where( 'amount_categories.code', '=', 2 )
        ->join( 'bmi_ids', 'user_transactions.bmi_id', '=', 'bmi_ids.id' )->where( 'bmi_ids.status', '!=', 3 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [ 'user_transactions.date', 'user_transactions.amount' ] );
        $totalExpenseCollected = 0;
        foreach ( $getExpenseCollected as $key=>$value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $totalExpenseCollected = $totalExpenseCollected+$value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $totalExpenseCollected = $totalExpenseCollected+$value->amount;
            }
        }
        // return   $totalExpenseCollected;
        $totalExpenseUsed = 0;
        $getExpenseUsed = Expense::where( 'transferfromBank', '=', 1 )->get( [ 'amount', 'date' ] );
        foreach ( $getExpenseUsed as $key=>$value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $totalExpenseUsed = $totalExpenseUsed+$value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $totalExpenseUsed = $totalExpenseUsed+$value->amount;
            }
        }
        // return  $totalExpenseUsed ;
        return $totalExpenseCollected-$totalExpenseUsed;
    }

    public function getTotalProfit() {
        $getTotalProfit = User_profit::where( 'bmi_ids.status', '=', 0 ) ->where( 'bmi_ids.verify', '=', 1 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_profits.bmi_id' )
        ->sum( 'user_profits.amount' );
        return $getTotalProfit;
    }

    public function getTotalProfit_m( $bmi_id, $year, $month ) {
        $getTotalProfit = User_profit::get( [
            'amount',
            'date_of_payment',
        ] );
        $t = 0;
        if ( count( $getTotalProfit ) == 0 ) {
            return $t;
        }
        foreach ( $getTotalProfit as $key => $value ) {
            $y = Carbon::parse( $value->date_of_payment )->year;
            $m = Carbon::parse( $value->date_of_payment )->month;
            if ( $y < $year ) {
                $t += $value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $t += $value->amount;
            }
        }
        return $t;
    }

    public function getTotalProfit_v( $fromDate, $toDate ) {
        // if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) == Carbon::now()->format( 'd-m-Y' ) ) {
        //     $temp = 0;
        // } else {
        //     $temp = 1;
        // }
        // $getTotalProfit = User_profit::where( 'bmi_ids.status', '=', 0 ) ->where( 'bmi_ids.verify', '=', 1 )
        // ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_profits.bmi_id' )
        // ->get( [ 'user_profits.date_of_payment', 'user_profits.amount' ] );
        // $profit = 0;
        // foreach ( $getTotalProfit as $key=>$value ) {
        //     $d = Carbon::parse( $value->date_of_payment );
        //     if ( $temp == 1 ) {

        //         if ( $fromDate <= $d && $d <= $toDate ) {
        //             $profit = $profit+$value->amount;
        //         }
        //     } else {
        //         // if ( $d <= $toDate ) {
        //         $profit = $profit+$value->amount;
        //         // }
        //     }

        // }
        // return $profit;
        if ( ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) ) != ( Carbon::now()->format( 'd-m-Y' ) ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $fromDate );
        $toDate = Carbon::parse( $toDate );

        $getTotalProfit = User_profit::get( [
            'amount',
            'date_of_payment',
        ] );
        $t = 0;
        if ( count( $getTotalProfit ) == 0 ) {
            return $t;
        }
        foreach ( $getTotalProfit as $key => $value ) {
            $d = Carbon::parse( $value->date_of_payment );

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

    // public function getUserRatio( $bmi_id ) {
    //     $currentYear = Carbon::now()->year;
    //     $totalMonthlySip = Monthly_sip::where( 'year', '=', $currentYear )->sum(
    //         'amount'
    // );

    //     $userMonthlySip = Monthly_sip::where( 'year', '=', $currentYear )
    //     ->where( 'bmi_id', '=', $bmi_id )
    //     ->get( 'amount' )
    //     ->first();

    //     if ( $userMonthlySip && $totalMonthlySip ) {
    //         $ratio = $userMonthlySip->amount / $totalMonthlySip;
    //         return $ratio;
    //     }
    // }

    public function getUserRatio( $bmi_id ) {

        $getAllMembers = Bmi_id::where( 'status', '!=', 3 )
        ->where( 'verify', '=', 1 )
        ->get();
        $t1 = 0;
        foreach ( $getAllMembers as $key => $value ) {
            $getFund = $this->getAvailableFundToInvest( $value->id );
            $t1 = $t1+$getFund;
        }

        $getAll = Bmi_id::where( 'status', '!=', 3 )->where( 'id', '=', $bmi_id )
        ->where( 'verify', '=', 1 )
        ->get();
        if ( count( $getAll )>0 ) {
            $getFund = $this->getAvailableFundToInvest( $bmi_id );
            return$ratio = ( $getFund/ $t1 )*100;
        }

    }

    public function getTotalUserFund( $bmi_id ) {
        // return $bmi_id;
        // total fund = total monthly sip paid + total profit get
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        // calculating total monthl sip
        $getTotalMonthlySIP = $this->getUserTotalMonthlySip( $bmi_id );

        // calculating total profit
        $getTotalProfit = $this->getUserTotalProfit( $bmi_id );

        return $getTotalMonthlySIP + $getTotalProfit;
    }

    public function getTotalUserFund_m( $bmi_id, $year, $month ) {
        // return $bmi_id;
        // total fund = total monthly sip paid + total profit get

        // calculating total monthl sip
        $getTotalMonthlySIP = $this->getUserTotalMonthlySip_m(
            $bmi_id,
            $year,
            $month
        );

        // calculating total profit
        $getTotalProfit = $this->getUserTotalProfit_m( $bmi_id, $year, $month );

        //total others
        $getTotalothers = $this->getUserothers_m( $bmi_id, $year, $month );

        return $getTotalMonthlySIP + $getTotalProfit+$getTotalothers;
    }

    public function getTotalUserFund_v( $bmi_id, $fromDate, $toDate ) {
        // return $bmi_id;
        // total fund = total monthly sip paid + total profit get

        // calculating total monthl sip
        $getTotalMonthlySIP = $this->getUserTotalMonthlySip_v( $bmi_id, $fromDate, $toDate );

        // calculating total profit
        $getTotalProfit = $this->getUserTotalProfit_v( $bmi_id, $fromDate, $toDate );
        // $eibkunooz = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )->sum( 'amount' );

        // $totalinvestment = Companies::where( 's' )
        return $getTotalMonthlySIP + $getTotalProfit;
    }

    public function getUserTotalMonthlySip( $bmi_id ) {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        // $getTotalMonthlySIP = Monthly_sip_details::where(
        //     'monthly_sip_details.year',
        //     '<=',
        //     $currentYear
        // )
        // ->where( 'monthly_sip_details.bmi_id', '=', $bmi_id )

        // ->join(
        //     'user_transactions',
        //     'user_transactions.id',
        //     '=',
        //     'monthly_sip_details.transaction_id'
        // )
        // ->get( [
        //     'monthly_sip_details.amount',
        //     'monthly_sip_details.month',
        //     'monthly_sip_details.year',
        // ] );
        return $getTotalMonthlySIP = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )
        ->where( 'amount_categories.code', '=', 1 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->sum( 'user_transactions.amount' );

        // $totalAmount = 0;
        // foreach ( $getTotalMonthlySIP as $key => $value ) {
        //     // if ( $value->year < $currentYear ) {
        //     //     $totalAmount += $value->amount;
        //     // }
        //     // if (
        //     //     $value->year == $currentYear &&
        //     //     $value->month <= $currentMonth
        //     // ) {
        //     $totalAmount += $value->amount;
        //     // }
        // }
        // return $totalAmount;
    }

    public function getUserTotalMonthlySip_m( $bmi_id, $year, $month )
 {
        $currentYear = $year;
        $currentMonth = $month;
        // $getTotalMonthlySIP = Monthly_sip_details::where(
        //     'monthly_sip_details.year',
        //     '<=',
        //     $currentYear
        // )
        // // ->where( 'month', '<=', $currentMonth )
        // ->where( 'monthly_sip_details.bmi_id', '=', $bmi_id )
        // // ->where( 'user_transactions.transferToBank', '=', 1 )
        // ->join(
        //     'user_transactions',
        //     'user_transactions.id',
        //     '=',
        //     'monthly_sip_details.transaction_id'
        // )
        // ->get( [
        //     'monthly_sip_details.amount',
        //     'monthly_sip_details.month',
        //     'monthly_sip_details.year',
        // ] );
        $getTotalMonthlySIP = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )
        ->where( 'amount_categories.code', '=', 1 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [ 'user_transactions.date', 'user_transactions.amount' ] );
        $totalAmount = 0;
        if ( count( $getTotalMonthlySIP ) > 0 ) {
            foreach ( $getTotalMonthlySIP as $key => $value ) {
                $y = Carbon::parse( $value->date )->year;
                $m = Carbon::parse( $value->date )->month;
                // if ( $value->year < $currentYear ) {
                //     $totalAmount = $totalAmount+ $value->amount;
                // }
                // if (
                //     $value->year == $currentYear &&
                //     $value->month <= $currentMonth
                // ) {
                //     $totalAmount = $totalAmount+ $value->amount;
                // }
                if ( $y < $currentYear ) {
                    $totalAmount = $totalAmount+ $value->amount;
                }
                if (
                    $y == $currentYear &&
                    $m <= $currentMonth
                ) {
                    $totalAmount = $totalAmount+ $value->amount;
                }
            }
        }

        return $totalAmount;
    }

    public function getUserTotalMonthlySip_v( $bmi_id, $fromDate, $toDate ) {

        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) != Carbon::now()->format( 'd-m-Y' ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromYear = Carbon::parse( $fromDate )->year;
        $fromMonth = Carbon::parse( $fromDate )->month;
        $currentYear = Carbon::parse( $toDate )->year;
        $currentMonth = Carbon::parse( $toDate )->month;
        // $getTotalMonthlySIP = Monthly_sip_details::where(
        //     'monthly_sip_details.year',
        //     '<=',
        //     $currentYear
        // )
        // ->where( 'monthly_sip_details.bmi_id', '=', $bmi_id )
        // ->join(
        //     'user_transactions',
        //     'user_transactions.id',
        //     '=',
        //     'monthly_sip_details.transaction_id'
        // )
        // // ->where( 'user_transactions.transferToBank', '=', 1 )
        // ->get( [
        //     'monthly_sip_details.amount',
        //     'monthly_sip_details.month',
        //     'monthly_sip_details.year'
        // ] );
        $getTotalMonthlySIP = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )
        ->where( 'amount_categories.code', '=', 1 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [ 'user_transactions.date', 'user_transactions.amount' ] );
        $totalAmount = 0;
        if ( count( $getTotalMonthlySIP ) == 0 ) {
            return $totalAmount;
        }

        foreach ( $getTotalMonthlySIP as $key => $value ) {
            $d = Carbon::parse( $value->date );
            if ( $temp == 1 ) {
                // if ( $fromYear<$value->year && $value->year < $currentYear ) {
                //     $totalAmount += $value->amount;
                // }
                // if (
                //     $value->year == $currentYear && $fromMonth <= $value->month &&
                //     $value->month <= $currentMonth
                // ) {
                //     $totalAmount += $value->amount;
                // }
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $totalAmount = $totalAmount+$value->amount;
                }

            } else {
                // if ( $value->year < $currentYear ) {
                //     $totalAmount += $value->amount;
                // }
                // if (
                //     $value->year == $currentYear &&
                //     $value->month <= $currentMonth
                // ) {
                //     $totalAmount += $value->amount;
                // }
                $totalAmount = $totalAmount+$value->amount;
            }

        }
        return $totalAmount;
    }

    public function getUserTotalProfit( $bmi_id ) {
        $getTotalProfit = User_profit::where( 'bmi_id', '=', $bmi_id )->sum(
            'amount'
        );
        return $getTotalProfit;
    }

    public function getUserTotalProfit_m( $bmi_id, $year, $month ) {
        $getTotalProfit = User_profit::where( 'bmi_id', '=', $bmi_id )->get( [
            'amount',
            'date_of_payment',
        ] );
        $t = 0;
        if ( count( $getTotalProfit ) == 0 ) {
            return $t;
        }
        foreach ( $getTotalProfit as $key => $value ) {
            $y = Carbon::parse( $value->date_of_payment )->year;
            $m = Carbon::parse( $value->date_of_payment )->month;
            if ( $y < $year ) {
                $t += $value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $t += $value->amount;
            }
        }
        return $t;
    }

    public function getUserTotalProfit_v( $bmi_id, $fromDate, $toDate ) {
        if ( ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) ) != ( Carbon::now()->format( 'd-m-Y' ) ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $fromDate );
        $toDate = Carbon::parse( $toDate );

        $getTotalProfit = User_profit::where( 'bmi_id', '=', $bmi_id )->get( [
            'amount',
            'date_of_payment',
        ] );
        $t = 0;
        if ( count( $getTotalProfit ) == 0 ) {
            return $t;
        }
        foreach ( $getTotalProfit as $key => $value ) {
            $d = Carbon::parse( $value->date_of_payment );

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

    public function getAvailableFundToInvest( $bmi_id )
 {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $getTotalMonthlySIP = Monthly_sip_details::where(
            'monthly_sip_details.year',
            '<=',
            $currentYear
        )
        ->where( 'monthly_sip_details.bmi_id', '=', $bmi_id )
        ->where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'monthly_sip_details.bmi_id' )
        ->join(
            'user_transactions',
            'user_transactions.id',
            '=',
            'monthly_sip_details.transaction_id'
        )
        ->where( 'user_transactions.transferToBank', '=', 1 )
        ->get( [
            'monthly_sip_details.amount',
            'monthly_sip_details.month',
            'monthly_sip_details.year',
        ] );
        $sip = 0;
        foreach ( $getTotalMonthlySIP as $key=>$value ) {

            if ( $value->year<$currentYear ) {
                $sip = $sip+$value->amount;
            }
            if ( $value->year == $currentYear && $value->month <= $currentMonth ) {
                $sip = $sip+$value->amount;
            }

        }
        // return $sip;
        $getTotalProfit = User_profit::where( 'bmi_id', '=', $bmi_id )
        ->where( 'company_transactions.transferToBank', '=', 1 )
        ->join( 'company_transactions', 'company_transactions.id', '=', 'user_profits.company_transaction_id' )
        ->sum( 'user_profits.amount' );
        $totalFund = $sip+$getTotalProfit;

        $investedAmount = User_investment::where(
            'user_investments.bmi_id',
            '=',
            $bmi_id
        )
        ->where( 'companies.status', '=', 1 )
        ->join( 'companies', 'companies.id', '=', 'user_investments.company_id' )
        ->sum( 'user_investments.invested_amount' );
        $investmentReturnAmount = User_investment::where(
            'user_investments.bmi_id',
            '=',
            $bmi_id
        )
        ->where( 'company_transactions.transferToBank', '=', 1 )
        ->join( 'company_transactions', 'company_transactions.id', '=', 'user_investments.company_transaction_id' )
        ->sum( 'user_investments.investment_return' );
        $totalinvested = $investedAmount-$investmentReturnAmount ;

        $getZakatDetails =  User_zakat::where( 'user_zakats.bmi_id', '=', $bmi_id )
        ->where( 'zakats.transferFromBank', '=', 1 )
        ->join( 'zakats', 'zakats.id', '=', 'user_zakats.zakat_id' )
        ->sum( 'user_zakats.amount' );
        // return $investedAmount;
        $eibkunooz = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )->sum( 'amount' );
        return $totalFund - $totalinvested-$getZakatDetails+$eibkunooz;
    }

    public function getAvailableFundToInvest_m( $bmi_id, $year, $month )
 {

        // $getTotalMonthlySIP = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )
        // ->where( 'user_transactions.transferToBank', '=', 1 )
        // ->where( 'amount_categories.code', '=', 1 )
        // ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        // ->get( [ 'user_transactions.date', 'user_transactions.amount' ] );
        // $totalSipTransferedToBank = 0;
        // if ( count( $getTotalMonthlySIP ) > 0 ) {
        //     foreach ( $getTotalMonthlySIP as $key => $value ) {
        //         $y = Carbon::parse( $value->date )->year;
        //         $m = Carbon::parse( $value->date )->month;

        //         if ( $y<$year ) {

        //             // if ( $fromDate <= $d && $d <= $toDate ) {
        //             $totalSipTransferedToBank = $totalSipTransferedToBank+$value->amount;
        //             // }

        //         }

        //         if ( $y == $year && $m <= $month ) {

        //             $totalSipTransferedToBank = $totalSipTransferedToBank+$value->amount;
        //         }

        //     }
        // }

        $getTotalMonthlySIP = Monthly_sip_details::where(
            'monthly_sip_details.year',
            '<=',
            $year
        )  ->where( 'monthly_sip_details.bmi_id', '=', $bmi_id )
        ->where( 'bmi_ids.status', '=', 0 )
        ->where( 'bmi_ids.verify', '=', 1 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'monthly_sip_details.bmi_id' )
        ->join(
            'user_transactions',
            'user_transactions.id',
            '=',
            'monthly_sip_details.transaction_id'
        )
        ->where( 'user_transactions.transferToBank', '=', 1 )
        ->get( [
            'monthly_sip_details.amount',
            'monthly_sip_details.month',
            'monthly_sip_details.year',
        ] );
        $sip = 0;
        foreach ( $getTotalMonthlySIP as $key=>$value ) {

            if ( $value->year<$year ) {
                $sip = $sip+$value->amount;

            }
            if ( $value->year == $year && $value->month <= $month ) {
                $sip = $sip+$value->amount;
            }

        }

        // $sip = 0;
        // foreach ( $getTotalMonthlySIP as $key=>$value ) {

        //     if ( $value->year<$year ) {
        //         $sip = $sip+$value->amount;
        //     }
        //     if ( $value->year == $year && $value->month <= $month ) {
        //         $sip = $sip+$value->amount;
        //     }

        // }

        // Total profit transfered to bank

        $getTotalProfit = User_profit::where( 'bmi_id', '=', $bmi_id )
        ->where( 'company_transactions.transferToBank', '=', 1 )
        ->join( 'company_transactions', 'company_transactions.id', '=', 'user_profits.company_transaction_id' )
        ->get( [
            'user_profits.amount',
            'user_profits.date_of_payment',
        ] );
        $totalProfitTransferedToBank = 0;
        if ( count( $getTotalProfit ) > 0 ) {
            foreach ( $getTotalProfit as $key => $value ) {
                $y = Carbon::parse( $value->date )->year;
                $m = Carbon::parse( $value->date )->month;

                if ( $y<$year ) {

                    // if ( $fromDate <= $d && $d <= $toDate ) {
                    $totalProfitTransferedToBank = $totalProfitTransferedToBank+$value->amount;
                    // }

                }

                if ( $y == $year && $m <= $month ) {

                    $totalProfitTransferedToBank = $totalProfitTransferedToBank+$value->amount;
                }

            }
        }

        $totalFundTransferedToBank = $sip+$totalProfitTransferedToBank;

        // Total investment transfer from bank

        $investableAmount = User_investment::where(
            'user_investments.bmi_id',
            '=',
            $bmi_id
        )
        ->where( 'companies.status', '=', 1 )
        ->join( 'companies', 'companies.id', '=', 'user_investments.company_id' )
        ->get( [ 'user_investments.invested_amount', 'user_investments.date' ] );
        $investmentReturnAmount = User_investment::where(
            'user_investments.bmi_id',
            '=',
            $bmi_id
        )
        ->where( 'company_transactions.transferToBank', '=', 1 )
        ->join( 'company_transactions', 'company_transactions.id', '=', 'user_investments.company_transaction_id' )
        ->get( [ 'user_investments.investment_return', 'user_investments.date' ] );

        $totalinvested_amounttransferToBank = 0;
        $totalinvest_return_transferedToBank = 0;
        if ( count( $investableAmount ) >0 ) {
            foreach ( $investableAmount as $key => $value ) {

                $y = Carbon::parse( $value->date )->year;
                $m = Carbon::parse( $value->date )->month;

                if ( $y<$year ) {

                    // if ( $fromDate <= $d && $d <= $toDate ) {
                    $totalinvested_amounttransferToBank = $totalinvested_amounttransferToBank+$value->invested_amount;
                    // }

                }

                if ( $y == $year && $m <= $month ) {

                    $totalinvested_amounttransferToBank = $totalinvested_amounttransferToBank+$value->invested_amount;
                }

            }
        }
        if ( count( $investmentReturnAmount )>0 ) {
            foreach ( $investmentReturnAmount as $key => $value ) {

                $y = Carbon::parse( $value->date )->year;
                $m = Carbon::parse( $value->date )->month;

                if ( $y<$year ) {

                    // if ( $fromDate <= $d && $d <= $toDate ) {
                    $totalinvest_return_transferedToBank = $totalinvest_return_transferedToBank+$value->investment_return;
                    // }

                }

                if ( $y == $year && $m <= $month ) {

                    $totalinvest_return_transferedToBank = $totalinvest_return_transferedToBank+$value->investment_return;
                }

            }
        }

        // return $totalinvested_amounttransferToBank;
        $total_invested_amount_transfer_from_bank = $totalinvested_amounttransferToBank - $totalinvest_return_transferedToBank;

        // total zakat transfer from bank

        $getUserZakat = User_zakat::where( 'user_zakats.bmi_id', '=', $bmi_id )
        ->where( 'zakats.transferFromBank', '=', 1 )
        ->join( 'zakats', 'zakats.id', '=', 'user_zakats.zakat_id' )
        ->get( [ 'user_zakats.amount', 'zakats.date' ] );

        $totalZakatTransferedFromBank = 0;
        if ( count( $getUserZakat ) > 0 ) {
            foreach ( $getUserZakat as $key=>$value ) {

                $y = Carbon::parse( $value->date )->year;
                $m = Carbon::parse( $value->date )->month;

                if ( $y<$year ) {

                    // if ( $fromDate <= $d && $d <= $toDate ) {
                    $totalZakatTransferedFromBank = $totalZakatTransferedFromBank+$value->amount;
                    // }

                }

                if ( $y == $year && $m <= $month ) {

                    $totalZakatTransferedFromBank = $totalZakatTransferedFromBank+$value->amount;
                }
            }
        }
        $totalEibkunooz = 0;
        $getEibKunooz = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )->get( [ 'date_of_payment', 'amount' ] );
        if ( count( $getEibKunooz )>0 ) {
            foreach ( $getEibKunooz as $kk=>$vv ) {
                $y = Carbon::parse( $vv->date )->year;
                $m = Carbon::parse( $vv->date )->month;

                if ( $y < $year ) {
                    $totalEibkunooz += $vv->amount;
                }
                if ( $y == $year && $m <= $month ) {
                    $totalEibkunooz += $vv->amount;
                }

            }
        }
        return $totalInvestableAmount = $totalFundTransferedToBank-$total_invested_amount_transfer_from_bank-$totalZakatTransferedFromBank+$totalEibkunooz;

        // return $totalAmount;
    }

    public function getAvailableFundToInvest_v( $bmi_id, $fromDate, $toDate )
 {
        // $totalFund = $this->getTotalUserFund_v( $bmi_id, $fromDate, $toDate );

        // $investedAmount = $this->getInvestableAmount_v( $bmi_id, $fromDate, $toDate );
        // $getZakatDetails = User_zakat::where( 'user_zakats.bmi_id', '=', $bmi_id )
        // ->join( 'zakats', 'zakats.id', '=', 'user_zakats.zakat_id' )
        // ->get( [ 'zakats.date', 'user_zakats.amount' ] );
        // $z = 0;
        // foreach ( $getZakatDetails as $key=>$value ) {
        //     $d = Carbon::parse( $value->date );
        //     if ( $fromDate <= $d && $d <= $toDate ) {
        //         $z = $z+$value->amount;
        //     }
        // }
        // // $l = new UserZakatController();
        // // $zakat = $l->getUserZakat_v( $value->id, $fromDate, $toDate );

        // // return $investedAmount;
        // return $totalFund - $investedAmount-$zakat;

        // Total Sip transfered to bank
        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) != Carbon::now()->format( 'd-m-Y' ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromYear = Carbon::parse( $fromDate )->year;
        $fromMonth = Carbon::parse( $fromDate )->month;
        $currentYear = Carbon::parse( $toDate )->year;
        $currentMonth = Carbon::parse( $toDate )->month;

        // $getTotalMonthlySIP = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )
        // ->where( 'user_transactions.transferToBank', '=', 1 )
        // ->where( 'amount_categories.code', '=', 1 )
        // ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        // ->get( [ 'user_transactions.date', 'user_transactions.amount' ] );
        // $totalSipTransferedToBank = 0;
        // if ( count( $getTotalMonthlySIP ) > 0 ) {
        //     foreach ( $getTotalMonthlySIP as $key => $value ) {
        //         $d = Carbon::parse( $value->date );
        //         if ( $temp == 1 ) {

        //             if ( $fromDate <= $d && $d <= $toDate ) {
        //                 $totalSipTransferedToBank = $totalSipTransferedToBank+$value->amount;
        //             }

        //         } else {

        //             $totalSipTransferedToBank = $totalSipTransferedToBank+$value->amount;
        //         }

        //     }
        // }
        $getTotalMonthlySIP = Monthly_sip_details::where(
            'monthly_sip_details.year',
            '<=',
            $currentYear
        )
        ->where( 'monthly_sip_details.bmi_id', '=', $bmi_id )
        // ->where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'monthly_sip_details.bmi_id' )
        ->join(
            'user_transactions',
            'user_transactions.id',
            '=',
            'monthly_sip_details.transaction_id'
        )
        ->where( 'user_transactions.transferToBank', '=', 1 )
        ->get( [
            'monthly_sip_details.amount',
            'monthly_sip_details.month',
            'monthly_sip_details.year',
        ] );
        $sip = 0;
        foreach ( $getTotalMonthlySIP as $key=>$value ) {
            if ( $temp == 1 ) {
                if ( $value->year == $fromYear && $value->month >= $fromMonth && $value->year<$currentYear ) {
                    $sip = $sip+$value->amount;

                }
                if ( $value->year == $currentYear && $value->month <= $currentMonth ) {
                    $sip = $sip+$value->amount;
                }

            } else {
                if ( $value->year<$currentYear ) {
                    $sip = $sip+$value->amount;

                }
                if ( $value->year == $currentYear && $value->month <= $currentMonth ) {
                    $sip = $sip+$value->amount;
                }
            }

        }
        // return $sip;
        // Total profit transfered to bank

        $fromDate = Carbon::parse( $fromDate );
        $toDate = Carbon::parse( $toDate );

        $getTotalProfit = User_profit::where( 'bmi_id', '=', $bmi_id )
        ->where( 'company_transactions.transferToBank', '=', 1 )
        ->join( 'company_transactions', 'company_transactions.id', '=', 'user_profits.company_transaction_id' )
        ->get( [
            'user_profits.amount',
            'user_profits.date_of_payment',
        ] );
        $totalProfitTransferedToBank = 0;
        if ( count( $getTotalProfit ) > 0 ) {
            foreach ( $getTotalProfit as $key => $value ) {
                $d = Carbon::parse( $value->date_of_payment );

                if ( $temp == 1 ) {
                    if ( $fromDate <= $d && $d <= $toDate ) {
                        $totalProfitTransferedToBank += $value->amount;
                    }

                } else {
                    // if ( $d <= $toDate ) {
                    $totalProfitTransferedToBank += $value->amount;
                    // }
                }

            }
        }
        // return $totalProfitTransferedToBank;
        $totalFundTransferedToBank = $sip+$totalProfitTransferedToBank;

        // Total investment transfer from bank

        $investableAmount = User_investment::where(
            'user_investments.bmi_id',
            '=',
            $bmi_id
        )
        // ->where( 'companies.status', '=', 1 )
        ->join( 'companies', 'companies.id', '=', 'user_investments.company_id' )
        ->get( [ 'user_investments.invested_amount', 'user_investments.date' ] );
        $investmentReturnAmount = User_investment::where(
            'user_investments.bmi_id',
            '=',
            $bmi_id
        )
        // ->where( 'company_transactions.transferToBank', '=', 1 )
        // ->join( 'company_transactions', 'company_transactions.id', '=', 'user_investments.company_transaction_id' )
        ->join( 'companies', 'companies.id', '=', 'user_investments.company_id' )
        ->get( [ 'user_investments.investment_return', 'user_investments.date' ] );

        $totalinvested_amounttransferToBank = 0;
        $totalinvest_return_transferedToBank = 0;
        if ( count( $investableAmount ) >0 ) {
            foreach ( $investableAmount as $key => $value ) {
                $d = Carbon::parse( $value->date );
                if ( $temp == 1 ) {
                    if ( $fromDate <= $d && $d <= $toDate ) {
                        $totalinvested_amounttransferToBank = $totalinvested_amounttransferToBank+ $value->invested_amount ?: 0;
                    }
                } else {
                    // if ( $d <= $toDate ) {
                    $totalinvested_amounttransferToBank = $totalinvested_amounttransferToBank+ $value->invested_amount ?: 0;
                    // }
                }

            }
        }
        // return   $totalinvested_amounttransferToBank;
        if ( count( $investmentReturnAmount )>0 ) {
            foreach ( $investmentReturnAmount as $key => $value ) {
                $d = Carbon::parse( $value->date );
                if ( $temp == 1 ) {
                    if ( $fromDate <= $d && $d <= $toDate ) {
                        $totalinvest_return_transferedToBank = $totalinvest_return_transferedToBank+ $value->investment_return ?: 0;
                    }
                } else {
                    // if ( $d <= $toDate ) {
                    $totalinvest_return_transferedToBank = $totalinvest_return_transferedToBank+ $value->investment_return ?: 0;
                    // }
                }
            }
        }

        // return $totalinvest_return_transferedToBank;
        $total_invested_amount_transfer_from_bank = $totalinvested_amounttransferToBank - $totalinvest_return_transferedToBank;

        // total zakat transfer from bank

        $getUserZakat = User_zakat::where( 'user_zakats.bmi_id', '=', $bmi_id )
        ->where( 'zakats.transferFromBank', '=', 1 )
        ->join( 'zakats', 'zakats.id', '=', 'user_zakats.zakat_id' )
        ->get( [ 'user_zakats.amount', 'zakats.date' ] );

        $totalZakatTransferedFromBank = 0;
        if ( count( $getUserZakat ) > 0 ) {
            foreach ( $getUserZakat as $key=>$value ) {
                $d = Carbon::parse( $value->date );
                if ( $temp == 1 ) {
                    if ( $fromDate <= $d && $d <= $toDate ) {
                        $totalZakatTransferedFromBank += $value->amount;
                    }
                } else {
                    // if ( $d <= $toDate ) {
                    $totalZakatTransferedFromBank += $value->amount;
                    // }
                }

            }
        }
        // return $totalZakatTransferedFromBank;
        $totalEibkunooz = 0;
        $getEibKunooz = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )->get( [ 'date_of_payment', 'amount' ] );
        if ( count( $getEibKunooz )>0 ) {
            foreach ( $getEibKunooz as $kk=>$vv ) {
                $d = Carbon::parse( $vv->date );
                if ( $temp == 1 ) {
                    if ( $fromDate <= $d && $d <= $toDate ) {
                        $totalEibkunooz += $vv->amount;
                    }
                } else {
                    // if ( $d <= $toDate ) {
                    $totalEibkunooz += $vv->amount;
                    // }
                }
            }
        }
        // return $totalEibkunooz;

        return $totalInvestableAmount = $totalFundTransferedToBank-$total_invested_amount_transfer_from_bank-$totalZakatTransferedFromBank+$totalEibkunooz;

        // return $totalAmount;
    }

    public function getInvestableAmount( $bmi_id )
 {
        $investableAmount = User_investment::where( 'bmi_id', '=', $bmi_id )->sum(
            'invested_amount'
        );
        $investmentReturnAmount = User_investment::where(
            'bmi_id',
            '=',
            $bmi_id
        )->sum( 'investment_return' );
        return $investedAmount = $investableAmount - $investmentReturnAmount;
    }

    public function getInvestableAmount_m( $bmi_id, $year, $month )
 {
        $investableAmount = User_investment::where(
            'bmi_id',
            '=',
            $bmi_id
        )->get( [ 'invested_amount', 'date' ] );
        $investmentReturnAmount = User_investment::where(
            'bmi_id',
            '=',
            $bmi_id
        )->get( [ 'investment_return', 'date' ] );
        $i = 0;
        $j = 0;
        if ( count( $investableAmount ) == 0 ) {
            return 0;
        }
        foreach ( $investableAmount as $key => $value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y < $year ) {
                $i += $value->invested_amount ?: 0;
            }
            if ( $y == $year && $m <= $month ) {
                $i += $value->invested_amount ?: 0;
            }
        }

        foreach ( $investmentReturnAmount as $key => $value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y < $year ) {
                $j += $value->investment_return ?: 0;
            }
            if ( $y == $year && $m <= $month ) {
                $j += $value->investment_return ?: 0;
            }
        }
        return $z = $i - $j;
    }

    public function getInvestableAmount_v( $bmi_id, $fromDate, $toDate ) {
        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) == Carbon::now()->format( 'd-m-Y' ) ) {
            $temp = 0;
        } else {
            $temp = 1;
        }
        $investableAmount = User_investment::where(
            'bmi_id',
            '=',
            $bmi_id
        )->get( [ 'invested_amount', 'date' ] );
        $investmentReturnAmount = User_investment::where(
            'bmi_id',
            '=',
            $bmi_id
        )->get( [ 'investment_return', 'date' ] );
        $i = 0;
        $j = 0;
        if ( count( $investableAmount ) == 0 ) {
            return 0;
        }
        foreach ( $investableAmount as $key => $value ) {
            $d = Carbon::parse( $value->date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $i += $value->invested_amount ?: 0;
                }
            } else {
                // if ( $d <= $toDate ) {
                $i += $value->invested_amount ?: 0;
                // }
            }

        }
        foreach ( $investmentReturnAmount as $key => $value ) {
            $d = Carbon::parse( $value->date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $j += $value->investment_return ?: 0;
                }
            } else {
                // if ( $d <= $toDate ) {
                $j += $value->investment_return ?: 0;
                // }
            }
        }
        return $z = $i - $j;
    }

    public function getAvailableToWithdraw( $bmi_id )
 {
        // user_transaction + user_profit + user_eib_kunooz - user_expense - user_zakat
        $usertransactionAmt = User_transaction::where( 'bmi_id', '=', $bmi_id )
        // ->where( 'transferToBank', '=', 1 )
        ->sum( 'amount' );
        $user_profit = User_profit::where( 'bmi_id', '=', $bmi_id )->sum(
            'amount'
        );
        $userEibKunooz = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )->sum(
            'amount'
        );
        $user_expense = User_expense::where( 'bmi_id', '=', $bmi_id )->sum(
            'amount'
        );
        $user_zakat = User_zakat::where( 'bmi_id', '=', $bmi_id )->sum( 'amount' );

        return $usertransactionAmt +
        $user_profit +
        $userEibKunooz -
        $user_expense -
        $user_zakat;
    }

    public function getAvailableToWithdraw_v( $bmi_id, $fromDate, $toDate )
 {
        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) != Carbon::now()->format( 'd-m-Y' ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }

        // user_transaction + user_profit + user_eib_kunooz - user_expense - user_zakat
        $usertransactionAmt = User_transaction::where( 'bmi_id', '=', $bmi_id )
        // ->where( 'transferToBank', '=', 1 )
        // ->where( 'amount_categories.code', '=', 1 )
        // ->where( 'amount_categories.code', '=', 3 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [ 'amount', 'date' ] );
        $i = 0;
        foreach ( $usertransactionAmt as $key => $value ) {
            $d = Carbon::parse( $value->date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {

                    $i = $i+ $value->amount ;
                }
            } else {

                // if ( $d <= $toDate ) {
                $i = $i+$value->amount;
                // }
            }

        }
        // return $i;
        $user_profit = User_profit::where( 'bmi_id', '=', $bmi_id )
        ->get( [ 'amount', 'date_of_payment' ] );
        $i1 = 0;
        foreach ( $user_profit as $key1 => $value1 ) {
            $t = Carbon::parse( $value1->date_of_payment );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $i1 = $i1+ $value1->amount ;
                }
            } else {
                // if ( $d <= $toDate ) {
                $i1 = $i1+$value1->amount;
                // }
            }

        }
        // return $i1;
        // return$tf = $i +$i1 ;
        $userEibKunooz = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )->join( 'eib_kunoozs', 'user_eib_kunoozs.eib_kunooz_id', '=', 'eib_kunoozs.id' )
        ->get( [ 'user_eib_kunoozs.amount', 'user_eib_kunoozs.date_of_payment' ] );
        $l = 0;
        foreach ( $userEibKunooz as $k => $v ) {
            $t1 = Carbon::parse( $v->date_of_payment );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $l = $l+ $v->amount ;
                }
            } else {
                // if ( $d <= $toDate ) {
                $l = $l+$v->amount;
                // }
            }

        }
        // return $l;
        $user_expense = User_expense::where( 'bmi_id', '=', $bmi_id )
        ->join( 'expenses', 'user_expenses.expense_id', '=', 'expenses.id' )
        ->get( [ 'expenses.date', 'user_expenses.amount' ] );

        $l1 = 0;
        foreach ( $user_expense as $k1 => $v1 ) {
            $t1 = Carbon::parse( $v1->date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $l1 = $l1+ $v1->amount ;
                }
            } else {
                // if ( $d <= $toDate ) {
                $l1 = $l1+$v1->amount;
                // }
            }

        }
        // return $l1;
        $expense = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )

        ->where( 'amount_categories.code', '=', 2 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [ 'user_transactions.date', 'user_transactions.amount' ] );
        $l12 = 0;
        foreach ( $expense as $kk1 => $vv1 ) {
            $t11 = Carbon::parse( $vv1->date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $l12 = $l12+ $vv1->amount ;
                }
            } else {
                // if ( $d <= $toDate ) {
                $l12 = $l12+$vv1->amount;
                // }
            }

        }
        // return $l12;
        // return$totalexpense = $l12-$l1;

        $user_zakat = User_zakat::where( 'bmi_id', '=', $bmi_id )
        ->join( 'zakats', 'user_zakats.zakat_id', '=', 'zakats.id' )
        ->get( [ 'zakats.date', 'user_zakats.amount' ] );

        $a = 0;
        foreach ( $user_zakat as $r => $o ) {
            $e = Carbon::parse( $o->date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $a = $a+ $o->amount ;
                }
            } else {
                // if ( $d <= $toDate ) {
                $a = $a+$o->amount;
                // }
            }

        }
        return $i+$i1+$l-$l1-$a;
    }

    public function getAvailableToWithdraw_m( $bmi_id, $year, $month ) {

        $usertransactionAmt = User_transaction::where( 'bmi_id', '=', $bmi_id )

        ->get( [ 'amount', 'date' ] );
        $i = 0;
        foreach ( $usertransactionAmt as $key => $value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {

                $i = $i+ $value->amount ;

            }
            if ( $y == $year && $m <= $month ) {

                $i = $i+$value->amount;

            }
        }
        $totalamountcollected = $i;
        // return $i;
        $user_profit = User_profit::where( 'bmi_id', '=', $bmi_id )
        ->get( [ 'amount', 'date_of_payment' ] );
        $i1 = 0;
        foreach ( $user_profit as $key1 => $value1 ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $i1 = $i1+ $value1->amount ;
            }
            if ( $y == $year && $m <= $month ) {

                $i1 = $i1+$value1->amount;

            }
        }
        $total_userprofit = $i1;
        // return $i1;
        $userEibKunooz = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )
        ->get( [ 'amount', 'date_of_payment' ] );
        $l = 0;
        foreach ( $userEibKunooz as $k => $v ) {
            $y = Carbon::parse( $v->date )->year;
            $m = Carbon::parse( $v->date )->month;
            if ( $y<$year ) {
                $l = $l+ $v->amount ;
            }

            if ( $y == $year && $m <= $month ) {

                $l = $l+$v->amount;
            }

        }
        $totaleibkunooz = $l;
        // return $l;
        $user_expense = User_expense::where( 'bmi_id', '=', $bmi_id )
        ->join( 'expenses', 'user_expenses.expense_id', '=', 'expenses.id' )
        ->get( [ 'expenses.date', 'user_expenses.amount' ] );
        $l1 = 0;
        foreach ( $user_expense as $k1 => $v1 ) {
            $y = Carbon::parse( $v1->date )->year;
            $m = Carbon::parse( $v1->date )->month;
            if ( $y<$year ) {
                $l1 = $l1+ $v1->amount ;
            }

            if ( $y == $year && $m <= $month ) {
                $l1 = $l1+$v1->amount;
            }

        }
        $totalexpenseused = $l1;
        // return $l1;
        $expense = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )

        ->where( 'amount_categories.code', '=', 2 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [ 'user_transactions.date', 'user_transactions.amount' ] );
        $l12 = 0;
        foreach ( $expense as $kk1 => $vv1 ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $l12 = $l12+ $vv1->amount ;
            }

            if ( $y == $year && $m <= $month ) {
                $l12 = $l12+$vv1->amount;

            }
        }
        $totalexpensecollected = $l12;
        // return $l12;
        // $totalexpense = $l12-$l1;

        $user_zakat = User_zakat::where( 'bmi_id', '=', $bmi_id )
        ->join( 'zakats', 'user_zakats.zakat_id', '=', 'zakats.id' )
        ->get( [ 'zakats.date', 'user_zakats.amount' ] );

        $a = 0;
        foreach ( $user_zakat as $r => $o ) {
            $y = Carbon::parse( $o->date )->year;
            $m = Carbon::parse( $o->date )->month;
            if ( $y<$year ) {
                $a = $a+ $o->amount ;
            }
            if ( $y == $year && $m <= $month ) {
                $a = $a+$o->amount;

            }
        }
        $total_userZakat = $a;
        return $i +
        $i1 +
        $l1-
        $l -

        $a;

    }

    public function getmonthlysip_treasurer()
 {
        $select = DB::table( 'monthly_sips' )
        ->join( 'bmi_ids', 'monthly_sips.bmi_id', '=', 'bmi_ids.id' )
        ->get( [
            'monthly_sips.year',
            'monthly_sips.amount',
            'bmi_ids.name',
            'bmi_ids.bmi_id',
        ] );
        return $select;
    }

    public function addMonthlySip() {
        $currentYear = Carbon::now()->year;
        $getBmiIdDetails = Bmi_id::where( 'status', '=', 0 )
        ->where( 'bmi_ids.verify', '=', 1 )->get();
        foreach ( $getBmiIdDetails as $key=>$value ) {
            $getCurrentMonthlySipDetails = Monthly_sip::where( 'bmi_id', '=', $value->id )->where( 'year', '=', $currentYear )->get();
            if ( count( $getCurrentMonthlySipDetails ) == 0 ) {
                $getPreviousYear = Monthly_sip::where( 'bmi_id', '=', $value->id )->where( 'year', '=', $currentYear-1 )->get();
                if ( count( $getPreviousYear )>0 ) {
                    $amount = $getPreviousYear[ 0 ]->amount;
                } else {
                    $amount = 0;
                }
                $insert = Monthly_sip::create( [
                    'bmi_id'=>$value->id,
                    'amount'=>$amount,
                    'year'=>$currentYear
                ] );
            }

        }
        return 1;
    }

    public function getInvestableAmount_n( $bmi_id, $year, $month )
 {
        $investableAmount = User_investment::where(
            'user_investments.bmi_id',
            '=',
            $bmi_id
        )->get( [ 'invested_amount', 'date' ] );
        $investmentReturnAmount = User_investment::where(
            'user_investments.bmi_id',
            '=',
            $bmi_id
        )->get( [ 'investment_return', 'date' ] );
        $i = 0;
        $j = 0;
        if ( count( $investableAmount ) == 0 ) {
            return 0;
        }
        foreach ( $investableAmount as $key => $value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y < $year ) {
                $i += $value->invested_amount ?: 0;
            }
            if ( $y == $year && $m <= $month ) {
                $i += $value->invested_amount ?: 0;
            }
        }

        foreach ( $investmentReturnAmount as $key => $value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y < $year ) {
                $j += $value->investment_return ?: 0;
            }
            if ( $y == $year && $m <= $month ) {
                $j += $value->investment_return ?: 0;
            }
        }
        return $z = $i - $j;
    }

    public function AmountAvalilableinBank() {
        ///total profit

        // $getTotalprofit = Company_transaction::where( 'amount_categories.code', '=', 5 )
        // ->where( 'companies.status', '=', 1 )->where( 'company_transactions.transferToBank', '=', 1 )
        // ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
        // ->join( 'companies', 'companies.id', '=', 'company_transactions.company_id' )
        // ->sum( 'company_transactions.amount' );

        $getTotalprofit = User_profit::where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )

        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_profits.bmi_id' )
        ->sum( 'user_profits.amount' );

        //total fund

        $getuseramount = User_transaction::where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )
        ->where( 'user_transactions.transferToBank', '=', 1 )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_transactions.bmi_id' )
        ->sum( 'user_transactions.amount' );

        //total eib kunooz

        $totaleibkunooz = User_eib_kunooz::join( 'bmi_ids', 'user_eib_kunoozs.bmi_id', '=', 'bmi_ids.id' )->where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )->sum( 'amount' );

        // $getinvest = Company::where( 'status', '=', 1 )
        // ->sum( 'invested_amount' );
        // $return = Company::where( 'status', '=', 1 )
        // ->sum( 'investment_return' );

        //total investment

        $getinvest = User_investment::where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )

        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_investments.bmi_id' )
        ->sum( 'user_investments.invested_amount' );
        $return = User_investment::where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )

        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_investments.bmi_id' )
        ->sum( 'user_investments.investment_return' );

        $total = $getinvest-$return;

        //total zakat

        // $zakat = Zakat::where( 'transferFromBank', '=', 1 )->sum( 'zakat' );

        $zakat = User_zakat::
        where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )

        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_zakats.bmi_id' )
        ->sum( 'user_zakats.amount' );

        //total expense

        // $getUsedExpense = Expense::where( 'transferFromBank', '=', 1 )->sum( 'amount' );

        $getUsedExpense = User_expense::where( 'bmi_ids.status', '!=', 3 )
        ->where( 'bmi_ids.verify', '=', 1 )

        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_expenses.bmi_id' )
        ->sum( 'user_expenses.amount' );

        $v = $getTotalprofit+$totaleibkunooz+ $getuseramount ;
        $b = $total+$zakat+$getUsedExpense;
        $n = $v-$b;
        return $n;

    }

    public function getUserothers_m( $bmi_id, $year, $month )
 {
        $currentYear = $year;
        $currentMonth = $month;

        $getTotalothers = User_transaction::where( 'user_transactions.bmi_id', '=', $bmi_id )
        ->where( 'amount_categories.code', '=', 3 )
        ->join( 'amount_categories', 'amount_categories.id', '=', 'user_transactions.amount_cat_id' )
        ->get( [ 'user_transactions.date', 'user_transactions.amount' ] );
        $totalAmount = 0;
        if ( count( $getTotalothers ) > 0 ) {
            foreach ( $getTotalothers as $key => $value ) {
                $y = Carbon::parse( $value->date )->year;
                $m = Carbon::parse( $value->date )->month;

                if ( $y < $currentYear ) {
                    $totalAmount = $totalAmount+ $value->amount;
                }
                if (
                    $y == $currentYear &&
                    $m <= $currentMonth
                ) {
                    $totalAmount = $totalAmount+ $value->amount;
                }
            }
        }

        return $totalAmount;
    }

}