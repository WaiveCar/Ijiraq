<?
include_once('lib.php');
use Ramsey\Uuid\Uuid;

function dsp_create() {
  return Uuid::uuid4()->toString();
}

function dsp_signup($params) {
  $email = $params['email'];
  $user = Get::user(['email' => $email]);
  if(!$user) {
    $user = create('user', ['email' => $email]);
    $hoard = create('hoard', [
      'user_id' => $user['id'],
      'uuid' => Uuid::uuid4()->toString()
    ]);
  } else {
    $hoard = Get::hoard(['user_id' => $user['id']]);
  }
  return doSuccess($hoard);
}

function dsp_sow($params) {
  return sow($params);
  //error_log(print_r($params, true));
}

function dsp_default($params) {
  error_log(print_r($params, true));
}

function dsp_ping($params) {
  return ping($params);
}


/*
 * This is mostly a sanity check
 *
 * number of active screens say in the past X time period
 *    select distinct screen_id from job where last_update > today - 7 days and hoard_id = xxx
 *
 * revenue of a given screen
 *    select sum(completed_seconds) from job where hoard_id = xxx and screen_id = yyy
 *
 * revenue of all screens
 *    ... without the screen ...
 *
 * ---- notification of screens
 * coming online for the first time
 *
 *    currently expensive.
 *
 *    It really can't be done in batch on a schedule without extra accounting.
 *
 * going offline
 *
 *    currently expensive.
 *
 *    It can be done in batch on a schedule
 *
 * This is kinda bullshit. "how many screens do I have?" is a simple question.
 *
 * Looks like there needs to be a meta-table of hoard/screen eventually - but
 * let's skip over that for now. (2020-07-19)
 */
