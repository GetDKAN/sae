<?php

namespace SaeTest;

use Contracts\StorageInterface;

class UnsupportedMemory implements StorageInterface
{
    private $storage = [];

    public function retrieve(string $id): ?string
    {
        if (isset($this->storage[$id])) {
            return $this->storage[$id];
        }
        return null;
    }

    public function store(string $data, string $id = null): string
    {
        if (!isset($this->storage[$id])) {
            $this->storage[$id] = $data;
            return $id;
        }
        $this->storage[$id] = $data;
        return $id;
    }

    public function remove(string $id)
    {
        if (isset($this->storage[$id])) {
            unset($this->storage[$id]);
            return true;
        }
        return false;
    }
}