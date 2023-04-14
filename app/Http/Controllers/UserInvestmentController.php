<?php

namespace App\Http\Controllers;
use App\Models\User_investment;
use App\Models\Bmi_id;
use App\Models\Company;
use App\Models\User_profit;
use App\Models\Company_Profit;
use App\Models\Company_transaction;
use App\Models\Company_investment_history;
use Illuminate\support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Carbon\Carbon;

class UserInvestmentController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'company_id' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials' ];
        }
        $insert = User_investment::create( [
            'bmi_id' => $request->bmi_id,
            'company_id' => $request->company_id,
            'invested_amount' => $request->invested_amount,
            'date' => $request->date,
            'percentage' => $request->percentage,
            'investment_return' => $request->investment_return,
        ] );
        if ( $insert ) {
            return [ 'code' => 200, 'message' => 'inserted successfully ' ];
        } else {
            return [ 'code' => 401, 'message' => 'something went wrong' ];
        }
    }

    public function list_investment()
 {
        $select = DB::table( 'user_investments' )
        ->join( 'bmi_ids', 'user_investments.bmi_id', '=', 'bmi_ids.id' )
        ->get( [
            'bmi_ids.bmi_id',
            'bmi_ids.name as member_name',
            'user_investments.invested_amount',
            'user_investments.investment_return',
        ] );
        return $select;
    }

    public function list_members()
 {
        $select = DB::table( 'user_investments' )
        ->join( 'bmi_ids', 'user_investments.bmi_id', '=', 'bmi_ids.id' )
        ->join(
            'user_profits',
            'user_investments.bmi_id',
            '=',
            'user_profits.bmi_id'
        )
        ->get( [
            'bmi_ids.bmi_id',
            'bmi_ids.name as member_name',
            'user_investments.invested_amount',
            'user_profits.amount as profit',
        ] );
        return $select;
    }

    public function investment_out( Request $request )
 {
        $select = DB::table( 'user_investments' )->where(
            'company_id',
            '=',
            $request->id
        );

        return $select->count( [ 'bmi_id' ] );
        $select1 = DB::table( 'user_investments' )->where(
            'company_id',
            '=',
            $request->id
        );
        return $select1->sum( 'invested_amount' );
        $select3 = DB::table( 'user_investments' )
        ->where( 'company_id', '=', $request->id )
        ->join(
            'companies',
            'user_investments.company_id',
            '=',
            'companies.id'
        )
        ->get( [ 'date', 'companies.name' ] );
        // return $select3;
    }

    public function investment_summary( Request $request ) {
        if ( isset( $request->fromDate ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $request->fromDate );
        $toDate = Carbon::parse( $request->toDate );
        $companyList = Company::where( 'status', '!=', 0 )->get( [ 'id', 'name', 'status' ] );
        if ( count( $companyList ) == 0 ) {
            return [
                'companyList' => $companyList,
                'AllInvestment' =>[],
                'monthlyDetails' => [],
            ];
        }
        $getUsers = Bmi_id::where( 'verify', '=', 1 )->get();
        $arr = [];
        foreach ( $getUsers as $key => $value ) {

            $l[ 'bmi_id' ] = $value->bmi_id;
            $l[ 'member_name' ] = $value->name;
            $l[ 'status' ] = $value->status;
            foreach ( $companyList as $key1 => $value1 ) {

                $invested_amount = User_investment::where(
                    'bmi_id',
                    '=',
                    $value->id
                )
                ->where( 'company_id', '=', $value1->id )
                ->get( [ 'date', 'invested_amount' ] );

                $ia = 0;
                foreach ( $invested_amount as $key2=>$value2 ) {
                    $d = Carbon::parse( $value2->date );
                    if ( $temp == 1 ) {

                        if ( $fromDate <= $d && $d <= $toDate ) {
                            $ia = $ia+$value2->invested_amount;
                        }
                    } else {

                        // if ( $d <= $toDate ) {

                        $ia = $ia+$value2->invested_amount;
                        // }
                    }
                }

                $investment_return = User_investment::where(
                    'bmi_id',
                    '=',
                    $value->id
                )
                ->where( 'company_id', '=', $value1->id )
                ->get( [ 'date', 'investment_return' ] );
                $ir = 0;
                foreach ( $investment_return as $key3=>$value3 ) {

                    $d = Carbon::parse( $value3->date );
                    if ( $temp == 1 ) {
                        if ( $fromDate <= $d && $d <= $toDate ) {
                            $ir = $ir+$value3->investment_return;
                        }
                    } else {
                        // if ( $d <= $toDate ) {

                        $ir = $ir+$value3->investment_return;
                        // }
                    }
                }

                $amt = $ia - $ir;
                $l[ $value1->id ] = $amt;
            }

            array_push( $arr, $l );
        }
        // return $arr;
        $arr1 = [];
        $company_investment = Company_investment_history::get( 'date' );
        $largestYear = Carbon::parse( $toDate )->year;
        if ( $temp == 1 ) {
            $smallestYear = Carbon::parse( $fromDate )->year;
        } else {
            $smallestYear = $largestYear;
            if ( count( $company_investment ) > 0 ) {
                foreach ( $company_investment as $kk => $vv ) {
                    $d = $vv->date;
                    $year = Carbon::parse( $d )->year;
                    if ( $year < $smallestYear ) {
                        $smallestYear = $year;
                    }
                }
            }
        }
        while ( $smallestYear <= $largestYear ) {
            if ( $temp == 0 ) {
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
                    $p[ 'bmi_id' ] = $value->bmi_id;
                    $p[ 'member_name' ] = $value->name;
                    $p[ 'status' ] = $value->status;
                    $p[ 'month_year' ] =
                    $this->getMonthName( $currentMonth ) . ' ' . $smallestYear;
                    foreach ( $companyList as $key1 => $value1 ) {
                        $a = 0;
                        $b = 0;
                        $invested_amount = User_investment::where(
                            'bmi_id',
                            '=',
                            $value->id
                        )
                        ->where( 'company_id', '=', $value1->id )
                        ->get( [ 'invested_amount', 'date' ] );
                        foreach ( $invested_amount as $k => $v ) {
                            $y = Carbon::parse( $v->date )->year;
                            $m = Carbon::parse( $v->date )->month;
                            if ( $temp == 1 ) {
                                if ( $smallestYear < $y && $y < $largestYear ) {
                                    $a += $v->invested_amount;
                                }
                            } else {
                                if ( $y < $largestYear ) {
                                    $a += $v->invested_amount;
                                }
                            }

                            if ( $y == $largestYear && $m <= $currentMonth ) {
                                $a += $v->invested_amount;
                            }
                        }
                        $investment_return = User_investment::where(
                            'bmi_id',
                            '=',
                            $value->id
                        )
                        ->where( 'company_id', '=', $value1->id )
                        ->get( [ 'investment_return', 'date' ] );
                        foreach ( $investment_return as $k => $v ) {
                            $y = Carbon::parse( $v->date )->year;
                            $m = Carbon::parse( $v->date )->month;
                            if ( $temp == 1 ) {
                                if ( $smallestYear<$y && $y < $largestYear ) {
                                    $b += $v->investment_return;
                                }
                            } else {
                                if ( $y < $largestYear ) {
                                    $b += $v->investment_return;
                                }
                            }

                            if ( $y == $largestYear && $m <= $currentMonth ) {
                                $b += $v->investment_return;
                            }
                        }
                        $amt = $a - $b;
                        $p[ $value1->id ] = $amt;
                    }
                    array_push( $arr1, $p );
                }
                $currentMonth++;
            }
            $smallestYear++;
        }

        return [
            'companyList' => $companyList,
            'AllInvestment' => $arr,
            'monthlyDetails' => $arr1,
        ];
    }

    public function getMonthName( $n )
 {
        if ( $n == 1 ) {
            return $monthName = 'Jan';
        } elseif ( $n == 2 ) {
            return $monthName = 'Feb';
        } elseif ( $n == 3 ) {
            return $monthName = 'Mar';
        } elseif ( $n == 4 ) {
            return $monthName = 'Apr';
        } elseif ( $n == 5 ) {
            return $monthName = 'May';
        } elseif ( $n == 6 ) {
            return $monthName = 'Jun';
        } elseif ( $n == 7 ) {
            return $monthName = 'Jul';
        } elseif ( $n == 8 ) {
            return $monthName = 'Aug';
        } elseif ( $n == 9 ) {
            return $monthName = 'Sep';
        } elseif ( $n == 10 ) {
            return $monthName = 'Oct';
        } elseif ( $n == 11 ) {
            return $monthName = 'Nov';
        } else {
            return $monthName = 'Dec';
        }
    }

    public function investment_details_app( $bmi_id ) {
        $getTotalActiveInvestedAmount = User_investment::where( 'companies.status', '=', 1 )
        ->where( 'user_investments.bmi_id', '=', $bmi_id )
        ->join( 'companies', 'companies.id', '=', 'user_investments.company_id' )
        ->sum( 'user_investments.invested_amount' );
        $getTotalActiveInvestmentReturn =  User_investment::where( 'companies.status', '=', 1 )
        ->where( 'user_investments.bmi_id', '=', $bmi_id )
        ->join( 'companies', 'companies.id', '=', 'user_investments.company_id' )
        ->sum( 'user_investments.investment_return' );
        $i[ 'totalActive' ] = $getTotalActiveInvestedAmount-$getTotalActiveInvestmentReturn;
        $getTotalClosedInvestedAmount = User_investment::where( 'companies.status', '=', 2 )
        ->where( 'user_investments.bmi_id', '=', $bmi_id )
        ->join( 'companies', 'companies.id', '=', 'user_investments.company_id' )
        ->sum( 'user_investments.invested_amount' );
        $getTotalClosedInvestmentReturn =   User_investment::where( 'companies.status', '=', 2 )
        ->where( 'user_investments.bmi_id', '=', $bmi_id )
        ->join( 'companies', 'companies.id', '=', 'user_investments.company_id' )
        ->sum( 'user_investments.investment_return' );
        $i[ 'totalClosed' ] = $getTotalClosedInvestedAmount-$getTotalClosedInvestmentReturn;
        $getCompanyList = Company::get();
        $arr = array();
        foreach ( $getCompanyList as $key=>$value ) {

            $select = User_investment::where( 'company_id', '=', $value->id )->where( 'bmi_id', '=', $bmi_id )->get();
            if ( count( $select )>0 ) {
                $l[ 'company_id' ] = $value->id;
                $l[ 'company' ] = $value->name;
                $l[ 'Total_investment' ] = $value->invested_amount-$value->investment_return;
                $l[ 'your_investment' ] = ( User_investment::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->sum( 'invested_amount' ) )-( User_investment::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->sum( 'investment_return' ) );
                if ( $l[ 'Total_investment' ]>0 ) {
                    $l[ 'percentage' ] = ( $l[ 'your_investment' ]/$l[ 'Total_investment' ] )*100;
                }
                $l[ 'status' ] = $value->status;
                array_push( $arr, $l );
            }
        }
        // $select = DB::table( 'user_investments' )
        // ->where( 'bmi_id', '=', $bmi_id )
        // ->join(
        //     'companies',
        //     'companies.id',
        //     '=',
        //     'user_investments.company_id'
        // )
        // ->get( [
        //     'companies.name as company',
        //     'companies.invested_amount as Total investment',
        //     'user_investments.invested_amount as your investment',
        //     'user_investments.percentage',
        //     'companies.status',
        // ] );
        $i[ 'investment_details_data' ] = $arr;
        return $i;
    }

    public function InvestedCompany( $bmi_id, $year, $month ) {
        $c = new MonthlySipController();
        $np[ 'available_to_withdraw' ] = $c->getAvailableToWithdraw( $bmi_id, $year, $month );
        $np[ 'total_invested_amount' ] = $c->getInvestableAmount( $bmi_id, $year, $month );
        $getCompany = Company::where( 'status', '=', 1 )->get();
        $arr = array();
        foreach ( $getCompany as $key=>$value ) {

            $investment = User_investment::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->get();
            if ( count( $investment )>0 ) {
                $n[ 'company_id' ] = $value->id;
                $n[ 'company_name' ] = $value->name;
                $i = 0;
                $p = 0;

                foreach ( $investment as $k=>$v ) {
                    $k = Carbon::parse( $v->date )->year;
                    $m = Carbon::parse( $v->date )->month;

                    if ( $k<$year ) {

                        $i = $i+$v->invested_amount;
                        $p = $p+$v->investment_return;

                    }
                    if ( $k == $year && $m <= $month ) {

                        $i = $i+$v->invested_amount;
                        $p = $p+$v->investment_return;

                    }

                }
                $invested = $i-$p;
                $n[ 'invested_amount' ] = $invested;
                $profit = User_profit::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->get();
                $i = 0;

                foreach ( $profit as $t=>$l ) {
                    $k = Carbon::parse( $l->date_of_payment )->year;
                    $m = Carbon::parse( $l->date_of_payment )->month;

                    if ( $k<$year ) {

                        $i = $i+$l->amount;

                    }
                    if ( $k == $year && $m <= $month ) {

                        $i = $i+$l->amount;

                    }

                }

                $n[ 'profit' ] = $i;
                $y = User_investment::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->get( [ 'percentage' ] )->first();
                $n[ 'percentage' ] = $y->percentage;

                array_push( $arr, $n );
            }

        }
        $np[ 'data' ] = $arr;
        return $np;
    }

    public function company_investment( $bmi_id, $company_id, $year ) {
        $c = new MonthlySipController();
        $b = new BmiIdController();
        $tr = Company::where( 'companies.id', '=', $company_id )->get( [ 'companies.name' ] )->first();
        $np[ 'company_name' ] = $tr->name;
        $np[ 'available_to_withdraw' ] = $c->getAvailableToWithdraw( $bmi_id, $year );
        $np[ 'total_invested_amount' ] = $c->getInvestableAmount( $bmi_id, $year );
        $m1 = 1;
        while( $m1 <= 12 ) {

            $Company = Company_investment_history::where( 'company_id', '=', $company_id )->get()->sortBy( 'date' );
            $arr = array();
            foreach ( $Company as $key=>$value ) {
                $t =  Carbon::parse( $value->date )->month;
                $p =  Carbon::parse( $value->date )->year;

                $investment = User_investment::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $company_id )->get();
                if ( count( $investment )>0 ) {

                    $i = 0;
                    $p = 0;

                    foreach ( $investment as $k=>$v ) {
                        $k = Carbon::parse( $v->date )->year;
                        $m = Carbon::parse( $v->date )->month;

                        $n[ 'month' ] = $b->getMonthName( $t );
                        if ( $k<$year ) {

                            $i = $i+$v->invested_amount;
                            $p = $p+$v->investment_return;

                        }
                        if ( $k == $year && $m <= $m1 ) {

                            $i = $i+$v->invested_amount;
                            $p = $p+$v->investment_return;

                        }

                    }
                    $invested = $i-$p;
                    $n[ 'invested_amount' ] = $invested;
                    $companyinvest = Company_investment_history::where( 'company_id', '=', $company_id )->get();
                    $z = 0;
                    $s = 0;
                    foreach ( $companyinvest as $j=>$l ) {

                        if ( $k<$year ) {

                            $z = $z+$l->invested_amount;
                            $s = $s+$l->investment_return;

                        }
                        if ( $k == $year && $m <= $m1 ) {

                            $z = $z+$l->invested_amount;
                            $s = $s+$l->investment_return;

                        }

                    }
                    $companyfund = $z-$s;
                    $n[ 'company_investment' ] = $companyfund;
                    $y = User_investment::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $company_id )->get( [ 'percentage' ] )->first();
                    $n[ 'percentage' ] = $y->percentage;

                    array_push( $arr, $n );
                }
                $m1 = $m1+1;
            }

            $np[ 'data' ] = $arr;
            return $np;
        }
    }

    public function getInvestedCompanyDetails( $bmi_id ) {
        $getCompany = Company::get();
        $arr = array();
        foreach ( $getCompany as $key=>$value ) {
            $getInvestedCompany = User_investment::where( 'company_id', '=', $value->id )->get();
            if ( count( $getInvestedCompany )>0 ) {
                $i[ 'company_id' ] = $value->id;
                $i[ 'company_name' ] = $value->name;
                $i[ 'invested_date' ] = $value->investment_starting_date;
                $i[ 'invested_amount' ] = User_investment::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->sum( 'invested_amount' );
                $i[ 'profit' ] = User_profit::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->sum( 'amount' );
                $i[ 'investment_return' ] = User_investment::where( 'bmi_id', '=', $bmi_id )->where( 'company_id', '=', $value->id )->sum( 'investment_return' );
                $i[ 'total_amount' ] = User_investment::where( 'company_id', '=', $value->id )->sum( 'invested_amount' );
                array_push( $arr, $i );
            }
        }
        return $arr;
    }

    // public function updateUserInvestment( $bmi_id ) {
    //     $investedDetails = $this->getInvestedCompanyDetails( $bmi_id );
    //     $getInactiveDate = Bmi_id::where( 'id', '=', $bmi_id )->select( 'inactive_date' )->get()->first();
    //     $c = new CompanyController();
    //     foreach ( $investedDetails as $key=>$value ) {
    //         $invested_amount = $value[ 'invested_amount' ] - $value[ 'investment_return' ];
    //         $availabilityofInvestment = $this->getInvestmentAmount( $invested_amount, $value[ 'company_id' ] );
    //         $companyInvestedAmount = Company::where( 'id', '=', $value[ 'company_id' ] )->get()->first();
    //         $companyInvested = $companyInvestedAmount->invested_amount-$companyInvestedAmount->investment_return;

    //         if ( $companyInvested>0 ) {
    //             if ( $availabilityofInvestment[ 'code' ] == 401 ) {
    //                 return [ 'code' => $t[ 'code' ], 'message' => $t[ 'message' ] ];
    //             } else {

    //                 $userData = $availabilityofInvestment[ 'data' ];
    //                 foreach ( $userData as $key1 => $value1 ) {

    //                     $getInvestedAmount = ( User_investment::where( 'bmi_id', '=', $value1[ 'bmi_id' ] )->where( 'company_id', '=', $value[ 'company_id' ] )->sum( 'invested_amount' ) )-( User_investment::where( 'bmi_id', '=', $value1[ 'bmi_id' ] )->where( 'company_id', '=', $value[ 'company_id' ] )->sum( 'investment_return' ) );

    //                     $ratio = ( ( $value1[ 'invested_amount' ]+$getInvestedAmount )/$companyInvested )*100;

    //                     $createUserInvest = User_investment::create( [
    //                         'bmi_id' => $value1[ 'bmi_id' ],
    //                         'company_id' => $value[ 'company_id' ],
    //                         'invested_amount' => $value1[ 'invested_amount' ],
    //                         'date' => $getInactiveDate->inactive_date,
    //                         'percentage' => $ratio,
    // ] );
    //                 }
    //                 $createUserInvest1 = User_investment::create( [
    //                     'bmi_id' => $bmi_id,
    //                     'company_id' => $value[ 'company_id' ],
    //                     'date' => $getInactiveDate->inactive_date,
    //                     'percentage' => 0,
    //                     'investment_return'=>$invested_amount
    // ] );
    //             }
    //         }

    //     }

    // }

    public function updateUserInvestment( $bmi_id ) {
        $investedDetails = $this->getInvestedCompanyDetails( $bmi_id );
        $getInactiveDate = Bmi_id::where( 'id', '=', $bmi_id )->select( 'inactive_date' )->get()->first();
        $c = new CompanyController();
        foreach ( $investedDetails as $key=>$value ) {

            // $invested_amount = $value[ 'invested_amount' ] - $value[ 'investment_return' ];
            $availabilityofInvestment = $this->getInvestmentAmount( $value[ 'invested_amount' ], $value[ 'company_id' ] );
            $companyInvestedAmount = Company::where( 'id', '=', $value[ 'company_id' ] )->get()->first();
            $companyInvested = $companyInvestedAmount->invested_amount-$companyInvestedAmount->investment_return;

            if ( $companyInvested>0 ) {
                // return 6453;
                if ( $availabilityofInvestment[ 'code' ] == 401 ) {
                    return [ 'code' => $t[ 'code' ], 'message' => $t[ 'message' ] ];
                } else {

                    $userData = $availabilityofInvestment[ 'data' ];
                    foreach ( $userData as $key1 => $value1 ) {

                        $getInvestedAmount = ( User_investment::where( 'bmi_id', '=', $value1[ 'bmi_id' ] )->where( 'company_id', '=', $value[ 'company_id' ] )->sum( 'invested_amount' ) )-( User_investment::where( 'bmi_id', '=', $value1[ 'bmi_id' ] )->where( 'company_id', '=', $value[ 'company_id' ] )->sum( 'investment_return' ) );

                        $ratio = ( ( $value1[ 'invested_amount' ]+$getInvestedAmount )/$companyInvested )*100;

                        $createUserInvest = User_investment::create( [
                            'bmi_id' => $value1[ 'bmi_id' ],
                            'company_id' => $value[ 'company_id' ],
                            'invested_amount' => $value1[ 'invested_amount' ],
                            'date' => $getInactiveDate->inactive_date,
                            'percentage' => $ratio,
                        ] );
                    }
                    // return 56;
                    $createUserInvest1 = User_investment::create( [
                        'bmi_id' => $bmi_id,
                        'company_id' => $value[ 'company_id' ],
                        'date' => $getInactiveDate->inactive_date,
                        'percentage' => 0,
                        'investment_return'=>$value[ 'invested_amount' ]
                    ] );
                }
            }

        }

    }

    public function getInvestmentAmount( $amount, $company_id ) {
        // return $company_id;
        // return $amount;
        $c = new CompanyController();
        $t = new MonthlySipController();
        $totalFund = 0;
        // get all members
        $getAllMembers = $c->getMemberList( $company_id );
        $users = [];
        foreach ( $getAllMembers as $key => $value ) {
            // return $value[ 'id' ];
            $getRatio = $t->getUserRatio( $value[ 'id' ] );
            $getFund = $t->getAvailableFundToInvest( $value[ 'id' ] );
            $totalFund = $totalFund+$getFund;
            $l[ 'bmi_id' ] = $value[ 'id' ];
            $l[ 'name' ] = $value[ 'name' ];
            $l[ 'bmip_no' ] = $value[ 'bmi_id' ];
            $l[ 'fund' ] = $getFund;
            $l[ 'ratio' ] = $getRatio;
            $l[ 'invested_amount' ] = $value[ 'invested_amount' ];
            array_push( $users, $l );
        }

        if ( $totalFund < $amount ) {
            return [ 'code' => 401, 'message' =>'Not have available balance. Available balance is ' .$totalFund ];
        }
        // return  $users;
        $amt = 0;
        $investArray = [];
        foreach ( $users as $key => $value ) {
            // return 45;
            // return $amount;
            $a = ( $amount * $value[ 'ratio' ] )/100;
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

            // $i[ 'invested_ratio' ] = ( $i[ 'invested_amount' ] / $amount ) * 100;
            if ( $amount != 0 ) {
                $i[ 'invested_ratio' ] = ( $i[ 'invested_amount' ]/ $amount ) * 100;
            } else {
                $i[ 'invested_ratio' ] = 0;
            }

            // $i[ 'invested_ratio' ] = number_format( ( float )$i[ 'invested_ratio' ], 2, '.', '' );
            $amt += $i[ 'invested_amount' ];
            array_push( $investArray, $i );
        }
        // $getInvestedAmountOfCompany = Company::where( 'id', '=', $company_id )->get()->first();
        // $aaa = $getInvestedAmountOfCompany->invested_amount-$getInvestedAmountOfCompany->investment_return;
        if ( $amt < $amount ) {
            $b = $amount - $amt;
            foreach ( $investArray as $key => $value ) {
                if ( $amt != $amount ) {
                    if ( $b <= $value[ 'balance' ] ) {
                        $value[ 'invested_amount' ] += $b;
                        $value[ 'balance' ] -= $b;
                        $amt += $b;
                    } else {
                        $value[ 'invested_amount' ] += $value[ 'balance' ];
                        $value[ 'balance' ] -= $value[ 'balance' ];
                        $amt += $value[ 'balance' ];
                    }
                    if ( $amount != 0 ) {
                        $value[ 'invested_ratio' ] =
                        ( $value[ 'invested_amount' ] / $amount ) * 100;

                        // $value[ 'invested_ratio' ] = number_format( ( float ) $value[ 'invested_ratio' ], 2, '.', '' );
                        $investArray[ $key ] = $value;
                    } else {
                        $value[ 'invested_ratio' ] = 0;
                    }
                }
            }
        }
        return [ 'code' => 200, 'data' => $investArray ];

    }

    public function investment_out_app( $id, $bmi )
 {

        $select1 = DB::table( 'user_investments' )->where( 'company_id', '=', $id )->where( 'bmi_id', '=', $bmi );

        return $select1->sum( 'invested_amount' );

    }
}