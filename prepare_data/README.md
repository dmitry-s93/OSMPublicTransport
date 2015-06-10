# Подготовка и заливка данных
Устанавливаем последнюю версию [Osmosis](http://wiki.openstreetmap.org/wiki/Osmosis/Installation).

Скачиваем файл России в формате pbf: [RU.osm.pbf](http://data.gis-lab.info/osm_dump/dump/latest/RU.osm.pbf)

Открываем файл *config.sh* и прописываем необходимые параметры:
```
osmosis_bin_path - путь к исполняемому файлу Osmosis.
input_file_path - путь к файлу *RU.osm.pbf*.
output_file_path - путь к файлу, который будет получен в процессе обработки.
temp_path - каталог для хранения промежуточных файлов.
db_name - имя базы данных.
db_user - имя пользователя БД.
db_password - пароль.
```

Запускаем терминал и переходим в каталог *prepare_data*.

Устанавливаем права на исполнение:
`chmod u+rx prepare_db.sh update_data.sh`

Выполняем скрипт для создания структуры БД:
`./prepare_db.sh`

Выполняем скрипт для подготовки данных и загрузки их в БД:
`./update_data.sh`

Для последующего обновления данных достаточно выполнить скрипт *update_data.sh*
