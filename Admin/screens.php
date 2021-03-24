<?
include('../AdDaemon/lib/lib.php');
include('lib.php');
include('../AdDaemon/lib/accounting.php');

require_authorized_user();

$DAY = 60 * 60 * 24;

$carMap = [];
$screenList = get('screens', ['active' => 1]);
$carList = get('most_recent');
foreach($carList as $car) {
  $carMap[strtolower($car['name'])] = $car;
}

function time2delta($what) {
  global $DAY;
  $tmp = strtotime(str_replace(' ', 'T', $what . 'Z'));
  $sec = time() - $tmp;
  return [$sec, sprintf("%dd %d:%02d", floor($sec / $DAY), floor($sec / 60 / 60) % 24, floor($sec/60) % 60)];
}

function getcarlast($what) {
  global $carMap;
  $key = strtolower($what);
  if(!isset($carMap[$key])) { return [0, '??']; }
  return time2delta($carMap[$key]['last']);
}
 
$addrList = get_addressList(array_map(function($row) { 
  if($row['lat'] && $row['lng']) {
    return [$row['lat'],$row['lng']]; 
  } else  {
    return [];
  }
}, $screenList));

for($ix = 0; $ix < count($screenList); $ix++){
  if($addrList[$ix]) {
    $screenList[$ix]['addr'] = "<a target=_blank href=//maps.google.com/?q={$screenList[$ix]['lat']},{$screenList[$ix]['lng']}>{$addrList[$ix]}</a>";
  } else if($screenList[$ix]['lat']) {
    $screenList[$ix]['addr'] = "<a target=_blank href=//maps.google.com/?q={$screenList[$ix]['lat']},{$screenList[$ix]['lng']}>{$screenList[$ix]['lat']}, {$screenList[$ix]['lng']}</a>";
  } else {
    $screenList[$ix]['addr'] = '<em>unknown</em>';
  }
  foreach(['first','last'] as $key) {
    $screenList[$ix]["{$key}_local"] = strtotime(str_replace(' ', 'T', $screenList[$ix]["{$key}_seen"] . 'Z'));
  }

  $sec =  time() - $screenList[$ix]['last_local'];
  $screenList[$ix]['last_sec'] = $sec;

  if($sec > 300) {
    $screenList[$ix]['uptime'] = 'off';
  } 

  $screen_last = time2delta($screenList[$ix]['last_seen']);
  $car_last = getcarlast($screenList[$ix]['car']);
  $screencolor = $carcolor = 'none';
  if($car_last[0]) {
    $color = "rgba(255,128,128," .  abs($screen_last[0] - $car_last[0]) / (7 * $DAY) . ")";
    if($screen_last[0] > $car_last[0]) {
      $screencolor = $color;
    } else {
      $carcolor = $color;
    }
  } 
  $screenList[$ix]['last_local'] = "<span style=background:$screencolor class=screen-last title=screen>" . $screen_last[1] . "</span>" . 
    "<span style=background:$carcolor class=car-last title='{$screenList[$ix]['car']}'>" . $car_last[1] . "</span>";

  $screenList[$ix]['first_local'] = date("Y-m-d H:i:s", $screenList[$ix]['first_local']);

  if (isset( $screenList[$ix]['last_loc']) ) {
    $tmp = strtotime(str_replace(' ', 'T', $screenList[$ix]["last_loc"] . 'Z'));
    $sec =  time() - $tmp;
    $screenList[$ix]['diff_loc'] = sprintf("%dd %d:%02d:%02d", floor($sec / $DAY), floor($sec / 60 / 60) % 24, floor($sec/60) % 60, $sec %60);
    $screenList[$ix]['loc_sec'] = $sec;
  } else {
    $screenList[$ix]['loc_sec'] = 99999999999;
    $screenList[$ix]['diff_loc'] = '<em>never</em>';
  }
  if(isset($screenList[$ix]['ignition_time'])) {
    $screenList[$ix]['expected_hour'] = (strtotime($screenList[$ix]['ignition_time']) - strtotime($screenList[$ix]['last_seen'])) / 60 / 60;
    $screenList[$ix]['expected'] = round( abs($screenList[$ix]['expected_hour']) );
  } else {
    $screenList[$ix]['expected'] = '';
  }
  
  $id = $screenList[$ix]['uid'];
  $screenList[$ix]['shortid'] = substr($id, 0, 4) . '&hellip;' . substr($id, -4);
}

//$tagList = db_all("select name from tag");

$PROJECT_LIST = [
  '' => 'none',
  'LA' => 'LA', 
  'NY' => 'NY',
  'dev' => 'dev',
  'Amazon' => 'CES/Amazon',
  'CES' => 'CES/Oliver',
  'Oliver' => 'Oliver'
];
$MODEL_LIST = [
  '' => 'none',
  'ioniq_ev' => 'Ioniq EV', 
  'ioniq_hybrid' => 'Ioniq Hybrid', 
  'camry' => 'Camry'
];

$fieldList = [
  //'id'  => 'uid',
  'car' => 'car',
  'serial' => 'serial',
  'location' => 'addr',
  'version' => 'version',
  'uptime' => 'uptime',
  'expected' => 'expected',
  'last' => 'last_local',
  'first' => 'first_local'
];
$editable = ['car', 'serial'];

$value = [
  'version' => function($value, $row) {
    $id = $row['id'];
    return "<a href=#<?= $id ?> onclick='history(\"<?= $id ?>\")' class=version><?= $value ?></a>";
  }
];

