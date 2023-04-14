<?php

namespace App\Http\Controllers;
use App\Models\Bmi_id;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Treasurer;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
 {

    public function insertNotification( $date, $content, $viewers, $status ) {

        $insert = Notification::create( [
            'date'=>$date,
            'content'=>$content,
            'viewers'=>$viewers,
            'status'=> $status

        ] );
        if ( $insert ) {
            return [ 'code' => 200, 'message' => 'inserted' ];
        } else {
            return [ 'code' => 401, 'message' => 'Something went wrong' ];
        }

    }

    public function getNotification( Request $request ) {
        $select = Notification::orderby( 'id', 'DESC' )->get();

        $arr = array();
        foreach ( $select as $key=>$value ) {
            $viewers = json_decode( $value->viewers );

            if ( in_array( $request->bmi_id, $viewers ) ) {

                $l[ 'id' ] = $value->id;
                $l[ 'status' ] = $value->status;
                $l[ 'date' ] = $value->date;
                $l[ 'content' ] = $value->content;
                $viewed = $value->viewed?json_decode( $value->viewed ):[];
                if ( in_array( $request->bmi_id, $viewed ) ) {
                    $l[ 'view_status' ] = 1;
                } else {
                    $l[ 'view_status' ] = 0;
                }
                array_push( $arr, $l );
            }
        }
        return $arr;
    }

    public function viewNotification( Request $request ) {
        $select = Notification::where( 'id', '=', $request->id )->get()->first();
        if ( !$select ) {
            return [ 'code'=>401, 'message'=>'Notification is not exist' ];
        }
        $viewed = $select->viewed?json_decode( $select->viewed ):[];
        if ( !in_array( $request->bmi_id, $viewed ) ) {
            array_push( $viewed, $request->bmi_id );
        }
        $update = Notification::where( 'id', '=', $request->id )->update( [
            'viewed'=>json_encode( $viewed )
        ] );
        if ( $update ) {
            return [ 'code'=>200, 'message'=>'Updated successfully' ];
        } else {
            return [ 'code'=>401, 'message'=>'Something went wrong' ];
        }
    }

}
