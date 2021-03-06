<?php namespace jlourenco\base\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Sentinel;
use View;
use Validator;
use Input;
use Session;
use Redirect;
use Lang;
use URL;
use Activation;
use Base;
use \Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use \Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use Reminder;
use Mail;
use Illuminate\Support\MessageBag;

class AuthController extends controller
{
    use \jlourenco\support\Traits\CaptchaTrait;

    /**
     * Message bag.
     *
     * @var Illuminate\Support\MessageBag
     */
    protected $messageBag = null;

    /**
     * Initializer.
     *
     * @return void
     */
    public function __construct()
    {
        $this->messageBag = new MessageBag;
    }

    /**
     * Account sign in.
     *
     * @return View
     */
    public function getSignin()
    {
        // Is the user logged in?
        if (Sentinel::check())
        {
            if (Sentinel::inRole('admin'))
                return Redirect::route('home');
            else
                return Redirect::route('user.home');
        }

        // Show the page
        return View('auth.login');
    }

    /**
     * Account sign up.
     *
     * @return View
     */
    public function getSignup()
    {
        // Is the user logged in?
        if (Sentinel::check())
        {
            if (Sentinel::inRole('admin'))
                return Redirect::route('home');
            else
                return Redirect::route('user.home');
        }

        // Show the page
        return View('auth.register');
    }

    /**
     * Account sign up.
     *
     * @return View
     */
    public function getLostPassword()
    {
        // Is the user logged in?
        if (Sentinel::check())
        {
            if (Sentinel::inRole('admin'))
                return Redirect::route('home');
            else
                return Redirect::route('user.home');
        }

        // Show the page
        return View('auth.forgot-password');
    }

    /**
     * Forgot Password Confirmation page.
     *
     * @param number $userId
     * @param  string $passwordResetCode
     * @return View
     */
    public function getForgotPasswordConfirm($userId, $passwordResetCode)
    {
        // Find the user using the password reset code
        if(!$user = Sentinel::findById($userId))
        {
            // Redirect to the forgot password page
            return Redirect::route('forgot-password')->with('error', Lang::get('base.auth.forgot_password_confirm.error'));
        }

        // Show the page
        return View('auth.forgot-password-confirm', compact('userId', 'passwordResetCode'));
    }

    /**
     * Logout page.
     *
     * @return Redirect
     */
    public function getLogout()
    {
        // Log the user out
        Sentinel::logout();

        // Redirect to the users page
        return Redirect::to('/')->with('success', Lang::get('base.auth.logged_out'));
    }

    /**
     * User account activation page.
     *
     * @param number $userId
     * @param string $activationCode
     * @return
     */
    public function getActivate($userId, $activationCode)
    {
        // Is user logged in?
        if (Sentinel::check())
        {
            if (Sentinel::inRole('admin'))
                return Redirect::route('home');
            else
                return Redirect::route('user.home');
        }

        // Find the user using the password reset code
        if(!$user = Sentinel::findById($userId))
        {
            // Redirect to the forgot password page
            return Redirect::route('login')->with('error', Lang::get('base.auth.activate.error'));
        }
        // $activation = Activation::exists($user);

        if (Activation::complete($user, $activationCode))
        {
            $user->status = 1;
            $user->save();
            
            // Activation was successful
            // Redirect to the login page
            return Redirect::route('login')->with('success', Lang::get('base.auth.activate.success'));
        }
        else
        {
            // Activation not found or not completed.
            $error = Lang::get('base.auth.activate.error');
            return Redirect::route('login')->with('error', $error);
        }

    }

    /**
     * Account sign in form processing.
     *
     * @return Redirect
     */
    public function postSignin()
    {
        $isEmail = preg_match('/@/', Input::get('email'));

        // Declare the rules for the form validation
        $rules = array(
            'email' => 'required|email',
            'password' => 'required|between:3,32',
        );

        if (!$isEmail)
            $rules['email'] = 'required';

        // Create a new validator instance from our validation rules
        $validator = Validator::make(Input::all(), $rules);

        // If validation fails, we'll exit the operation now.
        if ($validator->fails()) {
            // Ooops.. something went wrong
            return back()->withInput()->withErrors($validator);
        }
        try {
            foreach(Sentinel::createModel()->getLoginNames() as $loginName)
            {
                $data = array(
                    $loginName => Input::only('email')["email"],
                    "password" => Input::only('password')["password"]
                );

                // Try to log the user in
                if(Sentinel::authenticate($data, Input::get('remember-me', false)))
                {
                    $user = Sentinel::check();
                    $user->update(['ip' => Request::ip()]);
                    Base::Log($user->username . ' (' . $user->first_name . ' ' . $user->last_name . ') logged in with IP ' . Request::ip() );
                    
                    // Redirect to the dashboard page
                    return Redirect::route("home")->with('success', Lang::get('base.auth.signin.success'));
                }
            }

            $this->messageBag->add('email', Lang::get('base.auth.account.not_found'));
        } catch (NotActivatedException $e) {
            $this->messageBag->add('email', Lang::get('base.auth.account.not_activated'));
        } catch (ThrottlingException $e) {
            $delay = $e->getDelay();
            $this->messageBag->add('email', Lang::get('base.auth.account.suspended', compact('delay' )));
        }
        Base::Log('Login attempt registred for ' . Input::only('email')["email"] . ' from IP ' . Request::ip() );

        // Ooops.. something went wrong
        return back()->withInput()->withErrors($this->messageBag);
    }

