<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Http\Request;

class GoogleLoginController extends Controller
{
    protected GoogleAuthController $authController;

    public function __construct(GoogleAuthController $authController)
    {
        $this->authController = $authController;
    }

    public function redirect()
    {
        return $this->authController->redirect();
    }

    public function callback(Request $request)
    {
        return $this->authController->callback($request);
    }
}
