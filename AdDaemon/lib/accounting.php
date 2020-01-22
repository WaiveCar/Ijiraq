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

