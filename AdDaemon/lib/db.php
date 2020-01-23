<?php
date_default_timezone_set('UTC');

$DBPATH = "/var/db/waivescreen/main.db";
$JSON = [
  'pre' => function($v) { 
    if ($v === null) { return $v; } 
    if (!is_string($v)) { $v = json_encode($v); }
    return db_string($v); 
  },
  'post' => function($v) { 
    if (!$v) { return $v; } 
    return json_decode($v, true); 
  }
];

$RULES = [
  'campaign' => [ 
    'shape_list' => $JSON,
    'asset_meta' => [
      'pre' => $JSON['pre'],
      'post' => function($v) {
         $v = json_decode($v, true);
         if(!is_array($v)) {
           $v = [ $v ];
         }

         return array_map(function($m) {
           if(strpos($m['url'], 'http') === false) {
             $m['url'] = 'http://waivecar-prod.s3.amazonaws.com/' . $m['url'];
           } 
           return $m;
         }, $v);
      }
    ],
    'asset' => [
      'pre' => $JSON['pre'],
      'post' => function($v) {
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
  ],
  'screen' => [
    'features' => $JSON,
    'panels' => $JSON,
    'location' => $JSON,
    'last_uptime' => $JSON,
    'last_task_result' => $JSON
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
    'goober_state'     => 'text default "unavailable"',
    //
    // This is to prevent the in-flight problem, which I was trying to avoid
    // desperately trying to avoid but I think it's too severe to ignore.
    //
    'goober_id'        => 'integer',

    'ignition_state'  => 'text',
    'ignition_time'   => 'datetime'
  ],

  // revenue historicals
  'revenue_history' => [
    'id'            => 'integer primary key autoincrement',
    'screen_id'     => 'integer',
    'revenue_total' => 'integer', // deltas can be manually computed for now
    'created_at'    => 'datetime default current_timestamp',
  ],

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
    'contact_id'  => 'integer',
    'brand_id'    => 'integer',
    'user_id'     => 'integer',
    'organization_id'    => 'integer',
    'order_id'    => 'integer',

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

    // This is the goal number of seconds for the entire campaign, 
    // historically referred as duration seconds, which has since 
    // been repuprosed to mean the total duration of the playing
    // of all the assets.  This means that we can do
    //
    // goal_seconds / duration_seconds = number of target plays.
    //
    'goal_seconds' => 'integer',
    'completed_seconds' => 'integer default 0',
    'project'     => 'text default "dev"',

    // TODO: 2020-01-02
    'duration_seconds' => 'integer',
    'play_count' => 'integer default 0',

    // 
    // This is a cheap classification system
    // for the Oliver project. It'll probably
    // change.
    //
    'topic'       => 'text',

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
    // essentially this one of 
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

    // The start_time and end_time are the bounds to do the 
    // campaign. It doesn't need to be exactly timebound by
    // these and can bleed over in either direction if it 
    // gets to that.
    'start_time'  => 'datetime default current_timestamp',
    'end_time'    => 'datetime'
  ],

  'job' => [
    'id'          => 'integer primary key autoincrement',
    'campaign_id' => 'integer',
    'screen_id'   => 'integer',
    'goal'        => 'integer',
    'completed_seconds' => 'integer default 0',
    'last_update' => 'datetime',
    'job_start'   => 'datetime',
    'job_end'     => 'datetime'
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

  // accounting, to be moved later.
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

  'service' => [
    'id'         => 'integer primary key autoincrement',
    'user_id'    => 'integer',
    'service'    => 'text',
    'username'   => 'text',
    'token'      => 'text',
    'created_at' => 'datetime default current_timestamp',
  ],

  'user' => [
    'id'         => 'integer primary key autoincrement',
    'uuid'       => 'text',
    'name'       => 'text',
    'password'   => 'text',
    'image'      => 'text',
    'contact_id' => 'integer',
    'auto_approve' => 'boolean default false',
    'title'      => 'text',
    'organization_id'     => 'integer',
    'brand_id'   => 'integer',
    'role'       => 'text', // either admin/manager/viewer
    'created_at' => 'datetime default current_timestamp',
  ],

  'order' => [
    'id'         => 'integer primary key autoincrement',
    'user_id'    => 'integer',
    'campaign_id'=> 'integer',
    'charge_id'  => 'text',
    'status'     => 'text',
    'amount'     => 'integer',
    'refunded'   => 'boolean default false',
    'ref_id'     => 'integer',
    'created_at' => 'datetime default current_timestamp',
  ],
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
 
  'ces' =>  [
    'id'          => 'integer primary key autoincrement',
    'phone'       => 'text',
    'message'     => 'text',
    'campaign_id' => 'integer',
    'created_at'  => 'datetime default current_timestamp',
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
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" 
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

function db_date($what) {
 return "datetime($what,'unixepoch')";
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
  public static function doquery($qstr, $table) {
    $res = _query($qstr, 'querySingle');
    return process($table, $res, 'post');
  }

  public static function __callStatic($name, $argList) {
    $arg = $argList[0];
    $key = 'id';
    if(!is_array($arg)) {
      if((is_string($arg) || is_numeric($arg)) && !empty($arg)) {
        $arg = ['id' => $arg];
      } else {
        return null;
      }
    }

    $fields = aget($argList, 1, '*');
    $kvargs = [];
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
    $kvstr = implode(' and ', $kvargs);

    $qstr = "select $fields from $name where $kvstr";
    return static::doquery($qstr, $name);
  }
};

class Many extends Get {
  public static function doquery($qstr, $table, $fields = '*') {
    return db_all($qstr, $table, $fields);
  }
};

function process($table, $obj, $what) {
  global $RULES;
  if($obj && $table && isset($RULES[$table])) {
    foreach($RULES[$table] as $key => $processor) {
      if(isset($obj[$key]) && isset($processor[$what])) {
        $obj[$key] = $processor[$what]($obj[$key], $obj);
      }
    }
  }
  return $obj;
}

function db_bottom($v) {
  if($v === null) {
    return "null";
  } elseif($v === false) {
    return "false";
  }
  return $v;
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
