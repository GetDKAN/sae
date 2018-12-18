<?php

class SaeTest extends \PHPUnit\Framework\TestCase
{
  public function test() {
    $json_schema = '
    {
       "$schema": "http://json-schema.org/draft-04/schema#",
       "title": "Product",
       "description": "A product from Acme\'s catalog",
       "type": "object",
      
       "properties": {
      
          "id": {
             "description": "The unique identifier for a product",
             "type": "integer"
          },
        
          "name": {
             "description": "Name of the product",
             "type": "string"
          },
        
          "price": {
             "type": "number",
             "minimum": 0,
             "exclusiveMinimum": true
          }
       },
      
       "required": ["id", "name", "price"]
    }
    ';
    $engine = new Sae\Sae(new Memory(), $json_schema);
    $engine->setIdGenerator(new Sequential());

    // Can not retrieve what is not there.
    $this->assertFalse($engine->get(1));

    // Can not post invalid data.
    $this->assertFalse($engine->post("{}"));

    // Can post valid data.
    $json_object = '
    {
      "id": 1, 
      "name": "friend", 
      "price": 20
    }
    ';
    $this->assertEquals(1, $engine->post($json_object));

    // Posted data can be retrieved.
    $this->assertEquals($json_object, $engine->get(1));

    // PUT works.
    $json_object = '
    {
      "id": 2, 
      "name": "enemy", 
      "price": 40
    }
    ';

    $this->assertTrue($engine->put(1, $json_object));

    // Confirm that PUT worked by retrieving the new object.
    $this->assertEquals($json_object, $engine->get(1));

    // PATCH works.
    $json_object = '{"id":2,"name":"enemy","price":50}';

    $json_patch = '
    { 
      "price": 50
    }
    ';

    $this->assertTrue($engine->patch(1, $json_patch));

    // Confirm that PATCH worked by retrieving the new object.
    $this->assertEquals($json_object, $engine->get(1));

    // DELETE works
    $this->assertTrue($engine->delete(1));

    // Confirm that DELETE worked by retrieving the object.
    $this->assertFalse($engine->get(1));

  }
}

class Memory implements \Sae\Contracts\Storage {
  private $storage = [];

  public function retrieve($id)
  {
    if (isset($this->storage[$id])) {
      return $this->storage[$id];
    }
    return FALSE;
  }

  public function store($data, $id = NULL)
  {
    if (!isset($this->storage[$id])) {
      $this->storage[$id] = $data;
      return $id;
    }
    $this->storage[$id] = $data;
    return TRUE;
  }

  public function remove($id)
  {
    if (isset($this->storage[$id])) {
      unset($this->storage[$id]);
      return TRUE;
    }
    return FALSE;
  }
}

class Sequential implements \Sae\Contracts\IdGenerator {
  private $id = 0;
  public function generate() {
    $this->id++;
    return $this->id;
  }
}