<?php
namespace booosta\database;

require_once __DIR__ . '/../vendor/autoload.php';

class DB extends \booosta\mysqli\Mysqli
{
  use DBtrait;
}
