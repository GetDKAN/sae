<?php

namespace SaeTest;

use Contracts\IdGenerator;

class Sequential implements IdGenerator
{
    private $id = 0;
    public function generate()
    {
        return ++$this->id;
    }
}