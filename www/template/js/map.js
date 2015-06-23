if (document.location.hash == '') {
	if (document.cookie.substr(19) !== '') {
		document.location.hash = document.cookie.substr(19);
	} else
	{
		document.location.hash = '#map=3/60.50/107.50&layer=S';
	}
}

with (document.location.hash.substr(5)) {
	if (typeof split('&')[0].split('/') !== "undefined"){
		MapPosition = split('&')[0].split('/');
	}
	if (typeof split('&')[1] !== "undefined"){
		MapBaseLayer = split('&')[1].split('=')[1];
	} else {
		MapBaseLayer = 'K';
	}
}

var RouteLayer = new L.LayerGroup();
var StopsLayer = new L.LayerGroup();
var PlatformsLayer = new L.LayerGroup();

var markers = new L.FeatureGroup();

var PlatformList = document.getElementById('platform-list');
var StopPositionList = document.getElementById('stop-position-list');

function onBaselayerChange() {
	switch (true) {
		case map.hasLayer(MapSurferLayer): MapBaseLayer = 'S'; break;
		case map.hasLayer(SputnikRuLayer): MapBaseLayer = 'K'; break;
		case map.hasLayer(MapnikLayer): MapBaseLayer = 'M'; break;
	}
	SetMapURL();
}

