# OSMPublicTransport
Проект веб-сайта для просмотра и валидации маршрутов общественного транспорта.

## Настройка веб-сервера
Устанавливаем Apache, PHP:
`sudo apt-get install apache2 php5`

Создаем конфигурационный файл osm-public-transport.conf примерно следующего содержания, где /var/www/osm-public-transport - путь до содержимого каталога www:
```
<VirtualHost *:80>
	ServerName osm-public-transport
	ServerAdmin webmaster@localhost
	DocumentRoot /var/www/osm-public-transport
	ErrorLog ${APACHE_LOG_DIR}/osm-public-transport-error.log
	CustomLog ${APACHE_LOG_DIR}/osm-public-transport-access.log combined
</VirtualHost>
```
Кладем этот файл по адресу: `/etc/apache2/sites-available`

Включаем сайт:
`sudo a2ensite osm-public-transport.conf`

Перезапускаем Apache:
`sudo service apache2 restart`

Добавляем следующую строку в файл /etc/hosts :
`127.0.0.1 osm-public-transport`

Теперь сайт доступен по адресу:
`http://osm-public-transport/`

## Настройка базы данных
TODO
