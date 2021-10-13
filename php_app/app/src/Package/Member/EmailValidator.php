<?php


namespace App\Package\Member;


interface EmailValidator
{
    /**
     * @param string $email
     * @return bool
     */
    public function validateEmail(string $email);
}