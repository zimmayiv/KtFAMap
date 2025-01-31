<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KTownForAll Route Mapping</title>
  <!-- Leaflet CSS -->
  <link rel="icon" type="image/x-icon" href="./imgs/favicon.ico">
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    crossorigin=""
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"
  />
  <style>
    html {
       font-family: "San Francisco Text", sans-serif;
    }
    #map {
      height: 80vh;
      z-index: 10;
    }
    #controls {
      margin: 10px;
      gap: 10px;
      width: 100%;
    }
    input[type="file"] {
      display: none;
    }
    #load {
      padding: 5px 10px;
      background-color: #a5156e;
      color: white;
      border-radius: 5px;
      cursor: pointer;
    }
    #load:hover {
      opacity: 90%;
    }
    label {
      padding: 5px 1px;
    }
    .tooltip-button {
      float: right;
      position: relative;
      z-index:100;
      background-color: #007BFF;
      color: white;
      border: none;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      font-size: 16px;
      cursor: pointer;
      text-align: center;
      line-height: 30px;
    }

    /* Tooltip container */
    .tooltip-button .tooltip {
      visibility: hidden;
      background-color: #333333;
      color: white;
      text-align: center;
      border-radius: 5px;
      padding: 7px 10px;
      position: fixed; /* Fixed to appear relative to the viewport */
      top: 45%; /* Center vertically */
      left: 50%; /* Center horizontally */
      transform: translate(-50%, -50%); /* Center the tooltip exactly */
      z-index: 1000; /* Ensure it appears on top */
    }

    /* Show the tooltip on hover */
    .tooltip-button:hover .tooltip {
      visibility: visible;
    }
  </style>
</head>
<body>
  <div id="map"></div>
  <button class="tooltip-button">
    ?
    <span class="tooltip">Either upload a GeoJSON representation of your route using the "Load GeoJSON" button, or draw it using the editing tools on the left - you can draw a line route or  add markers with or without annotations. (On mobile, when drawing a line route, you may need to press and hold for a second for intermediate waypoints, to avoid accidentally ending the route early.) You can also edit any objects already drawn (or loaded) on the map. When you're done, you can save using the "Save GeoJSON" button. This will save, in GeoJSON format, any layers you have drawn, with filename corresponding to the selected route and today's date.</p>

