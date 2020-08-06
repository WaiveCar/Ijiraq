<?php
date_default_timezone_set('UTC');
use Ramsey\Uuid\Uuid;

$DBPATH = "/var/db/waivescreen/main.db";
$JSON = [
  'pre' => function($v, $ignore, $type) { 
    if ($v === null) { return $v; } 
    if (!is_string($v)) { $v = json_encode($v); }
    return $type === 'pdo' ? $v : db_string($v); 
  },
  'post' => function($v, $type) { 
    if (!$v) { return $v; } 
    return json_decode($v, true); 
  }
];

$RULES = [
  'campaign' => [ 
    'table' => [
      'pre' => function($obj, $type) {
        if(!isset($obj['uuid'])) {
          $obj['uuid'] = Uuid::uuid4()->toString();
          if($type !== 'pdo') {
            $obj['uuid'] = db_string($obj['uuid']);
          }
        }
        return $obj;
      },
      'post' => function($obj) {
        $obj['play_count'] = ($obj['completed_seconds'] + $obj['boost_seconds']) / max(7.5, $obj['duration_seconds']);
        return $obj;
      }
    ],
    'columns' => [
      'shape_list' => $JSON,
      'flags' => $JSON,
      'asset_meta' => [
        'pre' => $JSON['pre'],
        'post' => function($v, &$obj) {
           $v = json_decode($v, true);
           if(!is_array($v) || array_key_exists('url', $v)) {
             $v = [ $v ];
           }
           // temporary. we are reconstructing the legacy
           // asset interface until all the cars ignore this.
           $asset = [];

           foreach($v as &$row) {
             if(is_string($row)) {
               $row = ['url' => $row];
             }
             if(!is_array($row)) {
               error_log($row);
             }
             if(strpos($row['url'], 'http') === false) {
               $row['url'] = 'http://waivecar-prod.s3.amazonaws.com/' . $row['url'];
             } 
             $asset = $row['url'];
           }
           if(!$obj['asset']) {
             $obj['asset'] = json_encode($asset);
           }
           return $v;
        }
      ],
      'asset' => [
        'pre' => $JSON['pre'],
        'post' => function($v) {
           if(is_array($v)) {
             error_log(json_encode(debug_backtrace()));
           }
           $v = json_decode($v, true);
           if(!is_array($v)) {
             $v = [ $v ];
           }

           return array_map(function($m) {
             if(strpos($m, 'http') === false) {
               return 'http://waivecar-prod.s3.amazonaws.com/' . $m;
             } 
             return $m;
           }, $v);
        }
      ]
    ]
  ],
  'service' => [
    'columns' => [
      'data' => [
        'pre' => function($v, $obj) {
          // we are adding things on and
          // giving them dates in the service.data
          // row
          //
          // bugbug: this approach doesn't normally work
          // unless we already have retrieved the record.
          //
          // We *could* retriece the record here but then we
          // have a recursion problem unless we're supppper
          // careful.
          $start = $obj['data'];

          if(!$start) {
            $start = [];
          }

          foreach(array_keys($v) as $k) {
            $v[$k]['_t'] = date('c');
          }
          return json_encode(array_merge($start, $v));
        },
        'post' => $JSON['post']
      ]
    ]
  ],
  'screen' => [
    'columns' => [
      'features' => $JSON,
      'panels' => $JSON,
      'location' => $JSON,
      'last_uptime' => $JSON,
      'last_task_result' => $JSON
    ]
  ]
];


// 
// Screens 
//  have 0 or 1 preset
//
// presets 
//  have 0 or 1 layout
//  have 0 or 1 exclusive sets 
//  belong to many screens
//
// exclusive sets
//  have 0 or more campaigns to include
//  have 0 or more campaigns to exclude
//
// layouts
//  have 0 or 1 template
//  have 0 or more widgets
//
// organizations
//  have 1 or more users
//  have 0 or more brands
//
// brands
//  have 0 or more campaigns
//
 
