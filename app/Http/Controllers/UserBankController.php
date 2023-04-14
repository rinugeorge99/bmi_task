<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User_bank;
use App\Models\Bmi_id;

class UserBankController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( request()->all(), [
            'bank_name' => 'required',
            'acc_name' => 'required',
            'acc_no' => 'required',
            'currency' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Something went wrong1' ];
        }
        $select = User_bank::where( 'bmi_id', '=', $request->bmi_id )
        ->get()
        ->first();
        if ( $select ) {
            return [
                'code' => 401,
                'message' => 'Details of this particular user is exist',
            ];
        }
        $insert = User_bank::create( [
            'bmi_id' => $request->bmi_id,
            'abroad' => $request->abroad,
            'bank_name' => $request->bank_name,
            'acc_name' => $request->acc_name,
            'acc_no' => $request->acc_no,
            'ifsc_code' => $request->ifsc_code,
            'branch' => $request->branch,
            'currency' => $request->currency,
            'iban_no' => $request->iban_no,
            'swift' => $request->swift,
        ] );
        if ( $insert ) {
            $update_formstatus = Bmi_id::where( 'id', '=', $request->bmi_id )->where( 'form_status', '=', 2 )
            ->update( [
                'form_status'=>3
            ] );
            if ( $update_formstatus ) {
                return [ 'code' => 200, 'message' => 'inserted' ];
            }
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function update( Request $request )
 {
        $insert = User_bank::where( 'id', '=', $request->id )->update( [
            'bmi_id' => $request->bmi_id,
            'abroad' => $request->abroad,
            'bank_name' => $request->bank_name,
            'acc_name' => $request->acc_name,
            'acc_no' => $request->acc_no,
            'ifsc_code' => $request->ifsc_code,
            'branch' => $request->branch,
            'currency' => $request->currency,
            'iban_no' => $request->iban_no,
            'swift' => $request->swift,
        ] );
        if ( $insert ) {
            return [ 'code' => 200, 'message' => 'updated' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }
}
