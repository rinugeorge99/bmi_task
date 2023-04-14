<?php

namespace App\Http\Controllers;
use App\Models\Fund_collector;
use App\Models\Bmi_id;
use App\Models\Zakat;
use App\Models\Expense;
use App\Models\User_transaction;
use App\Models\Company_transaction;
use Illuminate\Http\Request;
use Illuminate\support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Auth;
class FundCollectorController extends Controller
 {
    public function loginfundcollector( Request $request )
 {
        $bmi_id = $request->input( 'bmi_id' );
        $password = $request->input( 'password' );

        $user = DB::table( 'fund_collectors' )
        ->join( 'bmi_ids', 'fund_collectors.bmi_id', '=', 'bmi_ids.id' )
        ->where( 'bmi_ids.bmi_id', '=', $bmi_id )
        ->where( 'fund_collectors.ending_date', '=', null )
        ->get(['bmi_ids.*',
        'fund_collectors.bmi_id as fund_collector_id',
        'fund_collectors.status as fstatus',
        'fund_collectors.fund_collector_activity',
        'fund_collectors.starting_date',
        'fund_collectors.ending_date',


        ])
        ->first();

        if ( !$user ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
        if ( !Hash::check( $password, $user->password ) ) {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
        return [ 'code' => 200, 'message' => 'Login successfully ', 'data' => $user ];
    }

    public function insert_mainfundcollector( Request $request )
 {
        // $c = new NotificationController();
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'starting_date' => 'required',
            'fund_collector_activity' => 'required',
            // 'status' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials' ];
        }
        // $f = new TreasurerController();
        // $isTreasurer = $f->checkTreasurer( $request->bmi_id );
        // if ( $isTreasurer ) {
        //     return [ 'code' => 401, 'message' => 'Already a Treasurer' ];
        // }
        $select = Fund_collector::where( 'bmi_id', '=', $request->bmi_id )
        ->where( 'ending_date', '=', null )

        ->get();
        $i = 0;

        if ( count( $select ) > 0 ) {
            foreach ( $select as $key => $value ) {
                if ( $value->status == 0 ) {
                    return [
                        'code' => 401,
                        'message' => 'Already a fund collector',
                    ];
                }
                if ( $value->status == 1 ) {
                    return [
                        'code' => 401,
                        'message' => 'Already a Main fund collector',
                    ];
                }
            }
        } else {
            $i = 1;
        }

        if ( $i = 1 ) {
            $insert = Fund_collector::create( [
                'bmi_id' => $request->bmi_id,
                'starting_date' => $request->starting_date,
                // 'ending_date' => $request->ending_date,
                'fund_collector_activity' => $request->fund_collector_activity,
                'status' => 1,
            ] );

            if ( $insert ) {

                if ( $request->previous_bmi_id ) {
                    $updateStatus = Fund_collector::where(
                        'bmi_id',
                        '=',
                        $request->previous_bmi_id
                    )->update( [
                        'ending_date' => $request->starting_date,
                    ] );
                    // $select = Bmi_id::where( 'status', '=', 0 )->pluck( 'id' );
                    // $date = Carbon::now()->format( 'Y-m-d' );
                    // $getDetails = Bmi_id::where( 'id', '=', $request->bmi_id )->get()->first();
                    // $content = 'The Mainfundcollector of the Organization changed by super admin.New Mainfundcollector is '.$getDetails->name.'('.$getDetails->bmi_id.')';
                    // $k = $c->insertNotification( $date, $content, $select );
                }
                return [
                    'code' => 200,
                    'message' => 'inserted successfully ',
                ];
            } else {
                return [ 'code' => 401, 'message' => 'something went wrong' ];
            }
        }
    }

    public function insert_fundcollector( Request $request )
 {
        $c = new NotificationController();
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'starting_date' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials' ];
        }
        $select = Fund_collector::where( 'bmi_id', '=', $request->bmi_id )
        ->where( 'ending_date', '=', null )
        ->get();

        $i = 0;
        if ( count( $select ) > 0 ) {
            foreach ( $select as $key => $value ) {
                if ( $value->status == 0 ) {
                    return [
                        'code' => 401,
                        'message' => 'Already a fund collector',
                    ];
                }
                if ( $value->status == 1 ) {
                    return [
                        'code' => 401,
                        'message' => 'Already a Main fund collector',
                    ];
                }
            }
        } else {
            $i = 1;
        }
        if ( $i = 1 ) {
            $insert = Fund_collector::create( [
                'bmi_id' => $request->bmi_id,
                'starting_date' => $request->starting_date,
                'fund_collector_activity' => 0,
                'status' => 0,
            ] );

            if ( $insert ) {
                if ( $request->previous_bmi_id ) {
                    $updateStatus = Fund_collector::where(
                        'bmi_id',
                        '=',
                        $request->previous_bmi_id
                    )->update( [
                        'ending_date' => $request->starting_date,
                    ] );
                    $select = Bmi_id::where( 'status', '=', 0 )
                   -> where( 'verify', '=', 1 )->pluck( 'id' );
                    $date = Carbon::now()->format( 'Y-m-d' );
                    $getDetails = Bmi_id::where( 'id', '=', $request->bmi_id )->get()->first();
                    $content = 'The fundcollector of the Organization changed by super admin.New fundcollector is '.$getDetails->name.'('.$getDetails->bmi_id.')';
                    $k = $c->insertNotification( $date, $content, $select ,1);
                }
                return [
                    'code' => 200,
                    'message' => 'inserted successfully ',
                ];
            } else {
                return [
                    'code' => 401,
                    'message' => 'something went wrong',
                ];
            }
        }
    }

    public function list_fund_details()
 {
        $select = DB::table( 'user_transactions' )
        ->where( 'user_transactions.transfer_verification', '1' )
        ->where( 'user_transactions.transferToBank', '0' )
        ->get();
        $select1 = DB::table( 'company_transactions' )
        ->where( 'company_transactions.transfer_verification', '1' )
        ->where( 'company_transactions.transferToBank', '0' )
        ->get();
        $items = $select1->merge( $select );
        return $items;
    }

    public function list_fund_detailsapproved()
 {
        $select = DB::table( 'user_transactions' )
        ->where( 'user_transactions.transfer_verification', '1' )
        ->where( 'user_transactions.transferToBank', '0' )
        ->get( [
            'user_transactions.amount as invested_amount',
            'user_transactions.date as invested_date',
        ] );
        $select1 = DB::table( 'company_transactions' )
        ->where( 'company_transactions.transfer_verification', '1' )
        ->where( 'company_transactions.transferToBank', '0' )
        ->join(
            'companies',
            'company_transactions.company_id',
            '=',
            'companies.id'
        )
        ->get( [ 'companies.name as company_name', 'companies.location' ] );
        $items = $select1->merge( $select );
        return $items;
    }

    public function fundcollector_activity_user()
 {
        $select = DB::table( 'fund_collectors' )

        ->join( 'bmi_ids', 'fund_collectors.bmi_id', '=', 'bmi_ids.id' )

        ->join(
            'user_transactions',
            'user_transactions.bmi_id',
            '=',
            'bmi_ids.id'
        )

        ->join(
            'amount_categories',
            'user_transactions.amount_cat_id',
            '=',
            'amount_categories.id'
        )

        ->join(
            'monthly_sip_details',
            'monthly_sip_details.transaction_id',
            '=',
            'user_transactions.id'
        )

        ->get( [
            'bmi_ids.bmi_id as Fundcollector Bmi_id',
            'user_transactions.amount as Transaction amount(AED)',
            'user_transactions.date as Date of Transaction',
            'user_transactions.remarks as Remark',
            'amount_categories.amount_type as Fund type',
            'monthly_sip_details.status',
        ] );

        if ( $select->isEmpty() ) {
            return [ 'code' => 401, 'message' => 'no result found ' ];
        } else {
            return $select;
        }
    }

    public function fundcollector_activity_company()
 {
        $select = DB::table( 'fund_collectors' )

        ->join( 'bmi_ids', 'fund_collectors.bmi_id', '=', 'bmi_ids.id' )

        ->join(
            'company_transactions',
            'company_transactions.fund_collector_id',
            '=',
            'bmi_ids.id'
        )

        ->join(
            'amount_categories',
            'company_transactions.amount_cat_id',
            '=',
            'amount_categories.id'
        )

        ->join(
            'monthly_sip_details',
            'monthly_sip_details.transaction_id',
            '=',
            'company_transactions.id'
        )

        ->get( [
            'bmi_ids.bmi_id as Fundcollector Bmi_id',
            'company_transactions.amount as Transaction amount(AED)',
            'company_transactions.date as Date of Transaction',
            'company_transactions.remarks as Remark',
            'amount_categories.amount_type as Fund type',
            'monthly_sip_details.status',
            'collected_from',
        ] );

        if ( $select->isEmpty() ) {
            return [ 'code' => 401, 'message' => 'no result found ' ];
        } else {
            return $select;
        }
    }

    public function fundcollector_activity_all( Request $request )
 {
        if ( isset( $request->fromDate ) ) {

            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $request->fromDate );
        $toDate = Carbon::parse( $request->toDate );

        $select = Company_transaction::join(
            'bmi_ids',
            'company_transactions.fund_collector_id',
            '=',
            'bmi_ids.id'
        )
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
        ->leftjoin(
            'bmi_ids as bmi_tbl',
            'bmi_tbl.id',
            '=',
            'company_transactions.collected_from'
        )
        ->orderby( 'company_transactions.id', 'DESC' )
        ->get( [
            'company_transactions.id as id',
            'bmi_ids.id as fund_collector_id',
            'bmi_ids.bmi_id as bmipno',
            'bmi_ids.name as fund_collector_name',
            'company_transactions.amount',
            'company_transactions.date',
            'company_transactions.amount_cat_id as amount_category_id',
            'amount_categories.amount_type as amount_category_name',
            'company_transactions.remarks',
            'company_transactions.transfer',
            'company_transactions.transfer_verification',
            'company_transactions.transferToBank',
            'company_transactions.created_at',
            'companies.name as company_name',
            'bmi_tbl.name as collected_from',
        ] );
        $select1 = User_transaction::join(
            'bmi_ids',
            'user_transactions.fund_collector_id',
            '=',
            'bmi_ids.id'
        )
        ->join(
            'amount_categories',
            'amount_categories.id',
            '=',
            'user_transactions.amount_cat_id'
        )
        ->orderby( 'user_transactions.id', 'DESC' )
        ->get( [
            'user_transactions.id as id',
            'bmi_ids.id as fund_collector_id',
            'bmi_ids.bmi_id as bmipno',
            'bmi_ids.name as fund_collector_name',
            'user_transactions.amount',
            'user_transactions.date',
            'user_transactions.amount_cat_id as amount_category_id',
            'amount_categories.amount_type as amount_category_name',
            'user_transactions.remarks',
            'user_transactions.transfer',
            'user_transactions.transfer_verification',
            'user_transactions.transferToBank',
            'user_transactions.created_at',
        ] );
        foreach ( $select1 as $key => $value ) {
            $value->company_name = null;
            $value->collected_from = null;
            $value->date = Carbon::parse( $value->date )->format( 'd-m-Y' );
        }
        $arr = array_merge( $select->toArray(), $select1->toArray() );
        $key = count( $arr );
        while( $key <= count( $arr )-1 ) {
            $key1 = $key-1;
            while( $key1 <= count( $arr )-1 ) {
                $date = Carbon::parse( $arr[ $key ][ 'date' ] );
                $nextDate = Carbon::parse( $arr[ $key1 ][ 'date' ] );
                if ( $date<$nextDate ) {
                    $temp = $arr[ $key ];
                    $arr[ $key ] = $arr[ $key1 ];
                    $arr[ $key1 ] = $temp;
                }
                $key1 = $key1-1;
            }
            $key = $key-1;
        }

        $arr1 = array();
        foreach ( $arr as $k=>$v ) {

            $d = Carbon::parse( $v[ 'date' ] );
            if ( $temp == 0 ) {

                if ( $d <= $toDate ) {
                    array_push( $arr1, $v );
                }
            } else {

                if ( $fromDate <= $d && $d <= $toDate ) {

                    array_push( $arr1, $v );
                }

            }
        }
        return $arr1;
    }

    public function getFundCollectors()
 {
        $select = Fund_collector::join(
            'bmi_ids',
            'bmi_ids.id',
            '=',
            'fund_collectors.bmi_id'
        )
        ->where( 'fund_collectors.status', '=', 0 )
        ->where( 'fund_collectors.ending_date', '=', null )
        ->get( [
            'fund_collectors.id',
            'bmi_ids.id as bmi_id',
            'bmi_ids.bmi_id as bmipno',
            'bmi_ids.name',
            'fund_collectors.starting_date',
        ] );
        return $select;
    }

    public function getMainFundCollectors()
 {
        $select = Fund_collector::join(
            'bmi_ids',
            'bmi_ids.id',
            '=',
            'fund_collectors.bmi_id'
        )
        ->where( 'fund_collectors.status', '=', 1 )
        ->where( 'fund_collectors.ending_date', '=', null )
        ->get( [
            'bmi_ids.id as bmi_id',
            'bmi_ids.bmi_id as bmipno',
            'bmi_ids.name',
            'fund_collectors.status as fstatus',
            'fund_collectors.fund_collector_activity',
            'fund_collectors.starting_date',
        ] );
        return $select;
    }

    public function checkFundCollector( $bmi_id )
 {
        $select = Fund_Collector::where( 'bmi_id', '=', $bmi_id )
        ->where( 'ending_date', '=', null )
        ->get();
        if ( count( $select ) > 0 ) {
            return 1;
        } else {
            return 0;
        }
    }

    public function select_fundcollector( $userid )
 {
        $select = DB::table( 'fund_collectors' )
        ->join( 'bmi_ids', 'fund_collectors.bmi_id', '=', 'bmi_ids.id' )
        ->where( 'userid', '=', $userid )
        ->where( 'fund_collectors.ending_date', '=', null )
        // ->where( 'fund_collectors.status', '=', 0 )
        ->get( [
            'bmi_ids.id',
            'bmi_ids.bmi_id',
            'fund_collectors.starting_date',
            'fund_collectors.ending_date',

            'bmi_ids.status',

            'bmi_ids.name',
            'bmi_ids.email',
            'bmi_ids.contact',
            'fund_collectors.status as fStatus',
            'fund_collectors.fund_collector_activity',
            'bmi_ids.userid',
            'bmi_ids.joining_date',
        ] );
        return $select;
    }

    public function getAllFundCollectors() {
        $select = Fund_collector::join(
            'bmi_ids',
            'bmi_ids.id',
            '=',
            'fund_collectors.bmi_id'
        )
        ->where( 'fund_collectors.ending_date', '=', null )
        ->get( [
            'bmi_ids.id as bmi_id',
            'bmi_ids.bmi_id as bmipno',
            'bmi_ids.name',
            'fund_collectors.starting_date',
        ] );
        return $select;
    }

    public function getFundCollectorById( Request $request ) {
        $select = Fund_collector::where( 'fund_collectors.id', '=', $request->id )
        ->join( 'bmi_ids', 'bmi_ids.id', '=', 'fund_collectors.bmi_id' )
        ->get(
            [
                'fund_collectors.*',
                'bmi_ids.name'
            ]
        );
        return $select;
    }
    public function mf_fundcollector(){
       return $select=Fund_collector::where('fund_collectors.status','=',1)->where('fund_collectors.fund_collector_activity','=',1)
       ->join('bmi_ids','fund_collectors.bmi_id','=','bmi_ids.id')
       ->get([
        'fund_collectors.*',
        'bmi_ids.bmi_id as bmipno',
        'bmi_ids.name'
       ]);
    }

}
