<?
include_once('lib.php');
use Ramsey\Uuid\Uuid;

function dsp_create() {
  return Uuid::uuid4()->toString();
}

function dsp_sow($params) {
  return sow($params);
  //error_log(print_r($params, true));
}

function dsp_default($params) {
  error_log(print_r($params, true));
}
