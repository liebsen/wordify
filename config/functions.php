<?php 

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Slim\Views\Twig;
use Intervention\Image\ImageManager;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Tuupola\Base62;
use App\User;
use App\Email;
use App\Refocus;

function stringInsert($str,$pos,$insertstr){
    if (!is_array($pos))
        $pos=array($pos);

    $offset=-1;
    foreach($pos as $p){
        $offset++;
        $str = substr($str, 0, $p+$offset) . $insertstr . substr($str, $p+$offset);
    }
    return $str;
}

function reframe_guid($id){
    $str = md5(uniqid($id, true));
    $str = stringInsert($str,8,'-');
    $str = stringInsert($str,13,'-');
    $str = stringInsert($str,17,'-');
    return $str;
}

function send_refocus_pdf($email,$guid,$tpl="results",$subject="You have refocused!",$debug=0){

    global $container; 
    
    $view = new \Slim\Views\Twig( __DIR__ . '/../' . getenv('APP_PUBLIC') . 'templates', [
        'cache' => false
    ]);

    $html = $view->fetch("emails/{$tpl}.html",[
        'app_url' => getenv('APP_URL'),
        'api_url' => $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost(),
        'guid' => $guid
    ]);

    //if($_SERVER['REMOTE_ADDR'] == '127.0.0.1') return ['status' => 'success'];

    $attachment = \refocus2pdf($guid,'S');

    //Create a new PHPMailer instance
    $mail = new \PHPMailer;
    $mail->IsSMTP(); 
    $mail->SMTPDebug = $debug?:getenv('MAIL_SMTP_DEBUG');
    $mail->SMTPAuth = getenv('MAIL_SMTP_AUTH');
    $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE');
    $mail->Host = getenv('MAIL_SMTP_HOST');
    $mail->Port = getenv('MAIL_SMTP_PORT');
    $mail->CharSet = "UTF-8";
    $mail->IsHTML(true);
    $mail->Username = getenv('MAIL_SMTP_ACCOUNT');
    $mail->Password = getenv('MAIL_SMTP_PASSWORD');
    $mail->setFrom(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->addReplyTo(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = $html;
    $mail->addAddress($email, $subject);

    //$mail->addAttachment('images/phpmailer_mini.png');
    $mail->AddStringAttachment($attachment, "{$guid}.pdf", 'base64', 'application/pdf');// attachment
    $data = [];

    //send the message, check for errors
    if ( ! $mail->send()) {
        $data['success'] = false;
        $data['message'] = $mail->ErrorInfo;
    } else {
        $data['success'] = true;
    }

    return $data;
}

/* sends email to an account.. */

function send_email($subject,$recipient,$template,$data,$debug = 0){

    global $container; 

    $view = new \Slim\Views\Twig( __DIR__ . '/../' . getenv('APP_PUBLIC') . '/templates', [
        'cache' => false
    ]);

    $code = strtolower(Base62::encode(random_bytes(16)));

    while($container["spot"]->mapper("App\Email")->first(["code" => $code])){
        $code = strtolower(Base62::encode(random_bytes(16)));
    }

    $data['code'] = $code;
    $data['recipient'] = $recipient;
    $data['app_url'] = getenv('APP_URL');
    $data['api_url'] = $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost();

    $html = $view->fetch("emails/{$template}",$data);
    $full_name = $recipient->first_name + ' ' + $recipient->last_name;

    if( strpos($subject,getenv('APP_TITLE')) === false) {
        $subject = getenv('APP_TITLE') . " " . $subject;
    }

    $body = [
        'code' => $code,
        'subject' => $subject,
        'user_id' => $recipient->id,
        'email' => $recipient->email,
        'full_name' => $full_name,
        'content' => $html
    ];

    $email = new Email($body);
    $container["spot"]->mapper("App\Email")->save($email);

    //if($_SERVER['REMOTE_ADDR'] == '127.0.0.1') return ['status' => 'success'];

    //Create a new PHPMailer instance
    $mail = new \PHPMailer;
    $mail->IsSMTP(); 
    $mail->SMTPDebug = $debug?:getenv('MAIL_SMTP_DEBUG');
    $mail->SMTPAuth = getenv('MAIL_SMTP_AUTH');
    $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE');
    $mail->Host = getenv('MAIL_SMTP_HOST');
    $mail->Port = getenv('MAIL_SMTP_PORT');
    $mail->CharSet = "UTF-8";
    $mail->IsHTML(true);
    $mail->Username = getenv('MAIL_SMTP_ACCOUNT');
    $mail->Password = getenv('MAIL_SMTP_PASSWORD');
    $mail->setFrom(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->addReplyTo(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = \html2text($html);
    $mail->addAddress($recipient->email, $full_name);

    //$mail->addAttachment('images/phpmailer_mini.png');
    $data = [];


    //send the message, check for errors
    if ( ! $mail->send()) {
        $data['status'] =  "error";
        $data['message'] = $mail->ErrorInfo;
    } else {
        $data['status'] = "success";
    }

    return $data;
}


/* determine font size based on text length */

function belief_text_size($str){
    $size = 5;
    $strlength = strlen($str);

    if($strlength <= 5){
      $size = 15;
    } else if($strlength > 5 && $strlength <= 10){
      $size = 14;
    } else if($strlength > 10 && $strlength <= 15){
      $size = 13;
    } else if($strlength > 15 && $strlength <= 25){
      $size = 12;
    } else if($strlength > 25 && $strlength <= 25){
      $size = 11;
    } else if($strlength > 25 && $strlength <= 60){
      $size = 10;
    } else if($strlength > 60 && $strlength <= 100){
      $size = 9;
    } else if($strlength > 100 && $strlength <= 140){
      $size = 8;
    } else if($strlength > 140){
      $size = 6;
    }

    return $size;  
}

/* outputs a pdf document representing a refocus belief set */

function refocus2pdf($guid,$output=null,$name=null){

    global $container;    

    if($name == null){
        $name = $guid;
    }

    $mapper = $container["spot"]->mapper("App\Refocus")->first([
        'guid' => $guid
    ]);

    if(!$mapper){
        return false;
    }

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($mapper, new Refocus);
    $data = $fractal->createData($resource)->toArray();

    $beliefs = $data['data']['beliefs'];
    // create pdf canvas
    $fpdf = new FPDF();
    $fpdf->AddFont('gibson','','gibson.php');

    $imgpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . '/img/';
    $fpdf->AddPage('L');
    $fpdf->SetFillColor(250,250,250);
    $fpdf->Rect(0,0,148,295,'F');

    // make bubbles
    // core
    $fpdf->Image($imgpath . 'hallRedB.png',50,77,50,50);
    $fpdf->SetXY(58,93);
    $fpdf->SetTextColor(255);
    $fpdf->SetFont('Gibson','',\belief_text_size($beliefs->core)); 
    $fpdf->MultiCell(35,5,utf8_decode($beliefs->core),0,'C');

    // supports
    $map = [
        ['x' => 9,'y' => 36],
        ['x' => 90,'y' => 36],
        ['x' => 9,'y' => 118],
        ['x' => 90,'y' => 118]
    ];

    foreach($beliefs->supports as $i => $support){
        $fpdf->Image($imgpath . 'hallRed.png',$map[$i]['x'],$map[$i]['y'],50,50);
        $fpdf->SetXY(($map[$i]['x']+8),($map[$i]['y']+8));
        $fpdf->SetTextColor(255,0,0);
        $fpdf->SetFont('Gibson','',\belief_text_size($support)); 
        $fpdf->MultiCell(35,5,utf8_decode($support),0,'C');
    }

    // opposite
    $fpdf->Image($imgpath . 'hallBlueB.png',197,77,50,50);
    $fpdf->SetXY(205,91);
    $fpdf->SetTextColor(255);
    $fpdf->SetFont('Gibson','',\belief_text_size($beliefs->opposite)); 
    $fpdf->MultiCell(35,5,utf8_decode($beliefs->opposite),0,'C');


    // opposites
    $map = [
        ['x' => 156,'y' => 36],
        ['x' => 238,'y' => 36],        
        ['x' => 156,'y' => 118],
        ['x' => 238,'y' => 118]
    ];

    foreach($beliefs->opposites as $i => $opposite){
        $fpdf->Image($imgpath . 'hallBlue.png',$map[$i]['x'],$map[$i]['y'],50,50);
        $fpdf->SetXY(($map[$i]['x']+8),($map[$i]['y']+8));
        $fpdf->SetTextColor(1,71,140);
        $fpdf->SetFont('Gibson','',\belief_text_size($opposite)); 
        $fpdf->MultiCell(35,5,utf8_decode($opposite),0,'C');
    }

    return $fpdf->Output($output, $name);
}

/* recieves upload files, insert them into db & store them */

function process_uploads($body,$entry,$size='',$default_size='',$name = 'uploads'){

    global $container;

    $valid_exts = explode(',',getenv('APP_IMAGE_UPLOAD_EXT')); // valid extensions
    $max_size = getenv('APP_IMAGE_UPLOAD_MAX') * 1024; // max file size in bytes
    $keys = [];
    $data = [];
    $status = "success";
    $ext_error = "File could not been stored due to its extension. Make you are using common standard web compressions formats, like " . implode(", ", $valid_exts);
    $size_error = "File could not been stored due to its size. File must be smaller than " . (ceil($max_size / 1024) / 1000) . 'M';

    // copy, resizes and database storage
    foreach($_FILES[$name]['tmp_name'] as $i => $tmp_name){
        if(is_uploaded_file($_FILES[$name]['tmp_name'][$i]) ){
            $ext = strtolower(pathinfo($_FILES[$name]['name'][$i], PATHINFO_EXTENSION));
            if (in_array($ext, $valid_exts)) {
                if($_FILES[$name]['size'][$i] < $max_size){
                    // generic upload method per file
                    $udata = bucket_store($_FILES[$name]['tmp_name'][$i],getenv('APP_IMAGE_USER'),$default_size);

                    if(empty($udata['error'])) {
                        $data['position'] = ($i+1);
                        $data['file_url'] = $udata['url'];
                        $data['filesize'] = $_FILES[$name]['size'][$i];

                        //$upload = new Upload($body);
                        //$data[$i] = $container["spot"]->mapper("App\Upload")->save($upload);

                        $data['url'] = $udata['url'];
                    } else {
                        $status = "error";
                        $data['error'][$i] = $udata['error'];
                    }
                } else {
                    $status = "error";
                    $data['error'][$i] = $size_error;
                }
            } else {
                $status = "error";
                $data['error'][$i] = $ext_error;
            }
        }
    }
    
    $data['status'] = $status;
    return $data;
}

/* files store  */

function bucket_store($tmp_name,$res,$size = '',$folder = ''){

    global $container;

    $manager = new ImageManager();

    $jti = Base62::encode(random_bytes(8) . date('_YmdHs_'));
    $key = $jti . '.' . getenv('APP_IMAGE_EXTENSION');
    $resolutions = explode(',',$res);

    try {

        $url = getenv('UPLOADS_URL') . '/' . $folder . $size . $key;

        $orig = $manager->make($tmp_name)
            ->orientate()
            ->save(getenv('UPLOADS_PATH') . '/' . $folder . $key, (int) getenv('APP_IMAGE_QUALITY'));

        foreach($resolutions as $res){
            $parts = explode('x',$res);
            $resized = $manager->make($tmp_name)
                ->orientate()
                ->fit((int) $parts[0],(int) $parts[1])
                ->save(getenv('UPLOADS_PATH') . '/' . $folder .  $parts[0] . 'x' . $parts[1] . $key, (int) getenv('APP_IMAGE_QUALITY'));
        }

        $data['url'] = $url;

    } catch (S3Exception $e) {
      // Catch an S3 specific exception.
        $data['error'] = $e->getMessage();
    }

    return $data;
}

function log2file($path, $data, $mode="a"){
   $fh = fopen($path, $mode) or die($path);
   fwrite($fh,$data . "\n");
   fclose($fh);
   chmod($path, 0777);
}

function login_redirect($data){
    \log2file( __DIR__ . "/../logs/ecma-" . date('Y-m-d') . ".log",json_encode($data)); 
    return "<script>location.href = '" . \login_redirect_url($data) . "';</script>";
}

function login_redirect_url($data){
    return getenv('APP_URL') . "/opener?token=" . json_encode($data) . "&url=" . getenv('APP_REDIRECT_AFTER_LOGIN');
}

function strToHex($string){
    $hex = '';
    for ($i=0; $i<strlen($string); $i++){
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0'.$hexCode, -2);
    }
    return strToUpper($hex);
}

function hexToStr($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}

function set_username($intended){

    global $container; 

    if($intended == ""){
        $intended = strtolower(Base62::encode(random_bytes(8)));
    }

    $j=0;
    $username = $intended;

    while($container["spot"]->mapper("App\User")->first(["username" => \slugify($username)])){
        $j++;
        $username = $intended . $j;
    }

    return \slugify($username);
}

function slugify($text){

    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return strtolower(Base62::encode(random_bytes(8)));
    }

    return $text;
}

function set_token($user){

    global $container;

    $now = new DateTime();
    $future = new DateTime("now +" . getenv('APP_JWT_EXPIRATION'));
    $jti = Base62::encode(random_bytes(16));

    $payload = [
        "uid" => $user->id,
        "rid" => $user->role_id,
        "iat" => $now->getTimeStamp(),
        "exp" => $future->getTimeStamp(),
        "jti" => $jti
    ];

    return JWT::encode($payload, getenv("APP_JWT_SECRET"), "HS256");
}

function register_if_not_exists($email){

    global $container;

    if(!strlen($email)) return false;

    $user = $container["spot"]->mapper("App\User")->first([
        "email" => $email
    ]);

    $fakenames = ['Fresh','Hot','Flamming','Bumpy'];
    $fakesurenames = ['Feeling','Splendorous','Jackets'];

    if(!$user){
        $password = strtolower(Base62::encode(random_bytes(10)));
        $emaildata['readable_password'] = $password;
        $emaildata['email_encoded'] = Base62::encode($email);
        $hash = sha1($password . getenv('APP_HASH_SALT'));
        $user = new User([
            "email" => $email,
            "password" => $hash,
            "first_name" => "User"
        ]);

        /*
        \log2file( __DIR__ . "/../logs/password-" . date('Y-m-d') . ".log",json_encode([
            'hash' => $hash,
            'salt' => getenv('APP_HASH_SALT'),
            'password' => $password
        ])); */

        $container["spot"]->mapper("App\User")->save($user);

        \send_email("Welcome to " . getenv('APP_TITLE'),$user,'welcome.html',$emaildata);
    }

    return $user;
}

function html2text($Document) {
    $Rules = array ('@<style[^>]*?>.*?</style>@si',
                    '@<script[^>]*?>.*?</script>@si',
                    '@<[\/\!]*?[^<>]*?>@si',
                    '@([\r\n])[\s]+@',
                    '@&(quot|#34);@i',
                    '@&(amp|#38);@i',
                    '@&(lt|#60);@i',
                    '@&(gt|#62);@i',
                    '@&(nbsp|#160);@i',
                    '@&(iexcl|#161);@i',
                    '@&(cent|#162);@i',
                    '@&(pound|#163);@i',
                    '@&(copy|#169);@i',
                    '@&(reg|#174);@i',
                    '@&#(d+);@e'
             );
    $Replace = array ('',
                      '',
                      '',
                      '',
                      '',
                      '&',
                      '<',
                      '>',
                      ' ',
                      chr(161),
                      chr(162),
                      chr(163),
                      chr(169),
                      chr(174),
                      'chr()'
                );
  return preg_replace($Rules, $Replace, $Document);
}

function human_timespan_short($time){

    $str = "";
    $diff = time() - $time; // to get the time since that moment
    $diff = ($diff<1)? $diff*-1 : $diff;

    $Y = date('Y', $time);
    $n = date('n', $time);
    $w = date('w', $time);
    $wdays = ['dom','lun','mar','mié','jue','sáb'];

    if($diff < 86400){
        $str = date('H:i',$time); 
    } elseif($diff < 604800){
        $str = $wdays[$w];
    } elseif($Y <> date('Y')){
        $str = date('j/n/y',$time);  
    } elseif($n <> date('n')){
        $str = date('j/n',$time); 
    } else {
        $str = date('j',$time);  
    }

    return $str;
}

function human_timespan($time){

    $time = time() - $time; // to get the time since that moment
    $time = ($time<1)? $time*-1 : $time;
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'min',
        1 => 'sec'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.($numberOfUnits>1)?'s':'';
    }
}