<?
require $_SERVER['DOCUMENT_ROOT'] .  'AdDaemon/vendor/autoload.php';
session_start();

use Aws\S3\S3Client;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Twilio\Rest\Client;

$TWIL = [
  'num'  => '+18559248355',
  'sid'  => 'ACa061f336122514af845ea65fb1e6c2bb',
  'token'=> 'b39d95c162c1e9ad1893acbb61af8bb4'
];

$mypath = $_SERVER['DOCUMENT_ROOT'] . 'AdDaemon/lib/';
include_once($mypath . 'db.php');

$PORT_OFFSET = 7000;
$DAY = 24 * 60 * 60;
$PROJECT_LIST = ['LA', 'NY', 'REEF', 'CES'];
$DEFAULT_CAMPAIGN_MAP = [
  'none' => 1,
  'LA' => 1,
  'NY' => 2,
  'dev' => 3,
  'REEF' => 131
];

// Play time in seconds of one ad.
$PLAYTIME = 7.5;

function mapBy($obj, $key) {
  $res = [];
  foreach($obj as $row) {
    $res[$row[$key]] = $row;
  }
  return $res;
}

function aget($source, $keyList, $default = null) {
  if(!is_array($keyList)) {
    $keyStr = $keyList;
    $keyList = explode('.', $keyStr);

    $orList = explode('|', $keyStr);
    if(count($orList) > 1) {

      $res = null;
      foreach($orList as $key) {
        // this resolves to the FIRST valid value
        if($res === null) {
          $res = aget($source, $key);
        }
      }
      return ($res === null) ? $default : $res;
    }   
  }
  $key = array_shift($keyList);

  if($source && isset($source[$key])) {
    if(count($keyList) > 0) {
      return aget($source[$key], $keyList);
    } 
    return $source[$key];
  }

  return $default;
}

function jemit($what) {
  echo json_encode($what);
  exit;
}

function doSuccess($what) {
  return [
    'res' => true,
    'data' => $what
  ];
}

function doError($what) {
  return [
    'res' => false,
    'data' => $what
  ];
}

function missing($what, $list) {
  $res = [];
  foreach($list as $field) {
    if(!isset($what[$field])) {
      $res[] = $field;
    }
  }
  if(count($res)) {
    return $res;
  }
}


function find_missing($obj, $fieldList) {
  return array_diff($fieldList, array_keys($obj));
}

function inside_polygon($test_point, $points) {
  $p0 = end($points);
  $ctr = 0;
  foreach ( $points as $p1 ) {
    // there is a bug with this algorithm, when a point in "on" a vertex
    // in that case just add an epsilon
    if ($test_point[1] == $p0[1]) {
      $test_point[1] += 0.0000000001; #epsilon
    }

    // ignore edges of constant latitude (yes, this is correct!)
    if ( $p0[1] != $p1[1] ) {
      // scale latitude of $test_point so that $p0 maps to 0 and $p1 to 1:
      $interp = ($test_point[1] - $p0[1]) / ($p1[1] - $p0[1]);

      // does the edge intersect the latitude of $test_point?
      // (note: use >= and < to avoid double-counting exact endpoint hits)
      if ( $interp >= 0 && $interp < 1 ) {
        // longitude of the edge at the latitude of the test point:
        // (could use fancy spherical interpolation here, but for small
        // regions linear interpolation should be fine)
        $long = $interp * $p1[0] + (1 - $interp) * $p0[0];
        // is the intersection east of the test point?
        if ( $long > $test_point[0] ) {
          // if so, count it:
          $ctr++;
        }
      }
    }
    $p0 = $p1;
  }
  return ($ctr & 1);
}

function distance($pos1, $pos2) {
  list($lon1, $lat1) = $pos1;
  list($lon2, $lat2) = $pos2;

  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  // meters
  return $dist * 60 * 1397.60312636;
}


$_redis = false;
function get_redis() {
  global $_redis;
  if(!$_redis) {
    $_redis = new Redis();
    $_redis->connect('127.0.0.1', 6379);
  }
  return $_redis;
}


