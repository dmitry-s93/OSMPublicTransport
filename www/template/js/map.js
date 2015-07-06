function parseURL() {
	if (document.location.hash == '') {
		if (document.cookie.substr(19) !== '') {
			document.location.hash = document.cookie.substr(19);
		}
	}

	var param = new Array();

	with (document.location.hash.substr(1)) {
		for(var i=0; i < split('&').length; i++) {
			param['url_'+split('&')[i].split('=')[0]] = split('&')[i].split('=')[1];
		}
	}
	if ('url_map' in param) {
		MapPosition = param['url_map'].split('/');
	} else {
		MapPosition = '3/60.50/107.50'.split('/');
	}
	if ('url_layer' in param) {
		MapBaseLayer = param['url_layer'];
	} else {
		MapBaseLayer = 'S';
	}
	if ('url_route' in param) {
		RouteID = param['url_route'];
	} else {
		RouteID = '';
	}
}

function setMapURL() {
	if (RouteID !== '') {
		var urlRouteID = '&route='+RouteID;
	} else {
		var urlRouteID = '';
	}
	MapUrl= '#map='+map.getZoom()+'/'+map.getCenter().lat.toFixed(4)+'/'+map.getCenter().lng.toFixed(4)+'&layer='+MapBaseLayer+urlRouteID;
	location.replace(MapUrl);
	var date = new Date(new Date().getTime() + 3600 * 1000 * 24 * 365);
	document.cookie = "OSMPublicTransport="+MapUrl+"; path=/; expires=" + date.toUTCString() + ";";
}

function onBaselayerChange() {
	switch (true) {
		case map.hasLayer(MapSurferLayer): MapBaseLayer = 'S'; break;
		case map.hasLayer(SputnikRuLayer): MapBaseLayer = 'K'; break;
		case map.hasLayer(MapnikLayer): MapBaseLayer = 'M'; break;
	}
	setMapURL();
}

function setBaselayer() {
	switch (true) {
		case map.hasLayer(MapSurferLayer): map.removeLayer(MapSurferLayer); break;
		case map.hasLayer(SputnikRuLayer): map.removeLayer(SputnikRuLayer); break;
		case map.hasLayer(MapnikLayer): map.removeLayer(MapnikLayer); break;
	}
	switch (MapBaseLayer) {
		case 'S': map.addLayer(MapSurferLayer); break;
		case 'K': map.addLayer(SputnikRuLayer); break;
		case 'M': map.addLayer(MapnikLayer); break;
	}
	setMapURL();
}

function setLayersOrder() {
	if (map.hasLayer(StopLayer)) {
		StopLayer.bringToFront();
	}
	if (map.hasLayer(PlatformLayer)) {
		PlatformLayer.bringToFront();
	}
	if (map.hasLayer(StationLayer)) {
		StationLayer.bringToFront();
	}
}

function clearRouteLayer() {
	RouteLayer.clearLayers();
	StationLayer.clearLayers();
	PlatformLayer.clearLayers();
	StopLayer.clearLayers();

	RouteID = '';

	$('#content_panel').hide();
	map.invalidateSize();
	getData();
	setMapURL();
}

var RouteLayer = new L.FeatureGroup();
var StationLayer = new L.FeatureGroup();
var PlatformLayer = new L.FeatureGroup();
var StopLayer = new L.FeatureGroup();

