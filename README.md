# OSMPublicTransport
Проект веб-сайта для просмотра и валидации маршрутов общественного транспорта.

## Настройка веб-сервера
Устанавливаем Apache, PHP:
```
sudo apt-get install apache2 php5
```

Создаем конфигурационный файл *osm-public-transport.conf* примерно следующего содержания, где */var/www/osm-public-transport* - путь до содержимого каталога *www*:
```
<VirtualHost *:80>
	ServerName osm-public-transport
	ServerAdmin webmaster@localhost
	DocumentRoot /var/www/osm-public-transport
	ErrorLog ${APACHE_LOG_DIR}/osm-public-transport-error.log
	CustomLog ${APACHE_LOG_DIR}/osm-public-transport-access.log combined
</VirtualHost>
```
Кладем этот файл по адресу: */etc/apache2/sites-available*

Включаем сайт:
```
sudo a2ensite osm-public-transport.conf
```

Включаем mod_rewrite:
```
sudo a2enmod rewrite
```

Перезапускаем Apache:
```
sudo service apache2 restart
```

Добавляем следующую строку в файл /etc/hosts :
```
127.0.0.1 osm-public-transport
```

Теперь сайт доступен по адресу: *http://osm-public-transport/*

## Настройка базы данных
Устанавливаем PostgreSQL с PostGIS:
```
sudo apt-get install postgresql-9.4-postgis-2.1 postgresql-contrib php5-pgsql
```

Перезапускаем Apache:
```
sudo service apache2 restart
```

В процессе установки *PostgreSQL* автоматически был создан пользователь *postgres*.

Назначаем пароль пользователю *postgres*:
```
sudo passwd postgres
```

Заходим под пользователем *postgres*:
```
su postgres
```

Вводим:`psql`

Создаем базу данных и пользователя:
```
CREATE DATABASE osm_pt_ru;

CREATE USER pt_user WITH password 'pt_password';

GRANT ALL privileges ON DATABASE osm_pt_ru TO pt_user;
```

Для выхода вводим:`\q`

Вводим:`psql osm_pt_ru`

Загружаем необходимые расширения:
```
CREATE EXTENSION postgis;

CREATE EXTENSION postgis_topology;

CREATE EXTENSION hstore;
```

Для выхода вводим:`\q`

Прописываем параметры БД в файле проекта *www/include/db_connect.php*.

## Подготовка и заливка данных
См. *README.md* в каталоге *prepare_data*.
