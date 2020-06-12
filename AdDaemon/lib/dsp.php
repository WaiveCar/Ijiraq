<?
use Ramsey\Uuid\Uuid;

function dsp_create() {
  return Uuid::uuid4()->toString();
}

function dsp_sow() {
}

function dsp_default() {
}
