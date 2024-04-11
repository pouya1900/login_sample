<?php

namespace App\Http\Controllers\Web\Home;

use App\Events\SendPushNotificationEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class HomeController extends Controller
{

    public function index()
    {
        $user = $this->request->current_user;
        return view("front.home.index", compact('user'));
    }

}
