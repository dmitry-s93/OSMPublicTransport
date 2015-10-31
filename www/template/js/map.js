var MapBaseLayer;
var MapOverlays;
var MapOverlaysTmp;
var RouteID;

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
		MapBaseLayer = 'M';
	}
	if ('url_overlays' in param) {
		MapOverlays = param['url_overlays'];
	} else {
		MapOverlays = 'P';
	}
	if ('url_route' in param) {
		RouteID = param['url_route'];
	} else {
		RouteID = '';
	}
}

function setMapURL() {
	var urlRouteID = '';
	var urlMapOverlays = '';
	if (RouteID !== '') {
		urlRouteID = '&route='+RouteID;
	}
	if (MapOverlays !== '') {
		urlMapOverlays = '&overlays='+MapOverlays;
	}
	MapUrl= '#map='+map.getZoom()+'/'+map.getCenter().lat.toFixed(4)+'/'+map.getCenter().lng.toFixed(4)+'&layer='+MapBaseLayer+urlMapOverlays+urlRouteID;
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
}

function onOverlayChange() {
	MapOverlays = '';
	if (map.hasLayer(PTLayer) == true) MapOverlays +='T';
	if (map.hasLayer(stationsGeoJsonTileLayer) == true) MapOverlays +='N';
	if (map.hasLayer(platformsGeoJsonTileLayer) == true) MapOverlays +='P';
	if (map.hasLayer(stopsGeoJsonTileLayer) == true) MapOverlays +='S';
	setMapURL();
}

function setOverlays(OverlaysStr) {
	if (OverlaysStr.indexOf('T') + 1) {
		map.addLayer(PTLayer);
	} else {
		map.removeLayer(PTLayer);
	}
	if (OverlaysStr.indexOf('N') + 1) {
		map.addLayer(stationsGeoJsonTileLayer);
	} else {
		map.removeLayer(stationsGeoJsonTileLayer);
	}
	if (OverlaysStr.indexOf('P') + 1) {
		map.addLayer(platformsGeoJsonTileLayer);
	} else {
		map.removeLayer(platformsGeoJsonTileLayer);
	}
	if (OverlaysStr.indexOf('S') + 1) {
		map.addLayer(stopsGeoJsonTileLayer);
	} else {
		map.removeLayer(stopsGeoJsonTileLayer);
	}
}

function setLayersOrder() {
	if (map.hasLayer(stopsGeoJsonTileLayer)) {
		stopsGeoJsonTileLayer.bringToFront();
	}
	if (map.hasLayer(platformsGeoJsonTileLayer)) {
		platformsGeoJsonTileLayer.bringToFront();
	}
	if (map.hasLayer(stationsGeoJsonTileLayer)) {
		stationsGeoJsonTileLayer.bringToFront();
	}
}

function clearRouteLayer() {
	RouteLayer.clearLayers();
	RoutePlatformLayer.clearLayers();
	RouteStopLayer.clearLayers();

	RouteID = '';

	$('#left_panel').hide();
	map.invalidateSize();

	setOverlays(MapOverlaysTmp);
	setMapURL();

	checkZoom();
}

var RouteLayer = new L.FeatureGroup();
var RoutePlatformLayer = new L.FeatureGroup();
var RouteStopLayer = new L.FeatureGroup();

function getRouteData(rID) {
	if (rID !== '') {
		RouteID = rID;
		RouteLayer.clearLayers();
		RoutePlatformLayer.clearLayers();
		RouteStopLayer.clearLayers();

		MapOverlaysTmp = MapOverlays;
		MapOverlays = '';
		setOverlays(MapOverlays);
		setMapURL();

		$("#platform-list").empty();
		$("#stop-position-list").empty();
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
						onEachFeature: function (feature, layer) {
							bindRoutePopup(feature, layer);
							createRouteInfo(feature);
						}
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
							bindLabel(feature, layer);
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
							bindLabel(feature, layer)
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
				$('#left_panel').show();
				map.invalidateSize();
				map.fitBounds(RouteLayer.getBounds());
				setMapURL();
			}
		});
	}
}

function checkZoom() {
	if (map.getZoom() < 14) {
		if(!RouteID) {
			document.getElementById("top-message-box").innerHTML = "Приблизьте карту";
			$('#top-message-box').fadeIn();
		}
	}
	else {
		//when all overlays are disabled
		if($("#top-message-box").text()==="Приблизьте карту") {
			$('#top-message-box').fadeOut();
		}
	}
}

function bindLabel(feature, layer) {
	if (feature.properties.name == "") {
		feature.properties.name = "Без названия";
	}
	layer.bindLabel(feature.properties.name, {
		direction: 'auto'
	});
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
						if (routes[i].from == null) {
							routes[i].from = 'Неизвестно';
						}
						if (routes[i].to == null) {
							routes[i].to = 'Неизвестно';
						}
						switch (routes[i].type) {
							case 'bus':
								busRes += '<a href="#" onclick="getRouteData('+routes[i].id+'); return false;">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'trolleybus':
								trolleybusRes += '<a href="#" onclick="getRouteData('+routes[i].id+'); return false;">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'share_taxi':
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
		with (feature.properties) {
			if ((from !== '') & (to !== '')) {
				var from_to = ": " + from + " ⇨ " + to;
			} else {
				from_to = '';
			}
			popupContent = "<b>" + type + " " + ref + from_to + "</b><hr>";
			popupContent += "Протяженность маршрута: " + length + " км.";
		}
		layer.bindPopup(popupContent);
	}
}