    /**
     * Account sign up form processing.
     *
     * @return Redirect
     */
    public function postSignup()
    {
        /************TEMP VARIABLE************/
        /*
         * 0 - Disabled
         * 1 - Enabled and no activation
         * 2 - User activation
         * 3 - Admin activation
         */

        $signupStatus = \Base::getSetting('USER_REGISTRATION');
        /************TEMP VARIABLE************/

        $signupEnabled = $signupStatus != 0;
        $userActivation = $signupStatus == 2;
        $adminActivation = $signupStatus == 3;

        if (!$signupEnabled)
            return Redirect::to(URL::previous())->withInput()->with('error', Lang::get('base.auth.account.registration_disabled'));

        $rules = array();

        // Declare the rules for the form validation
        foreach(Sentinel::createModel()->getRegisterFields() as $fieldid => $field)
            $rules[$fieldid] = $field['validator'];

        $rules['g-recaptcha-response'] = 'required';

        // Create a new validator instance from our validation rules
        $validator = Validator::make(Input::all(), $rules);

        $err = false;

        // If validation fails, we'll exit the operation now.
        if ($validator->fails() || ($err = $this->captchaCheck()) == false) {
            if ($err)
                return Redirect::to(URL::previous())->withInput()->withErrors(['g-recaptcha-response' => Lang::get('base.captcha.error')]);

            // Ooops.. something went wrong
            return Redirect::to(URL::previous())->withInput()->withErrors($validator);
        }

        try {
            $data = array();

            // Set the data to the user from the User class
            foreach(Sentinel::createModel()->getRegisterFields() as $fieldid => $field)
                $data[$fieldid] = Input::get($fieldid);

            // Set the standard data to the user
            $data['ip'] = Request::getClientIP();
            $data['status'] = 0;
            $data['staff'] = 0;
            if ($data['birthday'] != null)
                $data['birthday'] = \Carbon\Carbon::createFromFormat('d/m/Y', $data['birthday']);

            // Find the user if it exists and needs to be created
            $user = Sentinel::getUserRepository()->findByCredentials(['email' => Input::get('email')]);

            if ($user != null)
            {
                // Update the temporary user to the new one
                if (Sentinel::validForUpdate($data, ['email' => Input::get('email')])) {
                    $testing = Sentinel::createModel()->getRegisterFields();
                    $user = Sentinel::findById($user->id);

                    foreach($data as $fieldid => $field)
                        if (!isset($testing[$fieldid]) || $testing[$fieldid]['save'] == true)
                            $user[$fieldid] = $field;

                    $user['password'] = bcrypt($user['password']);
                    Sentinel::update($user, ['email' => Input::get('email')]);
                }
                else
                    return Redirect::to(URL::previous())->withInput()->with('error', Lang::get('base.auth.account.registration_failed'));
            }
            // Register the user
            else
                $user = Sentinel::register($data, false);

            // If the user needs to activate the account send him an email
            if ($userActivation)
            {
                $activation = Activation::create($user);

                // Data to be used on the email view
                $data = array(
                    'user'          => $user,
                    'activationUrl' => URL::route('activate', [$user->id, $activation->code]),
                );

                // Send the activation code through email
                Mail::queue('emails.auth.register-activate', $data, function ($m) use ($user) {
                    $m->to($user->email, $user->first_name . ' ' . $user->last_name);
                    $m->subject(Lang::get('base.mails.welcome') . ' ' . $user->first_name);
                });
            }

            // If the administrator needs to activate the account send the user a warning saying that
            if ($adminActivation)
            {
                Mail::queue('emails.auth.register-admin-activate', ['user' => $user], function ($m) use ($user) {
                    $m->to($user->email, $user->first_name . ' ' . $user->last_name);
                    $m->subject(Lang::get('base.mails.welcome') . ' ' . $user->first_name);
                });
            }

            // Log the user in
            if (!$adminActivation && !$userActivation)
            {
                $activation = Activation::create($user);

                if (Activation::complete($user, $activation->code)) {
                    Sentinel::login($user, false);

                    Mail::queue('emails.auth.activated', ['user' => $user], function ($m) use ($user) {
                        $m->to($user->email, $user->first_name . ' ' . $user->last_name);
                        $m->subject(Lang::get('base.mails.welcome') . ' ' . $user->first_name);
                    });
                }
            }

            Base::Log('New account registred for ' . $user->username . ' (' . $user->first_name . ' ' . $user->last_name . ') from IP ' . Request::ip() );

            // Redirect to the home page with success menu
            return Redirect::to("login")->with('success', Lang::get('base.auth.signup.success') . $adminActivation ? Lang::get('base.auth.signup.admin') : $userActivation ? Lang::get('base.auth.signup.self') : Lang::get('base.auth.signup.ready'));
        } catch (UserExistsException $e) {
            $this->messageBag->add('email', Lang::get('base.auth.account.already_exists'));
        }

        Base::Log('New account registration attempt from IP ' . Request::ip() );

        // Ooops.. something went wrong
        return Redirect::back()->withInput()->withErrors($this->messageBag);
    }

