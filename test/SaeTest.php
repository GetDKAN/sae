<?php

declare(strict_types=1);

namespace SaeTest;

use \Sae\Sae;

class SaeTest extends \PHPUnit\Framework\TestCase
{

    private $defaultJson = '{
      "id": 1,
      "name": "First Product",
      "dimensions": {
        "length": 1,
        "width": 2.0,
        "height": 3e0
      },
      "price": 4.99
    }';

    private $engine;

    protected function setUp()
    {
        $this->engine = new Sae(new Memory(), file_get_contents(__DIR__ . '/schema.json'));
        $this->engine->setIdGenerator(new Sequential());

      // Start each test with a single product.
        $this->engine->post($this->defaultJson);
    }

    public function testCannotGetMissingData()
    {
        $this->assertNull($this->engine->get("2"));
    }

    public function testCanGetValidData()
    {
        $this->assertEquals($this->defaultJson, $this->engine->get("1"));
    }

    public function testCanGetDataInBulk()
    {
        $json_object1 = $this->engine->get("1");

        $json_object2 = '{
      "id": 2,
      "name": "Product Two",
      "dimensions": {
        "length": 2,
        "width": 2
      },
      "price": 22.0
    }';
        $this->assertEquals(2, $this->engine->post($json_object2));

        $counter = 1;
        foreach ($this->engine->get() as $object) {
            $object_name = "json_object{$counter}";
            $this->assertEquals(${$object_name}, $object);
            $counter++;
        }
    }

    public function testCannotGetBulkFromUnsupportedStorage()
    {
        $unsupported_storage_engine = new Sae(new UnsupportedMemory(), file_get_contents(__DIR__ . '/schema.json'));
        $this->expectExceptionMessage('Neither data for the id, nor storage supporting bulk retrieval found.');
        $data = $unsupported_storage_engine->get();
    }

    public function testCanGetAnEmptySet()
    {
      // Remove item from setUp() before testing for empty set.
        $this->engine->delete("1");
        $data = $this->engine->get();
        $this->assertEmpty($data);
    }

    public function testCanPostValidData()
    {
      // Verify item from setUp().
        $this->assertEquals($this->defaultJson, $this->engine->get("1"));
      // Create and verify a second item.
        $json_post = '{
      "id": 2,
      "name": "Product Two",
      "price": 5.50
    }';
        $this->assertEquals(2, $this->engine->post($json_post));
        $this->assertEquals($json_post, $this->engine->get("2"));
    }

    public function testCannotPostInvalidData()
    {
        $this->expectExceptionMessage(
            '{"valid":false,"errors":['.
            '{"keyword":"required","pointer":"","message":"The attribute property \'id\' is required."},' .
            '{"keyword":"required","pointer":"","message":"The attribute property \'name\' is required."},' .
            '{"keyword":"required","pointer":"","message":"The attribute property \'price\' is required."}]}'
        );
        $this->assertFalse($this->engine->post("{}"));
    }

    public function testCanPutToReplaceValidData()
    {
        $json_put = '{
      "id": 1,
      "name": "First Product Updated by PUT",
      "price": 9.99
    }';
        $this->assertEquals(1, $this->engine->put("1", $json_put));
      // Confirm that PUT worked by retrieving the new object.
        $this->assertEquals($json_put, $this->engine->get("1"));
    }

    public function testCanPutToCreateMissingData()
    {
        $json_put = '{
      "id": 2,
      "name": "Second Product, created by PUT",
      "price": 9.99
    }';
        $this->assertEquals(2, $this->engine->put("2", $json_put));
      // Confirm that PUT worked by retrieving the new object.
        $this->assertEquals($json_put, $this->engine->get("2"));
    }

    public function testCannotPutWithInvalidPayload()
    {
        $invalid_json = '{
      "name": "Product missing required properties id and price"
    }';
        $this->expectExceptionMessage(
            '{"valid":false,"errors":[' .
            '{"keyword":"required","pointer":"","message":"The attribute property \'id\' is required."},' .
            '{"keyword":"required","pointer":"","message":"The attribute property \'price\' is required."}]}'
        );
        $this->assertFalse($this->engine->put("1", $invalid_json));
    }

    public function testCanPatchToModifyData()
    {
        $json_patch = '{
      "name": "First Product, updated by PATCH",
      "dimensions": {
        "length": 1,
        "width": 5.0,
        "height": null
      }
    }';
        $this->assertEquals("1", $this->engine->patch("1", $json_patch));
      // Confirm that PATCH worked by retrieving the udpated object.
        $json_result = '{
      "id": 1,
      "name": "First Product, updated by PATCH",
      "dimensions": {
        "length": 1,
        "width": 5
      },
      "price": 4.99
    }';
        $this->assertEquals(
            json_encode(json_decode($json_result)),
            $this->engine->get("1")
        );
    }

    public function testCannotPatchResultingInInvalidData()
    {
      // Remove price, a required property.
        $json_patch = '{
      "price": null
    }';
        $this->expectExceptionMessage(
            '{"valid":false,"errors":[' .
            '{"keyword":"required","pointer":"","message":"The attribute property \'price\' is required."}]}'
        );
        $this->engine->patch("1", $json_patch);
    }

    public function testCannotPatchMissingData()
    {
        $json_patch = '{
      "id": 2,
      "name": "Non-existent Product",
      "price": 22.0
    }';
        $this->assertFalse($this->engine->patch("2", $json_patch));
    }

    public function testCanDeleteValidData()
    {
        $this->assertTrue($this->engine->delete("1"));
      // Confirm DELETE worked by not retrieving the object.
        $this->assertNull($this->engine->get("1"));
    }

    public function testCannotDeleteMissingData()
    {
        $this->assertFalse($this->engine->delete("3"));
    }
}