function createRouteInfo(feature) {
	var template = _.template($('#route_info_template').html());
	$('#left_panel_content').html(template({
		'type' : feature.properties.type,
		'ref': feature.properties.ref,
		'length': feature.properties.length
	}));
}

function createListElements(feature, layer) {
	if (feature.properties) {
		if (feature.properties.type == 'platform') {
			var item = document.getElementById('platform-list').appendChild(document.createElement('li'));
			item.innerHTML = feature.properties.name;
			if (feature.geometry.type=='Point') {
				item.onmouseover = function() {
					layer.showLabel();
				};
				item.onmouseout = function() {
					layer.hideLabel();
				};
			}
			item.onclick = function() {
				loadFeaturePopupData(feature, layer);
			};
		}
		if (feature.properties.type == 'stop') {
			var item = document.getElementById('stop-position-list').appendChild(document.createElement('li'));
			item.innerHTML = feature.properties.name;
			if (feature.geometry.type=='Point') {
				item.onmouseover = function() {
					layer.showLabel();
				};
				item.onmouseout = function() {
					layer.hideLabel();
				};
			}
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

var platformsGeoJsonTileLayer = new L.TileLayer.GeoJSON('/platform/{z}/{x}/{y}.geojson', {
		clipTiles: false,
		unique: function (feature) {
			return feature.properties.id;
		},
		minZoom: 14
	}, {
		style: {
			"color": "#1E90FF",
			"weight": 2,
			"opacity": 1
		},
		pointToLayer: function (feature, latlng) {
			var cMarker = L.circleMarker(latlng, {
				radius: 6,
				fillColor: "#FFFFFF",
				color: "#000",
				weight: 1,
				opacity: 1,
				fillOpacity: 1
			});
			return cMarker;
		},
		onEachFeature: function (feature, layer) {
			bindLabel(feature, layer);
			layer.on('click', function() {
				loadFeaturePopupData(feature, layer);
			});
		}
	}
);

var stationsGeoJsonTileLayer = new L.TileLayer.GeoJSON('/station/{z}/{x}/{y}.geojson', {
		clipTiles: false,
		unique: function (feature) {
			return feature.properties.id;
		},
		minZoom: 14
	}, {
		style: {
			"color": "#008000",
			"weight": 3,
			"opacity": 1
		},
		pointToLayer: function (feature, latlng) {
			var cMarker = L.circleMarker(latlng, {
				radius: 8,
				fillColor: "#FFFFFF",
				color: "#000",
				weight: 1,
				opacity: 1,
				fillOpacity: 1
			});
			return cMarker;
		},
		onEachFeature: function (feature, layer) {
			bindLabel(feature, layer);
			layer.on('click', function() {
				loadFeaturePopupData(feature, layer);
			});
		}
	}
);

var stopsGeoJsonTileLayer = new L.TileLayer.GeoJSON('/stop_pos/{z}/{x}/{y}.geojson', {
		clipTiles: false,
		unique: function (feature) {
			return feature.properties.id;
		},
		minZoom: 14
	}, {
		style: {
			"color": "#FFFFFF",
			"weight": 1,
			"opacity": 1
		},
		pointToLayer: function (feature, latlng) {
			var cMarker = L.circleMarker(latlng, {
				radius: 6,
				fillColor: "#1E90FF",
				color: "#000",
				weight: 2,
				opacity: 1,
				fillOpacity: 1
			});

			bindLabel(feature, cMarker);
			cMarker.on('click', function() {
				loadFeaturePopupData(feature, cMarker);
			});
			cMarker.on('add', function() {
				cMarker.bringToBack();
			});
			return cMarker;
		}
	}
);

var baseLayers = {
	"MapSurfer": MapSurferLayer,
	"sputnik.ru": SputnikRuLayer,
	"Mapnik": MapnikLayer
};

var overlays = {
	"Слой маршрутов": PTLayer,
	"Станции": stationsGeoJsonTileLayer,
	"Остановки / платформы": platformsGeoJsonTileLayer,
	"Места остановок": stopsGeoJsonTileLayer,
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

checkZoom();

var loading_layers = [ stationsGeoJsonTileLayer, platformsGeoJsonTileLayer, stopsGeoJsonTileLayer ];

loading_layers.forEach(function(element, index, array) {
	element.on('loading', function() {
		document.getElementById("top-message-box").innerHTML = "Загрузка";
		$('#top-message-box').fadeIn();
	});
	element.on('load', function() {
		var is_completed = array.reduce(function(prev, cur) {
			var tilesToLoad = cur._tilesToLoad || 0;
			return prev && (tilesToLoad < 1);
		}, true);
		if(is_completed)
			$('#top-message-box').fadeOut();
	});
});

setBaselayer();
setOverlays(MapOverlays);

getRouteData(RouteID);

map.on('baselayerchange', onBaselayerChange);
map.on('overlayadd', function () {
	setLayersOrder();
	onOverlayChange();
});
map.on('overlayremove', function () {
	onOverlayChange();
});
map.on('moveend', setMapURL);
map.on('zoomend', checkZoom);
