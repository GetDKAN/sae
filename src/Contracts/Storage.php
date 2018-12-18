<?php

namespace Sae\Contracts;

interface Storage
{
  public function retrieve($id);
  public function store($data, $id = Null);
  public function remove($id);
}