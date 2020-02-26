
// home
var center = [49.2202194, 16.5558572]

// instancel of leaflet map
var bsmap = L.map('bsmap', { fullscreenControl: true }).setView(center, 12);

// attributes to be added to map as static text
var osmAttr = '&copy; <a href="http://openstreetmap.org" target="_blank">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank">CC-BY-SA</a>';
var mapyCzAttr = '&copy; <a href="https://www.seznam.cz/" target="_blank">Seznam.cz, a.s</a>, ' + osmAttr;

// colors that are picked for individual tracks
const trackColors = ['red', 'violet', 'green', 'blue', 'orange']

// global list of rendered tracks
var tracks = [];

// global bounds continuously updated by each track to cover all rendered tracks
var bounds = null;

// format distance - round number and add units
function formatDistance(dist) {
    unit = "m";

    if (dist > 1000) {
        dist = dist / 1000;
        unit = "km";
    }
    return dist.toFixed(1) + unit;
}

// format elevation - round number and add units
function formatElevation(el) {
    unit = "m";
    return el.toFixed(0) + unit;
}

// called when single track is rendered to map
function updateTracks() {
    legendDiv.innerHTML = '';

    // update legend
    for (var i = 0; i < tracks.length; i++) {
        legendDiv.innerHTML += '<div class="track-legend"><i style="background:' + tracks[i].color + '"></i>&nbsp;' +
            tracks[i].name + '&nbsp;' +
            formatDistance(tracks[i].distance) + ',&nbsp;' +
            formatElevation(tracks[i].elevation_gain) + ',&nbsp;' +
            tracks[i].moving_time +
            '</div>';
    }

    // resize map to fit all currently rendered tracks
    if (bounds) {
        bsmap.fitBounds(bounds);
    }
}

// legend control + div for rendering content
var legendDiv = L.DomUtil.create('div', 'info legend');
var legend = L.control({position: 'bottomleft'});
legend.onAdd = function (map) {
    return legendDiv;
};
legend.addTo(bsmap);

// tile layer - mapy.cz tourist map
L.tileLayer('https://mapserver.mapy.cz/turist-m/{z}-{x}-{y}', {
    attribution: mapyCzAttr,
    minZoom: 2,
    maxZoom: 20,
    maxNativeZoom: 18,
    id: 'mapycz',
    tileSize: 256
}).addTo(bsmap);

// configuration of track markers - we need to set
// path to icons since all files live in plugin dir (and url)
const marker_options = {
    startIconUrl: params.iconsUrl + '/pin-icon-start.png',
    endIconUrl: params.iconsUrl + '/pin-icon-end.png',
    shadowUrl: params.iconsUrl + '/pin-shadow.png'
}

// render individual tracks
if (params && params.gpxList) {

    for (var i = 0; i < params.gpxList.length; i++) {

        // get different color for each track - modulo is used since color list has fixed length
        let color = trackColors[i % trackColors.length];

        var polyline_options = {
            color: color,
            opacity: 1,
            weight: 3,
            lineCap: 'round'
        }

        new L.GPX(params.gpxList[i], {async: true, marker_options, polyline_options}).on('loaded', function(e) {
            // extend global bounds
            if (bounds) {
                bounds.extend(e.target.getBounds());
            } else {
                bounds = e.target.getBounds();
            }

            // store some of track attributes (mainly for rendering of legend)
            tracks.push({
                name: e.target.get_name(),
                distance: e.target.get_distance(),
                elevation_gain: e.target.get_elevation_gain(),
                moving_time: e.target.get_duration_string_iso(e.target.get_moving_time()),
                color
            });
            updateTracks();
        }).addTo(bsmap);
    }
}
