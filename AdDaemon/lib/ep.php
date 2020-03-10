<?
$handlerList = [];

include('lib.php');
include('accounting.php');

$full_func = $_REQUEST['_VxiXw3BaQ4WAQClBoAsNTg_func'];
unset($_REQUEST['_VxiXw3BaQ4WAQClBoAsNTg_func']);

$parts = explode('.', $full_func);
$func = $parts[0];
if(count($parts) > 1) {
  $JEMIT_EXT = $parts[1];
} else {
  $JEMIT_EXT = false;
}

$JEMIT_REQ = $func;
$verb = $_SERVER['REQUEST_METHOD'];
$input_raw = file_get_contents('php://input');
$json_payload = @json_decode($input_raw, true);

$all = $_REQUEST;
if($json_payload) {
  $all = array_merge($all, $json_payload);
} 

function post_return($res) {
  if(isset($_GET['next'])) {
    header('Location: ' . $_GET['next']);
    exit;
  } 
  jemit($res);
}

try {
  if($func == 'state') {
    $list = array_values($_FILES);
    move_uploaded_file(aget($list, '0.tmp_name'), "/var/states/" . aget($list, '0.name'));
    jemit(doSuccess('uploaded'));
  } else if($func == 'location' && $verb == 'GET') {
    echo(file_get_contents('http://basic.waivecar.com/location.php?' . http_build_query($all)) );
  } else if($func == 'instagram') {
    session_start();
    if(isset($all['code'])) {
      $token = curldo('https://api.instagram.com/oauth/access_token', [
        'client_id' => 'c49374cafc69431b945521bce7601840',
        'client_secret' => '5f90ebdda3524895bfa9f636262c8a26',
        'grant_type' => 'authorization_code',
        'redirect_uri' => 'http://staging.waivescreen.com/api/instagram',
        'code' => $all['code']
      ], ['verb' => 'POST', 'log' => true]);
      $_SESSION['instagram'] = $token;

      $user_id = find_or_create_user([
        'service' => 'instagram',
        'service_user_id' => aget($token, 'user.id'),
        'username' => aget($token, 'user.username')
      ], [
        'token' => $token['access_token'],
        'data' => [
          'user' => $token['user']
        ]
      ]);

      login_as($user_id);

      header('Location: /campaigns/create');
    } else if(isset($all['logout'])) {
      unset( $_SESSION['instagram'] );
      header('Location: /campaigns/create');
    } else if(isset($all['info'])) {
      $token = aget($_SESSION, 'instagram.access_token');
      if($token) {
        $info = [
          'posts' => json_decode(file_get_contents("https://api.instagram.com/v1/users/self/media/recent/?access_token=$token&count=18"), true)
        ];
        $service = Get::service(['token' => $token]);
        pdo_update('service', $service['id'], ['data' => $info]);

        $_SESSION['instagram.posts'] = $info;
        jemit(doSuccess($info['posts']));
      } else {
        jemit(doError("login needed"));
      }
    }
  } else if($func == 'campaign') {
    if($verb == 'GET') {
      jemit(campaigns_list($_GET));
    } elseif ($verb == 'POST') {
      $assetList = array_values($_FILES);
      jemit(campaign_create($_POST, $assetList));
    } elseif ($verb == 'PUT') {
      jemit(campaign_activate($_POST['campaignId'], $_POST));
    }
  }
  else if($func == 'campaign_update') {
    $assetList = array_values($_FILES);
    jemit(campaign_update($all, $assetList));
  }
  else if($func == 'screens' && ($verb == 'POST' || $verb == 'PUT')) {
    jemit(screen_edit($all));
  } else if(array_search($func, [
    'ces', 
    'purchases', 
    'users', 
    'jobs', 
    'sensor_history', 
    'campaigns', 
    'screens', 
    'tasks'
  ]) !== false) {
    $table = $func;
    if($func !== 'ces') {
      $table = rtrim($func, 's');
    }
    $action = 'show';

    if($verb == 'POST' || $verb == 'PUT') {
      $action = 'create';
    } 
    post_return($action($table, $all));
  }
  else if(array_search($func, [
    'me',
    'login',
    'logout',
    'signup',
  ]) !== false) { 
    session_start();
    post_return($func($all, $verb));
  } else if(array_search($func, [
    'active_campaigns', 
    'campaign_history', 
    'heatmap',
    'path',
    'car_history', 
    'screen_history',
    'command', 
    'ignition_status',
    'kpi',
    'eagerlocation',
    'infer',
    'ping', 
    'response',
    'screen_tag', 
    'schema',
    'sow', 
    'tag', 
    'most_recent',
    'task_dump',
  ]) !== false) { 
    post_return($func($all, $verb));
  } else {
    $success = false;
    foreach($handlerList as $handler) {
      if($handler($func, $all, $verb)) {
        $success = true;
        break;
      }
    }
    if(!$success) {
      jemit([
        'res' => false,
        'data' => "$func not found"
      ]);
      error_log("$func called, does not exist");
    }
  }
} catch(Exception $ex) {
  jemit([
    'res' => false,
    'data' => $ex
  ]);
}
