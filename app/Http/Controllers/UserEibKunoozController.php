<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User_eib_kunooz;
use Carbon\Carbon;

class UserEibKunoozController extends Controller
 {

    public function getUserEibKunooz( $bmi_id ) {
        return $select = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )->sum( 'amount' );
    }

    public function getUserEibKunooz_v( $bmi_id, $fromDate, $toDate ) {
        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) != Carbon::now()->format( 'd-m-Y' ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $select = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )->get( [ 'date_of_payment', 'amount' ] );
        $t = 0;
        foreach ( $select as $key=>$value ) {
            $d = Carbon::parse( $value->date_of_payment );
            if ( $temp == 0 ) {
                // if ( $d <= $toDate ) {
                $t = $t+$value->amount;
                // }
            } else {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $t = $t+$value->amount;
                }
            }
        }
        return $t;
    }

    public function getUserEibKunooz_m( $bmi_id, $year, $month ) {
        $select = User_eib_kunooz::where( 'bmi_id', '=', $bmi_id )->get( [ 'date_of_payment', 'amount' ] );
        $t = 0;
        foreach ( $select as $key=>$value ) {
            $y = Carbon::parse( $value->date_of_payment )->year;
            $m = Carbon::parse( $value->date_of_payment )->month;
            if ( $y < $year ) {
                $t = $t+$value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $t = $t+$value->amount;
            }
        }
        return $t;
    }
}
