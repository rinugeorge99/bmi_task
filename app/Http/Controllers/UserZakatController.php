<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User_zakat;
use Carbon\Carbon;

class UserZakatController extends Controller
 {
    public function getUserZakat( $bmi_id ) {
        return $getUserZakat = User_zakat::where( 'bmi_id', '=', $bmi_id )->sum( 'amount' );

    }

    public function getUserZakat_m( $bmi_id, $year, $month ) {
        $getUserZakat = User_zakat::where( 'user_zakats.bmi_id', '=', $bmi_id )
        ->join( 'zakats', 'zakats.id', '=', 'user_zakats.zakat_id' )
        ->get( [ 'user_zakats.amount', 'zakats.date' ] );

        $t = 0;
        if ( count( $getUserZakat ) == 0 ) {
            return $t;
        }
        foreach ( $getUserZakat as $key=>$value ) {
            $y = Carbon::parse( $value->date )->year;
            $m = Carbon::parse( $value->date )->month;
            if ( $y<$year ) {
                $t = $t +$value->amount;
            }
            if ( $y == $year && $m <= $month ) {
                $t = $t + $value->amount;
            }
        }
        return $t;
    }

    public function getUserZakat_v( $bmi_id, $fromDate, $toDate ) {
        if ( Carbon::parse( $fromDate )->format( 'd-m-Y' ) != Carbon::now()->format( 'd-m-Y' ) ) {
            $temp = 1;
        } else {
            $temp = 0;
        }
        $getUserZakat = User_zakat::where( 'user_zakats.bmi_id', '=', $bmi_id )
        ->join( 'zakats', 'zakats.id', '=', 'user_zakats.zakat_id' )
        ->get( [ 'user_zakats.amount', 'zakats.date' ] );

        $t = 0;
        if ( count( $getUserZakat ) == 0 ) {
            return $t;
        }
        foreach ( $getUserZakat as $key=>$value ) {
            $d = Carbon::parse( $value->date );
            if ( $temp == 1 ) {
                if ( $fromDate <= $d && $d <= $toDate ) {
                    $t = $t + $value->amount;
                }
            } else {
                // if ( $d <= $toDate ) {
                $t = $t +$value->amount;
                // }
            }

        }
        return $t;
    }

    public function listUserZakat1( $id ) {

        $select = User_zakat::where( 'bmi_id', '=', $id )
        ->join( 'zakats', 'zakats.id', '=', 'user_zakats.zakat_id' )
        ->get( [
            'zakats.from_date',
            'zakats.to_date',
            'user_zakats.profit as individual_profit',
            'user_zakats.amount as zakat'
        ] );
        return $select;
    }

    public function listUserZakat( $id ) {
        $i = $this->listUserZakat1( $id );
        $a = User_zakat::where( 'bmi_id', '=', $id )
        ->join( 'zakats', 'zakats.id', '=', 'user_zakats.zakat_id' )->sum( 'user_zakats.profit' );
        $b = User_zakat::where( 'bmi_id', '=', $id )
        ->join( 'zakats', 'zakats.id', '=', 'user_zakats.zakat_id' )->sum( 'user_zakats.amount' );

        $select = User_zakat::where( 'bmi_id', '=', $id )->get();
        $q = ( [ 'total_profit'=>$a, 'total_zakat'=>$b, 'zakat_details'=>$i ] );
        return $q;

    }
}