//
// users have 0 or 1 contacts
// campaigns have 0 or 1 contacts
//
$SCHEMA = [
  //
  // SCREEN SIDE
  //
  'screen' => [
    'id'          => 'integer primary key autoincrement', 

    # A uid self-reported by the screen (as of the writing
    # of this comment, using dmidecode to get the CPU ID)
    'uid'         => 'text not null', 

    # A human readable name
    'serial'      => 'text',

    # If the device goes offline this will tell us
    # what it is that dissappeared so we can check
    'last_campaign_id' => 'integer',
    'imei'        => 'text',
    'imsi'        => 'text',
    'icc'         => 'text',
    'phone'       => 'text',
    'car'         => 'text',
    'project'     => 'text',
    'has_time'    => 'boolean default false',
    'app_id'      => 'integer',
    'ticker_id'   => 'integer',
    'model'       => 'text',
    'panels'      => 'text',
    'photo'       => 'text',
    'revenue'     => 'integer',
    'impact'      => 'integer',
    'lat'         => 'float default null',
    'lng'         => 'float default null',
    'location'    => 'text',
    'version'     => 'text',
    'version_time'=> 'integer',
    'uptime'      => 'integer',
    'ping_count'  => 'integer default 0',
    'bootcount'   => 'integer default 0',
    'port'        => 'integer', 
    'active'      => 'boolean default true',
    'removed'     => 'boolean default false',
    'is_fake'     => 'boolean default false',
    'features'    => 'text',
    'first_seen'  => 'datetime', 
    'last_task'   => 'integer default 0',
    'last_loc'    => 'datetime',
    'last_seen'   => 'datetime',
    'last_task_result'  => 'text',
    'last_uptime'  => 'text',

    // The car is either 
    //
    //  available   -- can be picked up
    //  reserved    -- someone intends to book it
    //  confirmed   -- the driver is going
    //  driving     -- the ride is going
    //  unavailable -- out of commission
    //
    //                 passenger, driver
    //  available -> [ reserved, unavailable ]
    //
    //                passenger cancel, driver accept, driver reject
    //  reserved -> [ available, confirmed, unavailable (malfunction) ]
    //
    //                 passenger pickup, malfunction, passenger cancel
    //  confirmed -> [ driver, unavailable, available ]
    //
    //  driving -> [ available, unavailable ]
    //
    //  unavailable -> [ available ]
    //  
    // 'goober_state'     => 'text default "unavailable"',
    //
    // This is to prevent the in-flight problem, which I was trying to avoid
    // desperately trying to avoid but I think it's too severe to ignore.
    //
    // 'goober_id'        => 'integer',

    'ignition_state'  => 'text',
    'ignition_time'   => 'datetime',

    'hoard_id'        => 'text'
  ],

  // revenue historicals
  'revenue_history' => [
    'id'            => 'integer primary key autoincrement',
    'screen_id'     => 'integer',
    'revenue_total' => 'integer', // deltas can be manually computed for now
    'created_at'    => 'datetime default current_timestamp',
  ],

  'job' => [
    'id'          => 'integer primary key autoincrement',
    'campaign_id' => 'integer',
    'screen_id'   => 'integer',
    'hoard_id'    => 'integer',
    'goal'        => 'integer',
    'completed_seconds' => 'integer default 0',

    //
    // We are very likely to increase the fidelity of
    // this in the future to something more sophisticated
    // but for now let's make it easy.
    //
    'is_boost'    => 'boolean default false',

    // TODO: VV this can be used for re-allocation.
    'last_update' => 'datetime',

    'job_start'   => 'datetime',
    'job_end'     => 'datetime'
  ],

  'sensor_data' => [
    'id'          => 'integer primary key autoincrement',
    'screen_id'   => 'integer',
    'run'         => 'integer default 0',
    'Light'       => 'float default null',
    'Voltage'     => 'float default null',
    'Current'     => 'float default null',
    'Accel_x'     => 'float default null',
    'Accel_y'     => 'float default null',
    'Accel_z'     => 'float default null',
    'Gyro_x'      => 'float default null',
    'Gyro_y'      => 'float default null',
    'Gyro_z'      => 'float default null',
    'Temp_2'      => 'float default null',
    'Temp'        => 'float default null',
    'Humidity'    => 'float default null',
    'Pitch'       => 'float default null',
    'Roll'        => 'float default null',
    'Yaw'         => 'float default null',
    'Lat'         => 'float default null',
    'Lng'         => 'float default null',
    'Fridge_door' => 'boolean default null',
    'Jolt_event'  => 'boolean default null',
    'DPMS1'       => 'boolean default false',
    'DPMS2'       => 'boolean default false',
    'Time'        => 'float default null',
    'created_at'  => 'datetime default current_timestamp'
  ],

  // #107 - scoped tasks
  // The id here is the referential id so that we 
  // can group the responses
  'task' => [
    'id'           => 'integer primary key autoincrement',
    'created_at'   => 'datetime default current_timestamp',
    'expiry_sec'   => 'integer default 172800',
    'scope'        => 'text',
    'command'      => 'text',
    'args'         => 'text'
  ],
   
  // #39
  'task_screen' => [
    'id'           => 'integer primary key autoincrement',
    'task_id'      => 'integer',
    'screen_id'    => 'integer',
  ],

  'task_response' => [
    'id'          => 'integer primary key autoincrement',
    'task_id'     => 'integer',
    'screen_id'   => 'integer',
    'response'    => 'text',
    'ran_at'      => 'datetime default current_timestamp',
    'created_at'  => 'datetime default current_timestamp',
  ],
    
  'runtime_history' => [
    'id'          => 'integer primary key autoincrement',
    'screen_id'   => 'integer',
    'bootcount'   => 'integer',
    'uptime'      => 'integer',
    'booted_at'   => 'datetime default current_timestamp',
    'created_at'  => 'datetime default current_timestamp'
  ],

  'uptime_history' => [
    'id'          => 'integer primary key autoincrement',
    // either the carname (eg waive43) or uid
    'name'      => 'text',
    // either car or screen
    'type'      => 'text',
    // either on or off
    'action'      => 'text',

    // this is easier than stringing on/offs together
    // and then trying to compute deltas
    'uptime'      => 'integer default null',
    
    'lat'         => 'float default null',
    'lng'         => 'float default null',

    'created_at'  => 'datetime default current_timestamp'
  ],

  # 143
  'screen_history' => [
    'id'          => 'integer primary key autoincrement',
    'screen_id'   => 'integer',
    'action'      => 'text',
    'value'       => 'text',
    'old'         => 'text',
    'created_at'  => 'datetime default current_timestamp'
  ],

  // #65
  'job_history' => [
    'id'        => 'integer primary key autoincrement',
    'job_id'    => 'integer',
    'start'     => 'datetime',
    'end'       => 'datetime'
  ],

  'ping_history' => [
    'id'        => 'integer primary key autoincrement',
    'screen_id' => 'integer',
    'created_at'=> 'datetime default current_timestamp',
  ],

  # This is a normalized system. Dunno if it's a good idea
  # becaue most of the time this will return no results. Maybe
  # keeping a counter in a screen definition of "has_campaigns"
  # and then when they are purged from this list that gets updated.
  #
  # This "optimization" which shouldn't be done because of
  # that word I just used, would avoid the extra query for no 
  # results problem
  'screen_campaign' => [
    'id'          => 'integer primary key autoincrement',
    'screen_id'   => 'integer',
    'campaign_id' => 'integer',
    'created_at'  => 'datetime default current_timestamp',
  ],

  'location_history' => [
    'id'          => 'integer primary key autoincrement',
    'job_id'      => 'integer',
    'campaign_id' => 'integer',
    'screen_id'   => 'integer',
    'lat'         => 'float default null',
    'lng'         => 'float default null',
    'created_at'  => 'datetime default current_timestamp',
  ],

  // 
  // DEMAND SIDE (advertiser)
  //
  //
  // consider: potentially create a second table for "staging" campaigns
  // that aren't active as opposed to relying on a boolean
  // in this table below
  //
  'campaign' => [
    'id'          => 'integer primary key autoincrement',
    'title'       => 'text',
    'ref_id'      => 'text',
    'uuid'        => 'text',
    /*
    'contact_id'  => 'integer',
    'brand_id'    => 'integer',
    'organization_id'    => 'integer',
     */
    'user_id'     => 'integer',
    'purchase_id' => 'integer',

    'asset'       => 'text not null',
    //
    // ^^ This will eventually be deprecated in favor of VV this
    //
    //    The form below is {url: <text>, duration: <number>, ... }
    //    The engine already gets things in this format because of
    //    the cache system
    //
    //    For now (2020-01-02) both will be defined until we do a
    //    release and everything is off the above format.
    //
    //    This is necessary to be able to facilitate dynamically 
    //    different durations.
    //
    'asset_meta' => 'text',

    //  
    // This is the goal number of seconds for the entire campaign, 
    // historically referred as duration seconds, which has since 
    // been repuprosed to mean the total duration of the playing
    // of all the assets.  This means that we can do
    //
    // goal_seconds / duration_seconds = number of target plays.
    //
    'goal_seconds' => 'integer',
    'completed_seconds' => 'integer default 0',

    // 
    // Total seconds things played is
    //
    //  boost_seconds + completed_seconds
    //
    // Because for now (2020-01-27), boost seconds 
    // is additional. 
    //
    'boost_seconds' => 'integer default 0',

    // 
    // How long 1 play of this campaign lasts.
    //
    'duration_seconds' => 'integer',

    // 
    // This is a cheap classification system
    // for the Oliver project. It'll probably
    // change.
    //
    'topic'       => 'text',
    'project'     => 'text default "dev"',

    //
    // For now, until we get a geo db system
    // this makes things easily queriable
    //
    // Stuff will be duplicated into shapelists
    //
    'lat'         => 'float default null',
    'lng'         => 'float default null',
    'radius'      => 'float default null',

    //
    // shape_list := [ polygon | circle ]* 
    //  polygon   := [ "Polygon", [ coord, ... ] ]
    //  circle    := [ "Circle", coord, radius ]
    //  coord     := [ lon, lat ]
    //  radius    := integer (meters)
    //
    'shape_list'  => 'text',

    //
    // The start_minute and end_minute are for campaigns that 
    // don't run 24 hours a day.
    //
    // If they are empty, then it means that it's 24 hours a day
    //
    'start_minute'=> 'integer default null',
    'end_minute'  => 'integer default null',
    'is_approved' => 'boolean default false',
    'is_default'  => 'boolean default false',

    // 
    // Essentially this is one of 
    //
    //  active, approved, pending, completed, or rejected
    //
    //  We'll explain it in a set of valid "next states", as a DFA (https://en.wikipedia.org/wiki/Deterministic_finite_automaton)
    //
    //  Current      Next      | Fail case
    //
    //  START     -> Approved  | Pending 
    //  Pending   -> Approved  | Rejected 
    //  Approved  -> Active    | Pending 
    //  Active    -> Completed | Pending 
    //  Completed -> END
    //  Rejected  -> END
    //
    // Only campaigns in an "active" state are shown on the screens and for now, Completed and Rejected are the two terminal outcomes.
    // Campaigns can enter in either Approved or Pending. 
    //
    // The Approved -> Active transition happens as the user specifies through a start date.
    // At any point an admin can "move" the state back to pending which then could be rejected.
    // If it goes back into approved then there will be something that reactivates it accordingly,
    // some thing like "make_active_if_applicable($id)"...
    //
    // These interim states and consequentially a model with fewer transition edges, should make 
    // the code a little easier.
    //
    'state'       => 'text default null',

    'priority'    => 'integer default 0',
    'impression_count' => 'integer',

    'flags' => 'text',

    //
    // The start_time and end_time are the bounds to do the 
    // campaign. It doesn't need to be exactly timebound by
    // these and can bleed over in either direction if it 
    // gets to that.
    //
    'start_time'  => 'datetime default current_timestamp',
    'end_time'    => 'datetime'
  ],

  'contact' => [
    'id'         => 'integer primary key autoincrement',
    'name'       => 'text',
    'twitter'    => 'text',
    'instagram'  => 'text',
    'facebook'   => 'text',
    'email'      => 'text',
    'website'    => 'text',
    'phone'      => 'text',
    'location'   => 'text',
    'lat'        => 'float',
    'lng'        => 'float'
  ],

  'purchase' => [
    'id'         => 'integer primary key autoincrement',
    'user_id'    => 'integer',
    'campaign_id'=> 'integer',
    'card_id'    => 'text',
    'charge_id'  => 'text',
    'status'     => 'text',

    'amount'     => 'integer',
    // ^ This is always filled.
    // if credit is charged then this V is filled
    'credit'     => 'integer default 0',

    // This means you can do amount - credit = amount paid.
    // Also this means that refunds can re-appear as
    // credit.
    'refunded'   => 'integer default 0',

    'ref_id'     => 'integer',
    'created_at' => 'datetime default current_timestamp',
  ],

  'service' => [
    'id'         => 'integer primary key autoincrement',
    'user_id'    => 'integer',
    'service'    => 'text',

    'service_user_id' => 'text',
    'username'   => 'text',
    'token'      => 'text',
    // most recent data
    'data'       => 'text',
    'created_at' => 'datetime default current_timestamp',
  ],

  'user' => [
    'id'         => 'integer primary key autoincrement',
    'uuid'       => 'text',
    'name'       => 'text',
    'phone'      => 'text',
    'email'      => 'text',
    'password'   => 'text',
    'image'      => 'text',
    'role'       => 'text', // either admin / supply / demand
    'credit'     => 'integer default 0',
    'contact_id' => 'integer',
    'is_verfied' => 'boolean default false',
    'auto_approve' => 'boolean default false',
    'title'      => 'text',
    //'organization_id'     => 'integer',
    //'brand_id'   => 'integer',
    'created_at' => 'datetime default current_timestamp',
  ],

  // 
  // SUPPLY SIDE (screen holders)
  //
  // a screen 'hoard' has a UUID that's generated from the
  // dsp side.
  //
  // A hoard has one human and potentially many screens. 
  // A screen may have multiple hoards.
  'hoard' => [
    'id'         => 'integer primary key autoincrement',
    'uuid'       => 'text',
    'user_id'    => 'integer',
    'created_at' => 'datetime default current_timestamp',
  ],

  // 
  // MISCELLANEOUS
  //
  // we should store the source data here for the future
  // also the params/data that correspond to the service are done
  // in a way to permit multi-sourcing
  //
  // data schema is:
  //  {
  //    service_id: { service: _string_, data: snapshot },
  //    service_id: { service: _string_, data: snapshot },
  //  }
  //
  'template_config' => [
    'id'          => 'integer primary key autoincrement',
    'user_id'     => 'integer',
    'template_name' => 'text',
    'params'      => 'text',
    'data'        => 'text',
    'created_at'  => 'datetime default current_timestamp',
  ]
];
/*

  'goober' => [
    'id'            => 'integer primary key autoincrement',
    'screen_id'     => 'integer',
    'user_id'       => 'text',
    'lat'           => 'float default null',
    'lng'           => 'float default null',
    'phone'         => 'text',
    'created_at'    => 'datetime default current_timestamp',
  ],
 
 // adcast related
  'attribution' => [
    'id'         => 'integer primary key autoincrement',
    'screen_id'  => 'integer',
    'type'       => 'text',    // such as wifi/plate, etc
    'signal'     => 'integer', // optional, could be distance, RSSI
    'mark'       => 'text',    // such as the 48-bit MAC address
    'created_at' => 'datetime default current_timestamp',
  ],
  'widget' => [
    'id'     => 'integer primary key autoincrement',
    'name'   => 'text', // what to call it
    'image'  => 'text', // url of logo or screenshot
    'type'   => 'text', // ticker or app
    'topic'  => 'text', // optional, such as "weather"
    'source' => 'text', // The url where to get things
    'created_at' => 'datetime default current_timestamp',
  ],

 
 // b2b model
  'brand' => [
    'id'         => 'integer primary key autoincrement',
    'organization_id'     => 'integer',
    'name'       => 'text',
    'image'      => 'text',
    'balance'    => 'integer',
    'created_at' => 'datetime default current_timestamp',
  ],
  'exclusive' => [
    'id'          => 'integer primary key autoincrement',
    'set_id'      => 'integer',
    'whitelist'   => 'boolean', // if true then this is inclusive, if false 
    'campaign_id' => 'integer'  // then we should leave it out.
  ],
  'organization' => [
    'id'         => 'integer primary key autoincrement',
    'name'       => 'text',
    'image'      => 'text',
  ],

 // ??
  'place' => [
    'id'     => 'integer primary key autoincrement',
    'name'   => 'text not null',
    'lat'    => 'float default null',
    'lng'    => 'float default null',
    'radius' => 'float default null'
  ],

  // In the future we can have different tag classes or namespaces
  // But for the time being we just need 1 separation: LA and NY
  // and that's literally it. Generalizability can come later.
  //
  // This is a list of tags, it's notable that we aren't really
  // doing some kind of "normalization" like all the proper kids
  // do because we don't want to be doing stupid table joins 
  // everywhere to save a couple bytes.
  'tag' => [
    'id'        => 'integer primary key autoincrement',
    'name'      => 'text',
    'created_at' => 'datetime default current_timestamp',
  ],

  // #47 - the screen_id/tag is the unique constraint. There's
  // probably a nice way to do it. Also if you really are doing
  // things well then you use the whitelist from the tag table
  // before inserting since we are keeping it daringly free-form
  'screen_tag' => [
    'id'        => 'integer primary key autoincrement',
    'screen_id' => 'integer',
    'tag'       => 'text',
    'created_at' => 'datetime default current_timestamp',
  ],

  // #95 If different tags need different default campaign ids 
  // or split kingdoms we do that here. It's basically a
  // key/value with a name-space. Right now we don't have 
  // a list of tags, probably should so that the screen_tag
  // and tag_info table references a tag_list but this is
  // fine for now.
  'tag_info' => [
    'id'         => 'integer primary key autoincrement',
    'tag'        => 'text not null',
    'key'        => 'text',
    'value'      => 'text',
    'created_at' => 'datetime default current_timestamp',
  ],



 */
