<?php

namespace App\Http\Controllers;
use App\Models\Company;
use Illuminate\support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User_transaction;
use App\Models\Amount_category;
use App\Models\Bmi_id;
use App\Models\User_profit;
use App\Models\Monthly_sip;
use App\Models\Company_investment_history;
use App\Models\User_investment;
use App\Models\Company_transaction;
use App\Models\Fund_collector;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
 {
    public function insert_company( Request $request )
 {
        $c = new NotificationController();
        $validator = Validator::make( $request->all(), [
            'name' => 'required',
            'location' => 'required',
            'investment_starting_date' => 'required',
            'treasure_id' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials' ];
        }
        $t = $this->checkInvestmentAvailability(
            $request->invested_amount
        );
        if ( $t[ 'code' ] == 401 ) {
            return [ 'code' => $t[ 'code' ], 'message' => $t[ 'message' ] ];
        }
        $insert = Company::create( [
            'name' => $request->name,
            'location' => $request->location,
            'investment_starting_date' => $request->investment_starting_date,
            'invested_amount' => $request->invested_amount,
            'status' => 0,
            'treasure_id' => $request->treasure_id,
        ] );
        if ( $insert ) {
            $select = Fund_collector::where( 'status', '=', 1 )->where( 'ending_date', '=', null )->pluck( 'bmi_id' );
            $date = Carbon::now()->format( 'Y-m-d' );

            $content = 'company : Waiting for approval';
            $status = 0;
            $k = $c->insertNotification( $date, $content, $select, $status );

            return [ 'code' => 200, 'message' => 'inserted successfully ' ];
        } else {
            return [ 'code' => 401, 'message' => 'something went wrong' ];
        }
    }

    public function update_company( Request $request )
 {
        $update = Company::where( 'id', '=', $request->id )->update( [
            'name' => $request->name,
            'location' => $request->location,
            'investment_starting_date' => $request->investment_starting_date,
            'inactivated_date' => $request->inactivated_date,
            'status' => $request->status,
        ] );
        if ( $update ) {
            return [ 'code' => 200, 'message' => 'updated successfully' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function approve_investment( Request $request )
 {
        $c = new NotificationController();
        $selectCompany = Company::where( 'id', '=', $request->id )
        ->get()
        ->first();
        // return $selectCompany;
        if ( !$selectCompany ) {
            return [ 'code' => 401, 'message' => 'Company is not exist' ];
        }

        $t = $this->checkInvestmentAvailability(
            $selectCompany->invested_amount
        );
        if ( $t[ 'code' ] == 401 ) {
            return [ 'code' => $t[ 'code' ], 'message' => $t[ 'message' ] ];
        } else {
            $update = Company::where( 'id', '=', $request->id )->update( [
                'status' => 1,
            ] );
            if ( $update ) {
                $insertCompanyInvestmentHistory = Company_investment_history::create(
                    [
                        'company_id' => $selectCompany->id,
                        'invested_amount' => $selectCompany->invested_amount,
                        'date' => $selectCompany->investment_starting_date,
                    ]
                );
                $userData = $t[ 'data' ];
                foreach ( $userData as $key => $value ) {
                    if ( $value[ 'invested_amount' ] != 0 ) {
                        $createUserInvest = User_investment::create( [
                            'bmi_id' => $value[ 'bmi_id' ],
                            'company_id' => $selectCompany->id,
                            'invested_amount' => $value[ 'invested_amount' ],
                            'date' => $selectCompany->investment_starting_date,
                            'percentage' => $value[ 'invested_ratio' ],
                        ] );
                    }

                }

                $select = Bmi_id::where( 'status', '=', 0 )->where( 'verify', '=', 1 )->pluck( 'id' );
                $date = Carbon::now()->format( 'Y-m-d' );
                $getDetails = Company::where( 'id', '=', $request->id )->get()->first();
                $content = 'Invested in '.$getDetails->name.'('.$getDetails->id.')';
                $status = 1;
                $k = $c->insertNotification( $date, $content, $select, $status );
                return [ 'code' => 200, 'message' => 'updated successfully ' ];
            } else {
                return [ 'code' => 401, 'message' => 'something went wrong' ];
            }

        }
    }

    public function selectapprovedcompany()
 {
        $select = Company::where( 'status', '=', '1' )
        ->orderby( 'id', 'desc' )
        ->get();
        return $select;
    }

    public function checkInvestmentAvailability( $amount )
 {
        // return $amount;
        $t = new MonthlySipController();
        // $totalFund = $t->getTotalFund();
        // $getTotalInvestedAmount = $t->getTotalInvestedAmount();

        // get all members
        $getAllMembers = Bmi_id::where( 'status', '=', 0 )
        ->where( 'verify', '=', 1 )
        ->get();
        $users = [];
        $t1 = 0;
        foreach ( $getAllMembers as $key => $value ) {

   
    //    $getRatio = $t->getUserRatio( $value->id );
        $getRatio = $t->getUserRatio( $value->id );

         $getFund = $t->getAvailableFundToInvest( $value->id );
            $t1 = $t1+$getFund;
            $l[ 'bmi_id' ] = $value->id;
            $l[ 'name' ] = $value->name;
            $l[ 'bmip_no' ] = $value->bmi_id;
          $l[ 'fund' ] = $getFund;
        $l[ 'ratio' ] = $getRatio;
            if ( $l[ 'fund' ]>0 ) {
                array_push( $users, $l );

            }

        }
        // return $l['ratio'];
        // return$t1;
        if ( $t1 < $amount ) {
            return [
                'code' => 401,
                'message' =>
                'Not have available balance.Available balance is ' .
                $t1,
            ];
        }

        $amt = 0;
        $investArray = [];
        $amtt = $amount;
        // return $users;
        foreach ( $users as $key => $value ) {
        
          $a = ($amount * $value[ 'ratio' ])/100;
          
            if ( $a > $value[ 'fund' ] ) {
                $i[ 'invested_amount' ] = $value[ 'fund' ];
                $i[ 'balance' ] = 0;
            } else {
                $i[ 'invested_amount' ] = $a;
                 $i[ 'balance' ] = $value[ 'fund' ] - $a;
            }
            $i[ 'bmi_id' ] = $value[ 'bmi_id' ];
            $i[ 'name' ] = $value[ 'name' ];
            $i[ 'bmip_no' ] = $value[ 'bmip_no' ];
           $i[ 'invested_ratio' ] = ( $i[ 'invested_amount' ] / $amount ) * 100;
            $amt = $amt+ $i[ 'invested_amount' ];

            array_push( $investArray, $i );
        }
        // return $amt;
        // return $amtt;
        $amtt = $amtt-$amt;
        while( $amtt>0 ) {

            foreach ( $investArray as $key => $value ) {

                if ( $amtt>0 ) {

                    if ( $amtt <= $value[ 'balance' ] ) {
                        $value[ 'invested_amount' ] += $amtt;
                        $value[ 'balance' ] = $value[ 'balance' ] - $amtt;
                        $amt = $amt+ $amtt;

                    } else {
                        $value[ 'invested_amount' ] += $value[ 'balance' ];
                        $amt = $amt+ $value[ 'balance' ];
                        $value[ 'balance' ] = 0;

                    }
                    $value[ 'invested_ratio' ] =
                    ( $value[ 'invested_amount' ] / $amount ) * 100;
                    $investArray[ $key ] = $value;
                    $amtt = $amount-$amt;
                }

            }
            // return [ 'amt'=>$amt, 'amount'=>$amount, 'amtt'=>$amtt = $amount-$amt, 'investmentArray'=>$investArray ] ;

        }

        return [ 'code' => 200, 'data' => $investArray, 'amount'=>$amt ];
    }

    public function fund_approval() {
        $select = DB::table( 'companies' )
        ->join( 'bmi_ids', 'companies.treasure_id', '=', 'bmi_ids.id' )
        ->where( 'companies.status', '0' )
        ->get( [ 'companies.*', 'bmi_ids.name as treasurer_name' ] );
        // return $select;
        if ( $select->isEmpty() ) {
            return [ 'code' => 401, 'message' => 'no result found ' ];
        } else {
            return $select;
        }
    }

    public function getCompanyInvestmentBankOut()
 {
        $v = new MonthlySipController;
        $getCompany = Company::where( 'status', '=', 1 )->get( [
            'id',
            'investment_starting_date',
            'name',
            'invested_amount',
            'investment_return',
        ] );
        // return $getCompany;
        $arr = [];
        foreach ( $getCompany as $key => $value ) {
            $l[ 'invested_date' ] = $value->investment_starting_date;

            $l[ 'company_name' ] = $value->name;

            $l[ 'invested_amount' ] =
            $value->invested_amount - ( $value->investment_return  ?: 0 );

            $l[ 'noOfMembers' ] = count(
                User_investment::where( 'user_investments.company_id', '=', $value->id )
                ->where( 'bmi_ids.status', '=', 0 )->where( 'bmi_ids.verify', '=', 1 )
                ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_investments.bmi_id' )
                ->groupby( 'user_investments.bmi_id' )
                ->get( 'user_investments.bmi_id' )
            );

            $l[ 'members_list' ] = $this->getMemberList( $value->id );
            array_push( $arr, $l );
        }
        $l = $v->AmountAvalilableinBank();
        return [ 'amount'=>$l, 'details'=>$arr ];
    }

    public function getMemberList( $company_id )
 {
        $getUsers = Bmi_id::get();
        $userArray = [];
        foreach ( $getUsers as $k => $v ) {
            $checkinvested = User_investment::where(
                'user_investments.company_id',
                '=',
                $company_id
            )
            ->where( 'bmi_ids.status', '=', 0 )
            ->where( 'bmi_ids.verify', '=', 1 )
            ->where( 'user_investments.bmi_id', '=', $v->id )
            ->join( 'bmi_ids', 'bmi_ids.id', '=', 'user_investments.bmi_id' )
            ->get();

            if ( count( $checkinvested ) > 0 ) {
                $li[ 'id' ] = $v->id;
                $li[ 'bmi_id' ] = $v->bmi_id;
                $li[ 'name' ] = $v->name;
                $i = User_investment::where( 'company_id', '=', $company_id )
                ->where( 'bmi_id', '=', $v->id )
                ->sum( 'invested_amount' );
                $j = User_investment::where( 'company_id', '=', $company_id )
                ->where( 'bmi_id', '=', $v->id )
                ->sum( 'investment_return' );
                $li[ 'invested_amount' ] = $i - ( $j ?: 0 );
                $li[ 'profit' ] = User_profit::where(
                    'company_id',
                    '=',
                    $company_id
                )
                ->where( 'bmi_id', '=', $v->id )
                ->sum( 'amount' );
                array_push( $userArray, $li );
            }
        }
        return $userArray;
    }

    public function getCompanyDetails()
 {
        $companyList = Company::get();
        $arr = [];
        foreach ( $companyList as $key => $value ) {
            $l[ 'company_id' ] = $value->id;
            $l[ 'company_name' ] = $value->name;
            $l[ 'location' ] = $value->location;
            $l[ 'invested_date' ] = $value->investment_starting_date;
            $l[ 'totalInvestedAmount' ] = $value->invested_amount;
            $l[ 'inactivated_date' ] = $value->inactivated_date;
            $l[ 'total_profit' ] = Company_transaction::where( 'company_transactions.company_id', '=', $value->id )
            ->where( 'amount_categories.code', '=', 5 )
            ->join( 'amount_categories', 'amount_categories.id', '=', 'company_transactions.amount_cat_id' )
            ->sum( 'company_transactions.amount' );
            $l[ 'profit_list' ] = $this->getProfitReport( $value->id );
            $l[ 'investment_return' ] = $value->investment_return;
            $l[ 'no_of_members' ] = count( $this->getMemberList( $value->id ) );
            if ( $value->status == 0 ) {
                $l[ 'status' ] = 'Waiting for approval';
            } elseif ( $value->status == 1 ) {
                $l[ 'status' ] = 'Active';
            } elseif ( $value->status == 2 ) {
                $l[ 'status' ] = 'Inactive';
            } else {
            }
            $l[ 'members_list' ] = $this->getMemberList( $value->id );
            array_push( $arr, $l );
        }
        return $arr;
    }

    public function CompanyDetailsapp( $id, $bmi ) {

        $m = new UserInvestmentController();
        $companyList = Company::where( 'id', '=', $id )->get();
        $arr = [];
        foreach ( $companyList as $key => $value ) {
            $l[ 'company_id' ] = $value->id;
            $l[ 'company_name' ] = $value->name;
            $l[ 'location' ] = $value->location;
            $l[ 'invested_date' ] = $value->investment_starting_date;
            $l[ 'totalInvestedAmount' ] = $value->invested_amount;
            $l[ 'inactivated_date' ] = $value->inactivated_date;
            $l[ 'total_profit' ] = Company_transaction::where(
                'company_transactions.company_id',
                '=',
                $value->id
            )
            ->where( 'amount_categories.code', '=', 5 )
            ->join(
                'amount_categories',
                'amount_categories.id',
                '=',
                'company_transactions.amount_cat_id'
            )
            ->sum( 'company_transactions.amount' );

            $l[ 'amount_invested' ] = $m->investment_out_app( $id, $bmi );

            $profitReceived = User_profit::where(
                'user_profits.company_id',
                '=',
                $value->id
            )
            ->get( 'user_profits.amount' )
            ->first();
            if ( $profitReceived ) {
                $l[ 'profit_recieved' ] = $profitReceived->amount;
            } else {
                $l[ 'profit_recieved' ] = 0;
            }
            $m = Company_transaction::where(
                'company_transactions.company_id',
                '=',
                $value->id
            )
            ->where( 'amount_categories.code', '=', 5 )
            ->join(
                'amount_categories',
                'amount_categories.id',
                '=',
                'company_transactions.amount_cat_id'
            )
            ->sum( 'company_transactions.amount' );
            $n = User_profit::where( 'user_profits.company_id', '=', $value->id )
            ->get( 'user_profits.amount' )
            ->first();
            if ( $m == 0 || $n->amount == 0 ) {
                $l[ 'profit_sharing' ] = 0;
            } else {

                $l[ 'profit_sharing' ] = ( $n->amount / $m ) * 100;
            }

            if ( $value->status == 0 ) {
                $l[ 'status' ] = 'Waiting for approval';
            } elseif ( $value->status == 1 ) {
                $l[ 'status' ] = 'Active';
            } elseif ( $value->status == 2 ) {
                $l[ 'status' ] = 'Inactive';
            } else {
            }
            array_push( $arr, $l );
        }
        return $arr;
    }

    public function getProfitReport( $company_id )
 {
        $getProfit = Company_transaction::where(
            'company_transactions.company_id',
            '=',
            $company_id
        )
        ->where( 'amount_categories.code', '=', 5 )
        ->join(
            'amount_categories',
            'amount_categories.id',
            '=',
            'company_transactions.amount_cat_id'
        )
        ->get( [ 'date', 'company_transactions.amount' ] );
        $a1 = [];
        $i = new BmiIdController();
        foreach ( $getProfit as $key => $value ) {
            $r[ 'date' ] = $value->date;
            $r[ 'month' ] = $i->getMonthName( Carbon::parse( $value->date )->month );
            $r[ 'year' ] = Carbon::parse( $value->date )->year;
            $r[ 'amount' ] = $value->amount;
            array_push( $a1, $r );
        }
        return $a1;
    }
    // $companyList = Company::where( 'status', '=', 1 )->get( [ 'id', 'name' ] );

    public function Profitdetailsapp( $id )
 {
        //  $companyList = Company::where( 'status', '=', 1 )->get( [ 'id', 'name' ] );
        $companyList1 = Company::where( 'id', '=', $id )
        ->where( 'status', '=', 1 )
        ->get();
        $arr = [];
        foreach ( $companyList1 as $key => $value ) {
            // return $value;
            $l[ 'company_name' ] = $value->name;

            $l[ 'total_profit' ] = Company_transaction::where(
                'company_transactions.company_id',
                '=',
                $value->id
            )
            ->where( 'amount_categories.code', '=', 5 )
            ->join(
                'amount_categories',
                'amount_categories.id',
                '=',
                'company_transactions.amount_cat_id'
            )
            ->sum( 'company_transactions.amount' );
            $l[ 'indivdual profit' ] = User_profit::where(
                'user_profits.company_id',
                '=',
                $value->id
            )->get( 'user_profits.amount' );
            $m = Company_transaction::where(
                'company_transactions.company_id',
                '=',
                $value->id
            )
            ->where( 'amount_categories.code', '=', 5 )
            ->join(
                'amount_categories',
                'amount_categories.id',
                '=',
                'company_transactions.amount_cat_id'
            )
            ->sum( 'company_transactions.amount' );
            $n = User_profit::where( 'user_profits.company_id', '=', $value->id )
            ->get( 'user_profits.amount' )
            ->first();
            if ( $m == 0 || $n == 0 ) {
                $l[ '%' ] = null;
            } else {
                $l[ '%' ] = ( $n / $m ) * 100;
            }
            if ( $value->status == 0 ) {
                $l[ 'status' ] = 'Waiting for approval';
            } elseif ( $value->status == 1 ) {
                $l[ 'status' ] = 'Active';
            } elseif ( $status == 2 ) {
                $l[ 'status' ] = 'Inactive';
            } else {
            }
            array_push( $arr, $l );
        }
        return  $arr ;
    }
}
