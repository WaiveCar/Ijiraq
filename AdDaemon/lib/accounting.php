<?
$TWIL = [
  'num'  => '+18559248355',
  'sid'  => 'ACa061f336122514af845ea65fb1e6c2bb',
  'token'=> 'b39d95c162c1e9ad1893acbb61af8bb4'
];

require $_SERVER['DOCUMENT_ROOT'] . 'AdDaemon/vendor/autoload.php';
use Twilio\Rest\Client;
include_once("lib.php");

function get_user() {
  if(isset($_SESSION['user_id'])) {
    return Get::user($_SESSION['user_id']);
  }
}

function me() {
  $res = [];
  if(isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $res = $_SESSION['user'];
  }
  foreach (['instagram', 'instagram.posts'] as $k) {
    if(isset($_SESSION[$k])) {
      $res[$k] = $_SESSION[$k];
    }
  }
  return $res;
}

function do_oth($oth) {
  $stuff = onetimehash($oth);
  if($stuff) {
    if($stuff['action'] == 'confirm') {
      pdo_update('user', ['id' => $stuff['data']], ['is_erified' => true]);
    }
    return true;
  }
}

function create($table, $payload = []) {
  // TODO: whitelist the tables
  global $SCHEMA;
  foreach($payload as $k => $v) {
    $typeRaw = aget($SCHEMA, "$table.$k");
    if($typeRaw) {
      $parts = explode(' ', $typeRaw);
      $type = $parts[0];
      if($k === 'password') {
        $v = password_hash($v, PASSWORD_BCRYPT);
      } else if(empty($payload[$k])) {
        unset($payload[$k]);
      } else {
        $payload[$k] = $v;
      }
    } else {
      unset($payload[$k]);
    }
  }

  $id = aget($payload, 'id');
  if($id) {
    return pdo_update($table, $id, $payload);
  } 

  return pdo_insert($table, $payload);
}

function upsert_user($all) {
  $who = aget($all, 'email');
  if(!$who) {
    return false;
  }
  $user = Get::user(['email' => $who]);
  if ($user) {
    $user_id = $user['id'];
    pdo_update('user', $user_id, $all, true);
  } else {
    $user_id = create('user', $all);
  }
  return Get::user($user_id);
}

function login_as($user_id) {
  if($user_id) {
    if(is_numeric($user_id)) {
      $user = Get::user($user_id);
    } else {
      $user = $user_id;
    }
    $_SESSION['user'] = $user;
    return $user;
  }
}
function signup($all) {
  $who = aget($all, 'email');

  if(!$who) {
    return doError("I need an email");
  }

  $user = Get::user(['email' => $who]);

  if ($user) {
    if( !$all['password'] || password_verify($all['password'], $user['password'])) {
      $_SESSION['user'] = $user;
      return doSuccess($user);
    } 
    return doError("Wrong password");
  }

  $user_id = create('user', $all);
  return doSuccess(login_as($user_id));
}

function logout() {
  session_destroy();
}

function text_rando($number, $message) {
  global $TWIL;
  $client = new Client($TWIL['sid'], $TWIL['token']);
  try {
    $client->messages->create($number, [ 
      'from' => $TWIL['num'], 
      'body' => $message
    ]);
  } catch(Exception  $e) {
    error_log($e);
  }
}

function notification_sweep() {
  global $PLAYTIME;
  // This will just go through and basically get all the things that need to be notified.
  // I want everything that has started and has not ended in the past day.
  $all = db_all("select * from campaign where start_date < date('now') and end_date > date('now', '-1 day')");

  foreach($all as $campaign) {
    $duration = $campaign['duration_seconds'] ?: $PLAYTIME;
    $plays = floor($campaign['completed_seconds'] / $duration);

    if($campaign['completed_seconds'] > 0) { notify_if_needed($campaign, 'campaign_start'); }
    if($plays > 50)   { notify_if_needed($campaign, 'plays_50'); }
    if($plays > 200)  { notify_if_needed($campaign, 'plays_200'); }
    // TODO: boost
    // TODO: extension offer
    // TODO: complete
  } 
}