$_db = false;
$_pdo = false;
function db_connect() {
  global $_db, $DBPATH;
  if(!$_db) {
    if(!file_exists($DBPATH)) {
      touch($DBPATH);
    }
    $_db = new SQLite3($DBPATH);
    $_db->busyTimeout(5000);
    // WAL mode has better control over concurrency.
    // Source: https://www.sqlite.org/wal.html
    $_db->exec('PRAGMA journal_mode = wal;');
  }
  return $_db;
}

function pdo_connect() {
  global $_pdo, $DBPATH;
  if(!$_pdo) {
    $charset = 'utf8mb4';

    //$dsn = "mysql:host=localhost;dbname=ws;charset=utf8";
    $dsn = "sqlite:$DBPATH";
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
      //PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" 
    ];
    try {
      $_pdo = new PDO($dsn, 'www-data', false, $options);
    } catch (\PDOException $e) {
      error_log($e->getMessage() . (int)$e->getCode());
      throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
  }
  return $_pdo;
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


function db_int($what) {
  return intval($what);
}

function db_string($what) {
  $where = strpos($what, "'");
  if ($where === false) {
    return "'$what'";
  } else if ($where != 0) {
    return "'" . SQLite3::escapeString($what) . "'";
  }
  return $what;
}

function pdo_date($what) {
  return date("Y-m-d H:i:s", $what);
}
function db_date($what) {
  return "datetime($what,'unixepoch')";
}

function is_flagged($campaign, $what) {
  $flags = aget($campaign, 'flags', []);
  return aget($flags, $what);
}

function flag($campaign, $what, $value = 1) {
  $flags = aget($campaign, 'flags', []);
  $flags[$what] = $value;
  return pdo_update('campaign', $campaign['id'], ['flags' => $flags]);
}

function unflag($campaign, $what) {
  $flags = aget($campaign, 'flags', []);
  if(isset($flags[$what])) {
    unset($flags[$what]);
  }
  return pdo_update('campaign', $campaign['id'], ['flags' => $flags]);
}

function _pdo_query($qstr, $values, $func='execute') {
  $pdo = pdo_connect();
  try {
    return $pdo->prepare($qstr)->execute($values);
  } catch (\PDOException $e) {
    error_log($qstr . ' ' . $e->getMessage() . (int)$e->getCode());
  }
}

function _query($qstr, $func='exec') {
  $db = db_connect();
  try {
    if($func === 'querySingle') {
      $res = $db->$func($qstr, true);
    } else {
      $res = $db->$func($qstr);
    }
    if($res) {
      return $res;
    } else {
      error_log("Failed Query:" . $qstr);
    }
  } catch(Exception $ex) { 
    error_log("$qstr $ex " . json_encode($ex->getTrace()));
  }
}

function get_column_list($table_name) {
  $db = db_connect();
  $res = $db->query("pragma table_info( $table_name )");

  return array_map(function($row) { 
    return $row['name'];
  }, db_all($res));
}

function get_campaign_remaining($id) {
  $res = (db_connect())->querySingle("select goal_seconds - completed_seconds as remaining from campaign where campaign.id = $id");

  if($res === null) {
    return (db_connect())->querySingle("select goal_seconds from campaign where id=$id");
  }
  return $res;
}

class Get {
  protected static $_cache = [];
  
  public static function doquery($qstr, $table) {
    $res = _query($qstr, 'querySingle');
    return process($table, $res, 'post');
  }

  public static function __callStatic($name, $argList) {
    $arg = false;
    if(count($argList) > 0) {
      $arg = $argList[0];
    }
    $cache = count($argList) > 1;
    $key = 'id';
    if(!is_array($arg)) {
      if((is_string($arg) || is_numeric($arg)) && !empty($arg)) {
        $arg = ['id' => $arg];
      } else if(count($argList) > 0) {
        return null;
      }
    }
    if($key === 'id' && $cache === true) {
      $cache_key = implode(':', [$name, json_encode($arg)]);
      if (array_key_exists($cache_key, static::$_cache)) { 
        return static::$_cache[$cache_key];
      }
    }

    $fields = aget($argList, 1, '*');
    $kvargs = [];
    $kvstr = '';
    if($arg) {
      foreach($arg as $key => $value) {
        // this means a raw string was passed
        if(is_integer($key)) {
          $kvargs[] = $value;
        } else {
          if(is_array($value)) {
            $kvargs[] = "$key like " . db_string('%' . $value['like']);
          } else {
            if(is_string($value)) {
              $value = db_string($value);
            }
            $kvargs[] = "$key=$value";
          }
        }
      }
      $kvstr = "where " . implode(' and ', $kvargs);
    }

    $qstr = "select $fields from $name $kvstr";
    $res = static::doquery($qstr, $name);

    if($key === 'id' && $cache === true) {
      static::$_cache[$cache_key] = $res;
    }

    return $res;
  }
};

class Many extends Get {
  public static function doquery($qstr, $table, $fields = '*') {
    return db_all($qstr, $table, $fields);
  }
};

function process($table, $obj, $what, $type='none') {
  global $RULES;
  if($obj && $table && isset($RULES[$table])) {
    $ref = $RULES[$table];
    if(isset($ref['columns'])) {
      foreach($ref['columns'] as $key => $processor) {
        if(isset($obj[$key]) && isset($processor[$what])) {
          $obj[$key] = $processor[$what]($obj[$key], $obj, $type);
        }
      }
    }
    if(isset($ref['table'])) {
      if(isset($ref['table'][$what])) {
        $obj = $ref['table'][$what]($obj, $type);
      }
    }
  }
  return $obj;
}

function pdo_bottom($v) {
  if($v === 'null') {
    return null;
  } elseif($v === 'true' || $v === true || $v === false || $v === 'false') {
    return intval(boolval($v));
  }
  return $v;
}
function db_bottom($v) {
  if($v === null) {
    return "null";
  } elseif($v === false) {
    return "false";
  }
  return $v;
}

function pdo_upsert($table, $condition, $kv) {
  $res = Get::$table($condition);
  return $res ? 
    pdo_update($table, $condition, $kv) : 
    pdo_insert($table, $kv);
}

function pdo_update($table, $id, $kv) {
  $values = [];
  $fields = [];

  $kv = process($table, $kv, 'pre', 'pdo');
  
  foreach($kv as $k => $v) {
    $values[] = db_bottom($v);
    $fields[] = "$k=?";
  } 

  if(is_array($id)) {
    $parts = array_keys($id);
    $key = $parts[0];
    $values[] = $id[$key];
  } else {
    $key = 'id';
    $values[] = $id;
  }

  $fields = implode(',', $fields);

  $qstr = "update $table set $fields where $key = ?";
  return _pdo_query($qstr, $values);
}

function db_update($table, $id, $kv) {
  $fields = [];

  $kv = process($table, $kv, 'pre');
  
  if(is_array($id)) {
    $parts = array_keys($id);
    $key = $parts[0];
    $value = $id[$key];
  } else {
    $key = 'id';
    $value = $id;
  }

  foreach($kv as $k => $v) {
    $v = db_bottom($v);
    $fields[] = "$k=$v";
  } 

  $fields = implode(',', $fields);

  $qstr = "update $table set $fields where $key = $value";
  // error_log($qstr);
  return _query($qstr);
}

function db_clean($kv) {
  $res = [];
  $db = db_connect();
  foreach($kv as $k => $v) {
    if(is_array($v)) {
      //error_log(json_encode([$k, $v]));
    } else {
      $res[$db->escapeString($k)] = $db->escapeString($v);
    }
  } 
  return $res;
}

function sql_kv($hash, $operator = '=', $quotes = "'", $intList = []) {
  $ret = [];
  foreach($hash as $key => $value) {
    if ( is_numeric($value) ) {
      $ret[] = "$key $operator $value";
    }
    else if ( is_string($value) ) {
      $parts = explode($value, ',');
      if(count($parts) > 1) {
        $numbers = true;
        foreach($parts as $el) {
          $numbers &= is_numeric($el);
        }
        if($numbers) {
          $ret[] = "$key in ($value)";
          continue;
        }
      } 
      
      if(in_array($key, $intList)) {
        $ret[] = "$key $operator $value";
      } else {
        $ret[] = "$key $operator $quotes$value$quotes";
      }
    }
  } 
  return $ret;
}

function db_all($qstr, $table = false) {
  global $RULES;
  $ruleTable = false;
  if($table && isset($RULES[$table])) {
    $ruleTable = $RULES[$table];
  }

  $rowList = [];
  if(!is_string($qstr)) {
    $res = $qstr;
  } else {
    $res = _query($qstr, 'query');
    if(is_bool($res)) {
      return [];
    }
  }
  if($res) {
    while( $row = $res->fetchArray(SQLITE3_ASSOC) ) {
      if($ruleTable) {
        $row = process($table, $row, 'post');
      }
      $rowList[] = $row;
    } 
  }
  return $rowList;
}

function db_insert_many($table, $kvList) {
  if(count($kvList) === 0) {
    return null;
  }
  $fields = [];
  $valueList = [];
  $isFirst = true;
  $db = db_connect();

  foreach($kvList as $kv) {
    $kv = process($table, $kv, 'pre');
    $row = [];
    foreach($kv as $k => $v) {
      if($isFirst) {
        $fields[] = $k;
      }
      if($v === false) {
        $row[] = 'false';
      } else {
        $row[] = $v;
      }
    } 
    $valueList[] = "(" . implode(',', $row) . ")";
    $isFirst = false;
  }
  $fields = implode(',', $fields);
  $values = implode(',', $valueList);
  $qstr = "insert into $table($fields) values $values";
  //error_log($qstr);

  if(_query($qstr)) {
    return $db->lastInsertRowID();
  }
  return null;
}

function pdo_insert($table, $kv) {
  $values = [];
  //error_log(json_encode($kv));

  $kv = process($table, $kv, 'pre', 'pdo');
  foreach($kv as $k => $v) {
    $values[] = pdo_bottom($v);
  } 
  if(count($values) === 0) {
    $qstr = "insert into $table default values";
  } else {
    $pdo_values = implode(',', array_fill(0, count($values), '?'));
    $fields = implode(',', array_keys($kv));

    $qstr = "insert into $table($fields) values($pdo_values)";
  }
  error_log(json_encode([$qstr, $values, $_REQUEST]));

  _pdo_query($qstr, $values);
  return pdo_connect()->lastInsertId();
}

function db_insert($table, $kv) {
  $fields = [];
  $values = [];

  $db = db_connect();
  $kv = process($table, $kv, 'pre');

  foreach($kv as $k => $v) {
    $fields[] = $k;
    if($v === false) {
      $values[] = 'false';
    } else {
      $values[] = $v;//db->escapeString($v);
    }
  } 

  $values = implode(',', $values);
  $fields = implode(',', $fields);

  $qstr = "insert into $table($fields) values($values)";

  if(_query($qstr)) {
    return $db->lastInsertRowID();
  }
  return null;
}

function onetimehash($payload, $expire_hours = 72) {
  $r = get_redis();

  if(is_string($payload)) {
    $stuff = $r->get("OTH:$payload");
    if($stuff) { 
      return json_decode($stuff, true);
    }
  }
  $hash = Uuid::uuid4()->toString();
  $r->set("OTH:$hash", json_encode($payload));
  $r->expire("OTH:$hash", $expire_hours * 3600);
  return $hash;
  
}