function getRouteData(rID) {
	if (rID !== '') {
		RouteID = rID;
		RouteLayer.clearLayers();
		StationLayer.clearLayers();
		PlatformLayer.clearLayers();
		StopLayer.clearLayers();
		$("#platform-list").empty();
		$("#stop-position-list").empty();
		createRouteInfo();
		$.ajax({
			type: "POST",
			url: "/ajax/get_route_data.php",
			data: {
				id: rID
			},
			dataType: "script",
			async: false,
			success: function(data){
				if (typeof geojsonRoute !== "undefined") {
					L.geoJson(geojsonRoute, {
						style: {
							"color": "#1E90FF",
							"weight": 6,
							"opacity": 0.6
						},
						onEachFeature: bindRoutePopup
					}).addTo(RouteLayer);
					delete geojsonRoute;
				}
				if (typeof geojsonStops !== "undefined") {
					L.geoJson(geojsonStops, {
						style: {
							"color": "#FFFFFF",
							"weight": 1,
							"opacity": 1
						},
						pointToLayer: function (feature, latlng) {
							return L.circleMarker(latlng, {
								radius: 6,
								fillColor: "#1E90FF",
								color: "#000",
								weight: 2,
								opacity: 1,
								fillOpacity: 1
							});
						},
						onEachFeature: function (feature, layer) {
							layer.on('click', function() {
								loadFeaturePopupData(feature, layer);
							});
							createListElements(feature, layer);
						}
					}).addTo(StopLayer);
					delete geojsonStops;
				}
				if (typeof geojsonPlatforms !== "undefined") {
					L.geoJson(geojsonPlatforms, {
						style: {
							"color": "#1E90FF",
							"weight": 2,
							"opacity": 1
						},
						pointToLayer: function (feature, latlng) {
							return L.circleMarker(latlng, {
								radius: 6,
								fillColor: "#FFFFFF",
								color: "#000",
								weight: 1,
								opacity: 1,
								fillOpacity: 1
							});
						},
						onEachFeature: function (feature, layer) {
							layer.on('click', function() {
								loadFeaturePopupData(feature, layer);
							});
							createListElements(feature, layer);
						}
					}).addTo(PlatformLayer);
					delete geojsonPlatforms;
				}

				map.addLayer(RouteLayer);
				map.addLayer(StopLayer);
				map.addLayer(PlatformLayer);
				setLayersOrder();

				if (document.getElementById('stop-position-list').childNodes.length > document.getElementById('platform-list').childNodes.length) {
					document.getElementById("SelectList").options[1].selected=true;
					document.getElementById('platform-list').style.display = 'none';
					document.getElementById('stop-position-list').style.display = 'block';
				}
				$('#content_panel').show();
				map.invalidateSize();
				map.fitBounds(RouteLayer.getBounds());
				setMapURL();
			}
		});
	}
}

function getData() {
	if (RouteID == '') {
		StationLayer.clearLayers();
		PlatformLayer.clearLayers();
		StopLayer.clearLayers();
		if (map.getZoom() > 14) {
			document.getElementById("top-message-box").innerHTML = "Загрузка";
			$('#top-message-box').fadeIn();

			if (map.hasLayer(StationLayer)) {
				getStations = true;
			} else {
				getStations = false;
			}
			if (map.hasLayer(PlatformLayer)) {
				getPlatforms = true;
			} else {
				getPlatforms = false;
			}
			if (map.hasLayer(StopLayer)) {
				getStops = true;
			} else {
				getStops = false;
			}

			var bbox = map.getBounds();
			$.ajax({
				type: "POST",
				url: "/ajax/get_data.php",
				data: {
					point1: (bbox._southWest.lng)+","+(bbox._southWest.lat),
					point2: (bbox._northEast.lng)+","+(bbox._northEast.lat),
					station: getStations,
					platform: getPlatforms,
					stop_pos: getStops
				},
				dataType: "script",
				async: true,
				success: function(data){
					if (typeof geojson_stop_positions !== "undefined") {
						L.geoJson(geojson_stop_positions, {
							style: {
								"color": "#FFFFFF",
								"weight": 1,
								"opacity": 1
							},
							pointToLayer: function (feature, latlng) {
								return L.circleMarker(latlng, {
									radius: 6,
									fillColor: "#1E90FF",
									color: "#000",
									weight: 2,
									opacity: 1,
									fillOpacity: 1
								});
							},
							onEachFeature: function (feature, layer) {
								layer.on('click', function() {
									loadFeaturePopupData(feature, layer);
								});
							}
						}).addTo(StopLayer);
						delete geojson_stop_positions;
					}
					if (typeof geojson_platforms !== "undefined") {
						L.geoJson(geojson_platforms, {
							style: {
								"color": "#1E90FF",
								"weight": 2,
								"opacity": 1
							},
							pointToLayer: function (feature, latlng) {
								return L.circleMarker(latlng, {
									radius: 6,
									fillColor: "#FFFFFF",
									color: "#000",
									weight: 1,
									opacity: 1,
									fillOpacity: 1
								});
							},
							onEachFeature: function (feature, layer) {
								layer.on('click', function() {
									loadFeaturePopupData(feature, layer);
								});
							}
						}).addTo(PlatformLayer);
						delete geojson_platforms;
					}
					if (typeof geojson_stations !== "undefined") {
						L.geoJson(geojson_stations, {
							style: {
								"color": "#008000",
								"weight": 3,
								"opacity": 1
							},
							pointToLayer: function (feature, latlng) {
								return L.circleMarker(latlng, {
									radius: 8,
									fillColor: "#FFFFFF",
									color: "#000",
									weight: 1,
									opacity: 1,
									fillOpacity: 1
								});
							},
							onEachFeature: function (feature, layer) {
								layer.on('click', function() {
									loadFeaturePopupData(feature, layer);
								});
							}
						}).addTo(StationLayer);
						delete geojson_stations;
					}
				}
			});
			$('#top-message-box').fadeOut();
		} else {
			document.getElementById("top-message-box").innerHTML = "Приблизьте карту";
			$('#top-message-box').fadeIn();
		}
	}
}