$props = [
  'version' => [ 
    'order' => function($value, $row) {
      $base = substr($value, 3);
      $parts = explode('-', $base);
      $sec = 0;
      if(count($parts) > 2) {
        $sec = intval($parts[2]);      
      }

      return intval($parts[0]) * 10000 + $sec;
    }
  ],
  'last' => [ 
    'order' => function($value, $row) {
      return $row['last_sec'];
    }
  ],
  'updated' => [ 
    'order' => function($value, $row) {
      return $row['loc_sec'];
    }
  ],
  'uptime' => [ 
    'order' => function($value, $row) {
      return $value === 'off' ? PHP_INT_MAX : $value;
    }
  ]
];

function split($str) {
  return $str;
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/css/sb-admin-2.min.css" rel="stylesheet">
		<link rel=stylesheet href=https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css>
    <link href="/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <title>Screen Admin</title>
  </head>
  <style>
    #content-wrapper span { color: #000 }
    .id,.version { font-family: monospace }
    .edit { color: #999; cursor: pointer }
    .last { text-align: right }
    em { color: #555 }
#commander {white-space: nowrap }
    .table td {padding: .75rem .2rem; }
    td.edit { white-space: nowrap; }
    .removed { opacity: 0.5;background:#ddd }
.screen-last { display: block; white-space: nowrap; }
.car-last { display: block;font-size: 80%;white-space: nowrap }
    .modal-body span {
      min-width: 7rem; 
      display: inline-block;
      vertical-align: top;
    }
    .modal-body span div {
      margin-left: 1rem;
    }
    .edit:hover { color: #000 }
    #notice { position: absolute; top:0; left:0; width: 100%; z-index: 100;display:none}
    .btn.disabled,.btn-primary.disabled { background: #aaa !important }
  </style>
  <body id="page-top">
  <div id="wrapper">
  <? include ('partials/sidebar.php'); ?>
  <div id="content-wrapper" class="d-flex flex-column">
    <? include ('partials/topbar.php'); ?>

    <h3>
    <div class="alert alert-primary" id="notice" role="alert"></div>
    </h3>

    <div class="table-responsive">
      <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">

        <thead>
          <tr>
          <th scope="col">id</th>
          <th scope="col">project</th>
          <th scope="col">model</th>
          <? foreach($fieldList as $key => $value) { ?>
            <th scope="col"><?= $key ?></th>
          <? } ?>
          <th scope="col">cmd</th>
          </tr>
        </thead>
        <tbody>
        <? foreach($screenList as $screen) {
          if($screen['removed']) {
            $klass = ' class="removed"';
          } else {
            $klass = '';
          }
 ?>
          <tr<?=$klass?>>
            <td data-search="<?= implode(' ',[$screen['port'], $screen['phone'], $screen['uid']]) ?>">
              <a href="#<?=$screen['id']?>" onclick='edit("<?=$screen['id']?>")' class=id><?= $screen['shortid'] ?></a>
            </td>
            <td data-search="<?= $screen['project']; ?>">
              <select onchange=change(<?=$screen['id']?>,'project',this)>
                <?foreach($PROJECT_LIST as $value => $project) { 
                  $selected = ($value === $screen['project']) ? 'selected' : '';
                 ?>
                  <option value="<?=$value?>" <?=$selected?>><?=$project?></option>
                <? } ?>
              </select>
            </td>
            <td data-search="<?= $screen['model']; ?>">
              <select onchange=change(<?=$screen['id']?>,'model',this)>
                <?foreach($MODEL_LIST as $value => $project) { 
                  $selected = ($value === $screen['model']) ? 'selected' : '';
                ?>
                  <option value="<?=$value?>" <?=$selected?>><?=$project?></option>
                <? } ?>
              </select>
            </td>
            <? foreach($fieldList as $name => $key) { 
              
                 $dataVals = [];
                 if(array_key_exists($name, $props)) {
                   foreach($props[$name] as $propKey => $propValue) {
                     $dataVals[] = "data-$propKey='" . $propValue($screen[$key], $screen) . "'";
                   }
                 }
                 $dataVals = implode(' ', $dataVals);
                 $canedit = array_search($key, $editable) !== false ? 'edit' : '';
            ?>
              <td class="<?= $name?> <?=$canedit?>" <?=$dataVals?>>
                <span><?= aget($screen, $key) ?></span>
                <? if ($canedit) { ?>
                  <a onclick="promptchange(<?=$screen['id']?>,'<?=$key?>',this)"><i class="edit fa fa-pencil"></i></a>
                <? } ?>
              </td>
            <? } ?>
            <td>
              <button onclick='command("<?=$screen['id']?>","<?=$screen['car']?>")' class="btn btn-secondary">cmd</button>
            </td>
          </tr>
        <? } ?>
      </tbody>
    </table>
    <div id='commander'>
    <input size=4 id=field placeholder=field></input>
    <input id=value placeholder=value></input>
    <input id=command placeholder=command></input>
    <input id=args placeholder=args></input>
    <button onclick=scope_command()>do it</button>
    </div>
   </div>

    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ModalLabel">screen</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="modal-body">
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-danger btn-sm mr-auto" id="remove" onclick=remove()>Remove Screen</button>
            <button type="button" class="btn btn-primary btn-sm mr-auto" id="unremove" onclick=unremove()>Unremove Screen</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

  </div>
    <script>
    var Data=<?=json_encode($screenList)?>,now='<?= date("Y-m-d H:i:s")?>';
    </script>
  <script
    src="https://code.jquery.com/jquery-3.4.1.min.js"
    integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
    crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="/js/jquery.easing.min.js"></script>
    <script src="/js/sb-admin-2.min.js"></script>
    <script src="/js/jquery.dataTables.min.js"></script>
    <script src="/js/dataTables.bootstrap4.min.js"></script>
    <script src="/Admin/script.js?1"></script>
  </body>
</html>
