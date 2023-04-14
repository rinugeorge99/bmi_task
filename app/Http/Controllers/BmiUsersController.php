<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Bmi_users;
use App\Models\Bmi_id;

use Illuminate\Support\Facades\File;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BmiUsersController extends Controller
 {
    public function insert( Request $request )
 {
        // return $request;
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'full_name' => 'required',
            'dob' => 'required',
            'contact' => 'required',
            'contact2' => 'required',
            'email' => 'required',
            'country_of_residence' => 'required',
            'national_id_proof' => 'required',
            'proof_details' => 'required',
            'passport_no' => 'required',
            'passport_expiry' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Invalid credentials' ];
        }
        $select = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )
        ->get()
        ->first();
        if ( $select ) {
            return [
                'code' => 401,
                'message' => 'Details of this particular user already exist',
            ];
        }

        // $proof_details[ 'id' ] = 1;
        // $proof_details[ 'proof_no' ] = 43756734;
        // $pr = array();
        // array_push( $pr, $proof_details );
        // return json_encode( $pr );
        $proof_details_arr = [];
        // return $request;
        $proof_image_array = $this->imageUpload( $request );
        if ( $request->proof_details ) {
            // return json_decode( $request->proof_details );
            foreach ( json_decode( $request->proof_details ) as $key => $value ) {
                foreach ( $proof_image_array as $key1=>$value1 ) {
                    if ( $key == $key1 ) {
                        $value->img = $value1;
                    }
                }
                // foreach ( json_decode( $request->proof_images )  as $key1 => $value1 ) {
                // if ( $request->proof_images ) {
                //     foreach ( json_decode( $request->proof_images )   as $key1 => $value1 ) {

                //         if ( $key == $key1 ) {
                //             // return 1;
                //             return $img = $this->imageUpload( $value1 );

                //             $value->image = $img;
                //         } else {
                //             // return 0;
                //         }
                //     }
                // }

                array_push( $proof_details_arr, $value );
            }
        }

        // return $proof_details_arr;
        $passport_img = '';
        if ( $request->passport_upload ) {
            if ( $request->hasFile( 'passport_upload' ) ) {
                $passport_img = $this->imageUploadForPassport(
                    $request->passport_upload
                );
            }
        }
        // $user_image = '';
        // if ( $request->image ) {
        //     if ( $request->hasFile( 'image' ) ) {
        //         $user_image = $this->imageUploadForusers(
        //             $request->image
        // );
        //     }
        // }
        $insert = Bmi_users::create( [
            'bmi_id' => $request->bmi_id,
            'full_name' => $request->full_name,
            'dob' => $request->dob,
            'contact' => $request->contact,
            'contact2' => $request->contact2,
            'email' => $request->email,
            'country_of_residence' => $request->country_of_residence,
            'national_id_proof' => $request->national_id_proof,
            'proof_details' => json_encode( $proof_details_arr ),
            'passport_no' => $request->passport_no,
            'passport_expiry' => $request->passport_expiry,
            'passport_upload' => $passport_img,
            // 'image'=>$user_image,
        ] );
        if ( $insert ) {
            $update_formstatus = Bmi_id::where( 'id', '=', $request->bmi_id )->where( 'form_status', '=', 0 )
            ->update( [
                'form_status'=>1
            ] );
            if ( $update_formstatus ) {
                return [
                    'code' => 200,
                    'message' => 'Inserted successfully',
                    'user_details' => $insert,
                ];
            } else {
                return [ 'code' => 402, 'message' => 'Something went wrong' ];

            }

        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function update_bmiusers( Request $request ) {
        $existingimages = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )
        ->get()
        ->first();
        if ( $existingimages->image == null ) {
            $doc = $this->imageUploadForusers( $request->image );
            $update = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )->update( [
                'image' => $doc,
            ] );
            if ( $update ) {
                return [ 'code' => 200, 'message' => 'updated successfully' ];
            } else {
                return [ 'code' => 401, 'message' => 'Something went wrong' ];
            }
        }
        if ( $existingimages->image !== null ) {

            if ( $request->hasfile( 'image' ) ) {
                $images = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )
                ->get()
                ->first();
                unlink( $images->image );
            }
            $doc = $this->imageUploadForusers( $request->image );
            $update = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )->update( [
                'image' => $doc,
            ] );
            if ( $update ) {
                return [ 'code' => 200, 'message' => 'updated successfully' ];
            } else {
                return [ 'code' => 401, 'message' => 'Something went wrong' ];
            }
        }
    }

    public function deleteProofDetails( Request $request )
 {
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'id' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Invalid credentials' ];
        }
        $getDetails = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )
        ->get()
        ->first();
        if ( $getDetails ) {
            if ( $getDetails->proof_details ) {
                $proof_details = json_decode( $getDetails->proof_details );
                foreach ( $proof_details as $key => $value ) {
                    if ( $value->id == $request->id ) {
                        array_splice( $proof_details, $key, 1 );
                    }
                }
            }
            $update = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )->update(
                [
                    'proof_details' => json_encode( $proof_details ),
                ]
            );
            if ( $update ) {
                return [ 'code' => 200, 'message' => 'updated' ];
            } else {
                return [ 'code' => 401, 'message' => 'Something went wrong' ];
            }
        }
    }

    public function addProofDetails( Request $request )
 {
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
            'proof_no' => 'required',
            'id' => 'required',
            'proof_image' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Invalid credentials' ];
        }
        $getDetails = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )
        ->get()
        ->first();
        if ( $getDetails ) {
            if ( $getDetails->proof_details ) {
                $proof_details = json_decode( $getDetails->proof_details );
                foreach ( $proof_details as $key => $value ) {
                    if ( $value->id == $request->id ) {
                        return [
                            'code' => 401,
                            'message' => 'Already uploaded this proof id',
                        ];
                    }
                }
                $img = $this->imageUpload( $request->proof_image );
                $p[ 'id' ] = $request->id;
                $p[ 'proof_no' ] = $request->proof_no;
                $p[ 'expiry_date' ] = $request->expiry_date;
                $p[ 'image' ] = $img;
                array_push( $proof_details, $p );
            }
            $update = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )->update(
                [
                    'proof_details' => json_encode( $proof_details ),
                ]
            );
            if ( $update ) {
                return [ 'code' => 200, 'message' => 'updated' ];
            } else {
                return [ 'code' => 401, 'message' => 'Something went wrong' ];
            }
        }
    }

    public function updateProofDetails( Request $request )
 {
        $validator = Validator::make( $request->all(), [
            'bmi_id' => 'required',
        ] );
        if ( $validator->fails() ) {
            return [ 'code' => 401, 'message' => 'Invalid credentials' ];
        }
        $i = 0;
        if ( $request->hasFile( 'proof_image' ) ) {
            $img = $this->imageUpload( $request->proof_image );

            $i = 1;
        }
        $getDetails = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )
        ->get()
        ->first();
        if ( $getDetails ) {
            if ( $getDetails->proof_details ) {
                $proof_details = json_decode( $getDetails->proof_details );
                foreach ( $proof_details as $key => $value ) {
                    if ( $value->id == $request->id ) {
                        $value->proof_no = $request->proof_no;
                        $value->expiry_date = $request->expiry_date;
                        if ( $i == 1 ) {
                            unlink( $value->image );
                        }
                        $value->image = $i ? $img : $request->proof_image;
                    }
                }
            }
        }
        $update = Bmi_users::where( 'bmi_id', '=', $request->bmi_id )->update( [
            'proof_details' => json_encode( $proof_details ),
        ] );
        if ( $update ) {

            return [ 'code' => 200, 'message' => 'updated' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }
    }

    public function imageUpload( $request )
 {

        // return $imageName = time() .$image->extension() ;
        // $image->move( public_path( 'national_id_proof' ), $imageName );
        // return 'national_id_proof/' . $imageName;
        $data = array();
        if ( $request->hasFile( 'proof_images' ) )
 {

            //return [ 'data=>' ];
            foreach ( $request->file( 'proof_images' ) as $key=>$file )
 {
                // return [ 'data' ];
                $name = $file->getClientOriginalName();

                // $picture   = date( 'His' ).'-'.$name;

                $picture   = Str::uuid().'.'.$file->extension();

                $file->move( public_path().'/national_id_proof/', $picture );

                // $img = $file->store( 'image', 's3' );

                // $path = Storage::disk( 's3' )->url( $img );
                // return $path;
                //$data[ $key ] = $picture;

                //$path = 'http://192.168.0.154:8000/testimageup/'.$picture;
                array_push( $data, 'national_id_proof/' . $picture );
                // return $data;
            }
            // $out->writeln( json_encode( $data ) );

            return $data;
        } else {
            return $data;
        }
    }

    public function imageUploadForPassport( $image )
 {
        $imageName = time() . $image->getClientOriginalName();
        $image->move( public_path( 'passport' ), $imageName );
        return 'passport/' . $imageName;
    }

    public function imageUploadForusers( $image )
 {
        $imageName = time() . $image->getClientOriginalName();
        $image->move( public_path( 'user_image' ), $imageName );
        return 'user_image/' . $imageName;
    }

    public function expiry_notification( $bmi_id ) {
        $c = new NotificationController();
        $getDetails = Bmi_users::where( 'bmi_id', '=', $bmi_id )
        ->get()
        ->first();

        $proof_details = json_decode( $getDetails->proof_details );

        foreach ( $proof_details as $key => $value ) {

            if ( isset( $value->expiry_date ) ) {
                // return $value->expiry_date;
                // return Carbon::parse( trim( $value->expiry_date ) );
                $datetime1 = new DateTime( Carbon::parse( $value->expiry_date ) );
                $datetime2 = new DateTime( Carbon::now() );
                $interval = $datetime2->diff( $datetime1 );
                $days = $interval->format( '%a' );
                if ( $days <= 5 )
 {

                    $select = Bmi_users::where( 'bmi_id', '=', $bmi_id )->pluck( 'bmi_id' );
                    $date = Carbon::now()->format( 'Y-m-d' );

                    $content = 'Hello, the id proof will expire with in '.$days.'days Please update it as soon as possible !';

                    $selectnotification = DB::table( 'notifications' )

                    ->where( 'notifications.content', '=', $content )
                    ->where( 'notifications.date', '=', $date )->get()->first();
                    if ( !$selectnotification ) {

                        $k = $c->insertNotification( $date, $content, $select, 1 );

                    }

                }
            }
        }

    }
}