function loadFeaturePopupData(feature, layer) {
	var popupContent;
	$.ajax({
		type: "POST",
		url: "/ajax/get_detail_info.php",
		data: {
			id: feature.properties.id,
		},
		dataType: "text",
		async: false,
		success: function(data){
			var busRes = '';
			var trolleybusRes = '';
			var sharetaxiRes = '';
			var tramRes = '';
			var trainRes = '';
			if (data !== '') {
				routes = JSON.parse(data);
				for (var i = 0, length = routes.length; i < length; i++) {
					if (i in routes) {
						switch (routes[i].type) {
							case 'bus':
								busRes += '<a href="#" onclick="getRouteData('+routes[i].id+'); return false;">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'trolleybus':
								trolleybusRes += '<a href="#" onclick="getRouteData('+routes[i].id+'); return false;">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'sharetaxi':
								sharetaxiRes += '<a href="#" onclick="getRouteData('+routes[i].id+'); return false;">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'tram':
								tramRes += '<a href="#" onclick="getRouteData('+routes[i].id+'); return false;">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'train':
								trainRes += '<a href="#" onclick="getRouteData('+routes[i].id+'); return false;">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
						}
					}
				}
			}
			if (feature.properties) {
				switch (feature.properties.type) {
					case 'station':
						featureType = 'Станция';
						break;
					case 'platform':
						featureType = 'Остановка транспорта';
						break;
					case 'stop':
						featureType = 'Место остановки транспорта';
						break;
				}
				if (feature.properties.name == "") {
					feature.properties.name = "Без названия";
				}

				popupContent = "<span id='popup-title'>" + feature.properties.name + "</span>";
				popupContent += "<br>" + featureType + "<hr>";

				if (busRes !== '') {
					popupContent += '<b>Автобусы:</b><br>' + busRes;
				}
				if (trolleybusRes !== '') {
					popupContent += '<b>Троллейбусы:</b><br>' +trolleybusRes;
				}
				if (sharetaxiRes !== '') {
					popupContent += '<b>Маршрутные такси:</b><br>' +sharetaxiRes;
				}
				if (tramRes !== '') {
					popupContent += '<b>Трамваи:</b><br>' +tramRes;
				}
				if (trainRes !== '') {
					popupContent += '<b>Поезда:</b><br>' +trainRes;
				}
				if (feature.properties.description == '') {
					popupContent += feature.properties.description;
				}
			}

		}
	});
	layer.bindPopup(popupContent);
	layer.openPopup();
}

function bindRoutePopup(feature, layer) {

	var popupContent;

	if (feature.properties) {

		if (feature.properties.type == 'route') {
			popupContent = "<b>" + feature.properties.name + "</b>";
			popupContent += "<hr>";
			popupContent += feature.properties.description;
		}
		layer.bindPopup(popupContent);
	}
}

function createRouteInfo() {
	var contentPanel = document.getElementById('content_panel');
	contentPanel.innerHTML =
		'<div align="center"><a href="#" onclick="clearRouteLayer(); return false;">Закрыть маршрут</a></div> \n\
		<form action="" align="center"> \n\
				<select id="SelectList" onchange="SetList()"> \n\
					<option value="platform"> Остановки / платформы </option> \n\
					<option value="stop_position"> Места остановок </option> \n\
				</select> \n\
		</form> \n\
		<ol id="platform-list" class="marker-list"></ol> \n\
		<ol id="stop-position-list" class="marker-list" style="display: none;"></ol>';
}

