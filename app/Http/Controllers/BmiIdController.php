<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Bmi_id;
use App\Models\Fund_collector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationMail;
use App\Mail\forgotpassword;
use App\Models\Otp;
use App\Models\User_eib_kunooz;
use App\Models\User_beneficiary;

use App\Models\User_transaction;
use App\Models\Company_transaction;
use App\Models\Treasurer;

class BmiIdController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'name' => 'required',
            'email' => 'required|email',
            'contact' => 'required|numeric',
            'joining_date' => 'required',

            // 'contact' => 'required',

        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Invalid credentials' ];
        }
        $select = Bmi_id::where( 'bmi_id', '=', $request->bmi_id )
        ->get()
        ->first();
        if ( $select ) {
            return [ 'code' => 401, 'message' => 'This bmi id is already exist' ];
        }
        $select1 = Bmi_id::where( 'email', '=', $request->email )->where( 'status', '!=', 3 )
        ->get()
        ->first();
        if ( $select1 ) {
            return [ 'code' => 401, 'message' => 'This email is already exist' ];
        } else {
            $insert = Bmi_id::create( [
                'bmi_id' => trim( $request->bmi_id ),
                'name' => trim( $request->name ),
                'email' => trim( $request->email ),
                'contact'=>trim( $request->contact ),
                'password' => random_int( 100000, 999999 ),
                'joining_date' => trim( $request->joining_date ),
                'mail_status' => 0,
                'verify' => 0,
                'status' => 0,
                'userid' => Str::uuid(),
                'form_status' => 0,

            ] );
            if ( $insert ) {
                return [ 'code' => 200, 'message' => 'Inserted' ];
            } else {
                return [ 'code' => 401, 'message' => 'Something went wrong' ];
            }
        }

    }

    public function select() {

        $select = Bmi_id::orderBy( 'id', 'DESC' )->get( [
            'id',
            'bmi_id',
            'name',
            'joining_date',
            'email',
            'contact',
            'mail_status',
            'verify',
        ] );
        $m = new MonthlySipController();
        $m->addMonthlySip();
        return $select;
    }

    public function acceptTermsAndConditions( Request $request ) {
        $c = new NotificationController();
        $update = Bmi_id::where( 'id', '=', $request->id )->update( [
            'accept_terms_condition' => 1,
        ] );
        if ( $update ) {
            $select = '[0]';
            $date = Carbon::now()->format( 'Y-m-d' );
            // $getuserDetails = Bmi_id::where( 'id', '=', $request->id )->where( 'accept_terms_condition', '=', 1 )->get()->first();
            // if ( count( $select )>0 ) {

            $content = 'User is currently submitting a pending request.';

            $k = $c->insertNotification( $date, $content, $select, 0 );
            // }

            // $content = 'Pending request from User '.$getuserDetails->name.'('.$getuserDetails->bmi_id.')';

            return [ 'code'=>200, 'message'=>'updated successfully' ];
            return [ 'code' => 200, 'message' => 'Accept terms and conditions' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function sendingMail( $id ) {
        // sending mail section
        $getEmail = Bmi_id::where( 'id', '=', $id )
        ->get()
        ->first();
        if ( !$getEmail ) {
            return [ 'code' => 401, 'message' => 'User does not exist' ];
        }

        $details = [
            'bmi_id' => $getEmail->bmi_id,
            'password' => $getEmail->password,
        ];

        // return $details;
        Mail::to( $getEmail->email )->send( new VerificationMail( $details ) );

        // Change the status of the mail
        $updateMailStatus = Bmi_id::where( 'id', '=', $id )->update( [
            'mail_status' => 1,
            'password' => Hash::make( $getEmail->password ),
        ] );

        if ( $updateMailStatus ) {
            return [ 'code' => 200, 'message' => 'Mail sent' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    //     public function getPendingRequest()
    // {
    //         $select = Bmi_id::where( 'bmi_ids.verify', '=', 0 )
    //         ->join( 'bmi_users', 'bmi_users.bmi_id', '=', 'bmi_ids.id' )
    //         ->join( 'user_addresses', 'user_addresses.bmi_id', '=', 'bmi_ids.id' )
    //         ->join( 'user_banks', 'user_banks.bmi_id', '=', 'bmi_ids.id' )
    //         ->get( [
    //             'bmi_ids.id',
    //             'bmi_ids.bmi_id',
    //             'bmi_ids.name',
    //             'joining_date',
    //             'bmi_ids.contact',
    //             'bmi_ids.email',
    // ] );

    //         foreach ( $select as $key=>$value ) {
    //             $arr = [];
    //             $beneficiary = DB::table( 'user_beneficiaries' )
    //             ->where( 'user_beneficiaries.bmi_id', '=', $value->id )
    //             ->get();

    //             if ( $beneficiary ) {

    //                 $l[ 'id' ] = $value->id;

    //                 $l[ 'bmi_id' ] = $value->bmi_id;
    //                 $l[ 'name' ] = $value->name;
    //                 $l[ 'joining_date' ] = $value->joining_date;
    //                 $l[ 'contact' ] = $value->contact;
    //                 $l[ 'email' ] = $value->email;
    //                 array_push( $arr, $l );
    //             }

    //         }
    //         return $arr;

    //     }

    public function getPendingRequest()
 {
        return  $select = Bmi_id::where( 'bmi_ids.verify', '=', 0 )->where( 'form_status', '=', 4 )
        ->where( 'accept_terms_condition', '=', 1 )
        ->join( 'bmi_users', 'bmi_users.bmi_id', '=', 'bmi_ids.id' )
        ->join( 'user_addresses', 'user_addresses.bmi_id', '=', 'bmi_ids.id' )
        ->join( 'user_banks', 'user_banks.bmi_id', '=', 'bmi_ids.id' )
        ->get( [
            'bmi_ids.id',
            'bmi_ids.bmi_id',
            'bmi_ids.name',
            'joining_date',
            'bmi_ids.contact',
            'bmi_ids.email',

        ] );

    }

    //     public function verifyUser( $id )
    // {
    //         $c = new NotificationController();
    //         $getUser = Bmi_id::where( 'id', '=', $id )
    //         ->get()
    //         ->first();
    //         if ( $getUser && $getUser->verify == 1 ) {
    //             return [ 'code' => 401, 'message' => 'Already verified' ];
    //         }
    //         $verifyUser = Bmi_id::where( 'id', '=', $id )->update( [
    //             'verify' => 1,
    // ] );
    //         if ( $verifyUser ) {

    //             $select = DB::table( 'monthly_sips' )
    //             ->where( 'bmi_id', '=', $id )
    //             ->where( 'year', '=', Carbon::now()->year )
    //             ->get()
    //             ->first();
    //             if ( !$select ) {
    //                 $getUserMonthly_SIP = DB::table( 'monthly_sips' )
    //                 ->where( 'bmi_id', '=', $id )
    //                 ->where( 'year', '=', Carbon::now()->year )
    //                 ->get()
    //                 ->first();
    //                 if ( !$getUserMonthly_SIP ) {
    //                     $insert = DB::table( 'monthly_sips' )->insert( [
    //                         'bmi_id' => $id,
    //                         'amount' => 0,
    //                         'year' => Carbon::now()->year,
    // ] );
    //                 }
    //             }
    //             $select = Bmi_id::where( 'id', '=', $id )->where( 'status', '=', 0 )->where( 'verify', '=', 1 )->pluck( 'id' );
    //             $date = Carbon::now()->format( 'Y-m-d' );
    //             $content = 'the account has been approved by the superadmin. You can now access the app.';
    //             $k = $c->insertNotification( $date, $content, $select, 1 );
    //             $select1 = Treasurer::where( 'treasurers.status', '=', 0 )->where( 'ending_date', '=', null )
    //             ->join( 'bmi_ids', 'treasurers.bmi_id', '=', 'bmi_ids.id' )->get();
    //             if ( !$select1 ) {

    //                 $select = Treasurer::where( 'status', '=', 0 )->where( 'ending_date', '=', null )->pluck( 'id' );
    //                 $date = Carbon::now()->format( 'Y-m-d' );
    //                 $getDetails = Bmi_id::where( 'bmi_ids.id', '=',  $id )

    //                 ->get();
    //                 foreach ( $getDetails as $key=>$value ) {
    //                     $content = 'please update monthly sip of '.$value->name.'('.$value->bmi_id.')';
    //                 }

    //                 $k = $c->insertNotification( $date, $content, $select, 0 );
    //             }
    //             return [ 'code' => 200, 'message' => 'User verified' ];
    //         } else {
    //             return [ 'code' => 200, 'message' => 'Something went wrong' ];
    //         }
    //     }

    public function verifyUser( $id )
 {
        $c = new NotificationController();

        $getUser = Bmi_id::where( 'id', '=', $id )
        ->get()
        ->first();
        if ( $getUser && $getUser->verify == 1 ) {
            return [ 'code' => 401, 'message' => 'Already verified' ];
        }
        $verifyUser = Bmi_id::where( 'id', '=', $id )->update( [
            'verify' => 1,
        ] );
        if ( $verifyUser ) {
            $select = DB::table( 'monthly_sips' )
            ->where( 'bmi_id', '=', $id )
            ->where( 'year', '=', Bmi_id::where( 'id', '=', $id )->pluck( 'joining_date' ) )
            ->get()
            ->first();
            if ( !$select ) {
                $getUserMonthly_SIP = DB::table( 'monthly_sips' )
                ->where( 'bmi_id', '=', $id )
                ->where( 'year', '=', Bmi_id::where( 'id', '=', $id )->pluck( 'joining_date' ) )
                ->get()
                ->first();
                if ( !$getUserMonthly_SIP ) {
                    $joiningDate = DB::table( 'bmi_ids' )->where( 'id', $id )->value( 'joining_date' );
                    $year = date( 'Y', strtotime( $joiningDate ) );
                    $insert = DB::table( 'monthly_sips' )->insert( [
                        'bmi_id' => $id,
                        'amount' => 0,
                        'year' => $year,
                    ] );
                }
            }
            $select = Bmi_id::where( 'id', '=', $id )->where( 'status', '=', 0 )->where( 'verify', '=', 1 )->pluck( 'id' );
            $date = Carbon::now()->format( 'Y-m-d' );
            // $getDetails = Bmi_id::where( 'id', '=', $request->bmi_id )->get()->first();
            $content = 'the account has been approved by the superadmin. You can now access the app.';
            $k = $c->insertNotification( $date, $content, $select, 1 );
            $select1 = Treasurer::where( 'treasurers.status', '=', 0 )->where( 'ending_date', '=', null )
            ->join( 'bmi_ids', 'treasurers.bmi_id', '=', 'bmi_ids.id' )->get();
            if ( !$select1 ) {

                $select = Treasurer::where( 'status', '=', 0 )->where( 'ending_date', '=', null )->pluck( 'id' );
                $date = Carbon::now()->format( 'Y-m-d' );
                $getDetails = Bmi_id::where( 'bmi_ids.id', '=',  $id )

                ->get();
                foreach ( $getDetails as $key=>$value ) {
                    $content = 'please update monthly sip of '.$value->name.'('.$value->bmi_id.')';
                }

                $k = $c->insertNotification( $date, $content, $select, 0 );
            }
            return [ 'code' => 200, 'message' => 'User verified' ];
        } else {
            return [ 'code' => 200, 'message' => 'Something went wrong' ];
        }
    }

    public function login( Request $request )
 {
        $login = Bmi_id::where( 'bmi_id', '=', $request->bmi_id )
        ->where( 'status', '!=', 3 )
        ->get()
        ->first();
        if ( !$login ) {
            return [ 'code' => 401, 'message' => 'User does not exist.' ];
        }
        if ( !Hash::check( $request->password, $login->password ) ) {
            return [ 'code' => 401, 'message' => 'Password is incorrect' ];
        }
        return [
            'code' => 200,
            'message' => 'Login successfully ',
            'user' => $login,
        ];
    }

    public function passwordupdate( Request $request )
 {
        $update = Bmi_id::where( 'id', '=', $request->id )->update( [
            'password' => Hash::make( $request->password ),
        ] );

        if ( $update ) {
            return [ 'code' => 200, 'message' => 'updated successfully ' ];

        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function update_status( Request $request )
 {
        $c = new NotificationController();
        $update = Bmi_id::where( 'id', '=', $request->bmi_id )->update( [
            'status' => 3,
        ] );
        if ( $update ) {
            $select = '[0]';
            $date = Carbon::now()->format( 'Y-m-d' );
            $getDetails = Bmi_id::where( 'id', '=', $request->bmi_id )->get()->first();
            $content = 'The Deactive Member Fund has been settled.'.$getDetails->name.'('.$getDetails->bmi_id.')';
            $k = $c->insertNotification( $date, $content, $select, 0 );
            $i = new UserInvestmentController();
            $i->updateUserInvestment( $request->bmi_id );
            return [ 'code' => 200, 'message' => 'updated successfully ' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function getUser( $userid )
 {
        $select = Bmi_id::where( 'userid', '=', $userid )
        ->get()
        ->first();
        return $select;
    }

    public function getUserDetails( $bmi_id ) {
        $select = Bmi_id::where( 'bmi_ids.id', '=', $bmi_id )
        ->join( 'bmi_users', 'bmi_users.bmi_id', '=', 'bmi_ids.id' )
        ->join( 'user_addresses', 'user_addresses.bmi_id', '=', 'bmi_ids.id' )
        ->join( 'user_banks', 'user_banks.bmi_id', '=', 'bmi_ids.id' )
        ->join(
            'user_beneficiaries',
            'user_beneficiaries.bmi_id',
            '=',
            'bmi_ids.id'
        )
        ->join(
            'country_of_residences',
            'country_of_residences.id',
            '=',
            'bmi_users.country_of_residence'
        )
        ->get( [
            'bmi_users.full_name',
            'bmi_ids.id as bmi_id',
            'bmi_ids.bmi_id as bmipno',
            'bmi_users.dob',
            'bmi_ids.joining_date',
            'bmi_users.contact',
            'bmi_users.contact2',
            'bmi_ids.email',
            'country_of_residences.type as countryOfResidence',
            'user_addresses.id as user_address_id',
            'user_addresses.resi_house_name',
            'user_addresses.resi_landmark',
            'user_addresses.resi_city',
            'user_addresses.po_box',
            'bmi_users.proof_details',
            'bmi_users.passport_no',
            'bmi_users.passport_expiry',
            'bmi_users.passport_upload',
            'user_addresses.house_name',
            'user_addresses.post',
            'user_addresses.district',
            'user_addresses.state',
            'user_addresses.pincode',
            'user_addresses.landmark',
            'user_addresses.street_name as street',
            'user_addresses.contact as homeAddressContact',
            'user_banks.abroad',
            'user_banks.bank_name',
            'user_banks.acc_name',
            'user_banks.acc_no',
            'user_banks.ifsc_code',
            'user_banks.branch',
            'user_banks.currency',
            'user_banks.iban_no',
            'user_banks.swift',
            'bmi_users.image'
        ] )

        ->first();

        if ( $select->proof_details ) {
            $national = new NationalIdProofController();
            $proof_details = json_decode( $select->proof_details );
            foreach ( $proof_details as $key => $value ) {
                $getNational = $national->get( $value->id );
                $value->type = $getNational->type;
            }
            $select->proof_details = $proof_details;
        }
        if ( $select->bmipno ) {
            $b = new UserBeneficiaryController();
            $getUserBeneficiaryDetails = $b->getUserBeneficiaryDetailsbyBmipno(
                $select->bmi_id
            );
            $select->user_beneficiary = $getUserBeneficiaryDetails;
            $userinvest = new UserInvestmentController();
            $select->company_details = $userinvest->getInvestedCompanyDetails( $bmi_id );
        }

        return $select;
    }

    public function update_status_approved( Request $request )
 {
        $c = new NotificationController();
        $update = Bmi_id::where( 'id', '=', $request->bmi_id )->update( [
            'status' => 1,
        ] );
        $update1 = Bmi_id::where( 'status', '=', '1' )->get( [ 'deactivated_by' ] );
        if ( $update ) {
            // $select = Treasurer::where( 'status', '=', 0 )->where( 'ending_date', '=', null )->pluck( 'id' );
            // $date = Carbon::now()->format( 'Y-m-d' );
            // $getTreasurerDetails = Bmi_id::where( 'bmi_id', '=', $request->bmi_id )->get()->first();
            // $content = 'Deactivate member by Superadmin '.$getTreasurerDetails->name.'('.$getTreasurerDetails->bmi_id.')';
            // $k = $c->insertNotification( $date, $content, $select );
            return [
                'code' => 200,
                'message' => 'updated successfully ',
                'data' => $update1,
            ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function approved_inactive_members( Request $request )
 {
        $c = new NotificationController();
        $update = Bmi_id::where( 'id', '=', $request->bmi_id )->update( [
            'status' => 2,
            'inactive_date'=>Carbon::now()->format( 'Y-m-d' ),
            'deactivated_by'=>$request->treasurer_id
        ] );

        if ( $update ) {
            // $select = Fund_collector::where( 'status', '=', 1 )->where( 'ending_date', '=', null )->pluck( 'id' );
            // $date = Carbon::now()->format( 'Y-m-d' );

            // $content = 'Inactive member fund settlement ';
            // $k = $c->insertNotification( $date, $content, $select );
            return [
                'code' => 200,
                'message' => 'updated successfully ',
            ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function getActiveMembersList( Request $request ) {
        if ( isset( $request->fromDate ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $request->fromDate );
        $toDate = Carbon::parse( $request->toDate );
        $select = Bmi_id::where( 'bmi_ids.status', '=', 0 )->where( 'bmi_ids.verify', '=', 1 )
        ->join( 'bmi_users', 'bmi_users.bmi_id', '=', 'bmi_ids.id' )
        ->get( [
            'bmi_ids.id',
            'bmi_ids.bmi_id',
            'bmi_users.full_name',
            'bmi_ids.joining_date',
        ] );
        $arr = array();
        $i = new MonthlySipController();
        foreach ( $select as $key => $value ) {
            $d = Carbon::parse( $value->joining_date );

            if ( $temp == 0 ) {
                if ( $d <= $toDate ) {
                    $l[ 'id' ] = $value->id;
                    $l[ 'bmi_id' ] = $value->bmi_id;
                    $l[ 'full_name' ] = $value->full_name;
                    $l[ 'joining_date' ] = $value->joining_date;
                    $l[ 'total_amount' ] = $i->getTotalUserFund( $value->id );
                    $l[ 'availableToInvest' ] = $i->getAvailableFundToInvest(
                        $value->id
                    );
                    $l[ 'availableToWithdraw' ] = $i->getAvailableToWithdraw(
                        $value->id
                    );
                    array_push( $arr, $l );
                }
            } else {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $l[ 'id' ] = $value->id;
                    $l[ 'bmi_id' ] = $value->bmi_id;
                    $l[ 'full_name' ] = $value->full_name;
                    $l[ 'joining_date' ] = $value->joining_date;
                    $l[ 'total_amount' ] = $i->getTotalUserFund( $value->id );
                    $l[ 'availableToInvest' ] = $i->getAvailableFundToInvest(
                        $value->id
                    );
                    $l[ 'availableToWithdraw' ] = $i->getAvailableToWithdraw(
                        $value->id
                    );
                    array_push( $arr, $l );
                }
            }
        }
        $m = new MonthlySipController();
        $m->addMonthlySip();
        return $arr;
    }

    public function getDeactiveMembersList( Request $request )
 {
        if ( isset( $request->fromDate ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $request->fromDate );
        $toDate = Carbon::parse( $request->toDate );
        $select = Bmi_id::where( 'bmi_ids.status', '!=', 0 )
        ->join( 'bmi_users', 'bmi_users.bmi_id', '=', 'bmi_ids.id' )
        ->get( [
            'bmi_ids.id',
            'bmi_ids.bmi_id',
            'bmi_users.full_name',
            'bmi_ids.joining_date',
            'bmi_ids.inactive_date',
            'bmi_ids.status',
            'bmi_ids.deactivated_by as treasurer',
        ] );
        $arr = array();
        foreach ( $select as $key => $value ) {
            $selectname = Bmi_id::where( 'id', '=', $value->treasurer )
            ->get( 'name' )
            ->first();
            $value->treasurer = $selectname;

            $i = new MonthlySipController();
            $value->total_amount = $i->getTotalUserFund( $value->id );

            $value->availableToWithdraw = $i->getAvailableToWithdraw(
                $value->id
            );

            // $d = Carbon::parse( $value->inactive_date );
            // if ( $temp == 0 ) {

            //     if ( $d <= $toDate ) {
            //         $l[ 'id' ] = $value->id;
            //         $l[ 'bmi_id' ] = $value->bmi_id;
            //         $l[ 'full_name' ] = $value->full_name;
            //         $l[ 'joining_date' ] = $value->joining_date;
            //         $l[ 'inactive_date' ] = $value->inactive_date;
            //         $l[ 'status' ] = $value->status;
            //         $selectname = Bmi_id::where( 'id', '=', $value->treasurer )
            //         ->get( 'name' )
            //         ->first();
            //         $l[ 'treasurer' ] = $selectname;

            //         $i = new MonthlySipController();
            //         $l[ 'total_amount' ] = $i->getTotalUserFund( $value->id );

            //         $l[ 'availableToWithdraw' ] = $i->getAvailableToWithdraw(
            //             $value->id
            // );
            //         array_push( $arr, $l );
            //     }
            // } else {

            //     if ( $fromDate <= $d && $d <= $toDate ) {
            //         $l[ 'id' ] = $value->id;
            //         $l[ 'bmi_id' ] = $value->bmi_id;
            //         $l[ 'full_name' ] = $value->full_name;
            //         $l[ 'joining_date' ] = $value->joining_date;
            //         $l[ 'inactive_date' ] = $value->inactive_date;
            //         $l[ 'status' ] = $value->status;
            //         $selectname = Bmi_id::where( 'id', '=', $value->treasurer )
            //         ->get( 'name' )
            //         ->first();
            //         $l[ 'treasurer' ] = $selectname;

            //         $i = new MonthlySipController();
            //         $l[ 'total_amount' ] = $i->getTotalUserFund( $value->id );

            //         $l[ 'availableToWithdraw' ] = $i->getAvailableToWithdraw(
            //             $value->id
            // );
            //         array_push( $arr, $l );
            //     }
            // }

        }
        return $select;
    }

    public function getTotalFund( $bmi_id )
 {
        // Total SIP + Total Profit
        // Total SIP = Monthly Sip till now
        // Total Profit = Company Profit till now
        // return 0;
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $getMonthlySipDetails = DB::table( 'monthly_sip_details' )
        ->where( 'year', '=', $currentYear )
        ->where( 'month', '=', $currentMonth )
        ->get();
        return $getMonthlySipDetails;
        return [ 'y' => $currentYear, 'm' => $currentMonth ];
    }

    public function getAvailableToInvest( $bmi_id )
 {
        return 1;
    }

    public function getAvailableToWithdraw( $bmi_id )
 {
        return 1;
    }

    public function getOverviewDetailsOfUser( $bmi_id )
 {
        $p = new BmiUsersController;
        $bmiDetails = Bmi_id::where( 'bmi_ids.id', '=', $bmi_id )
        ->join( 'bmi_users', 'bmi_users.bmi_id', '=', 'bmi_ids.id' )
        ->get( [
            'bmi_ids.id as bmi_id',
            'bmi_users.full_name',
            'bmi_ids.bmi_id as bmipno',
            'bmi_ids.status',
            'bmi_users.image',
        ] )
        ->first();

        $i = new MonthlySipController();
        $j = new UserTransactionController();
        $k = new MonthlySipDetailsController();
        $l = new UserZakatController();
        $m = new UserExpenseController();
        // $a = new UserProfitController();
        if ( $bmiDetails ) {
            $bmiDetails->totalFund = $i->getTotalUserFund( $bmiDetails->bmi_id );
            $bmiDetails->investableAmt = $i->getAvailableFundToInvest(
                $bmiDetails->bmi_id
            );
            $bmiDetails->totalProfit = $i->getUserTotalProfit( $bmiDetails->bmi_id );
            $bmiDetails->totalInvestment = $i->getInvestableAmount(
                $bmiDetails->bmi_id
            );
            $bmiDetails->totalExpense = $j->getUserExpenseDetails( $bmi_id )-$m->getUserExpense( $bmi_id );
            $bmiDetails->monthlySipStatus = $k->getCurrentSIPStatus( $bmi_id );
            $bmiDetails->totalMonthlySip = $i->getUserTotalMonthlySip( $bmi_id );
            $bmiDetails->userZakat = $l->getUserZakat( $bmi_id );
            $bmiDetails->totalBMIfund = $i->getTotalFund();
            $getAllMembers = Bmi_id::where( 'status', '=', 0 )
            ->where( 'verify', '=', 1 )
            ->get();

            $t1 = 0;
            foreach ( $getAllMembers as $key => $value ) {

                $getFund = $i->getAvailableFundToInvest( $value->id );
                $t1 = $t1+$getFund;

            }
            $bmiDetails->totalBMIInvestedAmount = $t1;
            // $bmiDetails->totalBMIInvestedAmount = $i->getTotalInvestedAmount();
            $bmiDetails->bmiExpenseFund = $m->getExpenseFund();
            $bmiDetails->eib_kunooz = User_eib_kunooz::sum( 'amount' );

            $m = new MonthlySipController();
            $m->addMonthlySip();
            $r = $p->expiry_notification( $bmi_id );
        }
        return $bmiDetails;
    }

    public function getAccountSummary( Request $request ) {
        if ( isset( $request->fromDate ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $fromDate = Carbon::parse( $request->fromDate );
        $toDate = Carbon::parse( $request->toDate );
        $getUsers = Bmi_id::where( 'verify', '=', 1 )->get();
        $i = new MonthlySipController();
        $l = new UserZakatController();
        $m = new UserTransactionController();
        $n = new UserExpenseController();
        $o = new UserEibKunoozController();
        $arr = [];
        foreach ( $getUsers as $key => $value ) {

            $j[ 'bmi_id' ] = $value->bmi_id;
            $j[ 'name' ] = $value->name;
            $j[ 'status' ] = $value->status;

            $j[ 'totalMonthlySip' ] = $i->getUserTotalMonthlySip_v( $value->id, $fromDate, $toDate );
            $j[ 'totalProfit' ] = $i->getUserTotalProfit_v( $value->id, $fromDate, $toDate );
            $j[ 'profitPercentage' ] =
            ( $j[ 'totalProfit' ]  != 0 && $i->getTotalProfit_v( $fromDate, $toDate ) != 0 )
            ? ( $j[ 'totalProfit' ] * 100 ) / $i->getTotalProfit_v( $fromDate, $toDate )
            : 0;
            $j[ 'totalFund' ] = $i->getTotalUserFund_v( $value->id, $fromDate, $toDate );
            $j[ 'totalInvestment' ] = $i->getInvestableAmount_v( $value->id, $fromDate, $toDate );
            $j[ 'zakat' ] = $l->getUserZakat_v( $value->id, $fromDate, $toDate );
            $j[ 'fundAvailableToInvest' ] = $i->getAvailableFundToInvest_v( $value->id, $fromDate, $toDate );
            //
            // $j[ 'fundAvailableToInvest' ] = $j[ 'totalFund' ] -$j[ 'totalInvestment' ] -$j[ 'zakat' ] ;
            $exp =
            $m->getUserExpenseDetails_v( $value->id, $fromDate, $toDate ) -
            $n->getUserExpense_v( $value->id, $fromDate, $toDate );
            $userEibKnooz = $o->getUserEibKunooz_v( $value->id, $fromDate, $toDate );
            // $j[ 'totalNetworth' ] = $j[ 'totalFund' ] - $j[ 'zakat' ] + $exp + $userEibKnooz;
            $j[ 'totalNetworth' ] = $i->getAvailableToWithdraw_v( $value->id, $fromDate, $toDate );
            array_push( $arr, $j );
        }
        $getUserTransaction = User_transaction::get( 'date' );
        $getCompanyTransaction = Company_transaction::get( 'date' );
        $a = $getCompanyTransaction->concat( $getUserTransaction );
        $largestYear = Carbon::parse( $toDate )->year;
        if ( $temp == 1 ) {
            $smallestYear = Carbon::parse( $fromDate )->year;
        } else {
            $smallestYear = $largestYear;

            if ( count( $a ) > 0 ) {
                foreach ( $a as $kk => $vv ) {
                    $d = $vv->date;
                    $year = Carbon::parse( $d )->year;

                    if ( $year < $smallestYear ) {
                        $smallestYear = $year;
                    }
                }
            }
        }
        // return $smallestYear;
        $arr1 = [];

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
                    $j1[ 'bmi_id' ] = $value->bmi_id;
                    $j1[ 'name' ] = $value->name;
                    $j1[ 'status' ] = $value->status;
                    $j1[ 'month_year' ] =
                    $this->getMonthName( $currentMonth ) . ' ' . $smallestYear;
                    $j1[ 'totalMonthlySip' ] = $i->getUserTotalMonthlySip_m(
                        $value->id,
                        $largestYear,
                        $currentMonth
                    );
                    $j1[ 'totalProfit' ] = $i->getUserTotalProfit_m(
                        $value->id,
                        $largestYear,
                        $currentMonth
                    );
                    $j1[ 'profitPercentage' ] =
                    $j1[ 'totalProfit' ] * 100 != 0
                    ? ( $j1[ 'totalProfit' ] * 100 ) / $i->getTotalProfit_m(
                        $value->id,
                        $largestYear,
                        $currentMonth
                    )
                    : 0;
                    $j1[ 'totalFund' ] = $i->getTotalUserFund_m(
                        $value->id,
                        $largestYear,
                        $currentMonth
                    );
                    $j1[ 'totalInvestment' ] = $i->getInvestableAmount_m(
                        $value->id,
                        $largestYear,
                        $currentMonth
                    );
                    $j1[ 'zakat' ] = $l->getUserZakat_m(
                        $value->id,
                        $largestYear,
                        $currentMonth
                    );

                    $j1[ 'fundAvailableToInvest' ] = $i->getAvailableFundToInvest_m(
                        $value->id,
                        $largestYear,
                        $currentMonth
                    );
                    $exp =
                    $m->getUserExpenseDetails_m(
                        $value->id,
                        $largestYear,
                        $currentMonth
                    ) ;
                    -
                    $n->getUserExpense_m(
                        $value->id,
                        $largestYear,
                        $currentMonth
                    );
                    $getUserEibkunooz = $o->getUserEibKunooz_m( $value->id, $largestYear, $currentMonth );
                    $j1[ 'totalNetworth' ] = $i->getAvailableToWithdraw_m( $value->id, $largestYear, $currentMonth );
                    $j1[ 'totalFund' ] - $j1[ 'zakat' ] + $exp+$getUserEibkunooz;
                    // $j1[ 'totalNetworth' ] = $i->getAvailableToWithdraw_m( $value->id,
                    // $largestYear,
                    // $currentMonth );

                    array_push( $arr1, $j1 );
                }
                $currentMonth++;
            }
            $smallestYear++;
        }
        return [ 'all' => $arr, 'monthly' => $arr1 ];
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

    public function bmipsummery( $bmi_id, $year, $month )
 {
        $i = new MonthlySipController();
        $m = new UserTransactionController();

        $n = new UserExpenseController();
        $v[ 'total_investment' ] = $i->getInvestableAmount( $bmi_id, $year, $month );
        $v[ 'avilable_for_withdraw' ] = $i->getAvailableToWithdraw( $bmi_id, $year, $month );
        $v[ 'total_bmip_fund' ] = $i->getTotalFund_m( $year, $month );
        // $v[ 'total_bmip_fund' ] = $i->getTotalFund();
        $getAllMembers = Bmi_id::where( 'status', '=', 0 )
        ->where( 'verify', '=', 1 )
        ->get();

        $t1 = 0;
        foreach ( $getAllMembers as $key => $value ) {

            $getFund = $i->getAvailableFundToInvest( $value->id );
            $t1 = $t1+$getFund;

        }

        $v[ 'available_for_invest' ] = $t1;
        $v[ 'bmip_expense_fund' ] = $i->getExpenseFund( $year, $month );

        return $v;
    }

    public function account( $id, $year, $month ) {

        $i = new MonthlySipController();
        $t = $i->getInvestableAmount( $id );
        $t1 = $i->getInvestableAmount_m( $id, $year, $month );
        $p = $i->getAvailableToWithdraw( $id );
        $q = $i->getUserTotalMonthlySip_m( $id, $year, $month );
        $w = $i->getUserTotalProfit_m( $id, $year, $month );
        $c = new UserZakatController();
        $e = $c->getUserZakat_m( $id, $year, $month );
        $m = new UserTransactionController();
        $qw = $i->getTotalProfit_m( $id, $year, $month );

        if ( $w == 0 || $qw == 0 ) {
            $percentage  = 0;
        } else {
            $percentage = ( $w/$qw )*100;
        }
        $n = new UserExpenseController();
        $y = $i->getAvailableFundToInvest_m( $id, $year, $month );

        $o = $m->getUserExpenseDetails_m( $id, $year, $month );
        $u = $n->getUserExpense_m( $id, $year, $month );
        $exp = $o-$u;
        $totalfund = $q+$w;
        $j = $i->getTotalUserFund_m( $id, $year, $month );
        $ee = new UserEibKunoozController( $id, $year, $month );
        $getUserEibkunooz = $ee->getUserEibKunooz_m( $id, $year, $month );
        $others = $m->getothers_m( $id, $year, $month );
        $h = $j-$e+$exp+$getUserEibkunooz;
        $select = ( [
            'investedAmt'=>$t,
            'available to withdraw'=>$p,
            'totalmonthlysip'=>$q,
            'totalprofit'=>$w,
            'zakat'=>$e,
            'total_investment_done'=>$t1,
            'fund_avilable_for_investment'=>$y,
            'totalNetworth'=>$h,
            'totalfund'=>$totalfund,
            'others'=>$others,
            'profit%'=>$percentage ] );

            return $select;

        }

        public function select_userlogin( Request $request ) {
            $select = Bmi_id::where( 'userid', '=', $request->userid )
            // ->where( 'fund_collectors.status', '=', 0 )
            ->get( [
                'id',
                'bmi_id',
                'name',
                'email',
                'contact',
                'password',
                'mail_status',
                'verify',
                'status',
                'deactivated_by',
                'userid',
                'joining_date',
                'inactive_date',
                'accept_terms_condition',
                'form_status'
            ] );
            return $select;
        }

        public function forgot_password( Request $request ) {
            $getEmail = Bmi_id::where( 'email', '=', $request->email )
            ->get()
            ->first();
            if ( !$getEmail ) {
                return [ 'code' => 401, 'message' => 'User does not exist' ];
            }
            $otpdetails = [
                'OTP' => random_int( 100000, 999999 ),
            ];
            $validator = Validator::make( $request->all(), [

                'email' => 'required|email',

            ] );
            if ( $validator->fails() ) {
                return [ 'code' => 401, 'message' => 'Invalid credentials' ];
            }
            // return  $otpdetails;
            Mail::to( $getEmail->email )->send( new forgotpassword( $otpdetails ) );
            $otp = implode( $otpdetails );
            $select = Otp::where( 'email', '=', $request->email )->get();
            $b = Bmi_id::where( 'email', '=', $request->email )->get();
            foreach ( $b as $key=>$value ) {
                $k = $value->id;
            }
            if ( $select->isNotEmpty() ) {
                $update = Otp::where( 'email', '=',  $request->email )->update( [
                    'otp' =>$otp,
                ] );
                if ( $update ) {

                    return [ 'code' => 200, 'message' => 'Mail sent' ];
                } else {
                    return [ 'code' => 401, 'message' => 'Something went wrong' ];
                }
            } else {
                $insert = DB::table( 'otps' )->insert( [
                    'bmi_id'=>$k,
                    'email' => $request->email,
                    'otp' =>$otp,
                ] );
                if ( $insert ) {

                    return [ 'code' => 200, 'message' => 'Mail sent' ];
                } else {
                    return [ 'code' => 401, 'message' => 'Something went wrong' ];
                }
            }

        }

        public function otp_verify( Request $request ) {
            $otp = $request->otp;
            $email = $request->email;
            $select = Otp::where( 'otp', '=', $request->otp )
            ->where( 'email', '=', $request->email )->get( [ 'otp', 'email', 'bmi_id' ] );
            if ( $select->isNotEmpty() ) {

                return [ 'code' => 200, 'message' => 'OTP verified', 'bmi_id'=>$select[ 0 ]->bmi_id ];
            } else {

                return [ 'code' => 401, 'message' => 'something went wrong' ];
            }
        }

        public function re_sendmail( $id ) {
            $getmail_status = Bmi_id::where( 'id', '=', $id )->where( 'mail_status', '=', 1 )
            ->where( 'verify', '=', 0 )->get();
            if ( $getmail_status ) {
                $update = Bmi_id::where( 'id', '=', $id )->where( 'mail_status', '=', 1 )->update( [
                    'mail_status'=>0,
                    'password' => random_int( 100000, 999999 )
                ] );
                if ( $update ) {
                    // return 90;
                    return $s = $this->sendingMail( $id );
                }
            } else {
                return [ 'code' => 401, 'message' => 'something went wrong' ];

            }
        }

    }