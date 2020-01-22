<?


// goober
/*
  } else if(array_search($func, ['available', 'unavailable', 'driving', 'finish', 'decline', 'accept', 'request', 'cancel']) !== false) {
    post_return(
      $func(array_merge(Get::screen($all['id']), ['kv' => $all]), $verb)
    ); 
 */
function campaign_ces_create($data) {
  global $PLAYTIME, $DAY;

  // we should have message and phone
  $props = [];
  foreach(['message','phone'] as $key) {
    $props[$key] = db_string($data[$key]);
  }

  // this will give us a ces id which we can use
  // for the campaign creation
  $ces_id = db_insert('ces', $props);

  $props = array_merge(circle( -115.033, 35.083, 410000 ),
    [
      'project' => db_string('CES'),
      'start_time' => db_date(time()),
      'goal_seconds' => $PLAYTIME * 500,
      'end_time' => db_date(time() + $DAY * 3),
      'is_approved' => true,
      'asset' => ["http://waivescreen.com/Products/ces/ces_oliver.php?id=$ces_id"],
      'asset_meta' => [
        ['nocache' => true, 'duration' => $PLAYTIME, 'url' => "http://waivescreen.com/Products/ces/ces_oliver.php?id=$ces_id"]
      ]
    ],
  );

  $campaign_id = db_insert( 'campaign', $props );

  db_update('ces', $ces_id, ['campaign_id' => $campaign_id]);

  $ph = aget($data, 'phone', '');
  if($ph[0] != '+') {
    $digits_only = preg_replace('/[^\d]/', '', $ph);

    // This looks like an american number.
    if(strlen($digits_only) == 10) {
      $candidate = "+1$ph";
    // this looks like an american number with a leading 1.
    } else if (strlen($digits_only) == 11 && $ph[0] == '1') {
      $candidate = "+$ph";
    } else {
      // otherwise it may be an international - we actually do the same thing.
      $candidate = "+$ph";
    }
  } else {
    $candidate = $ph;
  }
  $phone = $candidate;

  text_rando($candidate, "Thanks for using oliver, free exclusively at CES. Your message will be shown on the streets of Vegas shortly. You can see the progress at http://olvr.io/?id=$ces_id");

  return [
    'campaign_id' => $campaign_id,
    'ces_id' => $ces_id
  ];
}
function emit_js() {
  $params = [
    'admin' => false,
    'manager' => false,
    'viewer' => true
  ];
  
  if(isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    $params = array_merge($params, $user);
    $role = strtolower($user['role']);
    unset($params['password']);
    $params['manager'] = true;
    if($role === 'admin') {
      $params['admin'] = true;
    }
  }

  echo 'self._me = ' . json_encode($params);
}

function emit_css() {
  header("Content-type: text/css");
  $manager = false;
  $admin = false; 
  if(isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    $role = strtolower($user['role']);
    $manager = true;
    if($role === 'admin') {
      $admin = true;
    }
    echo '.p-nobody { display: none }';
  }
  if(!$manager) {
    echo '.p-manager { display: none }';
  }
  if(!$admin) {
    echo '.p-admin { display: none }';
  }
  return;
}

// rideflow
function goober_link($which) {
  return " <https://oliverces.com/driver.php?id=" . $which['id'] . "|Details>";
}

function goober_up($which, $what, $postop = [], $broadcast = []) {
  $poster = [
    'type' => 'update',
    'id' => $which['id'],
    'state' => $what
  ];
  if($what === 'available') {
    $postop['goober_id'] = null;
  } 
  $surgery = array_merge($postop, ['goober_state' => db_string($what)]); 

  db_update('screen', $which['id'], $surgery);

  $obj =  array_merge($postop, $poster, $broadcast);
  $goober = false;
  if(!empty($which['goober_id'])) {
    error_log("I'm here because I have the id");
    $goober = Get::goober($which['goober_id']);
    error_log("Now I have the object");
  }

  /*
  if(!$goober && !empty($obj['type']) && $obj['type'] == 'update' && !empty($obj['user_id'])) {
    $goober = Get::goober($obj['id']);
    $obj['user_id'] = $goober['user_id'];
  }
   */

  if($goober) {
    error_log("Now I'm populating the object");
    $obj['user_id'] = $goober['user_id'];
    $obj['goober_id'] = $goober['id'];
  }

  pub( $obj );
}

function goober_allowed($all, $list) {
  return in_array($all['goober_state'], $list);
}

function cancel($all) {
  $goober = Get::goober($all['goober_id']);
  pub([
    'type' => 'update',
    'id' => $all['id'],
    'goober_id' => $all['goober_id'],
    'user_id' => $goober['user_id'],
    'state' => 'canceled'
  ]);

  goober_up($all, 'available'); 
  slackie("#goober", ":broken_heart: The impudent malcontent canceled the ride with ${all['car']}." . goober_link($all));
}

function goobup($all) {
	pdo_connect();
  return "hi";
  $screen = Get::screen(['goober_id' => $all['id']]);
  slackie("#goober", ":phone: The goober in ${screen['car']} should be called at ${all['number']}." );

  db_update('goober', $all['id'], ['phone' => db_string($all['number'])]);
}

function request($all) {
  if (!goober_allowed($all, ['available'])) {
    slackie("#goober-flow", ":collision: Freakish shit happening with ${all['car']}, refusing to satisfy a request, car is not available.");
    return false;
  } else {
    error_log(json_encode($all));

    $id = db_insert('goober', [
      'user_id' => db_string($all['kv']['user_id']),
      'lat' => db_string($all['kv']['lat']),
      'lng' => db_string($all['kv']['lng']),
      'screen_id' => $all['id']
    ]);

    goober_up($all, 'reserved', 
      ['goober_id' => $id], 
      [ 'lat' => $all['kv']['lat'],
        'lng' => $all['kv']['lng'],
        'user_id' => $all['kv']['user_id']
      ]); 

    slackie("#goober", ":busstop: Some freeloading loafer wants to use ${all['car']}." . goober_link($all));
    slackie("#goober-flow", ":busstop: Some freeloading loafer wants to use ${all['car']}." . goober_link($all));
  }
  return $id;
}

function accept($all) {
  goober_up($all, 'confirmed');

  slackie("#goober-flow", ":runner: The eager driver of ${all['car']} accepted the ride." . goober_link($all));
}

function decline($all) {
  // a decline of a ride means the person probably can't do another
  // ride either so we go to unavailable ... as a "smart" move
  goober_up($all, 'unavailable');

  slackie("#goober-flow", ":cold_sweat: The overloaded driver of ${all['car']} declined the ride." . goober_link($all)); 
}

function driving($all) {
  goober_up($all, 'driving');

  slackie("#goober-flow", ":carousel_horse: The goober in ${all['car']} is off!" . goober_link($all)); 
}

function finish($all) {
  $goober = Get::goober($all['goober_id']);
  pub([
    'type' => 'update',
    'id' => $all['id'],
    'goober_id' => $all['goober_id'],
    'user_id' => $goober['user_id'],
    'state' => 'finished'
  ]);
  slackie("#goober-flow", ":checkered_flag: ${all['car']} finished the ride");
  return available($all);
}

function available($all) {
  goober_up($all, 'available', [], [
    'lat' => $all['lat'],
    'lng' => $all['lng']
  ]);

  slackie("#goober-flow", ":person_doing_cartwheel: ${all['car']} is available for goobering!");
}

function unavailable($all) {
  goober_up($all, 'unavailable');

  slackie("#goober-flow", ":slot_machine: ${all['car']} is no longer goobering...");
}
