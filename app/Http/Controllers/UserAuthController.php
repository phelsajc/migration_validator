<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

use App\UsersModel;

use Illuminate\Support\Facades\DB;
use Exception;

use Carbon\Carbon;

class UserAuthController extends Controller
{ 
    private $error;  

    public function logout(Request $request) {
        $request->session()->flush();
        return redirect('/')->with('success','You have successfully logged-out');
    }
        
    public function log_me_in(Request $request)
    {
        ### ADD USER ACCESS IN  192.168.70.90|md_contribution

        $data = $request->session()->all(); //get all sessions 
        //dd($data);

        $username = $request->input('username');
        $password = $request->input('password');

        $validate = request()->validate([
            'username' => 'required',
            'password' => 'required'
        ]);
        
        
        $allow_login = false;

        //$users = DB::connection('mongodb')->collection('users')->get();
        if ($username == "ftfuentes") {
            $username = "006240";
        }


        $users =     UsersModel::where('loginid', $username)->first();   
        //dd($users);
        if (empty($users)) {
            return redirect('/')->withInput()->with('error','User Not Found in Arcus Air');
        }
        else {
            if (!Hash::check($password, $users->password)) {
                // The password matches...
                return back()->withInput()->with('error','Invalid Password');
            }
            else {

                //dd($users);
                // Convert the UTCDateTime to a Carbon instance
                $user_activeto = null;
                if (empty($users->activeto)) {
                    if ($users->islocked) {
                        //echo "locked account";
                        return redirect('/')->withInput()->with('error','Locked Account in Arcus Air');    
                    }
                    else {
                        //echo "not locked account";
                        // allow login
                        $allow_login = true;
                    }
                    
                }
                else {    
                    $carbonDate = Carbon::instance($users->activeto->toDateTime());
                    $user_activeto = $carbonDate->format('Y-m-d'); 
                            
                    if ($user_activeto <= date('Y-m-d')) {
                        //echo "expired account";
                        return redirect('/')->withInput()->with('error','Expired Account in Arcus Air');                    
                    }
                    else {
                        //echo "not expired account";
                        // allow login
                        $allow_login = true;
                    }
                }

                if ($allow_login) {
                    //dd(true);exit;
                    //dd($employee);
                    # store sessions and Redirect to Main Page
                    //$request->session()->put('user_id', $users->_id);
                    //session(['user_id' => $users->_id]);
                    session()->put('user_id', $users->_id); // or a hardcoded value for testing
                    $request->session()->put('username', $users->loginid);
                    $request->session()->put('lastname', utf8_encode($users->lastname));
                    $request->session()->put('firstname', utf8_encode($users->name));
                    $request->session()->put('middlename', utf8_encode($users->middlename));
                    return redirect('/migration-validation'); 
                }
                else {
                    //dd(false);exit;
                    return redirect('/')->withInput()->with('error','No Access Provide in Arcus Air');    
                }
            }
        }
    }    

    public function index() {
        return view('auth.login');
    }    
}