function createListElements(feature, layer) {
	if (feature.properties) {
		if (feature.properties.type == 'platform') {
			var item = document.getElementById('platform-list').appendChild(document.createElement('li'));
			item.innerHTML = feature.properties.name;
			item.onclick = function() {
				loadFeaturePopupData(feature, layer);
			};
		}
		if (feature.properties.type == 'stop') {
			var item = document.getElementById('stop-position-list').appendChild(document.createElement('li'));
			item.innerHTML = feature.properties.name;
			item.onclick = function() {
				loadFeaturePopupData(feature, layer);
			};
		}
	}
}

function SetList() {
	var list_id = document.getElementById('SelectList').selectedIndex;
	if (list_id == 0) {
		document.getElementById('platform-list').style.display = 'block';
		document.getElementById('stop-position-list').style.display = 'none';
	}
	if (list_id == 1) {
		document.getElementById('platform-list').style.display = 'none';
		document.getElementById('stop-position-list').style.display = 'block';
	}
}

var OSMAttr = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
	CCBYSAAttr = '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';

var MapSurferAttr = OSMAttr+', ' + CCBYSAAttr + ', Rendering <a href="http://giscience.uni-hd.de/">GIScience Research Group @ Heidelberg University</a>',
	SputnikAttr = OSMAttr+', ' + CCBYSAAttr + ', Tiles <a href="http://www.sputnik.ru/">© Спутник</a>',
	MapnikAttr = OSMAttr+', ' + CCBYSAAttr,
	PTAttr = 'Маршруты © <a href="http://www.openmap.lt/">openmap.lt</a>';

var MapSurferUrl = 'http://129.206.74.245/tiles/roads/x={x}&y={y}&z={z}',
	SputnikUrl = 'http://tiles.maps.sputnik.ru/{z}/{x}/{y}.png',
	MapnikUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
	PTUrl = 'http://pt.openmap.lt/{z}/{x}/{y}.png';

var MapSurferLayer   = L.tileLayer(MapSurferUrl, {attribution: MapSurferAttr}),
	SputnikRuLayer  = L.tileLayer(SputnikUrl, {attribution: SputnikAttr}),
	MapnikLayer  = L.tileLayer(MapnikUrl, {attribution: MapnikAttr}),
	PTLayer = L.tileLayer(PTUrl, {attribution: PTAttr});

var baseLayers = {
	"MapSurfer": MapSurferLayer,
	"sputnik.ru": SputnikRuLayer,
	"Mapnik": MapnikLayer
};

var overlays = {
	"Слой маршрутов": PTLayer,
	"Станции": StationLayer,
	"Остановки / платформы": PlatformLayer,
	"Места остановок": StopLayer,
};

parseURL();

var map = L.map('map', {
	center: [MapPosition[1], MapPosition[2]],
	zoom: MapPosition[0],
	closePopupOnClick: false
});

L.control.layers(baseLayers, overlays).addTo(map);
L.control.scale().addTo(map);

L.control.fullscreen({
	position: 'topleft',
	title: 'Full Screen',
	forceSeparateButton: true,
	forcePseudoFullscreen: false
}).addTo(map);

L.control.locate({
	icon: 'fa fa-map-marker',
	iconLoading: 'fa fa-spinner fa-spin',
	onLocationError: function(err) {alert(err.message)},
	onLocationOutsideMapBounds:  function(context) {
			alert(context.options.strings.outsideMapBoundsMsg);
	},
	strings: {
		title: "Show me where I am",
		popup: "Вы находитесь в пределах {distance} м. от этой точки",
		outsideMapBoundsMsg: "You seem located outside the boundaries of the map"
	}
}).addTo(map);

var topMessage = L.Control.extend({
	options: {
		position: 'topleft'
	},
	onAdd: function (map) {
		var container = L.DomUtil.create('div', 'top-message');
		container.id = 'top-message-box';
		return container;
	}
});

map.addControl(new topMessage());

map.addLayer(PlatformLayer);
map.addLayer(StationLayer);

setBaselayer();
getData();

getRouteData(RouteID);

map.on('baselayerchange', onBaselayerChange);
map.on('overlayadd', function () {
	getData();
	setLayersOrder();
});
map.on('moveend', setMapURL);
map.on('dragend', getData);
map.on('zoomend', getData);
