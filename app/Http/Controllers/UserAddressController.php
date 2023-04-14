<?php
namespace App\Http\Controllers;
use App\Models\User_address;
use App\Models\Bmi_id;

use Illuminate\support\Facades\Validator;
use Illuminate\Http\Request;

class UserAddressController extends Controller
 {
    public function insert( Request $request )
 {
        $validator = Validator::make( request()->all(), [
            'bmi_id' => 'required',
            'resi_city' => 'required',
            'street_name' => 'required',
            'resi_house_name' => 'required',
            'house_name' => 'required',
            'post' => 'required',
            'district' => 'required',
            'state' => 'required',
            'pincode' => 'required',
            'contact' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'invalid credentials ' ];
        }
        $select = User_address::where( 'bmi_id', '=', $request->bmi_id )
        ->get()
        ->first();
        if ( $select ) {
            return [ 'code' => 401, 'message' => 'This bmi id user address is already exist' ];
        }
        $insert = User_address::create( [
            'bmi_id' => $request->bmi_id,
            'resi_city' => $request->resi_city,
            'street_name' => $request->street_name,
            'resi_house_name' => $request->resi_house_name,
            'resi_landmark' => $request->resi_landmark,
            'po_box' => $request->po_box,
            'house_name' => $request->house_name,
            'post' => $request->post,
            'district' => $request->district,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'landmark' => $request->landmark,
            'contact' => $request->contact,
        ] );
        if ( $insert ) {
            $update_formstatus = Bmi_id::where( 'id', '=', $request->bmi_id )->where( 'form_status', '=', 1 )
            ->update( [
                'form_status'=>2
            ] );
            if ( $update_formstatus ) {
                return [
                    'code' => 200,
                    'message' => 'inserted successfully ',
                ];
            }
        } else {
            return [ 'code' => 401, 'message' => 'something went wrong' ];
        }
    }

    public function update_useraddress( Request $request )
 {
        $update = User_address::where( 'id', '=', $request->id )->update( [
            'bmi_id' => $request->bmi_id,
            'resi_city' => $request->resi_city,
            'street_name' => $request->street_name,
            'resi_house_name' => $request->resi_house_name,
            'resi_landmark' => $request->resi_landmark,
            'po_box' => $request->po_box,
            'house_name' => $request->house_name,
            'post' => $request->post,
            'district' => $request->district,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'landmark' => $request->landmark,
            'contact' => $request->contact,

        ] );
        if ( $update ) {
            return [ 'code'=>200, 'message'=>'updated' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
    }
}
