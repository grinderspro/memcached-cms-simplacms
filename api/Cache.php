<?php

/**
 * SimplaCMS memcacheD
 *
 * Класс обертка memcached для удобного использования в SimplaCMS
 *
 * @copyright	Copyright (c) 2016 Grinderspro
 * @link		http://grinderspro.ru
 * @author		Grigoriy Miroschnichenko <grinderspro@gmail.com>
 */

require_once('Simpla.php');

class Cache extends Simpla
{

    /**
     * Конструктор класса
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Будущий экземпляр memcache
     */
    public $mem;

    /**
     * Массив - конфигурация параметров
     *
     * @return array customized attribute labels
     */
    private $config = [
        'host' => '127.0.0.1',
        'port' => 11211,
        'extension' => 'memcached', // or memcache
        'lifeTimeCache' => 86400,
    ];

    /**
     * Флаг типа используемого расширение PHP
     *
     * True - memcache
     * False - memcached
     */
    private $isMemcached = false;

    /**
     * Инициализация
     */
    private function init()
    {
        $this->isMemcached = $this->isMemcachedUse();

        if (!extension_loaded($this->config['extension'])) {
            throw new Exception("Php extension {$this->config['extension']} not foun. Please install the memcache extension.");
        }

        $this->isMemcached ? $this->mem = new Memcached() : $this->mem = new Memcache();
        $this->mem->addServer($this->config['host'], $this->config['port']);
    }

    /**
     * Извлекает значение из кеша с указанным ключом.
     *
     * @param string $stringKey уникальный ключ
     */
    public function get($stringKey)
    {
        if($this->mem != null)
            $result = $this->mem->get($this->stingToHash($stringKey));

        if (!empty($result))
            return $result;
        else
            return false;
    }

    /**
     * Помещает знаение в кеш по ключу
     *
     * @param string $stringKey ключ
     * @param $value Резальтирующий набор (набор данных, которые необходимо положить в кеш)
     * @param $lifeCache время жизни кеша
     */
    public function set($stringKey, $value, $lifeCache = 86400)
    {
        if ($this->isMemcached) {
            $this->mem->set($this->stingToHash($stringKey), $value, $lifeCache);
        } else {
            $this->mem->set($this->stingToHash($stringKey), $value, 0, $lifeCache);
        }
    }

    /**
     * Удаляет значение из памяти по ключу
     *
     * @var string $stringKey attribute labels
     */
    public function del($stringKey)
    {
        $this->mem->delete($this->stingToHash($stringKey));
    }

    /**
     * Аннулирует все существующие записи в кеше
     *
     * @var integer $delay Период времени, по истечению которого произвести полную очистку кеша
     * @return true or false
     */
    public function clearall($delay = 0)
    {
        $this->mem->flush();
    }

    /**
     * Из обычной строки в md5 hash
     *
     * @var string $stringKey - Ключ
     */
    private function stingToHash($stringKey)
    {
        return md5('key'.$stringKey);
    }

    /**
     * Проверяет исходя из значений конфигурационного массива $this->config
     * используется ли PHP расширение memcached
     */
    private function isMemcachedUse()
    {
        if($this->config['extension'] == 'memcached')
            return true;
    }

}