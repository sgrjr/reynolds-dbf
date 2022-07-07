<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use App\Models\User;
use App\Events\UserLoggedIn;

use stdclass;
use App\Exceptions\CustomException;

use \App\Events\GraphQLAuth\GraphQLLoginAttempted;
use \App\Events\GraphQLAuth\GraphQLUserAuthenticated;
use \App\Events\GraphQLAuth\GraphQLUserLoggedOut;
use \App\Events\GraphQLAuth\GraphQLUserAuthenticationFailed;

//This is only used in IndexController 
//The user model extends a different class for auth

trait AuthenticatesUsersTrait
{
    use RedirectsUsers, ThrottlesLogins;

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function login(Request $request)
    {
        $setup_tests = new \App\Helpers\SetupTests();

        if(!$setup_tests->test('OAUTH_CLIENT_EXISTS')->passed){
            
            GraphQLUserAuthenticationFailed::dispatch($request->all(), "Application is not setup!");

                throw new CustomException(
                    'Cannot authenticate any user.', //message
                    'Application is not setup.', //reason
                    'error'//severity
                );

        }
        $all = $request->all();

        if( isset($all['variables']) && isset($all['query']) ){
            $request->request->add([ 'email' => $all['variables']['email'] ]);
            $request->request->add(['password' => $all['variables']['password']]);
            $request->request->remove('variables');
            $request->request->remove('query');
        }

       $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }
        if ($user = $this->attemptLogin($request)) {
            return $this->sendLoginResponse($request, $user);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);

    }

    public function adminLogin(Request $request)
    {
        $args = $request->all();

        if($request->user() && $request->user()->can('LIST_ALL_USERS')){
            $user = User::where('id',base64_decode($args['id']))->first();
            return $user;
        } else {
            return $request->user();
        }
    }

    public function ajaxLogin(Request $request)
    {
        if ($this->attemptAjaxLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);

    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
           'email' => 'required|email|string',
           'password' => 'required|string|min:6'
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $valid_user = false;
        $credentials = $request->all();

        GraphQLLoginAttempted::dispatch($this->guard(), $credentials, $request->filled('remember'));

        //Retrieve from Mysql Database
        $users = \App\Models\User::where("EMAIL", $credentials['email'])->get();      
        
        foreach($users AS $record){ 
            if(\Hash::check($credentials['password'], $record->UPASS)){
                $valid_user = true;
                $user = $record;        
			}
		}

        if($valid_user === true ){

            if($request->wantsJson()){
                if(!isset($user)){
                    $user = \App\Models\User::where('EMAIL', $credentials['email'])->first();

                }
                GraphQLUserAuthenticated::dispatch($user);
                return $user;
            }

            $this->guard()->attempt($credentials, $request->filled('remember'));
            return $this->guard()->user();
           
        }
        return false;
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendLoginResponse(Request $request, $user)
    {

        $this->clearLoginAttempts($request);

        if($request->wantsJson()){
            $this->authenticated($request, $user);

           return $user;
        }
        
        $request->session()->regenerate();
        
        return $this->authenticated($request, $user)
                ?: redirect()->intended($this->redirectPath());
        }
        

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        GraphQLUserAuthenticationFailed::dispatch($request->all(), "Auth Failed");

        if($request->has("token")){
            return response()->json(["error"=>"Auth Failed"]);
        }else{
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.failed')],
            ]);
        }

    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'EMAIL';
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {

        if($request->wantsJson()){
           //$this->loggedOut($request);
            GraphQLUserLoggedOut::dispatch($request->user());
            return \App\Helpers\Application::props();
        }

        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->loggedOut($request) ?: redirect('/');
    }

        /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
   protected function authenticated(Request $request, $user)
    {
        event(new UserLoggedIn($user));

        return true;
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        event(new \Illuminate\Auth\Events\Logout($request));
        return true;
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        if(request()->wantsJson()){
            return Auth::guard();
		}else{
            return Auth::guard();
		}
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */

    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'EMAIL' => $data['email'],
            'password' => $data['password']
        ]);
    }

}
