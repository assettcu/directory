<?php namespace app\Http\Controllers;

use Auth;
use App\User;
use App\Models\System\ADAuth;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;

defined("MAX_LOGIN_ATTEMPTS") or define("MAX_LOGIN_ATTEMPTS",5);

class AuthController extends Controller {

    public function authenticate($username, $password)
    {
    }

    public function index()
    {
        return view('login');
    }

    public function login()
    {
        $username = $_REQUEST["username"];
        $password = $_REQUEST["password"];

        # Check the user account first, see if it's locked out or inactive
        $user = User::find($username);
        if(!is_null($user) and $user->exists) {
            if ($user->active == 0) {
                return redirect()->action('AuthController@index')->with([
                    'flash_message' => array('danger' => 'User account is inactive. Please contact an administrator.')
                ]);
            } else if ($user->active == 1 and $user->attempts > MAX_LOGIN_ATTEMPTS) {
                $user->attempts++;
                $user->save();
                return redirect()->action('AuthController@index')->with([
                    'flash_message' => array('danger' => 'User account is locked out. Please contact an administrator.')
                ]);
            }
        }

        # Next see if the user account is authenticated with the Active Directory
        $adauth = new ADAuth("adcontroller");
        $authenticated = $adauth->authenticate($username, $password);

        # Check if user exists and
        if(!$authenticated and !is_null($user) and $user->exists) {
            $user->attempts++;
            $user->save();
            return redirect()->action('AuthController@index')->with([
                'flash_message' => array('danger' => 'Could not authenticate user with those credentials.')
            ]);
        }
        else if($authenticated and !is_null($user) and $user->exists) {
            $user = User::find($username);
            Auth::login($user);
            return Redirect::intended('/')->withInput();
        }
        else if($authenticated and (is_null($user) or !$user->exists)) {
            return redirect()->action('AuthController@index')->with([
                'flash_message' => array('info' => 'You are authenticated but not in the system. Please contact an administrator')
            ]);
        }
        else {
            return redirect()->action('AuthController@index')->with([
                'flash_message' => array('danger' => 'Could not authenticate user with those credentials.')
            ]);
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->action('AuthController@index')->with([
            'flash_message' => array('success' => 'Successfully logged out.')
        ]);
    }

}