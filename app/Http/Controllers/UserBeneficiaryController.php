<?php

namespace App\Http\Controllers;
use App\Models\User_beneficiary;
use App\Models\Bmi_id;
use Illuminate\support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserBeneficiaryController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'name' => 'required',
            'relation' => 'required',
            'address' => 'required',
            'contact' => 'required',
            'national_id_proof' => 'required',
            'document' => 'required',
            'passport_no' => 'required',
            'passport_expiry' => 'required',
            'passport_upload' => 'required',
            'dob' => 'required',
            'acc_no' => 'required',
            'ifsc' => 'required',
            'branch' => 'required',
            'bank_name' => 'required',
            'beneficiary_share' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Invalid credentials' ];
        }

        $doc = $this->imageUploadforDocument( $request->document );
        $passport = $this->imageUploadforpassport( $request->passport_upload );
        $insert = User_beneficiary::create( [
            'bmi_id' => trim( $request->bmi_id ),
            'name' => trim( $request->name ),
            'relation' => trim( $request->relation ),
            'address' => trim( $request->address ),
            'contact' => trim( $request->contact ),
            'national_id_proof' => trim( $request->national_id_proof ),
            'document' => trim( $doc ),
            'passport_no' => trim( $request->passport_no ),
            'passport_expiry' => trim( $request->passport_expiry ),
            'passport_upload' => trim( $passport ),
            'dob' => trim( $request->dob ),
            'acc_no' => trim( $request->acc_no ),
            'ifsc' => trim( $request->ifsc ),
            'branch' => trim( $request->branch ),
            'bank_name' => trim( $request->bank_name ),
            'beneficiary_share' => trim( $request->beneficiary_share ),
        ] );

        if ( $insert ) {
            $update_formstatus = Bmi_id::where( 'id', '=', $request->bmi_id )->where( 'form_status', '=', 3 )
            ->update( [
                'form_status'=>4
            ] );
            if ( $update_formstatus ) {
                return [ 'code' => 200, 'message' => 'Inserted' ];
            }
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function update_beneficiary( Request $request )
 {

        if ( $request->hasfile( 'document' ) ) {

            $existingimages = User_beneficiary::where( 'id', '=', $request->id )
            ->get()
            ->first();
            $doc = $this->imageUploadforDocument( $request->document );

            unlink( $existingimages->document );
        } else {
            $doc = $request->document ;
        }
        if ( $request->hasfile( 'passport_upload' ) ) {
            $existingimages = User_beneficiary::where( 'id', '=', $request->id )
            ->get()
            ->first();
            $passport = $this->imageUploadforpassport( $request->passport_upload );
            unlink( $existingimages->passport_upload );
        } else {
            $passport = $request->passport_upload;
        }
        $select = User_beneficiary::where( 'id', '=', $request->id )->get();

        $update = User_beneficiary::where( 'id', '=', $request->id )->update( [
            'bmi_id' => $request->bmi_id,
            'name' => $request->name,
            'relation' => $request->relation,
            'address' => $request->address,
            'contact' => $request->contact,
            'national_id_proof' => $request->national_id_proof,
            'document' => $doc,
            'passport_no' => $request->passport_no,
            'passport_expiry' => $request->passport_expiry,
            'passport_upload' => $passport,
            'dob' => $request->dob,
            'acc_no' => $request->acc_no,
            'ifsc' => $request->ifsc,
            'branch' => $request->branch,
            'bank_name' => $request->bank_name,
            'beneficiary_share' => $request->beneficiary_share,
        ] );
        if ( $update ) {
            return [ 'code' => 200, 'message' => 'updated successfully' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function imageUploadforDocument( $image )
 {
        $imageName = time() . $image->getClientOriginalName();
        $image->move( public_path( 'document' ), $imageName );
        return 'document/' . $imageName;
    }

    public function imageUploadforpassport( $image )
 {
        $imageName = time() . $image->getClientOriginalName();
        $image->move( public_path( 'passport' ), $imageName );
        return 'passport/' . $imageName;
    }

    public function getUserBeneficiaryDetailsbyBmipno( $bmi_id )
 {
        $select = User_beneficiary::where( 'user_beneficiaries.bmi_id', '=', $bmi_id )
        ->join( 'national_id_proofs', 'national_id_proofs.id', '=', 'user_beneficiaries.national_id_proof' )
        ->get( [
            'user_beneficiaries.*',
            'national_id_proofs.type as national_id_proof_type'
        ] );
        return $select;
    }
}
