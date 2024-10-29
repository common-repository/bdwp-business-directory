function bdwpFitMap(locations, map, bounds) {
    if (locations.length === 1) {
        map.setZoom(17);
        map.setCenter(locations[0]['location']);
    } else {
        map.fitBounds(bounds);
    }
}

function bdwpInitMap (map_data) {
    var locations = map_data['locations'];

    var map = map_data['object'] = new google.maps.Map(document.getElementById(map_data['id']));


    var bounds = new google.maps.LatLngBounds();
    var infowindow = new google.maps.InfoWindow();

    for (var l = 0; l < locations.length; l++) {
        var location = locations[l];

        var pinColor = "FE7569";
        if(location.type === 'free') {
            pinColor = "CCCCCC";
        }
        var pinImage = new google.maps.MarkerImage("http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|" + pinColor, new google.maps.Size(21, 34), new google.maps.Point(0, 0), new google.maps.Point(10, 34));
        var pinShadow = new google.maps.MarkerImage("http://chart.apis.google.com/chart?chst=d_map_pin_shadow",
            new google.maps.Size(40, 37),
            new google.maps.Point(0, 0),
            new google.maps.Point(12, 35));

        var marker = new google.maps.Marker({
            map: map,
            position: location['location'],
            icon: pinImage,
            shadow: pinShadow,
            title: location['title']
        });

        var infoContent = location['info'];

        google.maps.event.addListener(infowindow, 'closeclick', function (locations, map, bounds) {
            return function () {
                bdwpFitMap(locations, map, bounds);
            }
        }(locations, map, bounds));

        google.maps.event.addListener(marker, 'click', (function (infowindow, map, marker, infoContent) {
            return function () {
                infowindow.setContent(infoContent);
                infowindow.open(map, marker);
            }
        })(infowindow, map, marker, infoContent));

        bounds.extend(marker.position);
    }

    bdwpFitMap(locations, map, bounds);
}

window.BDWP.initMap = function () {
    var maps_data = window.BDWP.maps;
    for (var m = 0; m < maps_data.length; m++) {
        var map_data = maps_data[m];
        bdwpInitMap(map_data);
    }
}

window.BDWP.initMapOnce = function (id) {
    var maps_data = window.BDWP.maps;
    var map_data;
    for (var m = 0; m < maps_data.length; m++) {
        if (maps_data[m]['id'] === id) {
            map_data = maps_data[m];
            break;
        }
    }
    if (map_data['inited'] === true) return;
    bdwpInitMap(map_data);
    map_data['inited'] = true;
}