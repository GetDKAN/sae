<?php

namespace Sae;

use Sae\Contracts\Storage;
use Sae\Contracts\IdGenerator;

class Sae
{
  /**
   * @var Storage
   */
  private $storage;
  private $jsonSchema;

  /**
   * @var IdGenerator
   */
  private $idGenerator;

  public function __construct(Storage $storage, $json_schema) {
    $this->storage = $storage;
    $this->jsonSchema = $json_schema;
  }

  public function setIdGenerator(IdGenerator $id_generator) {
    $this->idGenerator = $id_generator;
  }

  public function  get($id) {
    return $this->storage->retrieve($id);
  }

  public function post($json_data) {

    if (!$this->validate($json_data)) {
      return FALSE;
    }

    $id = Null;
    if ($this->idGenerator) {
      $id = $this->idGenerator->generate();
    }
    return $this->storage->store($json_data, $id);

  }

  public function  put($id, $json_data) {
    if (!$this->validate($json_data)) {
      return FALSE;
    }

    return $this->storage->store($json_data, $id);
  }

  public function  patch($id, $json_data) {
    $json_data_original = $this->storage->retrieve($id);
    $data_original = (array) json_decode($json_data_original);
    $data = (array) json_decode($json_data);

    $new = json_encode((object) array_merge($data_original, $data));

    if (!$this->validate($new)) {
      return FALSE;
    }

    return $this->storage->store($new, $id);

  }

  public function  delete($id) {
    return $this->storage->remove($id);
  }

  public function validate($json_data) {
    $data = json_decode($json_data);

    $validator = new \JsonSchema\Validator;
    $validator->validate($data, json_decode($this->jsonSchema));

    $is_valid = $validator->isValid();

    return $is_valid;
  }
}