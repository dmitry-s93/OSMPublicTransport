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

function onBaselayerChange(e) {
    switch (true) {
		case map.hasLayer(MapSurferLayer): MapBaseLayer = 'S'; break;
		case map.hasLayer(SputnikRuLayer): MapBaseLayer = 'K'; break;
		case map.hasLayer(MapnikLayer): MapBaseLayer = 'M'; break;
	}	
	//alert(Layer);
	SetMapURL();
}

function BaselayerChange(e) {
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
	SetMapURL();
}

function SetMapURL(e) {
	MapUrl= '#map='+map.getZoom()+'/'+map.getCenter().lat.toFixed(4)+'/'+map.getCenter().lng.toFixed(4)+'&layer='+MapBaseLayer;
	location.replace(MapUrl);		
	document.cookie = "OSMPublicTransport="+MapUrl;	
	//alert(document.cookie);
}

function zoomToFeature(e) {
	map.fitBounds(e.target.getBounds());
}

function onEachFeature(feature, layer) {	
	//if (feature.properties && feature.properties.type == 'route') {
		//Test = layer;
		//layer.on({
				////mouseover: zoomToFeature,
				////mouseout: zoomToFeature,
				//click: zoomToFeature
			//});	
	//}
			
	var popupContent;

	if (feature.properties) {
		if (feature.properties.type == 'route') {
			popupContent = "<b>" + feature.properties.name + "</b>";
			popupContent += "<hr>";
			popupContent += feature.properties.description;
		}
		if (feature.properties.type != 'route') {
			popupContent = "<b>" + feature.properties.name + "</b>";
			popupContent += "<hr>";
			popupContent += feature.properties.description;
		}		
		layer.bindPopup(popupContent);
	}
}

if (typeof geojsonRoute !== "undefined") {
	L.geoJson(geojsonRoute, {
		filter: function (feature, layer) {
			if (feature.properties) {
				// If the property "underConstruction" exists and is true, return false (don't render features under construction)
				return feature.properties.underConstruction !== undefined ? !feature.properties.underConstruction : true;
			}
			return false;
		},

		onEachFeature: onEachFeature
	}).addTo(RouteLayer);
}

if (typeof geojsonStops !== "undefined") {
	L.geoJson(geojsonStops, {

		style: function (feature) {
			return feature.properties && feature.properties.style;
		},

		onEachFeature: onEachFeature,

		pointToLayer: function (feature, latlng) {
			return L.circleMarker(latlng, {
				radius: 6,
				fillColor: "#1E90FF",
				color: "#000",
				weight: 1,
				opacity: 1,
				fillOpacity: 0.8
			});
		}
	}).addTo(StopsLayer);
}

if (typeof geojsonPlatforms !== "undefined") {
	L.geoJson(geojsonPlatforms, {

		style: function (feature) {
			return feature.properties && feature.properties.style;
		},

		onEachFeature: onEachFeature,

		pointToLayer: function (feature, latlng) {
			return L.circleMarker(latlng, {
				radius: 8,
				fillColor: "#ff7800",
				color: "#000",
				weight: 1,
				opacity: 1,
				fillOpacity: 0.8
			});
		}
	}).addTo(PlatformsLayer);
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

if ((typeof geojsonRoute !== "undefined") || (typeof geojsonStops !== "undefined") || (typeof geojsonPlatforms !== "undefined")) {	
	var map = L.map('map', {
		center: [MapPosition[1], MapPosition[2]],
		zoom: MapPosition[0],
		layers: [MapSurferLayer, RouteLayer, StopsLayer, PlatformsLayer]
	});
	
	var overlays = {
		"Общественный транспорт": PTLayer,
		"Маршрут": RouteLayer,
		"Места остановок": StopsLayer,
		"Остановки (Платформы)": PlatformsLayer
	};
	
	map.fitBounds(L.geoJson(geojsonRoute).getBounds());
} else
{
	var map = L.map('map', {
		center: [MapPosition[1], MapPosition[2]],
		zoom: MapPosition[0],
		layers: [MapSurferLayer, PTLayer]
	});
	
	var overlays = {	
		"Общественный транспорт": PTLayer
	};	
}

L.control.layers(baseLayers, overlays).addTo(map);
L.control.scale().addTo(map);

L.control.locate({
    icon: 'fa fa-map-marker',  // class for icon, fa-location-arrow or fa-map-marker
    iconLoading: 'fa fa-spinner fa-spin',  // class for loading icon
    onLocationError: function(err) {alert(err.message)},  // define an error callback function
    onLocationOutsideMapBounds:  function(context) { // called when outside map boundaries
            alert(context.options.strings.outsideMapBoundsMsg);
    },
    strings: {
        title: "Show me where I am",
        popup: "Вы находитесь в пределах {distance} м. от этой точки",
        outsideMapBoundsMsg: "You seem located outside the boundaries of the map"
    }
}).addTo(map);

//Fullscreen button
L.control.fullscreen({
	position: 'topleft',
	title: 'Full Screen',
	forceSeparateButton: true,
	forcePseudoFullscreen: false
}).addTo(map);

BaselayerChange();

map.on('baselayerchange', onBaselayerChange);
map.on('moveend', SetMapURL);




