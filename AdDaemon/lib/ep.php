<?
include('lib.php');

$func = $_REQUEST['_VxiXw3BaQ4WAQClBoAsNTg_func'];
unset($_REQUEST['_VxiXw3BaQ4WAQClBoAsNTg_func']);
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
  } else {
    jemit($res);
  }
}

try {
  if($func == 'state') {
    $list = array_values($_FILES);
    move_uploaded_file(aget($list, '0.tmp_name'), "/var/states/" . aget($list, '0.name'));
    jemit(doSuccess('uploaded'));
  } else if($func == 'me.css') {
    emit_css();
  } else if($func == 'me.js') {
    emit_js();
  } else if($func == 'me') {
    jemit(doSuccess($_SESSION));
  } else if($func == 'location') {
    echo(file_get_contents('http://basic.waivecar.com/location.php?' . http_build_query($all)) );
  } else if($func == 'feed') {
    jemit(json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "./reef-demo/APIWidget/widgetfiles/parsed_widget_data.json")));
  } else if($func == 'instagram') {
    if(isset($all['code'])) {
      $token = curldo('https://api.instagram.com/oauth/access_token', [
        'client_id' => 'c49374cafc69431b945521bce7601840',
        'client_secret' => '5f90ebdda3524895bfa9f636262c8a26',
        'grant_type' => 'authorization_code',
        'redirect_uri' => 'http://ads.waivecar.com/api/instagram',
        'code' => $all['code']
      ], 'POST');
      $_SESSION['instagram'] = $token;
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
  } else if(array_search($func, [ 'apps', 'tickers' ]) !== false ) {
    $type = rtrim($func, 's');
    post_return(show('widget', array_merge(['type' => $type], $all)));

  } else if(array_search($func, [
    'brands', 'organizations', 'attributions', 'users', 'widgets',
    'jobs', 'sensor_history', 'campaigns', 'screens', 'tasks']) !== false) {
    $table = rtrim($func, 's');
    $action = 'show';

    if($verb == 'POST' || $verb == 'PUT') {
      $action = 'create';
    }
    post_return($action($table, $all));
  }
  else if(array_search($func, [
    'active_campaigns', 
    'campaign_history', 
    'car_history', 
    'command', 
    'ignition_status',
    'login',
    'logout',
    'ping', 
    'response',
    'screen_tag', 
    'schema',
    'signup',
    'sow', 
    'tag', 
    'task_dump' 
  ]) !== false) { 
    post_return($func($all, $verb));
  } else {
    jemit([
      'res' => false,
      'data' => "$func not found"
    ]);
    error_log("$func called, does not exist");
  }
} catch(Exception $ex) {
  jemit([
    'res' => false,
    'data' => $ex
  ]);
}
