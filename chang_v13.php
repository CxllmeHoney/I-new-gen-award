<!DOCTYPE html>
<html lang="th">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Real-time Elephant Markers</title>
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
      rel="stylesheet"
    />
    <link
      href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css"
      rel="stylesheet"
    />
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.1.1/mapbox-gl-directions.js"></script>
    <link
      rel="stylesheet"
      href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.1.1/mapbox-gl-directions.css"
    />
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      :root {
        --primary-color: #4caf50;
        --primary-dark: #45a049;
        --primary-light: rgba(76, 175, 80, 0.1);
        --white: #ffffff;
        --shadow: rgba(0, 0, 0, 0.1);
        --transition: 0.3s;
      }

      html,
      body {
        height: 100%;
        font-family: "Kanit", -apple-system, BlinkMacSystemFont, "Segoe UI",
          Roboto, sans-serif;
        background: var(--white);
      }

      #map {
        width: calc(100% - 300px);
        height: 100%;
        margin-left: 300px;
        transition: all var(--transition) cubic-bezier(0.4, 0, 0.2, 1);
      }

      #map.expanded {
        width: 100%;
        margin-left: 0;
      }

      .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 300px;
        height: 100%;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 2px 0 20px var(--shadow);
        z-index: 1000;
        transform: translateX(0);
        transition: all var(--transition) cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
      }

      .sidebar.collapsed {
        transform: translateX(-100%);
      }

      .sidebar-header {
        padding: 25px 20px;
        background: linear-gradient(
          135deg,
          var(--primary-color) 0%,
          var(--primary-dark) 100%
        );
        color: var(--white);
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
      }

      .sidebar-header h2 {
        font-size: 1.5rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        letter-spacing: 0.5px;
      }

      .sidebar-content {
        padding: 20px 0;
        height: calc(100% - 80px);
        overflow-y: auto;
      }

      .menu-item {
        margin: 8px 16px;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        cursor: pointer;
        border-radius: 12px;
        background: var(--white);
        border: 1px solid rgba(76, 175, 80, 0.1);
        transition: all var(--transition);
        position: relative;
        overflow: hidden;
      }

      .menu-item::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        width: 4px;
        height: 100%;
        background: var(--primary-color);
        transform: scaleY(0);
        transition: transform var(--transition);
      }

      .menu-item:hover::before {
        transform: scaleY(1);
      }

      .menu-item:hover {
        background: var(--primary-light);
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      }

      .menu-item i {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--primary-light);
        border-radius: 8px;
        margin-right: 15px;
        color: var(--primary-color);
        font-size: 1.2rem;
        transition: all var(--transition);
      }

      .menu-item:hover i {
        background: var(--primary-color);
        color: var(--white);
        transform: rotate(5deg) scale(1.1);
      }

      .menu-item span {
        font-weight: 500;
        transition: all var(--transition);
      }

      .map-type-container {
        margin-top: 5px;
        overflow: hidden;
        transition: max-height var(--transition);
        animation: slideDown 0.3s ease-out;
      }

      .map-type-item {
        margin: 8px 16px 8px 40px;
        padding: 12px 16px;
        border-radius: 8px;
        background: var(--white);
        border: 1px solid rgba(76, 175, 80, 0.1);
        transition: all var(--transition);
        position: relative;
        cursor: pointer;
      }

      .map-type-item::before {
        content: "";
        position: absolute;
        left: -20px;
        top: 50%;
        width: 12px;
        height: 2px;
        background: var(--primary-color);
        transform: translateY(-50%);
        opacity: 0;
        transition: all var(--transition);
      }

      .map-type-item:hover::before {
        opacity: 1;
        left: -15px;
      }

      .map-type-item:hover {
        background: var(--primary-light);
        transform: translateX(5px);
      }

      .map-type-item.active {
        background: var(--primary-color);
        color: var(--white);
        transform: scale(1.02);
        box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
      }

      .toggle-btn {
        position: absolute;
        right: -45px;
        top: 50%;
        width: 45px;
        height: 45px;
        border: none;
        border-radius: 0 12px 12px 0;
        background: linear-gradient(
          135deg,
          var(--primary-color) 0%,
          var(--primary-dark) 100%
        );
        color: var(--white);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all var(--transition);
        box-shadow: 4px 0 15px var(--shadow);
      }

      .toggle-btn i {
        font-size: 1.2rem;
        transition: transform var(--transition);
      }

      .sidebar.collapsed .toggle-btn i {
        transform: rotate(180deg);
      }

      .search-container {
        position: absolute;
        top: 10px;
        left: 350px;
        z-index: 1;
        width: 300px;
        padding: 10px;
        transition: all var(--transition);
      }

      .search-container.expanded {
        left: 20px;
      }

      .search-box {
        width: 100%;
        padding: 15px 25px;
        padding-right: 40px;
        font-size: 16px;
        border: none;
        border-radius: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        background: var(--white);
        transition: all var(--transition);
      }

      .search-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
      }

      .search-box:focus {
        outline: none;
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
        transform: translateY(-2px);
      }

      .location-popup {
        position: fixed;
        bottom: -100%;
        left: 0;
        right: 0;
        background: white;
        padding: 20px;
        border-radius: 20px 20px 0 0;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        transition: bottom 0.3s ease-in-out;
        z-index: 1000;
        max-height: 60vh;
        overflow-y: auto;
      }

      .location-popup.show {
        bottom: 0;
      }

      .close-popup {
        position: absolute;
        right: 15px;
        top: 15px;
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #666;
      }

      .popup-header {
        position: relative;
        padding-bottom: 15px;
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
      }

      .popup-content {
        font-family: "Kanit", sans-serif;
      }

      @keyframes slideDown {
        from {
          opacity: 0;
          transform: translateY(-10px);
        }

        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes slideIn {
        from {
          opacity: 0;
          transform: translateX(-10px);
        }

        to {
          opacity: 1;
          transform: translateX(0);
        }
      }

      .sidebar-content::-webkit-scrollbar {
        width: 6px;
      }

      .sidebar-content::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
      }

      .sidebar-content::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 3px;
      }

      @media screen and (max-width: 768px) {
        #map {
          width: 100%;
          margin-left: 0;
        }

        .search-container {
          left: 20px;
          width: calc(100% - 40px);
        }

        .sidebar {
          width: 280px;
        }

        .menu-item {
          margin: 8px 12px;
          padding: 12px 16px;
        }

        .map-type-item {
          margin: 8px 12px 8px 35px;
        }
        .location-popup {
          max-height: 80vh;
        }
      }

      @media (prefers-color-scheme: dark) {
        .sidebar {
          background: rgba(33, 33, 33, 0.98);
        }

        .menu-item {
          background: rgba(45, 45, 45, 0.9);
          border-color: rgba(76, 175, 80, 0.2);
        }

        .menu-item span {
          color: rgba(255, 255, 255, 0.9);
        }

        .map-type-item {
          background: rgba(45, 45, 45, 0.9);
          color: rgba(255, 255, 255, 0.9);
        }
      }
    </style>
  </head>

  <body>
    <div class="sidebar">
      <div
        class="sidebar-header"
        style="
          display: flex;
          align-items: center;
          gap: 15px;
          padding: 20px;
          background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        "
      >
        <img
          src="https://aprlabtop.com/Honey_test/logo.png"
          alt="Logo"
          style="
            height: 80px;
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.2));
            border-radius: 80%;
          "
        />
        <h2
          style="
            font-size: 28px;
            font-weight: 600;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
          "
        >
          WILDSAFE
        </h2>
      </div>
      <div class="sidebar-content">
        <div
          class="menu-item"
          id="locate-btn"
          style="background: #e7caa0; border-color: #e7caa0"
        >
          <i
            class="fas fa-location-crosshairs"
            style="background: rgba(255, 255, 255, 0.2); color: white"
          ></i>
          <span style="color: white">ค้นหาตำแหน่งปัจจุบัน</span>
        </div>
        <div
          class="menu-item"
          style="background: #67cb78; border-color: #67cb78"
        >
          <i
            class="fas fa-layer-group"
            style="background: rgba(255, 255, 255, 0.2); color: white"
          ></i>
          <span style="color: white">รูปแบบแผนที่</span>
        </div>
        <div
          class="map-type-container"
          style="padding-left: 40px; display: none"
        >
          <div class="map-type-item" data-type="roadmap">แผนที่ถนน</div>
          <div class="map-type-item" data-type="satellite">ดาวเทียม</div>
          <div class="map-type-item" data-type="hybrid">ผสม</div>
          <div class="map-type-item" data-type="terrain">ภูมิประเทศ</div>
        </div>
        <div
          class="menu-item"
          onclick="callEmergency()"
          style="background: #ff4444; border-color: #ff4444"
        >
          <i
            class="fas fa-phone-alt"
            style="background: rgba(255, 255, 255, 0.2); color: white"
          ></i>
          <span style="color: white">ติดต่อเจ้าหน้าที่</span>
        </div>
        <div
          class="menu-item"
          onclick="window.location.href='https://aprlabtop.com/Honey_test/admin_login.php'"
          style="background: #2196f3; border-color: #2196f3"
        >
          <i
            class="fas fa-user-shield"
            style="background: rgba(255, 255, 255, 0.2); color: white"
          ></i>
          <span style="color: white">แผงควบคุมผู้ดูแล</span>
        </div>
      </div>
      <button class="toggle-btn">
        <i class="fas fa-bars"></i>
      </button>
    </div>

    <div class="search-container">
      <input
        id="search-box"
        class="search-box"
        type="text"
        placeholder="ค้นหาสถานที่..."
      />
      <i class="fas fa-search search-icon"></i>
    </div>
    <div id="map"></div>
    <div class="location-popup" id="locationPopup">
      <div class="popup-content"></div>
    </div>

    <script>
      let map;
      const elephantMarkers = [];
      let elephantCircle = null;
      let lastDetectionID = 0;
      let searchBox;
      let searchMarkers = [];
      let clickInfoWindow = null;
      let directions;
      let userLocation;
      let directionsService;
      let directionsRenderer;

      const sidebar = document.querySelector(".sidebar");
      const mapElement = document.getElementById("map");
      const searchContainer = document.querySelector(".search-container");
      const toggleBtn = document.querySelector(".toggle-btn");

      toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("collapsed");
        mapElement.classList.toggle("expanded");
        searchContainer.classList.toggle("expanded");
      });

      async function initMap() {
        const mapOptions = {
          center: { lat: 14.22512, lng: 101.40544 },
          zoom: 14,
          mapTypeControl: false,
          zoomControl: true,
          zoomControlOptions: {
            position: google.maps.ControlPosition.RIGHT_CENTER,
          },
          streetViewControl: true,
          streetViewControlOptions: {
            position: google.maps.ControlPosition.RIGHT_CENTER,
          },
          fullscreenControl: true,
          fullscreenControlOptions: {
            position: google.maps.ControlPosition.RIGHT_TOP,
          },
        };

        map = new google.maps.Map(document.getElementById("map"), mapOptions);
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
          map: map,
          panel: document.getElementById("directionsPanel"), // เพิ่ม div สำหรับแสดงคำแนะนำ
        });
        // Add click listener for location details
        map.addListener("click", showLocationPopup);

        new google.maps.Marker({
          position: { lat: 14.22414, lng: 101.40563 },
          map: map,
          icon: {
            url: "https://cdn-icons-png.flaticon.com/128/2642/2642715.png",
            scaledSize: new google.maps.Size(40, 40),
          },
          title: "Camera Location",
        });

        new google.maps.Marker({
          position: { lat: 14.22777, lng: 101.40369 },
          map: map,
          icon: {
            url: "https://cdn-icons-png.flaticon.com/128/2642/2642715.png",
            scaledSize: new google.maps.Size(40, 40),
          },
          title: "Camera Location",
        });

        new google.maps.Marker({
          position: { lat: 14.23162, lng: 101.40108 },
          map: map,
          icon: {
            url: "https://cdn-icons-png.flaticon.com/128/2642/2642715.png",
            scaledSize: new google.maps.Size(40, 40),
          },
          title: "Camera Location",
        });

        new google.maps.Marker({
          position: { lat: 14.23476, lng: 101.39829 },
          map: map,
          icon: {
            url: "https://cdn-icons-png.flaticon.com/128/2642/2642715.png",
            scaledSize: new google.maps.Size(40, 40),
          },
          title: "Camera Location",
        });

        const mapTypeContainer = document.querySelector(".map-type-container");
        const mapTypeButton = document.querySelector(".menu-item:nth-child(2)");

        mapTypeButton.addEventListener("click", () => {
          mapTypeContainer.style.display =
            mapTypeContainer.style.display === "none" ? "block" : "none";
        });

        const mapTypeItems = document.querySelectorAll(".map-type-item");
        mapTypeItems.forEach((item) => {
          item.addEventListener("click", () => {
            const mapType = item.dataset.type;
            map.setMapTypeId(mapType);
            mapTypeItems.forEach((i) => i.classList.remove("active"));
            item.classList.add("active");
          });
        });

        searchBox = new google.maps.places.SearchBox(
          document.getElementById("search-box")
        );

        map.addListener("bounds_changed", () => {
          searchBox.setBounds(map.getBounds());
        });

        searchBox.addListener("places_changed", () => {
          const places = searchBox.getPlaces();
          if (places.length === 0) return;

          searchMarkers.forEach((marker) => marker.setMap(null));
          searchMarkers = [];

          const bounds = new google.maps.LatLngBounds();

          places.forEach((place) => {
            if (!place.geometry || !place.geometry.location) return;

            const marker = new google.maps.Marker({
              map: map,
              title: place.name,
              position: place.geometry.location,
              animation: google.maps.Animation.DROP,
              icon: {
                url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
                scaledSize: new google.maps.Size(40, 40),
              },
            });

            const infoWindow = new google.maps.InfoWindow({
              content: `
                            <div style="padding: 10px; max-width: 300px;">
                                <h3 style="margin: 0 0 10px 0; color: #333;">${
                                  place.name
                                }</h3>
                                ${
                                  place.formatted_address
                                    ? `<p style="margin: 5px 0;"><i class="fas fa-map-marker-alt"></i> ${place.formatted_address}</p>`
                                    : ""
                                }
                                ${
                                  place.rating
                                    ? `<p style="margin: 5px 0;"><i class="fas fa-star"></i> ${place.rating} ดาว</p>`
                                    : ""
                                }
                                ${
                                  place.user_ratings_total
                                    ? `<p style="margin: 5px 0;"><i class="fas fa-users"></i> ${place.user_ratings_total} รีวิว</p>`
                                    : ""
                                }
                                ${
                                  place.website
                                    ? `<p style="margin: 5px 0;"><a href="${place.website}" target="_blank">เว็บไซต์</a></p>`
                                    : ""
                                }
                            </div>
                        `,
            });

            marker.addListener("click", () => {
              infoWindow.open(map, marker);
            });

            searchMarkers.push(marker);

            if (place.geometry.viewport) {
              bounds.union(place.geometry.viewport);
            } else {
              bounds.extend(place.geometry.location);
            }
          });

          map.fitBounds(bounds);
        });

        fetchData();
        setInterval(fetchData, 5000);
      }

      async function getLocationDetails(lat, lng) {
        const geocoder = new google.maps.Geocoder();
        try {
          const response = await new Promise((resolve, reject) => {
            geocoder.geocode({ location: { lat, lng } }, (results, status) => {
              if (status === "OK") {
                resolve(results);
              } else {
                reject(status);
              }
            });
          });

          if (response && response[0]) {
            const result = response[0];
            const components = result.address_components;

            let locality =
              components.find((c) => c.types.includes("locality"))?.long_name ||
              "";
            let sublocality =
              components.find((c) => c.types.includes("sublocality"))
                ?.long_name || "";
            let province =
              components.find((c) =>
                c.types.includes("administrative_area_level_1")
              )?.long_name || "";
            let postalCode =
              components.find((c) => c.types.includes("postal_code"))
                ?.long_name || "";

            return {
              address: result.formatted_address,
              locality,
              sublocality,
              province,
              postalCode,
              lat: lat.toFixed(6),
              lng: lng.toFixed(6),
            };
          }
          return null;
        } catch (error) {
          console.error("Geocoding error:", error);
          return null;
        }
      }

      async function showLocationPopup(event) {
        const lat = event.latLng.lat();
        const lng = event.latLng.lng();
        const details = await getLocationDetails(lat, lng);

        if (details) {
          const content = `
            <div class="popup-header">
                <h3 style="margin: 0; color: #333; font-size: 18px;">รายละเอียดสถานที่</h3>
                <button class="close-popup" onclick="closePopup()">&times;</button>
            </div>
            <div style="font-size: 14px;">
                <p style="margin: 10px 0;">
                    <strong>ที่อยู่:</strong> ${details.address}
                </p>
                ${
                  details.locality
                    ? `
                <p style="margin: 10px 0;">
                    <strong>ตำบล/แขวง:</strong> ${details.locality}
                </p>`
                    : ""
                }
                ${
                  details.sublocality
                    ? `
                <p style="margin: 10px 0;">
                    <strong>อำเภอ/เขต:</strong> ${details.sublocality}
                </p>`
                    : ""
                }
                ${
                  details.province
                    ? `
                <p style="margin: 10px 0;">
                    <strong>จังหวัด:</strong> ${details.province}
                </p>`
                    : ""
                }
                ${
                  details.postalCode
                    ? `
                <p style="margin: 10px 0;">
                    <strong>รหัสไปรษณีย์:</strong> ${details.postalCode}
                </p>`
                    : ""
                }
                 <p style="margin: 10px 0;">
                    <strong>พิกัด:</strong> ${details.lat}, ${details.lng}
                </p>
                <button onclick="startNavigation(${details.lat}, ${
            details.lng
          })" style="
                    width: 100%;
                    padding: 12px;
                    background: #4caf50;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    margin-top: 15px;
                    font-family: 'Kanit', sans-serif;">
                    <i class="fas fa-directions"></i>
                    นำทาง
                </button>
            </div>
        `;

          document.querySelector(".popup-content").innerHTML = content;
          document.getElementById("locationPopup").classList.add("show");
        }
      }
      function startNavigation(destLat, destLng) {
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition((position) => {
            const origin = {
              lat: position.coords.latitude,
              lng: position.coords.longitude,
            };
            const destination = {
              lat: destLat,
              lng: destLng,
            };

            const request = {
              origin: origin,
              destination: destination,
              travelMode: google.maps.TravelMode.DRIVING,
            };

            directionsService.route(request, (result, status) => {
              if (status === "OK") {
                directionsRenderer.setDirections(result);
                // เพิ่ม panel สำหรับแสดงคำแนะนำ
                document.querySelector(".popup-content").innerHTML +=
                  '<div id="directionsPanel"></div>';
              } else {
                alert("ไม่สามารถค้นหาเส้นทางได้: " + status);
              }
            });
          });
        } else {
          alert("ไม่สามารถระบุตำแหน่งปัจจุบันได้");
        }
      }

      function closePopup() {
        document.getElementById("locationPopup").classList.remove("show");
      }

      function clearMarkers() {
        elephantMarkers.forEach((marker) => marker.setMap(null));
        if (elephantCircle) {
          elephantCircle.setMap(null);
          elephantCircle = null;
        }
        elephantMarkers.length = 0;
      }

      function fetchData() {
        const apiUrl = `https://aprlabtop.com/elephant_api/get_detections.php?last_id=${lastDetectionID}`;

        fetch(apiUrl)
          .then((response) => {
            if (!response.ok)
              throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
          })
          .then((json) => {
            let dataArray = [];
            if (json && json.status === "success" && Array.isArray(json.data)) {
              dataArray = json.data;
            } else if (Array.isArray(json)) {
              dataArray = json;
            }

            if (dataArray.length === 0) return;

            dataArray.sort((a, b) => parseInt(a.id, 10) - parseInt(b.id, 10));
            const latestItem = dataArray[dataArray.length - 1];
            const latestId = parseInt(latestItem.id, 10) || 0;

            if (latestId > lastDetectionID) {
              clearMarkers();
              try {
                let latArray =
                  typeof latestItem.elephant_lat === "string"
                    ? JSON.parse(latestItem.elephant_lat)
                    : latestItem.elephant_lat;
                let lngArray =
                  typeof latestItem.elephant_long === "string"
                    ? JSON.parse(latestItem.elephant_long)
                    : latestItem.elephant_long;

                if (
                  Array.isArray(latArray) &&
                  Array.isArray(lngArray) &&
                  latArray.length > 0 &&
                  lngArray.length > 0
                ) {
                  const count = Math.min(latArray.length, lngArray.length);
                  let firstElephantPos = null;

                  for (let i = 0; i < count; i++) {
                    const lat = parseFloat(latArray[i]);
                    const lng = parseFloat(lngArray[i]);

                    if (!isNaN(lat) && !isNaN(lng)) {
                      const pos = { lat, lng };
                      if (!firstElephantPos) firstElephantPos = pos;

                      const elephantMarker = new google.maps.Marker({
                        position: pos,
                        map: map,
                        icon: {
                          url: "https://aprlabtop.com/Honey_test/icons/elephant-icon.png",
                          scaledSize: new google.maps.Size(40, 40),
                        },
                        title: `Elephant from detection #${latestId}`,
                        animation: google.maps.Animation.DROP,
                      });
                      elephantMarkers.push(elephantMarker);
                    }
                  }

                  if (firstElephantPos) {
                    elephantCircle = new google.maps.Circle({
                      strokeColor: "#FF0000",
                      strokeOpacity: 0.8,
                      strokeWeight: 2,
                      fillColor: "#FF0000",
                      fillOpacity: 0.15,
                      map: map,
                      center: firstElephantPos,
                      radius: 200,
                    });
                  }
                }
              } catch (err) {
                console.error("Failed to parse coordinates:", err);
              }
              lastDetectionID = latestId;
            }
          })
          .catch((err) => console.error("Error fetching data:", err));
      }

      document.getElementById("locate-btn").addEventListener("click", () => {
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            (position) => {
              const pos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
              };
              map.setCenter(pos);
              map.setZoom(15);

              new google.maps.Marker({
                position: pos,
                map: map,
                icon: {
                  url: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png",
                },
                title: "ตำแหน่งปัจจุบัน",
              });
            },
            (error) => {
              console.error("Error getting location:", error);
              alert("ไม่สามารถระบุตำแหน่งได้");
            }
          );
        } else {
          alert("เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง");
        }
      });

      document.addEventListener("DOMContentLoaded", function () {
        document.querySelector(".sidebar").classList.add("collapsed");
        document.getElementById("map").classList.add("expanded");
        document.querySelector(".search-container").classList.add("expanded");
      });

      function callEmergency() {
        const phoneNumber = "0970533906";
        window.location.href = `tel:${phoneNumber}`;
      }
    </script>

    <script
      src="https://maps.googleapis.com/maps/api/js?key=YOUR API KEY&libraries=places&callback=initMap"
      async
      defer
    ></script>
  </body>
</html>
