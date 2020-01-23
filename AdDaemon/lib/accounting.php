<?
$TWIL = [
  'num'  => '+18559248355',
  'sid'  => 'ACa061f336122514af845ea65fb1e6c2bb',
  'token'=> 'b39d95c162c1e9ad1893acbb61af8bb4'
];

require $_SERVER['DOCUMENT_ROOT'] .  'AdDaemon/vendor/autoload.php';
use Twilio\Rest\Client;
include_once("lib.php");

function get_user() {
  if(isset($_SESSION['user_id'])) {
    return Get::user($_SESSION['user_id']);
  }
}

function me() {
  if(isset($_SESSION['user'])) {
    return $_SESSION['user'];
  }
}

function signup($all) {
  //$organization = aget($all, 'organization');
  //$org_id = create('organization', ['name' => $organization]);
  $user_id = create('user', $all);
  if($user_id) {
    $_SESSION['user'] = Get::user($user_id);
  }
  return $user_id;
}

function login($all) {
  $who = aget($all, 'email');
  if($who) {
    $user = Get::user(['email' => $who]);
    if ($user) {
      if( password_verify($all['password'], $user['password'])) {
        $_SESSION['user'] = $user;
        return doSuccess($user);
      } else {
        return doError("Wrong password");
      }
    }
  }
  return doError("User $who not found");
}

function add_service($user, $service_obj) {
}

function get_service($user, $service_string) {
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

function render($M5YFgsLGQian24eTfLEQIA_template, $opts) {
  extract($opts);
  ob_start();
    include("{$_SERVER['DOCUMENT_ROOT']}AdDaemon/templates/email/$M5YFgsLGQian24eTfLEQIA_template.html");
    $M5YFgsLGQian24eTfLEQIA_res = trim(ob_get_contents());
  ob_end_clean();
  return $M5YFgsLGQian24eTfLEQIA_res;
}

function parser($user, $template) {
  $rendered = [];

  foreach(['_header', '_footer', $template] as $what) {
    $rendered[$what] = render($what, $user);
  }

  $rendered[$template] = explode('\n', $rendered[$template]);

  return [
    'sms'     => $rendered[$template][0],
    'subject' => $rendered[$template][1],
    'email'   => $rendered['_header'] + array_slice($rendered[$template], 2) + $rendered['_footer']
  ];
}

function send_message($user, $template) {
  $EMAIL= [
    'sender' => 'Waive <support@waive.com>',
    'api_key'=> 'key-2804ba511f20c47a3c2dedcd36e87c92'
  ];

  $stuff = parser($user, $template);
  text_rando($user['number'], $stuff['sms']);

  $res = curldo(
    'https://api.mailgun.net/v3/waive.com/messages', [
      'from'    => $EMAIL['sender']
      'to'      => $user['email'],
      'subject' => $stuff['subject'],
      'html'    => $stuff['email']
    ], [
      'auth' => [
        'user' => 'api', 
        'password' => $EMAIL['api_key']
      ],
      'verb' => 'post', 
      'json' => true
    ]
  );