function baselayerChange() {
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

function setMapURL(e) {
	MapUrl= '#map='+map.getZoom()+'/'+map.getCenter().lat.toFixed(4)+'/'+map.getCenter().lng.toFixed(4)+'&layer='+MapBaseLayer;
	location.replace(MapUrl);
	document.cookie = "OSMPublicTransport="+MapUrl;
}

function getData() {
	if (typeof geojsonRoute == "undefined") {
		markers.clearLayers();
		if (map.getZoom() > 14) {
			document.getElementById("topMessageBox").innerHTML = "Загрузка";
			$('#topMessageBox').fadeIn();
			var bbox = map.getBounds();
			$.ajax({
				type: "POST",
				url: "/ajax/get_data.php",
				data: {
					point2:(bbox._northEast.lng)+","+(bbox._northEast.lat),
					point1:(bbox._southWest.lng)+","+(bbox._southWest.lat)
				},
				dataType: "script",
				async: true,
				success: function(data){
					L.geoJson(geojsonResult, {
					style: {
						function (feature) {
							return feature.properties && feature.properties.style;
						},
						"color": "#FF8000",
						"weight": 2,
						"opacity": 0.8
					},
					pointToLayer: function (feature, latlng) {
						return L.circleMarker(latlng, {
							radius: 6,
							fillColor: "#FF8000",
							color: "#000",
							weight: 1,
							opacity: 1,
							fillOpacity: 0.8
						});
					},
					onEachFeature: function (feature, layer) {
						layer.on('click', function() {
							loadFeaturePopupData(feature, layer);
						});
					}
				}).addTo(markers);
				map.addLayer(markers);

				}
			});
			$('#topMessageBox').fadeOut();
		} else {
			document.getElementById("topMessageBox").innerHTML = "Приблизьте карту";
			$('#topMessageBox').fadeIn();
		}
	}
}

function zoomToFeature(e) {
	map.fitBounds(L.geoJson(e).getBounds());
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
								busRes += '<a href="/?id='+routes[i].id+'">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'trolleybus':
								trolleybusRes += '<a href="/?id='+routes[i].id+'">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'sharetaxi':
								sharetaxiRes += '<a href="/?id='+routes[i].id+'">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'tram':
								tramRes += '<a href="/?id='+routes[i].id+'">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
							case 'train':
								trainRes += '<a href="/?id='+routes[i].id+'">'+routes[i].ref+'</a> ('+routes[i].from+' ⇨ '+routes[i].to+')<br>';
								break;
						}
					}
				}
			}
			if (feature.properties) {
				if ((feature.properties.type == 'platform') || (feature.properties.type == 'stop_position')) {
					if (feature.properties.name == "") {
						feature.properties.name = "Без названия";
					}

					popupContent = "<b>" + feature.properties.name + "</b>";
					popupContent += "<hr>";
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
					if (feature.properties.description !== '') {
						popupContent += feature.properties.description;
					}
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

function createListElements(feature, layer) {
	if (feature.properties) {
		if (feature.properties.type == 'platform') {
			var item = PlatformList.appendChild(document.createElement('li'));
			item.innerHTML = feature.properties.name;
			item.onclick = function() {
				loadFeaturePopupData(feature, layer);
			};
		}
		if (feature.properties.type == 'stop_position') {
			var item = StopPositionList.appendChild(document.createElement('li'));
			item.innerHTML = feature.properties.name;
			item.onclick = function() {
				loadFeaturePopupData(feature, layer);
			};
		}
	}
}

function loadGeoJson() {
	map.removeLayer(RouteLayer);
	map.removeLayer(StopsLayer);
	map.removeLayer(PlatformsLayer);

	if (typeof geojsonRoute !== "undefined") {
		L.geoJson(geojsonRoute, {
			style: {
				"color": "#1E90FF",
				"weight": 6,
				"opacity": 0.6
			},
			filter: function (feature, layer) {
				if (feature.properties) {
					return feature.properties.underConstruction !== undefined ? !feature.properties.underConstruction : true;
				}
				return false;
			},
			onEachFeature: bindRoutePopup
		}).addTo(RouteLayer);
		map.addLayer(RouteLayer);
	}

	if (typeof geojsonStops !== "undefined") {
		L.geoJson(geojsonStops, {
			style: function (feature) {
				return feature.properties && feature.properties.style;
			},
			pointToLayer: function (feature, latlng) {
				return L.circleMarker(latlng, {
					radius: 6,
					fillColor: "#1E90FF",
					color: "#000",
					weight: 1,
					opacity: 1,
					fillOpacity: 0.8
				});
			},
			onEachFeature: function (feature, layer) {
				layer.on('click', function() {
					loadFeaturePopupData(feature, layer);
				});
				createListElements(feature, layer);
			}
		}).addTo(StopsLayer);
		map.addLayer(StopsLayer);
	}

	if (typeof geojsonPlatforms !== "undefined") {
		L.geoJson(geojsonPlatforms, {
			style: {
				function (feature) {
					return feature.properties && feature.properties.style;
				},
				"color": "#FF8000",
				"weight": 2,
				"opacity": 0.8
			},
			pointToLayer: function (feature, latlng) {
				return L.circleMarker(latlng, {
					radius: 6,
					fillColor: "#FF8000",
					color: "#000",
					weight: 1,
					opacity: 1,
					fillOpacity: 0.8
				});
			},
			onEachFeature: function (feature, layer) {
				layer.on('click', function() {
					loadFeaturePopupData(feature, layer);
				});
				createListElements(feature, layer);
			}
		}).addTo(PlatformsLayer);
		map.addLayer(PlatformsLayer);
	}
	if ((typeof geojsonStops !== "undefined") || (typeof geojsonPlatforms !== "undefined")) {
		if (document.getElementById('stop-position-list').childNodes.length > document.getElementById('platform-list').childNodes.length) {
			document.getElementById("SelectList").options[1].selected=true;
			document.getElementById('platform-list').style.display = 'none';
			document.getElementById('stop-position-list').style.display = 'block';
		}
		document.getElementById('infoPanel').style.display = 'block';
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
	"Маршрут": RouteLayer,
	"Места остановок": StopsLayer,
	"Остановки (Платформы)": PlatformsLayer,
};

var map = L.map('map', {
	center: [MapPosition[1], MapPosition[2]],
	zoom: MapPosition[0],
	layers: [MapSurferLayer]
});

L.control.layers(baseLayers, overlays).addTo(map);
L.control.scale().addTo(map);

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

L.control.fullscreen({
	position: 'topleft',
	title: 'Full Screen',
	forceSeparateButton: true,
	forcePseudoFullscreen: false
}).addTo(map);

baselayerChange();
loadGeoJson();
getData();

map.on('baselayerchange', onBaselayerChange);
map.on('moveend', setMapURL);
map.on('dragend', getData);
map.on('zoomend', getData);
