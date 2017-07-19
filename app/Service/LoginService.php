<?php
namespace Ritz\App\Service;

class LoginService
{
    public function login($username, $password)
    {
        if (strlen($username) == 0) {
            return false;
        }

        if ($username !== $password) {
            return false;
        }

        return [
            'username' => $username,
        ];
    }
}
