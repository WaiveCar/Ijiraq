<?
function curldo($url, $params = false, $opts = []) {
  $verb = strtoupper(isset($opts['verb']) ? $opts['verb'] : 'GET');

  $ch = curl_init();

  $header = [];
    
  if($verb !== 'GET') {
    if(!isset($opts['isFile'])) {
      if(!$params) {
        $params = [];
      }
      if(!empty($opts['json'])) {
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

  if(isset($opts['auth'])) {
    curl_setopt($ch, CURLOPT_USERPWD, implode(':', [aget($opts, 'auth.user'), aget($opts, 'auth.password')]));
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

echo curldo('http://localhost:5000/buy', $_POST, ['raw' => true]);
