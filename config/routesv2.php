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
use App\Belief;
use App\Refocus;
use App\Config;
use App\Section;
use App\Movie;
use App\Tool;
use App\Email;

$app->group('/api', function() {
    
    $this->group('/v2', function() {

        $this->post("/contact", function ($request, $response, $arguments) {

            $body = $request->getParsedBody();
            
            $user = (object) [
                'first_name' => "Administrator",
                'last_name' => "",
                'email' => getenv('MAIL_CONTACT')
            ];

            \send_email("New contact from Refocus app",$user,'contact.html',$body);

            $data["status"] = "success";
            
            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));  

        });


        $this->post('/movies/{slug}', function ($request, $response, $args) {
            $mapper = $this->spot->mapper("App\Movie")
                ->where(["enabled" => 1])
                ->where(["title_slug" => $args['slug']])
                ->first();

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Movie);
            $data = $fractal->createData($resource)->toArray();

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post('/putinhall/', function ($request, $response, $args) {
            $body = $request->getParsedBody();

            extract($body);

            $belief = new Belief();
            $reframe = $this->spot->mapper("App\Refocus")->first(['guid' => $guid]);

            if( $mapper === false ){
                $data['success'] = false;
            } else {

                $reframe->data(['putinhall' => 1]);
                $this->spot->mapper("App\Refocus")->save($reframe);        

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($reframe, new Refocus);
                $data = $fractal->createData($resource)->toArray();

                $data['success'] = true;
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post('/email/', function ($request, $response, $args) {
            $body = $request->getParsedBody();

            //$presentation = create_presentation($body['guid']);
            $data = send_results_email($body['email'],$body['guid']);

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post('/refocuses', function ($request, $response, $args) {
            $body = $request->getParsedBody();

            extract($body);

            $refocus = new Refocus();
            $refocus_id = $this->spot->mapper("App\Refocus")->save($refocus);
            $refocus_guid = \reframe_guid($refocus_id);
            $refocus->guid = $refocus_guid;
            $this->spot->mapper("App\Refocus")->save($refocus);
            $data['success'] = true;
            $data['refocus_id'] = $refocus_id;

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post('/beliefs', function ($request, $response, $args) {
            
            $payload = $request->getParsedBody();

            $belief = new Belief();
            $belief->data($payload);
            $this->spot->mapper("App\Belief")->save($belief);        

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($belief, new Belief);
            $data = $fractal->createData($resource)->toArray();

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->map(['PUT', 'POST'],'/beliefs/id/{id}', function ($request, $response, $args) {
           
            $id = $request->getAttribute('id');
            $body = $request->getParsedBody();
            $payload = [];

            foreach($body as $name => $value){
                if(!is_null($value)){
                    if(in_array($name,['created','updated'])) {
                        $payload[$name] = new \DateTime($value);
                    } else {
                        $payload[$name] = $value;
                    }
                }
            }

            if(!empty($payload['parent'])){
                $payload['parent_id'] = $payload['parent'];
                unset($payload['parent']);
            }

            if(!empty($payload['id'])){
                unset($payload['id']);
            }       

            $mapper = new Belief();
            $mapper->data($payload);
            $this->spot->mapper("App\Belief")->save($mapper);        

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Belief);
            $data = $fractal->createData($resource)->toArray();

            $data['success'] = true;

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));  

        });

        $this->map(['DELETE'],'/beliefs/id/{id}', function ($request, $response, $args) {
           
            $id = $request->getAttribute('id');

            $this->spot->mapper("App\Belief")->first([
                'id' => $id
            ])->delete;

            $data['success'] = true;

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->get('/pdf/{guid}', function ($request, $response, $args) {

            $mapper = $this->spot->mapper("App\Refocus")
                ->where(['guid' => $request->getAttribute('guid')])
                ->limit(1);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Refocus);
            $data = $fractal->createData($resource)->toArray();

            return $this->view->render($response, 'print.html',[
                'params' => $request->getQueryParams(),
                'reframes' => $reframes,
                'segments' => explode('/', $_SERVER['REQUEST_URI'])
            ]); 
        });

        $this->post('/navitems', function ($request, $response, $args) {

            $mapper = $this->spot->mapper("App\Section")
                ->where(['is_navitem' => 1])
                ->where(['enabled' => 1]);

            $data = [];
            foreach($mapper as $item){
                $data[] = (object) [
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'icon' => $item->icon
                ];
            }
            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post('/sections[/{slug:.*}]', function ($request, $response, $args) {
            $slug = str_replace('.','/',$args['slug']);
            $data = null;
            $mapper = $this->spot->mapper("App\Section")
                ->where(['slug' => '/'.$slug])
                ->where(['enabled' => 1])
                ->first();

            if($mapper === false){
                throw new ForbiddenException("No resource was found.", 404);
            }

            /* Serialize the response data. */
            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Section);
            $data = $fractal->createData($resource)->toArray();

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->group('/account', function() {

            /* pdf file */
            $this->post("/share/{guid}", function ($request, $response, $arguments) {

                if (false === $this->token->decoded->uid) {
                    throw new ForbiddenException("Token expired or invalid.", 403);
                }

                $user = $this->spot->mapper("App\User")->first([
                    'id' => $this->token->decoded->uid
                ]);

                $body = $request->getParsedBody();
                $sent = \send_refocus_pdf($body['email'],$request->getAttribute('guid'),'share', $user->first_name . ' shared a refocus with you');

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($sent));
            });

            /* pdf file */
            $this->post("/download/{guid}", function ($request, $response, $arguments) {

                if (false === $this->token->decoded->uid) {
                    throw new ForbiddenException("Token expired or invalid.", 403);
                }

                $guid = $request->getAttribute('guid');
                define('FPDF_FONTPATH',getenv('FPDF_FONTPATH'));

                header('Content-Description: File Transfer'); 
                header('Content-Type: application/octet-stream'); 
                header(`Content-Disposition: attachment; filename="{$guid}.pdf"`);
                header('Content-Transfer-Encoding: binary'); 

                echo \refocus2pdf($guid);                
            });

            $this->post("/password", function ($request, $response, $arguments) {

                if (false === $this->token->decoded->uid) {
                    throw new ForbiddenException("Token expired or invalid.", 403);
                }

                $body = $request->getParsedBody();

                if(empty($body)){
                    throw new ForbiddenException("No parameters recieved.", 403);
                }

                $data = [];
                $user = $this->spot->mapper("App\User")->first([
                    'id' => $this->token->decoded->uid,
                    'enabled' => 1,
                    'password' => sha1($body['password'].getenv('APP_HASH_SALT'))
                ]);

               
                if(!$user) { 
                    $data['status'] = "error";
                    $data['message'] = "Password is invalid. Changes were not saved.";
                    $data['messageType'] = "is-danger";
                } else {
                    
                    $hash = sha1($body['new_password'].getenv('APP_HASH_SALT'));
                    
                    $user->data(['password' => $hash]);
                    $this->spot->mapper("App\User")->save($user);

                    $data['status'] = "success";
                    $data['message'] = "Password was successfully updated.";
                    $data['messageType'] = "is-success";
                }

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data)); 

            });

            $this->post("/update", function ($request, $response, $arguments) {

                $body = $request->getParsedBody();
                $data = [];

                if (false === $this->token->decoded->uid) {
                    throw new ForbiddenException("Token expired or invalid.", 403);
                }

                if(empty($body)){
                    throw new ForbiddenException("No parameters recieved.", 403);
                }

                $user = $this->spot->mapper("App\User")->first([
                    "id" => $this->token->decoded->uid
                ]);   

                if(!$user) { 
                    $data['status'] = "error";
                } else {
                    $user->data($body);
                    $this->spot->mapper("App\User")->save($user);

                    $fractal = new Manager();
                    $fractal->setSerializer(new DataArraySerializer);
                    $resource = new Item($user, new User);
                    $data = $fractal->createData($resource)->toArray();
                }

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));                 
            });

            $this->post("/profile-picture", function ($request, $response, $arguments) {

                if (false === $this->token->decoded->uid) {
                    throw new ForbiddenException("Token expired or invalid.", 403);
                }

                $mapper = $this->spot->mapper("App\User")->first([
                    "id" => $this->token->decoded->uid
                ]);

                if( ! $mapper){
                    throw new NotFoundException("User not found. (1)", 404);
                }

                $body = $request->getParsedBody();
                
                $data = \process_uploads($body,['user_id' => $mapper->id],getenv('APP_IMAGE_USER'),'200x200');

                $mapper->data(['picture' => $data['url']]);

                $this->spot->mapper("App\User")->save($mapper);

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));
            }); 

            $this->post('/refocuses', function ($request, $response, $args) {

                if (false === $this->token->decoded->uid) {
                    throw new ForbiddenException("Token expired or invalid.", 403);
                }

                $mapper = $this->spot->mapper("App\Refocus")
                    ->where(['user_id' => $this->token->decoded->uid])
                    ->where(['complete' => 1])
                    ->where(['deleted' => 0])
                    ->where(['enabled' => 1])
                    ->order(['created' => 'DESC']);
                    //->limit(10);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Collection($mapper, new Refocus);
                $data = $fractal->createData($resource)->toArray();

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));   
            });


            $this->post('/archive', function ($request, $response, $args) {

                if (false === $this->token->decoded->uid) {
                    throw new ForbiddenException("Token expired or invalid.", 403);
                }

                $mapper = $this->spot->mapper("App\Refocus")
                    ->where(['user_id' => $this->token->decoded->uid])
                    ->where(['complete' => 1])
                    ->where(['deleted' => 0])
                    ->where(['enabled' => 0])
                    ->order(['created' => 'DESC']);
                    //->limit(10);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Collection($mapper, new Refocus);
                $data = $fractal->createData($resource)->toArray();

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));   
            });

            $this->post('/refocuses/status/{guid}', function ($request, $response, $args) {

                if (false === $this->token->decoded->uid) {
                    throw new ForbiddenException("Token expired or invalid.", 403);
                }

                $mapper = $this->spot->mapper("App\Refocus")
                    ->where(['guid' => $request->getAttribute('guid')])
                    ->where(['user_id' => $this->token->decoded->uid])
                    ->first();

                $body = $request->getParsedBody();
                $mapper->data($body);
                $updated = $this->spot->mapper("App\Refocus")->save($mapper);
                $data['status'] = $updated?"success":"error";

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));   
            });   

            $this->post('/refocuses/{guid}', function ($request, $response, $args) {

                if (false === $this->token->decoded->uid) {
                    throw new ForbiddenException("Token expired or invalid.", 403);
                }

                $mapper = $this->spot->mapper("App\Refocus")
                    ->where(['guid' => $request->getAttribute('guid')])
                    ->where(['user_id' => $this->token->decoded->uid])
                    ->first();

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($mapper, new Refocus);
                $data = $fractal->createData($resource)->toArray();

                return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($data));   
            });

        });
    });
});


$app->get("/m/{code}", function ($request, $response, $arguments) {

    $mapper = $this->spot->mapper("App\Email")->first([
        "code" => $request->getAttribute('code')
    ]);

    if( ! $mapper){
        throw new NotFoundException("No se encontró el email", 404);        
    }

    header('Content-Type: text/html; charset=utf-8');
    print $mapper->content;
    exit;
});


$app->get('/{slug:.*}', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html');
});  