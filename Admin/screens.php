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
    $screenList[$ix]['addr'] = "<a href=//maps.google.com/?q={$screenList[ix]['lat']},{$screenList[ix]['lng']}>{$addrList[$ix]}</a>";
  } else {
    $screenList[$ix]['addr'] = 'unknown';
  }
  foreach(['first','last'] as $key) {
    $screenList[$ix]["{$key}_local"] = strtotime(str_replace(' ', 'T', $screenList[$ix]["{$key}_seen"] . 'Z'));
  }
  $screenList[$ix]['last_local'] = time() -  $screenList[$ix]['last_local'];
  $screenList[$ix]['first_local'] = date("Y-m-d H:i:s", $screenList[$ix]['first_local']);
}

$fieldList = [
  'id'  => 'uid',
  'car' => 'car',
  'location' => 'addr',
  'phone' => 'phone',
  'version' => 'version',
  'last' => 'last_local',
  'first' => 'first_local'
];
$editable = ['car'];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel=stylesheet href=https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>Admin panel</title>
  </head>
  <style>
    .id,.version { font-family: monospace}
  </style>
  <body>
  <h2>screens <span class='id'><?= $VERSION?></span></h2>
  <table class="table">
    <thead>
      <tr>
      <? foreach($fieldList as $key => $value) { ?>
        <th scope="col"><?= $key ?></th>
      <? } ?>
      </tr>
    </thead>
  <tbody>
    <? foreach($screenList as $screen) { ?>
      <tr>
        <? foreach($fieldList as $name => $key) { ?>
          <td class="<?= $name?>">
            <?= $screen[$key] ?>
						<? if (array_search($key, $editable) !== false) { ?>
							<a onclick="change(<?=$screen['id']?>,'<?=$key?>')"><i class="fa fa-pencil"></i></a>
						<? } ?>
          </td>
      <? } ?>
      </tr>
    <? } ?>
  </tbody>
  </table>
    </div>
    <script src="/Admin/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  </body>
</html>
