# Модуль оплаты memcached-simplacms.

## Краткое описание
Разработан как класс-обертка над сервисом кеширования данных Memcached, для более удобного взаимодействия с API memcached, в стиле и по правилам CMS SimplaCMS.
Это базовый класс, всю логику формирования и обработку полученных данных формирует программист. Обьект класса позволяет получить либо записать данные из memcached.

## Новые файлы
api/Cache.php базовый класс

## Измененные файлы
### api/Simpla.php

Загрузим новый файл api/Cache.php в место назначения.

Добавим класс Cache в список доступных подклассов Simpla. После строки (38)
```php
'managers'   => 'Managers',
```
добавим
```php
'cache'   => 'Cache'
```

## Настройка
В классе Cache есть свойство, которое говорит само за себя:

```php
private $config = [
        'host' => '127.0.0.1',
        'port' => 11211,
        'extension' => 'memcached', // or memcache
        'lifeTimeCache' => 86400,
    ];

```
Массив с настройками. По умолчанию предпологается использование memcached

## Использование

Можно использовать как в контроллерах, так с моделях API (речь о терминологии MVC). Чаще всего конечно memcached необходимо использовать в API (моделях), так как именно в моделях происходит получение, формирование и предоставление данных, как правило с запросами в базу данных.

Простой пример:

```php
$data = 'Тестовая строка для mem';
$this->cache->set('key', $data);
echo $this->cache->get('key');
```

В следующем примере закешируем запрос к БД. 
Для примера возьмем класс api/Brands.php и его метод get_brands(), который получает список брендов, исходя из условий переданных в качестве параметра.

В стандартном виде мы имеем следующий код ближе к концу метода 

```php
...
// Выбираем все бренды
	$query = $this->db->placehold("SELECT DISTINCT b.id, b.name, b.url, b.meta_title, b.meta_keywords, b.meta_description, b.description, b.image
								 		FROM __brands b $category_id_filter ORDER BY b.name");
	$this->db->query($query);

	return $this->db->results();
...
```

Нас интересует конкретно строка запроса к БД, а именно:

```php
$this->db->query($query);
```

Обернем ее в условный оператор IF:

```php
if($result = $this->cache->get($query)) {
	return $result; // возвращаем данные из memcached
} else {
	$this->db->query($query); // иначе тянем из БД
	$this->cache->set($query, $this->db->results(), false, 86400); //помещаем в кеш
}
```

Итого будет получаться всегда для всех запросов одна и та же картина:

```php
// Выбираем все бренды
	$query = $this->db->placehold("SELECT DISTINCT b.id, b.name, b.url, b.meta_title, b.meta_keywords, b.meta_description, b.description, b.image
								 		FROM __brands b $category_id_filter ORDER BY b.name");

	if($result = $this->cache->get($query)) {
		return $result; // возвращаем данные из memcached
	} else {
		$this->db->query($query); // иначе тянем из БД
		$this->cache->set($query, $this->db->results(), false, 86400); //помещаем в кеш
	}

	return $this->db->results();
```

У нас будет: 
1) Строка SQL запроса $query
2) Сам запрос $this->db->query($query) который нужно завернуть в конструкцию из условного оператора IF/ELSE
3) Возвращение результата
