<?
use Ramsey\Uuid\Uuid;

function dsp_create() {
  return Uuid::uuid4()->toString();
}

function dsp_sow($params) {
  error_log(print_r($params, true));
}

function dsp_default($params) {
  error_log(print_r($params, true));
}
