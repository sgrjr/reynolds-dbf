<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

use \App\Helpers\ErrorMessage; //$message="Success", $extensions=null, $debugMessage=null, $severity="info", $field=null
use \App\Helpers\ErrorMessageExtensions; //$category, $props=[]

trait GraphQLLoginTrait {

        public static function attemptGraphQLLogin($email = false, $password = false){

            $validation_errors = static::validateGraphQLLogin($email, $password);

            $user = static::
                where('EMAIL',$email)
                ->where('user_pass_unsafe', $password)
                ->first();
            if($user){
                return [
                    'user'=>$user,
                    'application'=> \App\Helpers\Application::props($user),
                    'token'=> $user->generateToken()->toArray(),
                    'errors' => [ErrorMessage::make('Login sucessful.', $validation_errors)]
                    ];
            }

            return [
                    'errors' =>  [ErrorMessage::make('Login Failed.', $validation_errors, "Please try again.", "error", "signin_form")]
                    ];
        }

        public static function attemptGraphQLLogout(){
            if(request()->user()) request()->user()->tokens()->delete();
            return \App\Helpers\Application::props();
        }


        public static function validateGraphQLLogin($email, $password){

            $error = new ErrorMessageExtensions("validation");

            //Email Validation
            if($email === false || $email === null){
                $error->add('EMAIL','No email entered.');
            }
            if(!str_contains($email, "@") || !str_contains($email, ".")){
                $error->add('EMAIL','Not a valid email address');
            }

            //Password Validation
            if($password === false || $password === null){
                $error->add('password','No password entered.');
            }
            if(strlen($password) < 4 ){
                $error->add('password','Not a valid password.');
            }

            return json_decode(json_encode($error));

        }

        public function getToken(){
            $token = $this->tokens->last();
            //if($token === null)
            return $this->generateToken()->plainTextToken;
            $response = $token->getKey().'|'.$token->token;
            return $response;
        }

        
}
