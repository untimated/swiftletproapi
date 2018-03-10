<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Token;
use App\Bridge;
use App\Device;
use App\Devicedatas;
use DB;
use Carbon\Carbon;

class api extends Controller
{
    // JSON response format using Google's JSON guide
    public $successResponseArray = ['data'=>''];
    public $errorResponseArray = ['error'=>['code'=>'','message'=>'']];

    public function __construct(){}

    /**
     * Retrieve the user for the given ID.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return User::findOrFail($id);
    }

    //Post Method

    /**
     * Register User With Username & Password
     *
     * @param  Request $req
     * @return Response JSON
     */
    public function register(Request $req){
        if(($req->input('username')!=null) && ($req->input('password')!=null)){
            $user = new User;
            $user->username = $req->input('username');
            $user->password = $req->input('password');

            if($user->save()){
                $data = ['username'=>$req->input('username'), 'message'=>'Successfully Registered'];
                $successResponseArray['data'] = $data;
                return response()->json($successResponseArray);
            }else{
                $errorResponseArray['error']['code'] = '500';
                $errorResponseArray['error']['message'] = 'Register Fail';
                return response()->json($errorResponseArray);
            }
        }else{
            $errorResponseArray['error']['code'] = '501';
            $errorResponseArray['error']['message'] = 'Please Fill Required Fields';
            return response()->json($errorResponseArray);
        }    
    }

