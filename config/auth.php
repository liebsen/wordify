<?php 

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Tuupola\Base62;
use App\User;
use App\Email;

$app->group('/api', function() {
    
    $this->group('/v2', function() {

        $this->group('/auth', function() {

            $this->get("/google/signup", function ($request, $response, $arguments) {

                if(!session_id()) {
                    session_start();
                }

                $client = new Google_Client();
                $client->setAuthConfig(__DIR__ . '/../config/client_id.json');
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                $client->setAccessToken($token);

                //      \Firebase\JWT\JWT::$leeway = 60;

                if ($client->getAccessToken()) {
                    $decoded = $client->verifyIdToken();

                    $body['google_id'] = $decoded['sub'];
                    $body['email'] = $decoded['email'];
                    $body['first_name'] = $decoded['given_name'];
                    $body['last_name'] = $decoded['family_name'];
                    $body['picture'] = $decoded['picture'];
                    $body['enabled'] = 1;

                    $user = $this->spot->mapper("App\User")->first([
                        "email" => $decoded['email']
                    ]);

                    if( ! $user ){
                        $password = strtolower(Base62::encode(random_bytes(16)));
                        $body['password'] = sha1($password.getenv('APP_HASH_SALT'));
                        $body['username'] = \set_username($body['first_name'].$body['last_name']);
                        $user = new User($body);
                        $emaildata = $body;
                        $emaildata['readable_password'] = $password;
                        $emaildata['email_encoded'] = Base62::encode($decoded['email']);

                        \send_email("Bienvenido " . $body['first_name'] . " a " . getenv('APP_TITLE'),$user,'welcome.html',$emaildata);
                    } else {

                        $existing_ids = $this->spot->mapper("App\User")->all([
                            "id <>" => $user->id,
                            "google_id" => $body['google_id']
                        ]);

                        if($existing_ids){
                            foreach($existing_ids as $existing_id){
                                $existing_body = $existing_id->data(['google_id' => NULL]);
                                $this->spot->mapper("App\User")->save($existing_body);
                            }
                        }
                    }

                    unset($body['readable_password']);
                    unset($body['email_encoded']);

                    // copy to local 
                    $body['picture'] = copy_profile_photo($body['picture']);
                    $user->data($body);
                    $this->spot->mapper("App\User")->save($user);

                    $fractal = new Manager();
                    $fractal->setSerializer(new DataArraySerializer);
                    $resource = new Item($user, new User);
                    $data = $fractal->createData($resource)->toArray();

                    echo \login_redirect($data['data']);
                    exit;
                }
            });

            $this->get("/facebook/signup", function ($request, $response, $arguments) {

                if(!session_id()) {
                    session_start();
                }

                $fb = new Facebook\Facebook([
                  'app_id' => getenv("FB_APP_ID"),
                  'app_secret' => getenv("FB_APP_SECRET"),
                  'default_graph_version' => 'v2.2',
                ]);

                $helper = $fb->getRedirectLoginHelper();
                $_SESSION['FBRLH_state'] = $_GET['state'];

                try {
                  $accessToken = $helper->getAccessToken();
                } catch(Facebook\Exceptions\FacebookResponseException $e) {
                  // When Graph returns an error
                  echo 'Graph returned an error: ' . $e->getMessage();
                  exit;
                } catch(Facebook\Exceptions\FacebookSDKException $e) {
                  // When validation fails or other local issues
                  echo 'Facebook SDK returned an error: ' . $e->getMessage();
                  exit;
                }

                if (! isset($accessToken)) {
                  if ($helper->getError()) {
                    header('HTTP/1.0 401 Unauthorized');
                    echo "Error: " . $helper->getError() . "\n";
                    echo "Error Code: " . $helper->getErrorCode() . "\n";
                    echo "Error Reason: " . $helper->getErrorReason() . "\n";
                    echo "Error Description: " . $helper->getErrorDescription() . "\n";
                  } else {
                    header('HTTP/1.0 400 Bad Request');
                    echo 'Bad request';
                  }
                  exit;
                }

                if ($accessToken !== null) {
                    $oResponse = $fb->get('/me?fields=id,first_name,last_name,email,picture.type(large)', $accessToken);
                    $decoded = $oResponse->getDecodedBody();

                    $body['facebook_id'] = $decoded['id'];
                    $body['email'] = $decoded['email'];
                    $body['first_name'] = $decoded['first_name'];
                    $body['last_name'] = $decoded['last_name'];
                    $body['picture'] = $decoded['picture']['data']['url'];
                    $body['enabled'] = 1;
                    
                    $user = $this->spot->mapper("App\User")->first([
                        "email" => $decoded['email']
                    ]);

                    if( ! $user ){
                        $password = strtolower(Base62::encode(random_bytes(16)));
                        $body['password'] = sha1($password.getenv('APP_HASH_SALT'));
                        $body['username'] = \set_username($body['first_name'].$body['last_name']);
                        $user = new User($body);
                        $emaildata = $body; 
                        $emaildata['readable_password'] = $password;
                        $emaildata['email_encoded'] = Base62::encode($decoded['email']);
                        \send_email("Bienvenido " . $body['first_name'] . " a " . getenv('APP_TITLE'),$user,'welcome.html',$emaildata);
                    } else {

                        $existing_ids = $this->spot->mapper("App\User")->all([
                            "id <>" => $user->id,
                            "facebook_id" => $body['facebook_id']
                        ]);

                        if($existing_ids){
                            foreach($existing_ids as $existing_id){
                                $existing_body = $existing_id->data(['facebook_id' => NULL]);
                                $this->spot->mapper("App\User")->save($existing_body);
                            }
                        }
                    }

                    unset($body['readable_password']);
                    unset($body['email_encoded']);

                    // copy to local 
                    $body['picture'] = copy_profile_photo($body['picture']);
                    $user->data($body);
                    $this->spot->mapper("App\User")->save($user);

                    $fractal = new Manager();
                    $fractal->setSerializer(new DataArraySerializer);
                    $resource = new Item($user, new User);
                    $data = $fractal->createData($resource)->toArray();

                    $data['data']['picture'] = str_replace('&','__amp__',$data['data']['picture']);
                }

                echo \login_redirect($data['data']);
                exit;
            });

            $this->get("/validates/{encoded}", function ($request, $response, $arguments) {

                $decoded = Base62::decode($request->getAttribute('encoded'));

                $user = $this->spot->mapper("App\User")->first([
                    "email" => $decoded
                ]);

                if( ! $user){
                    $data["status"] = "error";
                    $data["message"] = "No user found";

                } else {

                    $body = $user->data(['validated' => 1]);
                    $this->spot->mapper("App\User")->save($body);

                    $data["status"] = "success";
                    $data["message"] = "Account successfully validated.";
                }

                $view = new \Slim\Views\Twig('templates', [
                    'cache' => false
                ]);

                $params = $request->getQueryParams();
                $data["redirect"] = getenv('CLIENTES_URL');

                if( ! empty($params['redirect'])){
                    $data["redirect"] = getenv('APP_URL') . $params['redirect'];
                }

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($user, new User);
                $data = $fractal->createData($resource)->toArray();

                echo \login_redirect($data['data']);
                exit;
            });

            $this->post("/signin", function ($request, $response, $arguments) {

                $body = $request->getParsedBody();

                $user = $this->spot->mapper("App\User")->first([
                    'email' => $body['email'],
                    //'validated' => 1,
                    'password' => sha1($body['password'].getenv('APP_HASH_SALT'))
                ]);

                if($user){

                    $fractal = new Manager();
                    $fractal->setSerializer(new DataArraySerializer);
                    $resource = new Item($user, new User);
                    $data = $fractal->createData($resource)->toArray();

                    $data["status"] = "success";
                    $data["message"] = "Hi {$user->first_name}";

                } else {
                    $data["status"] = "error";
                    $data["message"] = "Invalid login";
                }

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));  
            });

            $this->post("/signup", function ($request, $response, $arguments) {
                    
                $body = $request->getParsedBody();
                $data = ["status" => "error"];

                if(empty($body['password'])){
                    $body['password'] = strtolower(Base62::encode(random_bytes(10)));
                }

                $user = $this->spot->mapper("App\User")->first([
                    'email' => $body['email']
                ]);

                if($user) {

                    $data["status"] = "error";
                    $data["message"] = "Email already exists<br><a href='/recover-password'>Recover password</a>";
                } else {

                    $hash = sha1($body['password'].getenv('APP_HASH_SALT'));
                    $username = \set_username($body["email"]);
                    $tone =  substr(\strToHex($username),0,6);
                    $user = new User([
                        "email" => $body["email"], 
                        "first_name" => $body["first_name"],
                        "last_name" => $body["last_name"],
                        "enabled" => 1,
                        "password" => $hash,
                        "username" => $username
                    ]);

                    $this->spot->mapper("App\User")->save($user);
                    
                    $body['readable_password'] = $body["password"];
                    $body['email_encoded'] = Base62::encode($body["email"]);

                    \send_email("Welcome to " . getenv('APP_TITLE') . " - Please validate your account.",$user,'welcome.html',$body);
                    
                    //\send_message(1,$user->id,1,"Bienvenido a " . getenv('APP_TITLE'));

                    $fractal = new Manager();
                    $fractal->setSerializer(new DataArraySerializer);
                    $resource = new Item($user, new User);
                    $data = $fractal->createData($resource)->toArray();

                    $data["status"] = "success";
                    $data["message"] = "Usuario creado";
                }

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));
            });

            $this->post("/token", function ($request, $response, $arguments) {
                if ($this->token->decoded->uid) {
                    
                    $user = $this->spot->mapper("App\User")->first([
                        "id" => $this->token->decoded->uid
                    ]);

                    $data = [];

                    if ($user) {
                        $fractal = new Manager();
                        $fractal->setSerializer(new DataArraySerializer);
                        $resource = new Item($user, new User);
                        $data = $fractal->createData($resource)->toArray();
                    }
                }

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));                
            });

            $this->post("/update-password", function ($request, $response, $arguments) {

                $body = $request->getParsedBody();
                $new_password = $body['password'];
                $token = $body['token']?:false;
                $data["status"] = "error";
                $data["message"] = "Invalid token";
                $data["token"] = $token;

                if($token){
                    $user = $this->spot->mapper("App\User")->first([
                        "password_token" => $token
                    ]);
                }

                if( $user ){
                    //$password = strtolower(Base62::encode(random_bytes(16)));
                    $body['password'] = $new_password;
                    $body['email'] = $user->email;
                    $body['first_name'] = $user->first_name;
                    $hash = sha1($new_password.getenv('APP_HASH_SALT'));

                    $user->data([
                        'password' => $hash,
                        'password_token' => ""
                    ]);

                    $this->spot->mapper("App\User")->save($user);

                    \send_email("Password update",$user,'update-password.html',$body);

                    $fractal = new Manager();
                    $fractal->setSerializer(new DataArraySerializer);
                    $resource = new Item($user, new User);
                    $data = $fractal->createData($resource)->toArray();
                    $data["status"] = "success";
                    $data["redirect_url"] = \login_redirect_url($data['data']);
                }

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));    
            });

            $this->post("/recover-password", function ($request, $response, $arguments) {
                
                $body = $request->getParsedBody();

                $user = $this->spot->mapper("App\User")->first([
                    'email' => trim($body['email'])
                ]);

                if( $user ){

                    $password_token = strtolower(Base62::encode(random_bytes(16)));
                    $body['password_token'] = $password_token;
                    $user->data(['password_token' => $password_token]);
                    $this->spot->mapper("App\User")->save($user);

                    $sent = \send_email("Password recovery",$user,'recover-password.html',$body);

                    if($sent['status']=='success'){
                        $data["status"] = 'success';
                        $data["message"] = "An e-mail was sent with instructions.";
                    }
                    
                } else {
                    $data["status"] = "error";
                    $data["message"] = "There is no account registered with email {$body['email']}";        
                }

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));
            });        
        }); 
    });
});