<p>If there is a route drawn/loaded on the map, you can click the "Open Google Maps" button to open navigation between all the waypoints of the route in Google Maps. Note that if multiple routes are drawn, only the first will be navigated, so make sure you don't accidentally have more than one on the screen at once. (You can remove individual layers, or all layers at once, using a control on the left, to ensure this doesn't happen.)</p></span>
  </button>
  <div id="controls">
  <label for="route">Route:</label>
  <select id="route">
    <option value="olympic">Olympic</option>
    <option value="vons">Vons' Raiders</option>
    <option value="8th">8th Street</option>
  </select>
    <button id="save-btn">Save GeoJSON</button>
    <label id="load" for="load-geojson">Load GeoJSON</label>
    <input type="file" id="load-geojson" accept=".geojson" />
    <button id="nav-btn">Open Google Maps navigation</button>
  </div>

  <!-- Leaflet JS -->
  <script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    crossorigin=""
  ></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

  <script>
    // Initialize the map
    const map = L.map('map').setView([34.058294, -118.298108], 14);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap contributors',
    }).addTo(map);

    // Feature group to store drawn items
    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    // Initialize Leaflet Draw controls
    const drawControl = new L.Control.Draw({
      edit: {
        featureGroup: drawnItems,
      },
      draw: {
        polyline: true,
        polygon: false,
        circle: false,
        rectangle: false,
        marker: true,
      },
    });
    map.addControl(drawControl);

    // Handle creation of new layers
    map.on(L.Draw.Event.CREATED, function (e) {
      const layer = e.layer;

      // If the drawn layer is a marker
      if (layer instanceof L.Marker) {
        showAnnotationInput(layer);
      }

      // Add the new layer to the feature group
      drawnItems.addLayer(layer);
    });

    // Custom input for annotations
    function showAnnotationInput(layer) {
      const inputContainer = document.createElement('div');
      inputContainer.style.position = 'absolute';
      inputContainer.style.top = '10px';
      inputContainer.style.left = '10px';
      inputContainer.style.zIndex = '1000';
      inputContainer.style.backgroundColor = 'white';
      inputContainer.style.padding = '10px';
      inputContainer.style.border = '1px solid #ccc';
      inputContainer.style.borderRadius = '5px';

      const input = document.createElement('input');
      input.type = 'text';
      input.placeholder = 'Enter a note for this marker';
      input.style.marginRight = '5px';

      const saveButton = document.createElement('button');
      saveButton.textContent = 'Save';
      saveButton.style.marginRight = '5px';

      const cancelButton = document.createElement('button');
      cancelButton.textContent = 'Cancel';

      inputContainer.appendChild(input);
      inputContainer.appendChild(saveButton);
      inputContainer.appendChild(cancelButton);
      document.body.appendChild(inputContainer);

      saveButton.addEventListener('click', () => {
        const note = input.value.trim();
        if (note) {
          layer.bindPopup(note).openPopup();
        }
        document.body.removeChild(inputContainer);
      });

      cancelButton.addEventListener('click', () => {
        document.body.removeChild(inputContainer);
        drawnItems.removeLayer(layer); // Remove the marker if canceled
      });
    }
    function getLineStringLayers() {
       const layers = drawnItems.getLayers(); // Retrieve all layers in drawnItems

       // Filter only the LineString layers
       const lineStringLayers = layers.filter((layer) => {
          const geoJson = layer.toGeoJSON();
          return geoJson.geometry.type === "LineString";
       });
       return lineStringLayers;
    }

    function generateGoogleMapsUrl(lineStringLayer) {
      // Extract the coordinates from the LineString layer
      const coordinates = lineStringLayer.toGeoJSON().geometry.coordinates;

      // Check if there are at least two coordinates (start and end point)
      if (coordinates.length < 2) {
	console.error("LineString must have at least two coordinates for navigation.");
	return null;
      }

      // Format the coordinates for Google Maps
      const waypoints = coordinates
	.slice(1, -1) // Exclude the start and end points for waypoints
	.map(coord => coord.reverse().join(",")) // Reverse [lng, lat] to [lat, lng]
	.join("|");

      const start = coordinates[0].reverse().join(","); // First coordinate as start
      const end = coordinates[coordinates.length - 1].reverse().join(","); // Last coordinate as end

      // Construct the Google Maps URL
      let googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${start}&destination=${end}`;

      // Add waypoints if they exist
      if (waypoints) {
	googleMapsUrl += `&waypoints=${waypoints}`;
      }

      console.log("Google Maps URL:", googleMapsUrl);
      return googleMapsUrl;
    }

    // Save all layers to GeoJSON
    document.getElementById('save-btn').addEventListener('click', () => {
      const data = drawnItems.toGeoJSON();
      const blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);

      const a = document.createElement('a');
      a.href = url;
      const today = new Date();
      const dateString = today.toISOString().slice(0, 10).replace(/-/g, '');
      const route = document.getElementById('route').value;
      a.download = route + '-' + dateString + '.geojson';
      a.click();

      URL.revokeObjectURL(url);
    });

    document.getElementById('nav-btn').addEventListener('click', () => {
       const lines = getLineStringLayers();
       console.log(lines);
       if (lines.length == 0) { alert("No route drawn."); }
       else {
          const url = generateGoogleMapsUrl(lines[0]);
          window.open(url, "_blank");
       }
    });

    // Load layers from a GeoJSON file
    document.getElementById('load-geojson').addEventListener('change', (event) => {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const geojsonData = JSON.parse(e.target.result);

          L.geoJSON(geojsonData, {
            onEachFeature: function (feature, layer) {
              if (feature.properties && feature.properties.popupContent) {
                layer.bindPopup(feature.properties.popupContent);
              }
              drawnItems.addLayer(layer);
            },
          }).addTo(map);
        };
        reader.readAsText(file);
      }
    });
  </script>
</body>
</html>

