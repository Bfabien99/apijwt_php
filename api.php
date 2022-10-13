<?php

class Api extends Rest
{
    public function __construct()
    {
        parent::__construct();
    }

    public function generateToken(){
        $email = $this->validateParameter('email', $this->param['email'], STRING);
        $pass = $this->validateParameter('pass', $this->param['pass'], STRING);
    }
    
}