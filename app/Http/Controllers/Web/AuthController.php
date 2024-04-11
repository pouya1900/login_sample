<?php

namespace App\Http\Controllers\Web;

use App\Events\SendOtpEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Models\Activation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{

    /**
     * login page
     *
     * @return Response|Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public function login(): \Illuminate\Foundation\Application|Redirector|Response|RedirectResponse|Application
    {

        if (auth::guard('user')->check()) {
            return redirect(route('home'));
        }


        Inertia::setRootView("layouts.pure");
        return Inertia::render('login', [
            'trs'          => trans('messages'),
            'base_url'     => route('home'),
            'csrf'         => csrf_token(),
            'send_otp_url' => route('send_otp'),
            'login_url'    => route('do_login'),
        ]);

    }


    /**
     * send otp to user's mobile number
     *
     * @param string mobile
     * @return JsonResponse
     */
    public function send_otp(): JsonResponse
    {
        $mobile = $this->request->input('mobile');

        $code = rand(100000, 999999);

        $activation = Activation::where('mobile', $mobile)->first();

        if ($activation) {

            if ($activation->attempt == 3 && $activation->attempt_at > Carbon::now()->subMinutes(30)) {
                return response()->json(['status' => 1, 'error' => trans('messages.otp_too_many_attempt')]);
            }

            if ($activation->created_at > Carbon::now()->subMinutes(2)) {
                return response()->json(['status' => 1, 'error' => trans('messages.otp_should_send_in_two_minute')]);
            }

            $activation->delete();
        }

        $activation = Activation::create([
            'mobile'     => $mobile,
            'code'       => $code,
            'expired_at' => Carbon::now()->addMinutes(2),
        ]);

        Event::dispatch(new SendOtpEvent($mobile, $code));

//        code should delete in last version

        return response()->json(['status' => 0, 'message' => trans('trs.otp_sent'), 'code' => $code]);

    }


    /**
     * do the user login
     *
     * @param string mobile
     * @param string code
     * @return JsonResponse
     */
    public function do_login(LoginRequest $request): JsonResponse

    {
        $mobile = $request->input('mobile');
        $code = $request->input('code');

        $activation = Activation::where('mobile', $mobile)->first();

        if (!$activation) {
            return response()->json(['status' => 1, 'error' => trans('trs.there_is_an_error')]);
        }

        if ($activation->attempt == 3) {
            return response()->json(['status' => 1, 'error' => trans('trs.otp_too_many_attempt')]);
        }

        if ($activation->expired_at < Carbon::now()) {
            return response()->json(['status' => 1, 'error' => trans('trs.otp_expired')]);
        }

        $activation->update([
            'attempt'    => $activation->attept + 1,
            'attempt_at' => Carbon::now(),
        ]);


        if ($activation->code != $code) {
            return response()->json(['status' => 1, 'error' => trans('trs.otp_not_correct')]);
        }

        if (!$user = User::where('mobile', $mobile)->first()) {
            $user = User::create([
                'mobile' => $mobile,
            ]);
        }

        auth::guard('user')->login($user, $request->has('remember'));

        return response()->json(['status' => 0, 'url' => route('home')]);

    }


    /**
     * logout
     *
     * @return \Illuminate\Foundation\Application|Redirector|Application|RedirectResponse
     */
    public function logout(): \Illuminate\Foundation\Application|Redirector|Application|RedirectResponse
    {
        auth::guard('user')->logout();

        return redirect(route('home'));
    }


}
