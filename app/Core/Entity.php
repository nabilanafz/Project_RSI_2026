<?php
declare(strict_types=1);
/** Nilai atribut menggunakan nama dari class diagram; model tetap menyembunyikan data mentah. */
abstract class Entity implements ArrayAccess  {
    protected array $data;
    public function __construct(array $data=[])  {
        $this->data=$data;
        foreach(['targetJurusan','dokumenPersyaratan'] as $key) if(isset($this->data[$key]) && is_string($this->data[$key])) $this->data[$key]=json_decode($this->data[$key],true,512,JSON_THROW_ON_ERROR);
    }
    public function get(string $key,mixed $default=null):mixed  {
        return $this->data[$key]??$default;
    }
    public function toArray():array  {
        return $this->data;
    }
    public function offsetExists(mixed $offset):bool {
        return isset($this->data[$offset]);
    }
    public function offsetGet(mixed $offset):mixed {
        return $this->get((string)$offset);
    }
    public function offsetSet(mixed $offset,mixed $value):void {
        throw new LogicException('Gunakan operasi model untuk mengubah data.');
    }
    public function offsetUnset(mixed $offset):void {
        throw new LogicException('Gunakan operasi model untuk mengubah data.');
    }
}
