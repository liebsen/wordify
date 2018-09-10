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
use App\Page;
use App\Movie;

/* v1 */

$app->group('/api', function() {

    $this->group('/v1', function() {

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

            $data = \send_refocus_pdf($body['email'],$body['guid'],'results', "You have refocused!");
            $user = \register_if_not_exists($body['email']);

            $refocus = $this->spot->mapper("App\Refocus")->first([
                'guid' => $body['guid']
            ]);

            if(!$refocus->user_id){
                $refocus->data(['user_id' => $user->id]);
                $this->spot->mapper("App\Refocus")->save($refocus);        
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post('/beliefs/', function ($request, $response, $args) {
            
            $body = $request->getParsedBody();
            $payload = [];
            $refocus_id = $_SESSION['reframe_id'];

            foreach($body as $name => $value){
                if(!is_null($value)){
                    if(in_array($name,['created','updated'])) {
                        $payload[$name] = new \DateTime($value);
                    } else {
                        $payload[$name] = $value;
                    }
                }
            }
                
            if(!empty($body['parent']) AND $body['parent']){
                $parent = $this->spot->mapper("App\Belief")->first([
                    'id' => $body['parent']
                ]);

                if(!$refocus_id){
                    $refocus_id = (string) $parent->refocus_id;
                }

                $payload['parent_id'] = (string) $body['parent'];
            }

            $payload['refocus_id'] = $refocus_id;

            unset($payload['parent']);

            $belief = new Belief();
            $belief->data($payload);
            $this->spot->mapper("App\Belief")->save($belief);        

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($belief, new Belief);
            $data = $fractal->createData($resource)->toArray();

            if($body['is_core'] && $body['is_opposite']){
                $refocus = $this->spot->mapper("App\Refocus")->first([
                    'id' => $payload['refocus_id']
                ]);   

                if($refocus){
                    $refocus->data(['complete' => 1]);
                    $this->spot->mapper("App\Refocus")->save($refocus);
                }
            }

            $data['data']['refocus_id'] = (string) $refocus_id;
            $data['success'] = true;

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
                //unset($payload['id']);
                $mapper = $this->spot->mapper("App\Belief")->where([
                    'id' => $payload['id']
                ])->first();
            } else {
                $mapper = new Belief();    
            }
            
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
    });
});

$app->get('/refocus/{guid}', function ($request, $response, $args) {

    $mapper = $this->spot->mapper("App\Refocus")
        ->where(['guid' => $request->getAttribute('guid')])
        ->limit(1);

    $reframes = [];

    foreach($mapper as $parent){
        $reframe = [];
        foreach($parent->beliefs as $item){
            if($item->is_core){
                if($item->is_opposite){
                    $reframe['opposite'] = $item->text;
                } else {
                    $reframe['belief'] = $item->text;
                }
            } else {
                if($item->parent_id){
                    if($item->is_selected){
                        if($item->is_opposite){
                            $reframe['opposites'][] = $item->text;
                        } else {
                            $reframe['beliefs'][] = $item->text;
                        }
                    }
                }
            }
        }

        $reframes[$item->id] = $reframe;
    }

    return $this->view->render($response, 'guid.html',[
        'params' => $request->getQueryParams(),
        'reframes' => $reframes,
        'segments' => explode('/', $_SERVER['REQUEST_URI'])
    ]); 
});

$app->get('/tool', function ($request, $response, $args) {

    if(isset($_SESSION['reframe_id'])){
        unset($_SESSION['reframe_id']);
    }

    if(isset($_SESSION['reframe_guid'])){
        unset($_SESSION['reframe_guid']);
    }

    $reframe = new Refocus();
    $reframe_id = $this->spot->mapper("App\Refocus")->save($reframe);
    $reframe_guid = \reframe_guid($reframe_id);
    $reframe->guid = $reframe_guid;
    $this->spot->mapper("App\Refocus")->save($reframe);

    $_SESSION['reframe_id'] = $reframe_id;
    $_SESSION['reframe_guid'] = $reframe_guid;    

    $mapper = $this->spot->mapper("App\Tool")
        ->all();
    $instructions = [];

    foreach($mapper as $item){
        $value = $item->config_value;
        if($decoded = json_decode($value)){
            $value = $decoded;
        }
        $instructions[$item->config_key] = $value;
    }

    return $this->view->render($response, 'tool/compose.html',[
        'instructions' => json_encode($instructions),
        'reframe_id' => $_SESSION['reframe_id'],
        'reframe_guid' => $_SESSION['reframe_guid'],
        'segments' => explode('/', $_SERVER['REQUEST_URI'])
    ]);
});

$app->group('/tool', function() {

    $this->post('/token/{reframe_id}', function ($request, $response, $args) {
        if (false === $this->token->decoded->uid) {
            throw new ForbiddenException("Token expired or invalid.", 403);
        }

        $refocus = $this->spot->mapper("App\Refocus")
            ->where(['id' => $request->getAttribute('reframe_id')])
            ->first();

        $data = ['status' => "error"];

        if($refocus){
            $item = $refocus->data(["user_id" => $this->token->decoded->uid]);
            $this->spot->mapper("App\Refocus")->save($item);
            $data['status'] = "success";
        }

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data));
    });

    $this->get('/step/{id}/[{sid:.*}]', function ($request, $response, $args) {

        $mapper = $this->spot->mapper("App\Tool")
            ->all();
        $instructions = [];
        foreach($mapper as $item){
            $value = $item->config_value;
            if($decoded = json_decode($value)){
                $value = $decoded;
            }
            $instructions[$item->config_key] = $value;
        }

        return $this->view->render($response, 'tool/compose.html',[
            'instructions' => json_encode($instructions),
            'reframe_id' => $_SESSION['reframe_id'],
            'reframe_guid' => $_SESSION['reframe_guid'],
            'segments' => explode('/', $_SERVER['REQUEST_URI'])
        ]);
    });
});

$app->group('/print', function() {

    $this->get('/{guid}', function ($request, $response, $args) {

        $mapper = $this->spot->mapper("App\Refocus")
            ->where(['guid' => $request->getAttribute('guid')])
            ->limit(1);

        $reframes = [];

        foreach($mapper as $parent){
            $reframe = [];
            foreach($parent->beliefs as $item){
                if($item->is_core){
                    if($item->is_opposite){
                        $reframe['opposite'] = $item->text;
                    } else {
                        $reframe['belief'] = $item->text;
                    }
                } else {
                    if($item->parent_id){
                        if($item->is_selected){
                            if($item->is_opposite){
                                $reframe['opposites'][] = $item->text;
                            } else {
                                $reframe['beliefs'][] = $item->text;
                            }
                        }
                    }
                }
            }

            $reframes[$item->id] = $reframe;
        }

        return $this->view->render($response, 'print.html',[
            'params' => $request->getQueryParams(),
            'reframes' => $reframes,
            'segments' => explode('/', $_SERVER['REQUEST_URI'])
        ]); 
    });
});

/*
$app->get('/tool[/{slug:.*}]', function ($request, $response, $args) {
    return $this->view->render($response, 'refocus/compose.html',[
        'refocus_id' => $_SESSION['refocus_id'],
        'reframe_guid' => $_SESSION['reframe_guid'],
        'segments' => explode('/', $_SERVER['REQUEST_URI'])
    ]);
});*/ 
