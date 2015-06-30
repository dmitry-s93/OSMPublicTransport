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
		urlRouteID = '&route='+RouteID;
	} else {
		urlRouteID = '';
	}
	MapUrl= '#map='+map.getZoom()+'/'+map.getCenter().lat.toFixed(4)+'/'+map.getCenter().lng.toFixed(4)+'&layer='+MapBaseLayer+urlRouteID;
	location.replace(MapUrl);
	document.cookie = "OSMPublicTransport="+MapUrl;
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

function clearRouteLayer() {
	RouteLayer.clearLayers();
	RoutePlatformLayer.clearLayers();
	RouteStopLayer.clearLayers();

	RouteID = '';

	$('#route-panel').fadeOut();
	getData();
	setMapURL();
}

var RouteLayer = new L.FeatureGroup();
var RouteStopLayer = new L.FeatureGroup();
var RoutePlatformLayer = new L.FeatureGroup();

function getRouteData(rID) {
	if (rID !== '') {
		DataLayer.clearLayers();
		RouteLayer.clearLayers();
		RoutePlatformLayer.clearLayers();
		RouteStopLayer.clearLayers();
		$("#platform-list").empty();
		$("#stop-position-list").empty();
		$('#data-filter-box').fadeOut();
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
					}).addTo(RouteStopLayer);
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
					}).addTo(RoutePlatformLayer);
					delete geojsonPlatforms;
				}

				map.addLayer(RouteLayer);
				map.addLayer(RouteStopLayer);
				map.addLayer(RoutePlatformLayer);

				if (document.getElementById('stop-position-list').childNodes.length > document.getElementById('platform-list').childNodes.length) {
					document.getElementById("SelectList").options[1].selected=true;
					document.getElementById('platform-list').style.display = 'none';
					document.getElementById('stop-position-list').style.display = 'block';
				}
				RouteID = rID;
				$('#route-panel').fadeIn();
				map.fitBounds(RouteLayer.getBounds());
				setMapURL();
			}
		});
	}
}

var DataLayer = new L.FeatureGroup();

function getData() {
	if (RouteID == '') {
		DataLayer.clearLayers();
		$('#data-filter-box').fadeIn();
		if (map.getZoom() > 14) {
			document.getElementById("top-message-box").innerHTML = "Загрузка";
			$('#top-message-box').fadeIn();

			if (document.getElementById("station-checkbox").checked) {
				getStations = true;
			} else {
				getStations = false;
			}
			if (document.getElementById("platform-checkbox").checked) {
				getPlatforms = true;
			} else {
				getPlatforms = false;
			}
			if (document.getElementById("stop-checkbox").checked) {
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
					stations: getStations,
					platforms: getPlatforms,
					stops: getStops
				},
				dataType: "script",
				async: true,
				success: function(data){
					if (typeof geojsonResult !== "undefined") {
						L.geoJson(geojsonResult, {
							style: function (feature) {
								if (feature.properties.type == 'station') {
									return {
										"color": "#008000",
										"weight": 3,
										"opacity": 1
									}
								}
								if (feature.properties.type == 'platform') {
									return {
										"color": "#1E90FF",
										"weight": 2,
										"opacity": 1
									}
								}
								if (feature.properties.type == 'stop') {
									return {
										"color": "#FFFFFF",
										"weight": 1,
										"opacity": 1
									}
								}
							},
							pointToLayer: function (feature, latlng) {
								if (feature.properties.type == 'station') {
									return L.circleMarker(latlng, {
										radius: 8,
										fillColor: "#FFFFFF",
										color: "#000",
										weight: 1,
										opacity: 1,
										fillOpacity: 1
									});
								}
								if (feature.properties.type == 'platform') {
									return L.circleMarker(latlng, {
										radius: 6,
										fillColor: "#FFFFFF",
										color: "#000",
										weight: 1,
										opacity: 1,
										fillOpacity: 1
									});
								}
								if (feature.properties.type == 'stop') {
									return L.circleMarker(latlng, {
										radius: 6,
										fillColor: "#1E90FF",
										color: "#000",
										weight: 2,
										opacity: 1,
										fillOpacity: 1
									});
								}
							},
							onEachFeature: function (feature, layer) {
								layer.on('click', function() {
									loadFeaturePopupData(feature, layer);
								});
							}
						}).addTo(DataLayer);
						map.addLayer(DataLayer);
						delete geojsonResult;
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
	"Общественный транспорт": PTLayer,
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

var dataFilter = L.Control.extend({
	options: {
		position: 'topleft'
	},
	onAdd: function (map) {
		var container = L.DomUtil.create('div', 'data-filter');
		container.id = 'data-filter-box';
		container.innerHTML =
			'<input type="checkbox" id="station-checkbox" onclick="getData()"  checked>Станции<Br>'+
			'<input type="checkbox" id="platform-checkbox" onclick="getData()" checked>Остановки / платформы<Br>'+
			'<input type="checkbox" id="stop-checkbox" onclick="getData()">Места остановок<Br>';
		return container;
	}
});

map.addControl(new dataFilter());

var routePanel = L.Control.extend({
	options: {
		position: 'topleft'
	},
	onAdd: function (map) {
		var container = L.DomUtil.create('div', 'route-panel');
		container.id = 'route-panel';
		container.innerHTML =
			'<div id="infoPanelTop"> \n\
			<a href="#" onclick="clearRouteLayer(); return false;"><i class="fa fa-times"></i></a> \n\
		</div> \n\
		<form action="" align="center"> \n\
				<select id="SelectList" onchange="SetList()"> \n\
					<option value="platform"> Остановки / платформы </option> \n\
					<option value="stop_position"> Места остановок </option> \n\
				</select> \n\
		</form> \n\
		<ol id="platform-list" class="marker-list"></ol> \n\
		<ol id="stop-position-list" class="marker-list" style="display: none;"></ol>';
		return container;
	}
});

map.addControl(new routePanel());


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

setBaselayer();
getData();

getRouteData(RouteID);

map.on('baselayerchange', onBaselayerChange);
map.on('moveend', setMapURL);
map.on('dragend', getData);
map.on('zoomend', getData);