function render($M5YFgsLGQian24eTfLEQIA_template, $opts) {
  extract($opts);
  ob_start();
    include("{$_SERVER['DOCUMENT_ROOT']}AdDaemon/templates/$M5YFgsLGQian24eTfLEQIA_template");
    $M5YFgsLGQian24eTfLEQIA_res = trim(ob_get_contents());
  ob_end_clean();
  return $M5YFgsLGQian24eTfLEQIA_res;
}

function parser($template, $opts) {
  $head = render('_header', $opts);
  $stuff = render($template, $opts);
  $foot = render('_footer', $opts);

  $stuff = preg_split('/\n/m', $stuff);
  $body = implode("\n", array_slice($stuff, 2));

  return [
    'sms'     => $stuff[0],
    'subject' => $stuff[1],
    'email'   => $head . $body . $foot
  ];
}

function notify_if_needed($campaign, $event) {
  if(!is_flagged($campaign, $event)) {
    flag($campaign, $event);
    return send_campaign_message($campaign, $event);
  }
}

function send_message($user, $template, $params) {
  $params['user'] = $params['user'] ?: $user;
  $stuff = parser($template, $params);

  $res = [];
  if($user['phone']) {
    $res['text'] = text_rando($user['phone'], $stuff['sms']); 
  }

  $res['email'] = curldo(
    'https://api.mailgun.net/v3/waive.com/messages', [
      'from'    => 'Waive <support@waive.com>',
      'to'      => $user['email'],
      'subject' => $stuff['subject'],
      'html'    => $stuff['email']
    ], [
      'auth' => [
        'user' => 'api', 
        'password' => 'key-2804ba511f20c47a3c2dedcd36e87c92'
      ],
      'verb' => 'post', 
      'json' => false
    ]
  );

  return $res;
}

function send_campaign_message($campaign, $template, $user = false, $order = false) {
  $user = $user ?: Get::user($campaign['user_id']);
  $order = $order ?: Get::purchase($campaign['purchase_id']);

  $params = [
    'date_start' => $campaign['start_time'],
    'date_end'  => $campaign['end_time'],
    'campaign_link' => 'https://olvr.io/v/' . $campaign['id'],
    'play_count'=> $campaign['play_count'],
    'name'      => $user['name'],
    'amount'    => sprintf("$%.2f", $order['amount'] / 100),

    'campaign'  => $campaign,
    'user'      => $user,
    'order'     => $order
  ];
  return send_message($user, $template, $params);
}

function add_service($user, $service_obj) {
  if(!$user) {
    $user = get_user();
  }
  if($user) {
    return pdo_upsert('service', array_merge(
      $service_obj,
      [ 'user_id' => $user['id'] ]
    ));
  }
}

// From notebook:
// "If no user exists for a socnet account, we create an empty one"
function get_service($user, $service_string) {
}

// todo: we need to be able to have multiple users potentially connect
// the same instagram account.
function find_or_create_user($service_obj, $data) {
  $user = get_user();
  $row = Get::service($service_obj);

  if($row) {
    $user_id = $user ? 
      aget($user, 'id') : 
      aget($row, 'user_id');
  } else {
    $row = pdo_insert('service', $service_obj);

    $user_id = $user ? 
      $user['id'] : 
      create('user');
  }
  $data['user_id'] = $user_id;
  pdo_update('service', $row['id'], $data);
  return $user_id;
}


function provides() {
  $res = [];
  $serviceList = Many::service();
  foreach($serviceList as $service) {
    $row = [];
    if($service['service'] == 'instagram') {
      $data = $service['data'];
      $row = [
        'handle' => $service['username'],
        'logo' => aget($data, 'user.profile_picture'),
        'description' => aget($data, 'user.bio'),
        'name' => aget($data, 'user.full_name'),
        'created_at' => aget($data, 'posts._t'),
        'photoList' => []
      ];

      foreach(aget($data, 'posts.data') as $post) {
        $row['photoList'][] = [
          'url' => aget($post, 'images.standard_resolution.url'),
          'length' => aget($post, 'images.standard_resolution.width'),
          'height' => aget($post, 'images.standard_resolution.height'),
          'pop' => aget($post, 'likes.count'),
          'created_at' => aget($post, 'created_time')
        ];
      }
      $res[] = $row;
    }
  }
  return $row;
}




