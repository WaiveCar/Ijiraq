<?
include('../MadisonAve/lib/lib.php');
include('../AdDaemon/lib/const.php');
include('lib.php');

$screenList = get('screens');
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
  } else {
    $screenList[$ix]['addr'] = '<em>unknown</em>';
  }
  foreach(['first','last'] as $key) {
    $screenList[$ix]["{$key}_local"] = strtotime(str_replace(' ', 'T', $screenList[$ix]["{$key}_seen"] . 'Z'));
  }

  $sec =  time() - $screenList[$ix]['last_local'];
  $screenList[$ix]['last_local'] = sprintf("%dd %d:%02d:%02d", floor($sec / 60 / 60 / 24), floor($sec / 60 / 60) % 24, floor($sec/60) % 60, $sec %60);
  $screenList[$ix]['first_local'] = date("Y-m-d H:i:s", $screenList[$ix]['first_local']);

  if (isset( $screenList[$ix]['last_loc']) ) {
    $tmp = strtotime(str_replace(' ', 'T', $screenList[$ix]["last_loc"] . 'Z'));
    $sec =  time() - $tmp;
    $screenList[$ix]['diff_loc'] = sprintf("%dd %d:%02d:%02d", floor($sec / 60 / 60 / 24), floor($sec / 60 / 60) % 24, floor($sec/60) % 60, $sec %60);
  } else {
    $screenList[$ix]['diff_loc'] = '<em>never</em>';
  }
}

//$tagList = db_all("select name from tag");

$PROJECT_LIST = [
  '' => 'none',
  'LA' => 'LA', 
  'NY' => 'NY'
];
$MODEL_LIST = [
  'ioniq_ev' => 'Ioniq EV', 
  'ioniq_hybrid' => 'Ioniq Hybrid', 
  'camry' => 'Camry'
];

$fieldList = [
  //'id'  => 'uid',
  'car' => 'car',
  'serial' => 'serial',
  'location' => 'addr',
  'updated' => 'diff_loc',
  'phone' => 'phone',
  'port' => 'port',
  'version' => 'version',
  'last' => 'last_local',
  'first' => 'first_local'
];
$editable = ['car', 'serial', 'phone'];
function split($str) {
  return $str;
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel=stylesheet href=https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <title>Screen Admin</title>
  </head>
  <style>
    body,* { color: #000 }
    .id,.version { font-family: monospace }
    .edit { color: #999; cursor: pointer }
    .last { text-align: right }
    em { color: #555 }
    .edit:hover { color: #000 }
    #notice { position: absolute; top:0; left:0; width: 100%; z-index: 100;display:none}
  </style>
  <body id="page-top">
  <div id="wrapper">
  <div id="content-wrapper" class="d-flex flex-column">

    <div class="alert alert-primary" id="notice" role="alert"></div>

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
          <th scope="col">command</th>
          </tr>
        </thead>
        <tbody>
        <? foreach($screenList as $screen) { ?>
          <tr>
            <td>
              <a href="#<?=$screen['id']?>" onclick='edit("<?=$screen['id']?>")' class=id><?= split($screen['uid']) ?></a>
            </td>
            <td>
              <select onchange=change(<?=$screen['id']?>,'project',this)>
                <?foreach($PROJECT_LIST as $value => $project) { 
                  $selected = ($key === $screen['project']) ? 'selected' : '';
                 ?>
                  <option value="<?=$value?> <?=$selected?>"><?=$project?></option>
                <? } ?>
              </select>
            </td>
            <td>
              <select onchange=change(<?=$screen['id']?>,'model',this)>
                <?foreach($MODEL_LIST as $value => $project) { 
                  $selected = ($key === $screen['model']) ? 'selected' : '';
                ?>
                  <option value="<?=$value?>" <?=$selected?>><?=$project?></option>
                <? } ?>
              </select>
            </td>
            <? foreach($fieldList as $name => $key) { ?>
              <td class="<?= $name?>">
                <span><?= $screen[$key] ?></span>
                <? if (array_search($key, $editable) !== false) { ?>
                  <a onclick="promptchange(<?=$screen['id']?>,'<?=$key?>',this)"><i class="edit fa fa-pencil"></i></a>
                <? } ?>
              </td>
            <? } ?>
            <td>
              <button onclick='command("<?=$screen['id']?>","<?=$screen['car']?>")' class="btn btn-secondary">command</button>
            </td>
          </tr>
        <? } ?>
      </tbody>
    </table>
   </div>

    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ModalLabel">screen naem</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="modal-body">
            ( a few redundant things ) <br/>
            ( feature list ) <br/>
            ( tag list ) (editable)
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary">Update</button>
          </div>
        </div>
      </div>
    </div>

  </div>
    <script>
    var Data=<?=json_encode($screenList);?>
    </script>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="/js/jquery.easing.min.js"></script>
    <script src="/js/sb-admin-2.min.js"></script>
    <script src="/js/jquery.dataTables.min.js"></script>
    <script src="/js/dataTables.bootstrap4.min.js"></script>
    <script src="/Admin/script.js?1"></script>
  </body>
</html>
