# Подготовка и заливка данных
Устанавливаем последнюю версию [Osmosis](http://wiki.openstreetmap.org/wiki/Osmosis/Installation).

Скачиваем файл России в формате pbf: [RU.osm.pbf](http://data.gis-lab.info/osm_dump/dump/latest/RU.osm.pbf)

Открываем файл *config.sh* и прописываем необходимые пути и данные БД:
```
osmosis_bin_path="~/osmosis-latest/bin/osmosis"

input_file_path="~/osm_data/RU.osm.pbf"
output_file_path="~/osm_data/routes-ru.osm.pbf"
temp_path="/tmp"

db_host="localhost"
db_name="osm_pt_ru"
db_user="pt_user"
db_password="pt_password"
```

Запускаем терминал и переходим в каталог *prepare_data*.

Устанавливаем права на исполнение:
`chmod u+rx prepare_db.sh update_data.sh`

Выполняем скрипт для создания структуры БД, а также загрузки границ регионов и населенных пунктов:
`./prepare_db.sh`

Выполняем скрипт для подготовки данных и загрузки их в БД:
`./update_data.sh`

Для последующего обновления данных достаточно выполнить скрипт *update_data.sh*