    /**
     * Forgot password form processing page.
     *
     * @return Redirect
     */
    public function postForgotPassword()
    {
        // Declare the rules for the validator
        $rules = array(
            'email' => 'required|email',
        );

        // Create a new validator instance from our dynamic rules
        $validator = Validator::make(Input::all(), $rules);

        // If validation fails, we'll exit the operation now.
        if ($validator->fails()) {
            // Ooops.. something went wrong
            return Redirect::to(URL::previous())->withInput()->withErrors($validator);
        }

        // Get the user password recovery code
        $user = Sentinel::findByCredentials(['email' => Input::get('email')]);

        if($user)
        {
            //get reminder for user
            $reminder = Reminder::exists($user) ?: Reminder::create($user);

            // Data to be used on the email view
            $data = array(
                'user'              => $user,
                'forgotPasswordUrl' => URL::route('forgot-password-confirm',[$user->id, $reminder->code]),
            );

            Base::Log('Forgot password request for ' . $user->username . ' (' . $user->first_name . ' ' . $user->last_name . ') from IP ' . Request::ip() );

            // Send the activation code through email
            Mail::queue('emails.auth.forgot-password', $data, function ($m) use ($user) {
                $m->to($user->email, $user->first_name . ' ' . $user->last_name);
                $m->subject(Lang::get('base.mails.recovery'));
            });
        }

        //  Redirect to the forgot password
        return Redirect::to(URL::previous())->with('success', Lang::get('base.auth.forgot_password.success'));
    }

    /**
     * Forgot Password Confirmation form processing page.
     *
     * @param number $userId
     * @param  string   $passwordResetCode
     * @return Redirect
     */
    public function postForgotPasswordConfirm($userId, $passwordResetCode)
    {
        // Declare the rules for the form validation
        $rules = array(
            'password'         => 'required|between:3,32',
            'password_confirm' => 'required|same:password'
        );

        // Create a new validator instance from our dynamic rules
        $validator = Validator::make(Input::all(), $rules);

        // If validation fails, we'll exit the operation now.
        if ($validator->fails()) {
            // Ooops.. something went wrong
            return Redirect::route('forgot-password-confirm', compact(['userId','passwordResetCode']))->withInput()->withErrors($validator);
        }

        // Find the user using the password reset code
        $user = Sentinel::findById($userId);
        if(!$reminder = Reminder::complete($user, $passwordResetCode, Input::get('password')))
        {
            Base::Log('Forgot password confirm failed for ' . $user->username . ' (' . $user->first_name . ' ' . $user->last_name . ') from IP ' . Request::ip() );

            // Ooops.. something went wrong
            return Redirect::route('login')->with('error', Lang::get('base.auth.forgot_password_confirm.error'));
        } else {
            // Send the activation code through email
            Mail::queue('emails.auth.password-changed', [ 'user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->first_name . ' ' . $user->last_name);
                $m->subject(Lang::get('base.mails.password_changed'));
            });
        }

        Base::Log('Forgot password confirmed for ' . $user->username . ' (' . $user->first_name . ' ' . $user->last_name . ') from IP ' . Request::ip() );

        // Password successfully reseted
        return Redirect::route('login')->with('success', Lang::get('base.auth.forgot_password_confirm.success'));
    }

}