    public function signin(Request $req){
         if(($req->input('username')!=null) && ($req->input('password')!=null)){
            $resp = app('db')
            ->select("SELECT * FROM `users` WHERE `username`='".$req->input('username')."' AND
            `password`='".$req->input('password')."' LIMIT 1");

            if($resp!=null){
                $tokenObj = new Token;
                $tval = bin2hex(openssl_random_pseudo_bytes(16));
                $tokenObj->token = $tval;
                $tokenObj->user_id = $resp[0]->id;
                $tokenObj->save();

                $resp[0]->token = $tval;
                $successResponseArray['data'] = $resp;
                
                return response()->json($successResponseArray);
            }else{
                $errorResponseArray['error']['code'] = '502';
                $errorResponseArray['error']['message'] = 'User Not Exist';
                return response()->json($errorResponseArray);
            }
         }else{
            $errorResponseArray['error']['code'] = '501';
            $errorResponseArray['error']['message'] = 'Please Fill Required Fields';
            return response()->json($errorResponseArray);
         }
    }

    public function signinUsingCache(Request $req){
        if(($req->input('username')!=null) && ($req->input('password')!=null) && ($req->input('token'))){
            $user = app('db')
            ->select("SELECT * FROM `users` WHERE `username`='".$req->input('username')."' AND
            `password`='".$req->input('password')."' LIMIT 1");
            if($user != null){
                $token = Token::where([
                    ['token','=',$req->input('token')],
                    ['User_id','=',$user[0]->id],
                    ])->first();
                if($token != null){
                    $successResponseArray['data'] = ["status" => "Ok"];
                    return response()->json($successResponseArray);
                }else{
                    $errorResponseArray['error']['code'] = '503';
                    $errorResponseArray['error']['message'] = 'Session Expired';
                    return response()->json($errorResponseArray);
                }
            }else{
                $errorResponseArray['error']['code'] = '502';
                $errorResponseArray['error']['message'] = 'User Not Exist';
                return response()->json($errorResponseArray);
            }
        }else{
            $errorResponseArray['error']['code'] = '501';
            $errorResponseArray['error']['message'] = 'Please Fill Required Fields';
            return response()->json($errorResponseArray);
        }
    }

    public function addBridge(Request $req){

        $token = $req->input("token");
        $name = $req->input("name");
        $serial = $req->input("serial");
        $ip = $req->input("ip");
        if($token==null){
            //Token Required
            $errorResponseArray['error']['code'] = "502";
            $errorResponseArray['error']['message'] = "Access Token Required";
            return response()->json($errorResponseArray);
        }else{
            if(($name!=null) && ($serial!=null) && ($ip!=null)){
                $tokendata = Token::where('token',$token)->first();
                if($tokendata!=null){
                    $user = $tokendata->user_id;
                    $bridge = new Bridge;
                    $bridge->name = $name;
                    $bridge->serial = $serial;
                    $bridge->ip = $ip;
                    $bridge->user_id = $user;
                    $bridge->save();

                    $data = ['name'=>$name,'serial'=>$serial,'ip'=>$ip,'ownerid'=>$user];
                    $successResponseArray['data'] = $data;
                    return response()->json($successResponseArray);
                }else{
                    $errorResponseArray['error']['code'] = "502";
                    $errorResponseArray['error']['message'] = "Not Authorized User";
                    return response()->json($errorResponseArray);
                }
            }else{
                $errorResponseArray['error']['code'] = '501';
                $errorResponseArray['error']['message'] = "Please Fill Required Fields";
                return response()->json($errorResponseArray);
            }
        }
    }

    public function addDevice(Request $req){
        $token = $req->input("token");
        $name = $req->input("name");
        $serial = $req->input("serial");
        $ip = $req->input("ip");
        $bridge_serial = $req->input("bridge_serial");
        if($token==null){
            //Token Required
            $errorResponseArray['error']['code'] = "502";
            $errorResponseArray['error']['message'] = "Access Token Required";
            return response()->json($errorResponseArray);
        }else{
            $tokendata = Token::where('token',$token)->first();
            $bridge = Bridge::where('serial',$bridge_serial)->first();
            if($tokendata!=null && $bridge!=null){
                if(($name!=null) && ($serial!=null) && ($ip!=null) && ($bridge_serial!=null)){
                    $user = $tokendata->user_id;
                    $results = app('db')->table('devices')->insert(
                        ['name'=>$name,'serial'=>$serial,'ip'=>$ip,'bridge_id'=>$bridge->id]
                    );
                    $data = ['name'=>$name,'serial'=>$serial,'ip'=>$ip,'bridgeid'=>$bridge->id,'bridgeserial'=>$bridge_serial,'ownerid'=>$user];
                    $successResponseArray['data'] = $data;
                    return response()->json($successResponseArray);
                }else{
                    $errorResponseArray['error']['code'] = "502";
                    $errorResponseArray['error']['message'] = "Not Authorized User";
                    return response()->json($errorResponseArray);
                }
            }else{
                $errorResponseArray['error']['code'] = '501';
                $errorResponseArray['error']['message'] = "Please Fill Required Fields or Bridge is Not Exist";
                return response()->json($errorResponseArray);
            }
        }
    }

    public function showAll(){
        $users = User::all();
        if($users != null){
            $successResponseArray['data']=$users;
            return response()->json($successResponseArray);
        }else{
            $errorResponseArray['error']['code'] = '500';
            $errorResponseArray['error']['message'] = 'Retrieve Fail';
        }
    }

    public function report(Request $req){
        if($req->has("serial")){
            $deviceSerial = $req->input('serial');
            $h = $req->input('data.h');
            $t = $req->input('data.t');//Not used for now
            $device = app('db')->table('devices')->where('serial',$deviceSerial)->first();
            if($device != null){
                $current = Carbon::now();
                $res = app('db')->table('devicedatas')->insert(['humidity'=>$h,'temperature'=>$t,
                    'device_id'=>$device->id,'created_at'=>$current->toDateTimeString()]);
                $successResponseArray['data']['status'] = "Ok";
                return response()->json($successResponseArray);
            }else{
                $errorResponseArray['error']['code'] = "501";
                $errorResponseArray['error']['message'] = "Device Is Not Exist";
                return response()->json($errorResponseArray);
            }
        }else{
            $errorResponseArray['error']['code'] = "500";
            $errorResponseArray['error']['message'] = "Please Fill All Required Fields";
            return response()->json($errorResponseArray);
        }
    }

    public function automate(Request $req){
        $serial = $req->input("serial");
        $token = $req->input("token");
        $switch = $req->input("switch");
        if($serial!=null && $token!=null && $switch!=null){
            $res = app('db')->table('bridges')->where('serial',$serial)->update(['automate'=>$switch]);
            if($res){
                $data = ['serial'=>$serial,'automate'=>$switch,'status'=>"ok"];
                $successResponseArray['data'] = $data;
                return response()->json($successResponseArray);
            }
            $errorResponseArray['error']['code'] = "501";
            $errorResponseArray['error']['message'] = "Update Fail";
            return response()->json($errorResponseArray);
        }
        $errorResponseArray['error']['code'] = "500";
        $errorResponseArray['error']['message'] = "Please Fill All Required Fields";
        return response()->json($errorResponseArray);
    }

    public function actuate(Request $req){
        $serial = $req->input("serial");
        $token = $req->input("token");
        $switch = $req->input("switch");
        if($serial!=null && $token!=null && $switch!=null){
            $res = app('db')->table('bridges')->where('serial',$serial)->update(['actuate'=>$switch]);
            if($res){
                $data = ['serial'=>$serial,'actuate'=>$switch,'status'=>"ok"];
                $successResponseArray['data'] = $data;
                return response()->json($successResponseArray);
            }
            $errorResponseArray['error']['code'] = "501";
            $errorResponseArray['error']['message'] = "Update Fail";
            return response()->json($errorResponseArray);
        }
        $errorResponseArray['error']['code'] = "500";
        $errorResponseArray['error']['message'] = "Please Fill All Required Fields";
        return response()->json($errorResponseArray);
    }


    //Getter Method

    public function status($serial){
        if($serial){
            $bridge = Bridge::where('serial',$serial)->first();
            if($bridge != null){
                $data = ['serial'=>$bridge->serial,'automate'=>$bridge->automate,'actuate'=>$bridge->actuate];
                $successResponseArray['data'] = $data;
                return response()->json($successResponseArray);
            }
            $errorResponseArray['error']['code'] = "501";
            $errorResponseArray['error']['message'] = "Bridge Not Exist";
            return response()->json($errorResponseArray);
        }
        $errorResponseArray['error']['code'] = "500";
        $errorResponseArray['error']['message'] = "Please Fill All Required Fields";
        return response()->json($errorResponseArray);
    }

    public function myBridges($token){
        if($token!=null){
            $tokendata = Token::where('token',$token)->first();
            if($tokendata == null){
                $errorResponseArray['error']['code'] = "501";
                $errorResponseArray['error']['message'] = "Invalid Token";
                return response()->json($errorResponseArray);
            }
            
            $userid = $tokendata->user_id;
            $user = User::find($userid);
            $bridges = $user->bridges;

            if($bridges !=null){
                $successResponseArray['data'] = $bridges;
                return response()->json($successResponseArray);
            }else{
                $errorResponseArray['error']['code'] = "502";
                $errorResponseArray['error']['message'] = "Fail Retrieve Data";
                return response()->json($errorResponseArray);
            }
        }else{
            $errorResponseArray['error']['code'] = "500";
            $errorResponseArray['error']['message'] = "Token Required ";
            return response()->json($errorResponseArray);
        }
    }

    public function bridgeDevices($token,$serial){
        if($token!=null){
            if($serial== null){
                $errorResponseArray['error']['code'] = "503";
                $errorResponseArray['error']['message'] = "Serial ID Required";
                return response()->json($errorResponseArray);
            }
            $tokendata = Token::where('token',$token)->first();
            if($tokendata == null){
                $errorResponseArray['error']['code'] = "504";
                $errorResponseArray['error']['message'] = "Invalid Token";
                return response()->json($errorResponseArray);
            }
            $bridge = Bridge::where("serial",$serial)->firstorFail();
            if($bridge){
                $userid = $tokendata->user_id;
                $user = User::find($userid);
                if($user->id != $bridge->user_id){
                    $errorResponseArray['error']['code'] = "506";
                    $errorResponseArray['error']['message'] = "The Bridge Is Not Belong To This User";
                    return response()->json($errorResponseArray);
                }else{
                    $devices = app('db')->select("SELECT * FROM `devices` WHERE `bridge_id` = ".$bridge->id."");
                    if($devices!=null){
                        $successResponseArray['data'] = $devices;
                        return response()->json($successResponseArray);
                    }else{
                        $errorResponseArray['error']['code'] = "507";
                        $errorResponseArray['error']['message'] = "No Data";
                        return response()->json($errorResponseArray);
                    }
                }
            }else{
                $errorResponseArray['error']['code'] = "505";
                $errorResponseArray['error']['message'] = "Bridge Is Not Exist";
                return response()->json($errorResponseArray);
            }
        }else{
            $errorResponseArray['error']['code'] = "500";
            $errorResponseArray['error']['message'] = "Token Required ";
            return response()->json($errorResponseArray);
        }
    }

    public function bridgeDeviceDetail($token,$bSerial,$dSerial){
        if($token!=null && $bSerial!=null && $dSerial!=null){
            $tokendata = Token::where('token',$token)->first();
            if($tokendata!=null){
                $userid = $tokendata->user_id;
                $bridge = app('db')->table('bridges')->where([['serial','=',$bSerial],['user_id','=',$userid]])->first();
                
                if($bridge->id!=null){
                    $device = app('db')->table('devices')->where([['serial','=',$dSerial],['bridge_id','=',$bridge->id]])->first();

                    if($device->id!=null){
                        $currentTo = Carbon::now();
                        $currentFrom = Carbon::now()->subMinutes(60);
                        
                        $devicedata = app('db')->table('devicedatas')->where('device_id',$device->id)->whereBetween('created_at',[$currentFrom,$currentTo])->get();
                        if($devicedata){
                            $successResponseArray['data'] = $devicedata;
                            return response()->json($successResponseArray);
                        }else{
                            $errorResponseArray['error']['code'] = "504";
                            $errorResponseArray['error']['message'] = "No Data or Invalid";
                            return response()->json($errorResponseArray);
                        }
                    }else{
                        $errorResponseArray['error']['code'] = "503";
                        $errorResponseArray['error']['message'] = "Device Invalid";
                        return response()->json($errorResponseArray);
                    }
                }else{
                    $errorResponseArray['error']['code'] = "502";
                    $errorResponseArray['error']['message'] = "Bridge Invalid";
                    return response()->json($errorResponseArray);
                }
            }else{
                $errorResponseArray['error']['code'] = "501";
                $errorResponseArray['error']['message'] = "Token Invalid ";
                return response()->json($errorResponseArray);
            }
        }else{
            $errorResponseArray['error']['code'] = "500";
            $errorResponseArray['error']['message'] = "Token Required ";
            return response()->json($errorResponseArray);
        }
    }



}