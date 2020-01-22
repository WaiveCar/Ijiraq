<?

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

$EMAIL= [
  'sender' => 'Waive <support@waive.com>',
  'domain' => 'waive.com',
  'api_key'=> 'key-2804ba511f20c47a3c2dedcd36e87c92'
]

def parser(which, user):
  # first render the template
  rendered = {}
  for what in ['_header', '_footer', which]:
    rendered[what] = render_template('templates/email/{}.html'.format(what), user=user)

  return { 
    'sms': rendered[which][0],
    'subject': rendered[which][1],
    'email': rendered['_header'] + rendered[which][2:] + rendered['_footer']
  }

def send_message(recipient, subject, body):
  response = requests.post(
    'https://api.mailgun.net/v3/{}/messages'.format(config['domain']),
    auth=("api", config['api_key']),
    data={
      'from': config['sender'],
      'to': [config['recipient'] if 'recipient' in config else recipient],
      'subject': subject,
      'html': body
    }
  )
  try:
    response.raise_for_status()
  except requests.exceptions.HTTPError as e: 
    raise e
  return response



