<?
$TWIL = [
  'num'  => '+',
  'sid'  => '',
  'token'=> ''
];

require $_SERVER['DOCUMENT_ROOT'] . 'AdDaemon/vendor/autoload.php';
use Twilio\Rest\Client;
include_once("lib.php");

$SESSION_MAX_AGE = 7 * ( 24 * 60 * 60 ); // 7 days

ini_set('session.gc_maxlifetime', $SESSION_MAX_AGE);
ini_set('session.use_strict_mode', '1');

session_start();
if(!isset($_SESSION['start'])) {
  $_SESSION['start'] = date(DATE_RFC2822);
}
if(!isset($_SESSION['uid'])) {
  $_SESSION['uid'] = compact_uuid();
}

function get_user_id() {
  return aget($_SESSION, 'user.id');
}
function get_user() {
  $user_id = get_user_id();
  if($user_id) {
    $_SESSION['user'] = Get::user($user_id);
    return $_SESSION['user'];
  }
}

function user_update($data) {
  $who = get_user_id();
  if($who) {
    $props = [];
    foreach(['email','name','phone'] as $field) {
      if(!empty($data[$field])) {
        $props[$field] = $data[$field];
      }
    }
    if(!empty($props)) {
      pdo_update('user', $who, $props);
      // refreshes the session;
      get_user();
    }
  }
}


function sess() {
  var_dump($_SESSION);
  return $_SESSION;
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
      pdo_update('user', ['id' => $stuff['data']], ['is_verified' => true]);
    }
    return true;
  }
}

// Returns null on error, otherwise the object.
function create($table, $payload = []) {
  // TODO: whitelist the tables
  global $SCHEMA;
  foreach($payload as $k => $v) {
    $typeRaw = aget($SCHEMA, "$table.$k");
    if($typeRaw) {
      //error_log(json_encode([$k, $typeRaw]));
      $parts = explode(' ', $typeRaw);
      $type = $parts[0];
      if($k === 'password') {
        $v = password_hash($v, PASSWORD_BCRYPT);
      } else if(!isset($payload[$k])) {
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
    if(pdo_update($table, $id, $payload)) {
      return Get::$table($id);
    }
  } 

  $id = pdo_insert($table, $payload);
  if(is_numeric($id)) {
    return Get::$table($id);
  }
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

// Check if the posted email/password is valid
function authenticate_user($post) {
  $who = aget($post, 'email');
  if($who) {
    $user = Get::user(['email' => $who]);
    if ($user) {
      if( password_verify($post['password'], $user['password'])) {
        session_regenerate_id();
        $_SESSION['user_id'] = $user['id'];
        update_session_age();
        return true;
      }
    }
  }
  return false;
}

// Users who haven't accessed the site in 7 days are asked to re-login
function session_age_valid() {
  global $SESSION_MAX_AGE;
  $sess_last = aget($_SESSION, 'last_seen', $SESSION_MAX_AGE + 1);
  return ( time() - $sess_last < $SESSION_MAX_AGE );
}

function update_session_age() {
  $_SESSION['last_seen'] = time();
}

// Redirect to the login page and store where they want to go
function send_to_login_page() {
  $_SESSION['after_login_url'] = $_SERVER['REQUEST_URI'];
  header("Location: /admin/login");
  die();
}

// Check if the session is logged in and not too old
function require_authorized_user() {
  if( !$_SESSION['user_id'] ) {
    send_to_login_page();
  } elseif( !session_age_valid() ) {
    send_to_login_page();
  } else {
    update_session_age();
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
  $params['user'] = $params['user'] ?: $user ?: get_user_id();
  $stuff = parser($template, $params);

  $res = [];
  if(isset($user['phone'])) {
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
    'campaign_link' => 'https://olvr.io/dash/' . $campaign['id'],
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

  $user_id = aget($user, 'id') ?: aget($row, 'user_id') ?: aget($_SESSION, 'user.id');

  if(!$row) {
    $row = ['id' => pdo_insert('service', $service_obj)];

    if(!$user_id) {
      $user = create('user');
      $user_id = $user['id'];
    }
  }

  $data['user_id'] = $user_id;
  pdo_update('service', $row['id'], $data);
  return $user_id;
}

function fix_unicode($what) {
  return json_decode('"' . $what . '"');
}

function provides($filter) {
  $res = [];
  $serviceList = Many::service($filter);
  foreach($serviceList as $service) {
    if($service['service'] == 'instagram') {
      $data = $service['data'];
      $row = [
        'id' => $service['id'],
        'handle' => $service['username'],
        'logo' => aget($data, 'user.profile_pic'),
        'description' => aget($data, 'user.description'),
        'name' => aget($data, 'user.full_name'),
        'created_at' => aget($data, 'posts._t'),
        'photoList' => []
      ];
      foreach($row as $k => $v) {
        if(is_string($v)) {
          $row[$k] = fix_unicode($v);
        }
      }

      foreach(aget($data, 'posts.data') as $post) {
        error_log(json_encode($post));
        $row['photoList'][] = [
          'url' => aget($post, 'media_url'),
          'id' => aget($post, 'id'),
          'created_at' => aget($post, 'timestamp')
        ];
      }
      $res = array_merge($res, $row);
    }
    if($service['service'] == 'yelp') {
      foreach(aget($service, 'data.reviews.reviews') as  $review) {
        if($review['rating'] == 5) {
          $parts = explode('.', $review['text']);
          $res['bigtext'] = $parts[0] . ".<small>&#9733;&#9733;&#9733;&#9733;&#9733; - yelp</small>";
        }
      }

    }
  }
  return $res;
}


function proxy_get($url) {
  return file_get_contents("http://9ol.es/proxy.php?u=$url");
}

function insta_get_stuff($user) {
  function method2($user) {
    $raw = json_decode(proxy_get("https://instagram.com/$user/?__a=1"), true);
    $map = [];
    foreach([
      'biography' => 'description',
      'external_url' => 'website',
      'profile_pic_url_hd' => 'profile_pic',
      'full_name' => 'full_name'
    ] as $key => $reduce) {
      $map[$reduce] = aget($raw, "graphql.user.$key");
    }
    return $map;
  }

  function method1($user) {
    $mset = [];
    $fieldList = ['biography', 'external_url', 'full_name', 'profile_pic_url'];
    $fieldMap = [
      'biography' => 'description',
      'external_url' => 'website',
      'profile_pic_url' => 'profile_pic',
      'full_name' => 'full_name'
    ];
    $raw = file_get_contents("https://instagram.com/$user/");
    preg_match_all('/[{,]"('. implode('|', $fieldList)  . ')":"([^"]*)"/', $raw, $matches);
    if($matches) {
      $ix = 0;
      foreach($matches[1] as $field) {
        $mapname = $fieldMap[$field];
        if(!isset($mset[$mapname])) {
          // we need to do this otherwise we're double escaped.
          $mset[$mapname] = json_decode('"' . $matches[2][$ix] . '"');
        }
        $ix++;
      }
      return $mset;
    }
  }
  return method2($user);
}

function my_campaigns() {
  $user_id = get_user_id();
  $campaign_list = Many::campaigns(['user_id' => $user_id]);
  var_dump($campaign_list);
}