function pub($what) {
  $r = get_redis();
  $r->publish('goober', json_encode($what));
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

function create_screen($uid, $data = []) {
  global $PORT_OFFSET;
  // we need to get the next available port number
  $nextport = intval((db_connect())->querySingle('select max(port) from screen')) + 1;
  if($nextport < $PORT_OFFSET) {
    // gotta start from somewhere.
    $nextport = $PORT_OFFSET;
  }

  $data = array_merge($data, [
    'uid' => db_string($uid),
    'port' => $nextport,
    'first_seen' => 'current_timestamp',
    'last_seen' => 'current_timestamp'
  ]);

  $screen_id = db_insert('screen', $data);

  return Get::screen($screen_id);
}

function find_unfinished_job($campaignId, $screenId) {
  return Many::job([
    'campaign_id' => $campaignId,
    'screen_id' => $screenId,
    'completed_seconds < goal'
  ]);
}

function log_screen_changes($old, $new) {
  // When certain values change we should log that
  // they change.
  $deltaList = ['phone', 'removed', 'car', 'project', 'model', 'version', 'active', 'features'];
  foreach($deltaList as $delta) {
    if(!isset($new[$delta])) {
      continue;
    }
    $compare_before = $old[$delta];
    $compare_after = $new[$delta];

    if(is_array($old[$delta])) {
      $compare_before = json_encode($compare_before);
      $old[$delta] = $compare_before;
    }
    if(is_array($new[$delta])) {
      $compare_after = json_encode($compare_after);
      $new[$delta] = $compare_after;
    }

    $compare_before = trim($compare_before, "'");
    $compare_after = trim($compare_after, "'");
    if($compare_before !== $compare_after) {
      error_log("${old['id']}: '$compare_before' != '$compare_after'");
      db_insert('screen_history', [
        'screen_id' => $old['id'],
        'action' => db_string($delta),
        'old' => db_string($old[$delta]),
        'value' => db_string($new[$delta])
      ]);
      if($delta == 'features'){
        slack_alert_feature_change($old, $new);
      }
    }
  }
}

// Whenever we get some communication we know
// the screen is on and we may have things like
// lat/lng if we're lucky so let's try to gleam
// that.
function upsert_screen($screen_uid, $payload) {
  $screen = Get::screen(['uid' => $screen_uid]);

  if(!$screen) {
    $screen = create_screen($screen_uid);
  }

  $data = [
    // I don't care if it was manually removed, if we see it again
    // we are activating it again. That's how it works.
    'removed' => 0,
    'last_seen' => 'current_timestamp'
  ];
  $last = strtotime($screen['last_seen']);
  if(time() - $last > 150 && $screen['project'] != 'dev' && $screen['uptime'] == null) {
    //error_log($screen['uid'] . " " . time() . ' (' . (time() - $last) . ') ' );
    record_screen_on($screen, $payload);
  }
  if(!empty($payload['lat']) && floatval($payload['lat'])) {
    $data['lat'] = floatval($payload['lat']);
    $data['lng'] = floatval($payload['lng']);
    $data['last_loc'] = 'current_timestamp';
  }

  db_update('screen', ['uid' => db_string($screen_uid)], $data);

  return array_merge($screen, $data);
}

// After a screen runs a task it's able to respond... kind of 
// have a dialog if you will.
function response($payload) {
  //error_log(json_encode($payload));
  $missing = find_missing($payload, ['task_id', 'uid', 'response']);
  if($missing) {
    return doError("Missing fields: " . implode(', ', $missing));
  }
  $task_id = intval($payload['task_id']);

  $screen = Get::screen(['uid' => $payload['uid']]);

  if ($screen['last_task'] < $task_id) {
    db_update('screen', $screen['id'], ['last_task' => $task_id]);
  }

  return db_insert('task_response', [
    'task_id' => db_int($payload['task_id']),
    'screen_id' => db_int($screen['id']),
    'response' => db_string($payload['response'])
  ]);
}

// This is called from the admin UX
function command($payload) {
  $scope_whitelist = ['id', 'removed', 'project', 'model', 'version'];
  $idList = [];
  
  $field_raw = aget($payload, 'field');
  $value_raw = aget($payload, 'value');
  $command = aget($payload, 'command');

  if (in_array($field_raw, $scope_whitelist)) {
    $value = db_string($value_raw);
    $idList = array_map(
      function($row) { return $row['id']; }, 
      db_all("select id from screen where $field_raw = $value and active = true")
    );
  } else {
    return doError("Scope is wrong. Try: " . implode(', ', $scope_whitelist));
  }

  if(count($idList) == 0) {
    return doError("No screens match query");
  }

  if(empty($command)) {
    return doError("Command cannot be blank");
  }

  $taskId = db_insert('task', [
    'scope' => db_string("$field_raw:$value_raw"),
    'command' => db_string($command),
    'args' => db_string($payload['args'])
  ]);

  if(!$taskId) {
    return doError("Unable to create task");
  }

  $toInsert = [];
  foreach($idList as $id) {
    $toInsert[] = [
      'task_id' => $taskId,
      'screen_id' => $id 
    ];
  }

  db_insert_many('task_screen', $toInsert);

  return doSuccess( $taskId );
}

function default_campaign($screen) {
  global $DEFAULT_CAMPAIGN_MAP;
  $id = $DEFAULT_CAMPAIGN_MAP['none'];
  if($screen['project']) {
    $id = $DEFAULT_CAMPAIGN_MAP[$screen['project']];
  }
  return Get::campaign($id);
}

function record_screen_on($screen, $payload) {
    // this means this screen just turned on. 
    // "but wait, there's more!"
    // this also means the last time we heard from the screen, that is to say
    // $screen["uptime"] is the approximate uptime in seconds of the last runtime
    // Sooo here's what we do. We look for the most recent record of that car in 
    // the uptime_history like so:
    $uid = db_string($screen['uid']);
    $list = db_all("select * from uptime_history where action='on' and name=$uid order by id desc limit 1");
    if(count($list) > 0) {
      $opt = [
        'name' => $uid,
        'type' => db_string('screen'),
        'action' => db_string('off'),/*
        'created_at' => "datetime('$last')"*/
      ];
      if(isset($screen['uptime'])) {
        $first = $list[0];
        if(!$first['uptime']) {
          db_update('uptime_history', $first['id'], ['uptime' => $screen['uptime']]);
        }
        $str = intval(strtotime($first['created_at'])) + intval($screen['uptime']);
        $last = date('Y-m-d H:i:s', $str);
        $opt['created_at'] = "datetime('$last')";
      } else {
        error_log("${first['uid']} has no uptime");
      }

      if(isset($screen['lat'])) {
        $opt['lat'] = $screen['lat'];
        $opt['lng'] = $screen['lng'];
      }
      db_insert('uptime_history', $opt);
    } else {
      error_log("No records found for action on and name $uid");
    }

    $opt = [
      'name' => $uid,
      'type' => db_string('screen'),
      'action' => db_string('on')
    ];

    if(isset($payload['uptime'])) {
      $first = date('Y-m-d H:i:s', strtotime('now - ' . intval($payload['uptime']) . ' seconds'));
      $opt['created_at'] = "datetime('$first')";
    }

    if(isset($screen['lat'])) {
      $opt['lat'] = $screen['lat'];
      $opt['lng'] = $screen['lng'];
    }
    db_insert('uptime_history', $opt);
}

function ping($payload) {
  global $VERSION, $LASTCOMMIT;
  //error_log(json_encode($payload));

  // features/modem/gps
  foreach([
    'version', // consistent
    'imei', 'phone', 'Lat', 'Lng',                     // <v0.2-Bakeneko-347-g277611a
    'modem.imei', 'modem.phone', 'gps.lat', 'gps.lng', // >v0.2-Bakeneko-347-g277611a
    'version_time',                                    // >v0.2-Bakeneko-378-gf6697e1
    'uptime', 'features',                              // >v0.2-Bakeneko-384-g4e32e37
    'last_task',                                       // >v0.2-Bakeneko-623-g8989622
    'location', 'location.Lat', 'location.Lng',        // >v0.3-Chukwa-473-g725fa2c
    'last_uptime', 'last_task_result',                 // >v0.3-Chukwa-1316-g3b791be5-master
    'bootcount',
  ] as $key) {
    $val = aget($payload, $key);

    if($val) {
      $parts = explode('.', $key);
      $base = strtolower(array_pop($parts));
      if(is_array($val)) {
        $obj[$base] = $val;
      } else {
        $obj[$base] = db_string($val);
      }
    }
  }


  if(isset($payload['uid'])) {
    $uid = db_string($payload['uid']);

    $screen = Get::screen(['uid' => $payload['uid']]);

    if(!isset($payload['uptime'])) {
      error_log("Uptime not known for " . $payload['uid']);
    }
    if($screen) {
      if(isset($payload['uptime']) && intval($screen['uptime']) > intval($payload['uptime'])) {
        record_screen_on($screen, $payload);
      }
      //
      // If we are getting a bootcount from the screen that is less then what we
      // have previously recorded then we distrust the lying screen and keep our
      // own record.
      if(isset($obj['bootcount']) && $screen['bootcount'] > $obj['bootcount']) {
        unset($obj['bootcount']);
      }
    }

    $screen = upsert_screen($payload['uid'], $obj);
    //
    // After this point we know that $screen is valid.
    // 
    // IMPORTANT: We shouldn't permute any values in $obj
    // past this and expect them to be inserted into the screen
    // because ^^^ we just did that. 
    //

    if(isset($obj['last_uptime'])) {
      $bc = intval($payload['bootcount']) - 1;
      $opts = [
        'screen_id' => $screen['id'],
        'bootcount' => $bc
      ];

      $last_uptime = Get::runtime_history($opts);
      if(!$last_uptime) {
        $opts['uptime'] = floatval(aget($obj,'last_uptime.0'));
        $opts['booted_at'] = "datetime(" . db_string(aget($obj,'last_uptime.1')) . ")";
        db_insert('runtime_history', $opts);
      }
    }

    if(isset($obj['last_task_result'])) {
      $opts = [
        'screen_id' => $screen['id'],
        'task_id' => intval(aget($obj, 'last_task_result.0'))
      ];
      $last_task = Get::task_response($opts);
      if(!$last_task) {
        $opts['response'] = db_string(aget($obj,'last_task_result.1'));
        $opts['ran_at'] = 'datetime(' . db_string(aget($obj,'last_task_result.2')) . ")";
        db_insert('task_response', $opts);
      }
    }

  } else {
    return doError("UID needs to be set before continuing");
  }

  if($_SERVER['SERVER_NAME'] !== 'waivescreen.com') {
    $res = curldo('http://waivescreen.com/api/ping', $payload, ['verb' => 'post', 'json' => true]);
    $obj['port'] = aget($res, 'screen.port');
    error_log(" ${screen['port']} -> ${obj['port']} : forwarding request");
  }

  $obj['ping_count'] = intval($screen['ping_count']) + 1;

  log_screen_changes($screen, $obj);

  db_update('screen', $screen['id'], $obj);
  db_insert('ping_history', ['screen_id' => $screen['id']]);

  // We return the definition for the default campaign
  // The latest version of the software
  // and the definition of the screen.
  $res = [
    'screen' => $screen,
    'default' => default_campaign($screen)
  ];
  return task_inject($screen, $res);
}

function create_job($campaignId, $screenId) {
  $job_id = false;
  $ttl = get_campaign_remaining($campaignId);
  if($ttl < 0) {
    return false;
  }
  $campaign = Get::campaign($campaignId);

  if($campaign) {
    $goal = min($ttl, 60 * 4);

    $job_id = db_insert(
      'job', [
        'campaign_id' => db_int($campaignId),
        'screen_id' => $screenId,
        'job_start' => 'current_timestamp',
        'job_end' => db_string($campaign['end_time']),
        'last_update' => 'current_timestamp',
        'goal' => $goal
      ]
    );
  }
  if($job_id) {
    return Get::job($job_id);
  }
}

function update_job($jobId, $completed_seconds) {
  if($jobId) {
    return db_update('job', $jobId, [
      'completed_seconds' => $completed_seconds,
      'job_end' => 'current_timestamp'
    ]);
  } 
}

function task_master($screen) {
  // The crazy date math there is the simplest way I can get 
  // this thing to work, I know I know, it looks excessive.
  //
  // If you think you can do better crack open an sqlite3 shell
  // and start hacking.
  //
  return db_all("
    select * from task_screen 
      join task on task_screen.task_id = task.id
      where 
            task_screen.screen_id = {$screen['id']}
        and task.id > {$screen['last_task']} 
        and strftime('%s', task.created_at) + task.expiry_sec - strftime('%s', current_timestamp) > 0 
  ");
}

// ----
//
// end points
//
// ----

function schema($what) {
  global $SCHEMA;
  $table = aget($what, 'table');
  if($table) {
    return aget($SCHEMA, $table);
  }
}

function task_dump() {
  return [
    'task' => show('task', 'order by id desc'),
    'task_screen' => show('task_screen'),
    'response' => show('task_response'),
    'screen' => show('screen')
  ];
}

function screen_edit($data) {
  $whitelist = ['car', 'removed', 'phone', 'serial', 'project', 'model'];
  $update = [];
  foreach(array_intersect($whitelist, array_keys($data)) as $key) {
    $update[$key] = db_string($data[$key]);
  }
  $old = Get::screen($data['id']);
  log_screen_changes($old, $data);
  db_update('screen', $data['id'], $update);
  return Get::screen($data['id']);
}


// we need to find out if the screen has tasks we need
// to inject and then do so
//
// Why are we calling by reference like a weirdo? 
// We want the key to be empty if there's nothing
// that satisfies it.
function task_inject($screen, $res) {
  $taskList = task_master($screen);
  if(count($taskList) > 0) {
    if(empty($res['task'])) {
      // Vintage task
      $res['task'] = [];
      // modern tasklist
      $res['taskList'] = [];
    }
    foreach($taskList as $task) {
      $res['task'][] = [$task['command'],$task['args']];
      $res['taskList'][] = $task;
    }
  }
  $tasks = aget($res,'taskList');
  /*
  if ($tasks) {
    error_log($screen['uid'] . ' ' . json_encode($tasks));
  }
   */
  return $res;
}

function update_campaign_completed($id) {
  if($id) {
    // only update campaign totals that aren't our defaults
    return _query("update campaign 
      set completed_seconds=(
        select sum(completed_seconds) from job where campaign_id=$id
      ) where id=$id and is_default=0");
  }
  error_log("Not updating an invalid campaign: $id");
}
  
function inject_priority($job, $screen, $campaign) {
  $job['priority'] = aget($campaign, 'priority');
  return $job;
}

function sow($payload) {
  global $SCHEMA;
  $screen_dbg_id = 'jff6S9NbIGqv6cM3pA0gA';
  if(!isset($payload['uid'])) {
    return doError("UID needs to be set before continuing");
  } 
  $screen = upsert_screen($payload['uid'], $payload);

  pub([
    'type' => 'car',
    'car' => $screen['id'],
    'lat' => $screen['lat'],
    'lng' => $screen['lng']
  ]);

  $jobList = aget($payload, 'jobs', []);
  $campaignsToUpdateList = [];

  if($payload['uid'] == $screen_dbg_id) {
    error_log(json_encode($payload));
  }
  foreach($jobList as $job) {

    // this is the old system ... these machines
    // should just upgrade.
    $job_id = aget($job, 'job');
    // error_log(implode(' ', array_keys($job)));
    if(aget($job, 'id')) {
      // error_log("need to upgrade: {$payload['uid']}");
    } else if($job_id) {
      if (! update_job($job_id, $job['done']) ) {
        error_log("could not process job: " . json_encode($job));
      } else if(array_key_exists('location', $job)) {

        foreach($job['location'] as $row) {
          $row = array_merge($row, [
            'job_id' => $job_id,
            'screen_id' => $screen['id'],
            'campaign_id' => $job['camp']
          ]);
          $row['created_at'] = db_string($row['t']);
          unset($row['t']);
          db_insert('location_history', $row);
        }
      }

      if(!isset($job['campaign_id'])) {
        $job = Get::job($job_id);
      }
      if(isset( $job['campaign_id'] )) {
        db_update('screen', $screen['id'], ['last_campaign_id' => $job['campaign_id']]);
        $campaignsToUpdateList[] = $job['campaign_id'];
      }
    }
  }

  // Make sure we update our grand totals on a per campaign basis when it comes in.
  $uniqueCampaignList = array_unique($campaignsToUpdateList);
  foreach($uniqueCampaignList as $campaign_id) {
    if($campaign_id) {
      update_campaign_completed($campaign_id);
    } else {
      error_log("Couldn't update campaign");
    }
  }
  
  // --------
  // New Task Assignment
  // --------

  // If we are told to run specific campaigns
  // then we do that.
  $campaignList = show('screen_campaign', ['screen_id' => $screen['id']]);

  // If we have no campaigns to show then we 
  // start with all active campaigns.
  if(empty($campaignList) && $payload['lat']) {
    // If we didn't get lat/lng from the sensor then we just 
    // fallback to the default
    $test = [floatval($payload['lng']), floatval($payload['lat'])];
    $campaignList = array_filter(active_campaigns($screen), function($campaign) use ($test) {
      if(!empty($campaign['shape_list'])) {
        $isMatch = false;
        // This is important because if we have a polygon definition
        // then we actually don't want to show the ad outside that 
        // polygon.
        foreach($campaign['shape_list'] as $polygon) {
          if($polygon[0] === 'Polygon') {
            $isMatch |= inside_polygon($test, $polygon[1]); 
          } else if ($polygon[0] === 'Circle') {
            $isMatch |= distance($test, $polygon[1]) < $polygon[2];
          }
          if($isMatch) {
            return true;
          }
        }
      }
    });
    // so if we have existing outstanding jobs with the
    // screen id and campaign then we can just re-use them.
  }
  $server_response = task_inject($screen, ['res' => true]);

  $server_response['data'] = array_map(function($campaign) use ($screen) {
    $jobList = find_unfinished_job($campaign['id'], $screen['id']);
    //error_log(json_encode($jobList));
    if(!$jobList) {
      $jobList = [ create_job($campaign['id'], $screen['id']) ];
    }
    foreach($jobList as $job) {
      if(isset($job['id'])) {
        $job_res = array_merge([
          'job_id' => $job['id'],
          'campaign_id' => $campaign['id'],
          'asset' => $campaign['asset'],
          'asset_meta' => $campaign['asset_meta']
        ], $job);
        return inject_priority($job_res, $screen, $campaign);
      }
    }
  }, $campaignList);
  if($payload['uid'] == $screen_dbg_id) {
    error_log("response >> " . json_encode($server_response));
  }
  
  return $server_response; 
}

function curldo($url, $params = false, $opts = []) {
  $verb = strtoupper(isset($opts['verb']) ? $opts['verb'] : 'GET');

  $ch = curl_init();

  $header = [];
  if(isset($_SESSION['token']) && strlen($_SESSION['token']) > 2) {
    $header[] = "Authorization: ${_SESSION['token']}";
  }
    
  if($verb !== 'GET') {
    if(!isset($opts['isFile'])) {
      if(!$params) {
        $params = [];
      }
      if(isset($opts['json'])) {
        $params = json_encode($params);
        $header[] = 'Content-Type: application/json';
      } else {
        $params = http_build_query($params);
      }
    } else {
      $header[] = 'Content-Type: multipart/form-data';
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);  
    // $header[] = 'Content-Length: ' . strlen($data_string);
  }

  if($verb === 'POST') {
    curl_setopt($ch, CURLOPT_POST, 1);
  }

  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);  
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $res = curl_exec($ch);
  
  if(isset($opts['log'])) {
    $tolog = json_encode([
      'verb' => $verb,
      'header' => $header,
      'url' => $url,
      'params' => $params,
      'res' => $res
    ]);
    //var_dump(['>>>', curl_getinfo ($ch), json_decode($tolog, true)]);

    error_log($tolog);
  }

  if(isset($opts['raw'])) {
    return $res;
  }
  $resJSON = @json_decode($res, true);
  if($resJSON) {
    return $resJSON;
  }
  return $res;
}

function upload_s3($file) {
  // lol we deploy this line of code with every screen. what awesome.
  $credentials = new Aws\Credentials\Credentials('AKIAIL6YHEU5IWFSHELQ', 'q7Opcl3BSveH8TU9MR1W27pWuczhy16DqRg3asAd');

  // this means there was an error uploading the file
  // currently we'll let this routine fail and then hit
  // the error log
  if(empty($file['tmp_name'])) {}

  $parts = explode('/',$file['type']);
  $ext = array_pop($parts);
  $name = implode('.', [Uuid::uuid4()->toString(), $ext]);

  $s3 = new Aws\S3\S3Client([
    'version'     => 'latest',
    'region'      => 'us-east-1',
    'credentials' => $credentials
  ]);
  try {
    $res = $s3->putObject([
      'Bucket' => 'waivecar-prod',
      'Key'    => $name,
      'Body'   => fopen($file['tmp_name'], 'r'),
      'ACL'    => 'public-read',
    ]);
  } catch (Aws\S3\Exception\S3Exception $e) {
    throw new Exception("$file failed to upload");
  }
  // see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject
  return $name;
}

function feature_diff_recurse($a1, $a2, $key_prepend='') {
  $r = [];
  foreach($a1 as $k => $v) {
    if(is_array($v)) {
      if(array_key_exists($k, $a2)) {
        $tmp_a2 = $a2[$k];
      }
      else {
        $tmp_a2 = array();
      }
      $tmp_r = feature_diff_recurse($a1[$k], $tmp_a2, sprintf("%s%s,", $key_prepend, $k));
      $r = array_merge($r, $tmp_r);
    }
    else if(!array_key_exists($k, $a2)) {
      $r[] = sprintf("*%s%s*: %s", $key_prepend, $k, $a1[$k]);
    }
    else if($a1[$k] !== $a2[$k]) {
      $r[] = sprintf("*%s%s*: %s -> %s", $key_prepend, $k, $a2[$k], $a1[$k]);
    }
  }
  return $r;
}

function slack_alert_feature_change($old, $new) {
  $old_f = json_decode($old['features'], true);
  $new_f = json_decode($new['features'], true);
  $diff_txt = feature_diff_recurse($new_f, $old_f);
  $slack_url = 'https://hooks.slack.com/services/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';
  $msg = [
    'text' => sprintf("*Feature changes on %s:*\n>%s", $old['uid'], implode("\n>", $diff_txt))
  ];
  curldo($slack_url, $msg, ['verb' => 'post', 'json' => True]);
}

function show($what, $clause = []) {
  global $SCHEMA;
  $me = me();
  $where = [];
  //error_log(json_encode($_SESSION));
  if($me) {
    $schema = $SCHEMA[$what];
    if($me['organization_id'] && isset($schema['organization_id'])) {
      $where['organization_id'] = $me['organization_id'];
    }
  }

  $fields = '*';
  if(is_array($clause)) {
    if(array_key_exists('fields', $clause)) {
      $fields = $clause['fields'];
      unset($clause['fields']);
    }
    $clause = array_merge($where, $clause);
    if( !empty($clause) ) {
      $clause = " where " . implode(' and ', sql_kv($clause));
    } else {
      $clause = '';
    }
  }
  if(strpos($clause, 'order by') === false) {
    $clause .= ' order by id desc';
  }
  //error_log(preg_replace('/\s+/', ' ', "select * from $what $clause"));
  return db_all("select $fields from $what $clause", $what);
}

function create($table, $payload) {
  // TODO: whitelist the tables
  global $SCHEMA;
  foreach($payload as $k => $v) {
    $typeRaw = aget($SCHEMA, "$table.$k");
    if($typeRaw) {
      $parts = explode(' ', $typeRaw);
      $type = $parts[0];
      if($k === 'password') {
        $v = password_hash($v, PASSWORD_BCRYPT);
      }
      if($type == 'text') {
        $payload[$k] = db_string($v);
      }
      if(empty($payload[$k])) {
        unset($payload[$k]);
      }
    } else {
      unset($payload[$k]);
    }
  }

  $id = aget($payload, 'id');
  if($id) {
    return db_update($table, $id, $payload);
  } 

  return db_insert($table, $payload);
}


function make_infinite($campaign_id) {
  db_update('campaign', $campaign_id, [
    'goal_seconds' => pow(2,31),
    'end_time' => '2100-01-01 00:00:00'
  ]);
}

function active_campaigns($screen) {
  //  end_time > current_timestamp     and 
  //return [];
  return show('campaign', "where 
          is_default = 0 
    and   completed_seconds < goal_seconds 
    and   project = '${screen['project']}'
    order by start_time desc");
}

function campaigns_list($opts = []) {
  $filter = [];

  if(isset($opts['id'])) {
    // ah, with this slight increase in bullshit we 
    // can do comma separated mulit-request support.
    // What a life.
    $idList = array_map(
      function($row) { 
        return intval($row); 
      }, 
      explode(',', $opts['id'])
    );
    $filter[] = 'id in (' . implode(',', $idList) . ')';
  }
  $append = '';

  if($filter) {
    $append = 'where ' . implode(' and ', $filter);
  }

  return show('campaign', $append);
}

function path($data) {
  $heatmap = heatmap($data, 'job_id,screen_id,lng,lat');
  $nodup = [];
  $path = [];
  $last = [0,0,0,0];
  $path_sig = [0,0];

  foreach($heatmap as $x) {
    if($x[0] !== $path_sig[0] || $x[1] !== $path_sig[1]) {
      if(count($path) > 1) {
        $nodup[] = $path;
      }
      $path = [];
      $path_sig = $x;
      $last = $x;
    } else if($x[2] === $last[2] && $x[3] === $last[3]) {
      continue;
    }
    $path[] = array_slice($x, 2);
    $last = $x;
  }
  return $nodup;
}

function heatmap($data, $fields = 'lng,lat') {
  $campaign = Get::campaign($data);

  if($campaign) {
    $campaignId = $campaign['id'];
  } else if(isset($data['id'])) {
    $campaign = [];
    $campaignId = $data['id'];
  } else {
    return doError("Campaign not found");
  }  

  return array_map(function ($n) { 
    return array_values($n);
  }, Many::location_history(['campaign_id' => $campaignId], $fields));
}

function campaign_history($data) {
  $campaign = Get::campaign($data);

  if($campaign) {
    $campaignId = $campaign['id'];
  } else if(isset($data['id'])) {
    $campaign = [];
    $campaignId = $data['id'];
  } else {
    return doError("Campaign not found");
  }  

  return Many::location_history(['campaign_id' => $campaignId]);
}

function circle($lng = -118.390412, $lat = 33.999819, $radius = 3500) {
  return [
    'lat' => $lat, 'lng' => $lng, 'radius' => $radius,
    'shape_list' => [[ 'Circle', [$lng, $lat], $radius ]]
  ];
}

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
      'goal_seconds' => $PLAYTIME * 200,
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

  $_SESSION['campaign_id'] = $campaign_id;
  $_SESSION['ces_id'] = $ces_id;
  $_SESSION['phone'] = $data['phone'];

  //text_rando($number, "Thanks for trying oliver, free exclusively at CES. Your message will be shown on the streets of Vegas shortly. We'll text you after with a link so you can see where it played!");
  return [
    'campaign_id' => $campaign_id,
    'ces_id' => $ces_id
  ];
}

// This is the first entry point ... I know naming and caching
// are the hardest things.
//
// According to our current flow we may not know the user at the time
// of creating this
function campaign_create($data, $fileList, $user = false) {
  global $DAY, $PLAYTIME;

  $props = array_merge(circle(),
    [
      'project' => db_string('LA'),
      'start_time' => db_date(time()),
      'goal_seconds' => 240,
      'end_time' => db_date(time() + $DAY * 7),
      'asset' => [],
    ],
  );

  foreach(['title','organization_id','brand_id'] as $key) {
    if(isset($data[$key])) {
      if($key == 'title') {
        $props[$key] = db_string($data[$key]);
      } else {
        $props[$key] = $data[$key];
      }
    }
  }

  # This means we do #141
  if(aget($data, 'secret') === 'b3nYlMMWTJGNz40K7jR5Hw') {
    $ref_id = db_string(aget($data, 'ref_id'));

    if($ref_id) {
      $campaign = Get::campaign(['ref_id' => $ref_id]);
      $props['ref_id'] = $ref_id;
      $props['asset'] = [aget($data, 'asset')];
    }

    if(!$campaign) {
      $campaign_id = db_insert( 'campaign', $props );
    } else {
      error_log("Don't know how to proceed");
      //$campaign_id = $campaign['id'];
      //db_update('campaign', $campaign_id, ['asset' => $asset]);
    }
    return doSuccess(Get::campaign($campaign_id));
  }

  foreach($fileList as $file) {
    $props['asset'][] = upload_s3($file);
  }

  return db_insert('campaign', $props);
}

function campaign_update($data, $fileList, $user = false) {
  $assetList = [];
  $campaign_id = aget($data,'campaign_id|id');
  if(empty($campaign_id)) {
    return doError("Need to set the campaign id");
  }

  if(!$fileList) {
    $obj = [];
    foreach($data as $k => $v) {
      if (in_array($k, ['goal_seconds','active','lat','lng','radius'])) {
        $obj[$k] = db_string($v);
      }
    }
    if(!empty($data['geofence'])) {
      // first we filter for circles to do lat/lng/radius
      foreach($data['geofence'] as $geo) {
        if($geo[0] === 'Circle') {
          // the overlay system is lng/lat
          list($obj['lng'], $obj['lat']) = $geo[1];
          $obj['radius'] = $geo[2];
          break;
        }
      }
      // then we assign everything to the list.
      $obj['shape_list'] = $data['geofence'];
    }
    db_update('campaign', $campaign_id, $obj);
  } else {
    if(aget($data, 'append')) {
      $campaign = Get::campaign($campaign_id);
      $assetList = $campaign['asset'];
    }

    foreach($fileList as $file) {
      $assetList[] = upload_s3($file);
    }

    db_update('campaign', $campaign_id, ['asset' => db_string(json_encode($assetList))]);
  }
  return $campaign_id;
}

function kpi($opts) {
  $window_size = aget($opts, 'time', 3 * 24 * 60 * 60);
  $tz_offset = 8 * 3600;
  if($window_size < 12) {
    $window_size *= 86400;
  }
  if($window_size < 100) {
    $window_size *= 3600;
  }
  $distance = aget($opts, 'distance', 0.005);

  $res = [
    'window' => $window_size,
    'distance' => $distance,
    'ratio' => [],
    'runtime' => db_all("select uptime, d * $window_size + $tz_offset as unix from (
      select sum(uptime) as uptime, (strftime('%s', created_at) - $tz_offset) / $window_size as d from uptime_history 
        where uptime is not null 
          and not (abs(lat - 34.085121) < $distance and abs(lng - -118.340250) < $distance) 
          and not (abs(lat - 34.017979) < $distance and abs(lng - -118.409471) < $distance) 
          group by d) order by unix")
  ];

  foreach(['car','screen'] as $type) {
    $inner = "select distinct count(*) as times_seen, name, (strftime('%s', created_at) - $tz_offset) / $window_size as d from uptime_history 
        where type = '$type' and action = 'on' 
          and not (abs(lat - 34.085121) < $distance and abs(lng - -118.340250) < $distance) 
          and not (abs(lat - 34.017979) < $distance and abs(lng - -118.409471) < $distance) 
          group by d,name";
    $res[$type] = db_all("select count(*) as num, d * $window_size + $tz_offset as unix from ($inner) group by d");
  }

  $run_ix = 0;
  for($ix = 0; $ix < count($res['car']); $ix++) {
    $time = $res['screen'][$ix]['unix'];
    $cars = $res['car'][$ix]['num'];
    $screens = $res['screen'][$ix]['num'];
    $row = [
      'screen_perc' => $screens / $cars,
      'car_count' => $cars,
      'screen_count' => $screens,
      'unix' => $time
    ];
    if(aget($res, "runtime.$run_ix.unix") == $time) {
      $uptime = aget($res, "runtime.$run_ix.uptime");
      $row['screen_avg'] = $uptime / $screens;
      $row['screen_time'] = $uptime;
      $row['screen_avg_hrday'] = $row['screen_avg'] / ($window_size / 86400) / (3600);
      $run_ix ++;
    }
    $res['ratio'][] = $row;
  }
  foreach(['screen','car','runtime'] as $k) {
    unset( $res[$k] );
  }

  return $res;
}

function screen_history($param) {
  return db_all("select action,value,old,created_at from screen_history where screen_id=${param['id']} order by id desc");
}
  
function infer() {
  $all = db_all("SELECT id,name,type,action,lat,lng,strftime('%s', created_at) as unix from uptime_history where action='on' order by id asc");
  $ix = 0;
  $window = [$all[$ix]];
  $wtf = [];
  $delta = 60 * 20;
  $xref = [];
  while(true) {
    $ix ++;
    if($ix >= count($all)) {
      break;
    }
    // this means we move our window forward.  The item we are
    // about to purge will be cross referenced with everything 
    // else.
    while(count($window) > 0 && $all[$ix]['unix'] - $delta > $window[0]['unix']) {
      $toTry = 0;
      for($iz = 0; $iz < count($window); $iz++) {
        if($window[$iz]['unix'] - $delta / 2 > $window[0]['unix']) {
          $toTry = $iz;
          break;
        }
      }
      $toRef = $window[$toTry];
      array_shift($window);
      if(isset($wtf[$toRef['id']])) {
        break;
      }
      $wtf[$toRef['id']] = true;
      $name = $toRef['name'];
      $action = $toRef['action'];
      $type = $toRef['type'];
      if(!array_key_exists($name, $xref)) {
        $xref[$name] = ['ttl' => 0];
      }
      $xref[$name]['ttl']++;
      $cnodupes = [];
      foreach($window as $comp) {
        if($type !== $comp['type'] && $action === $comp['action']) {
          $cname = $comp['name'];
          if(isset($cnodupes[$cname])) {
            continue;
          }
          $cnodupes[$cname] = true;
          if(!array_key_exists($cname, $xref[$name])) {
            $xref[$name][$cname] = [0, 0, 0];
          }
          $xref[$name][$cname][0]++;
          if(!empty($toRef['lat']) && !empty($comp['lat'])) {
            $xref[$name][$cname][1] += distance(
              [$toRef['lat'], $toRef['lng']],
              [$comp['lat'], $comp['lng']]
            );
          }
        }
      }
    }

    $window[] = $all[$ix];
  }
  foreach($xref as $key => $val) {
    $ttl = $val['ttl'];
    $xref[$key]['current'] = null;
    if (strlen($key) < 10) {
      $xref[$key]['type'] = 'car';
      $current = Get::screen([
        'removed' => 0,
        'car' => ['like' => $key ]
      ]);
      if($current) {
        $xref[$key]['current'] = $current['uid'];
        $xref[$key]['version'] = $current['version'];
      } 
    } else {
      $xref[$key]['type'] = 'screen';
      $current = Get::screen(['uid' => $key]);
      if($current) {
        $xref[$key]['current'] = $current['car'];
        foreach(['version', 'lat', 'lng'] as $k) {
          $xref[$key][$k] = $current[$k];
        } 
      }
    }
    foreach($val as $k1 => $v1) {
      if($k1 == 'ttl') {
        continue;
      }
      $avg = $v1[1]/$v1[0];
      if(
        $v1[0] / $ttl < 0.40 ||
        // this tries to fix stuck gps
        ($avg > 7000 && $ttl > 3 && $v1[0] / $ttl < 0.80) || $ttl < 3
      ) {
        unset($xref[$key][$k1]);
      } else {
        $xref[$key][$k1] = [ $v1[0] / $ttl, $avg ];
      }
    }
  }
  return $xref;
}

function eagerlocation($all) {
  $screen = Get::screen(['uid' => $all['uid']]);

  pub([
    'type' => 'car',
    'car' => $screen['id'],
    'lat' => $all['lat'],
    'lng' => $all['lng']
  ]);

  return db_update('screen', 
    ['uid' => db_string($all['uid'])], [
      'lat' => $all['lat'],
      'lng' => $all['lng']
    ]
  );
}

function ignition_status($payload) {
  $car = aget($payload, 'name');

  if(strpos(strtolower($car), 'csul') !== false) {
    return [];
  }
  if(isset($payload['ignitionOn'])) {
    $state = $payload['ignitionOn'];
  } else {
    return error_log("Unable to find 'ignitionOn' in payload: " . json_encode($payload));
  }
  $state = db_string($state ? 'on' : 'off');

  if($car) {
    $qstr = "select * from screen where car like '$car'";
    $res = (db_connect())->querySingle($qstr, true);
  } else {
    return error_log("Unable to find 'name' in payload: " . json_encode($payload));
  }

  db_insert('uptime_history', [
    'name' => db_string($car),
    'type' => db_string('car'),
    'lng' => floatval($payload['lng']),
    'lat' => floatval($payload['lat']),
    'action' => $state
  ]);

  if($res) {
    $uid = aget($res, 'uid');
  } else {
    return false;//error_log("Unable to find screen for $car");
  }

  if($uid) {
    return db_update('screen', ['uid' => db_string($uid)], [
      'ignition_state' => $state,
      'ignition_time' => 'current_timestamp'
    ]);
  } else {
    return error_log("Could not find a uid in the result of ignition_status for $car: ($qstr) " . json_encode($res) );
  }
}

function getUser() {
  if(isset($_SESSION['user_id'])) {
    return Get::user($_SESSION['user_id']);
  }
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

function me() {
  if(isset($_SESSION['user'])) {
    return $_SESSION['user'];
  }
}
function signup($all) {
  $organization = aget($all, 'organization');
  $org_id = create('organization', ['name' => $organization]);
  $all['organization_id'] = $org_id;
  $all['role'] = 'Manager';
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

function logout() {
  session_destroy();
}



function slackie($where, $what) {
  $payload = [
    'channel' => $where,
    'text' => $what
  ];
  $res = curldo("https://hooks.slack.com/services/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", $payload, ['verb' => 'post', 'json' => true]);
  return $res;
}

// rideflow
function goober_link($which) {
  return " <http://oliverces.com/driver.php?id=" . $which['id'] . "|Details>";
}

function goober_up($which, $what, $postop = []) {
  if($what === 'available') {
    $postop['goober_id'] = null;
  }
  $surgery = array_merge($postop, ['goober_state' => db_string($what)]); 

  db_update('screen', $which['id'], $surgery);

  pub([
    'type' => 'update',
    'car' => $which['id'],
    'state' => $what
  ]);
}

function goober_allowed($all, $list) {
  return in_array($all['goober_state'], $list);
}

function cancel($all) {
  goober_up($all, 'available'); 
  slackie("#goober", ":broken_heart: The impudent malcontent canceled the ride with ${all['car']}." . goober_link($all));
}

function request($all) {
  if (!goober_allowed($all, ['available'])) {
    slackie("#goober-flow", ":collision: Freakish shit happening with ${all['car']}, refusing to satisfy a request, car is not available.");
    return false;
  } else {
    $id = db_insert('goober', ['screen_id' => $all['id']]);

    goober_up($all, 'reserved', ['goober_id' => $id]); 

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
  slackie("#goober-flow", ":checkered_flag: ${all['car']} finished the ride");
  return available($all);
}

function available($all) {
  goober_up($all, 'available');

  slackie("#goober-flow", ":person_doing_cartwheel: ${all['car']} is available for goobering!");
}

function unavailable($all) {
  goober_up($all, 'unavailable');

  slackie("#goober-flow", ":slot_machine: ${all['car']} is no longer goobering...");
}
