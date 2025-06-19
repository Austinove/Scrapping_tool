<?php

class AuthHandler
{
    private string $correct_password = 'mySecret123';
    public function isAuthorized(array $request_inputs): bool
    {
        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method Not Allowed
            return false;
        }
        
        return isset($request_inputs['pass']) && $request_inputs['pass'] === $this->correct_password;
    }
}