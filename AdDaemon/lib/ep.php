<?
$handlerList = [];

include('lib.php');
// this has a session start in it
include('accounting.php');
include('dsp.php');

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
foreach($all as $k => $v) {
  if($v === 'null') {
    $all[$k] = null;
  }
}

function post_return($res) {
  if(isset($_GET['next'])) {
    header('Location: ' . $_GET['next']);
    exit;
  } 
  jemit($res);
}

$instagram_props = [
  'client_id' => '1653482628156267',
  'client_secret' => '14f30a04d86253bb435b6fed5d4d8e78',
  'redirect_uri' => 'https://9ol.es/olvr/api/instagram'
];

try {
  if($func == 'state') {
    $list = array_values($_FILES);
    move_uploaded_file(aget($list, '0.tmp_name'), "/var/states/" . aget($list, '0.name'));
    jemit(doSuccess('uploaded'));
  } else if($func == 'location' && $verb == 'GET') {
    echo(file_get_contents('http://basic.waivecar.com/location.php?' . http_build_query($all)) );
  } else if($func == 'instagram') {
    if(isset($all['code'])) {
      $token = curldo('https://api.instagram.com/oauth/access_token', array_merge(
        $instagram_props, [
          'grant_type' => 'authorization_code',
          'code' => $all['code']
        ]), ['verb' => 'POST']);
      $_SESSION['instagram'] = $token;

      $userInfo = curldo('https://graph.instagram.com/me', [
        'fields' => 'id,username',
        'access_token' => $token['access_token']
      ]);

      // instagram is profoundly fucking stupid under fb management.
      // TODO: They stored the user_id as a number! so you'll get IEEE floating
      // point errors without careful audits. We undo that stupidity here.
      $scraped = insta_get_stuff($userInfo['username']);

      $profile_data = array_merge(
        $scraped, 
        [ 'user_id' => strval($token['user_id']) ],
        $userInfo
      );

      $user_id = find_or_create_user([
        'service' => 'instagram',
        'service_user_id' => aget($token, 'user_id'),
        'username' => aget($userInfo, 'username')
      ], [
        'token' => $token['access_token'],
        'data' => ['user' => $profile_data]
      ]);

      login_as($user_id);

      header('Location: /campaigns/create');
    } else if(isset($all['logout'])) {
      unset( $_SESSION['instagram'] );
      header('Location: /campaigns/create');
    } else if(isset($all['info'])) {
      $token = aget($_SESSION, 'instagram.access_token');
      if($token) {
        $service = Get::service(['token' => $token]);

        $fields = 'timestamp,media_url,media_type';
        $url = "https://graph.instagram.com/me/media?fields=$fields&access_token=$token";
        $raw = file_get_contents($url);
        $service['data']['posts'] = json_decode($raw, true);

        pdo_update('service', $service['id'], ['data' => $service['data']]);

        $_SESSION['instagram.posts'] = $service['data']['posts'];
        jemit(doSuccess($service));
         
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
    // these are essentially resources with CRUD interfaces
  } else if(array_search($func, [
    'services', 
    'purchases', 
    'users', 
    'jobs', 
    'sensor_data', 
    'template_config',
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
      error_log(json_encode([$verb, $func, $all]));
      if(!isset($all['screen_id']) && $func == 'sensor_data') {
        $all['screen_id'] = $_SERVER['HTTP_USER_AGENT'];
      }

    } 
    post_return($action($table, $all));
  }
  else if(array_search($func, [
    'me',
    'login',
    'logout',
    'signup',
  ]) !== false) { 
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
    'provides',
    'task_dump',
    'dsp_signup',
    'dsp_create',
    'dsp_default',
    'dsp_ping',
    'dsp_sow',
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
