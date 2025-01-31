<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <title>Real-time Elephant Markers</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet" />
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>
    <script
        src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.1.1/mapbox-gl-directions.js"></script>
    <link rel="stylesheet"
        href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.1.1/mapbox-gl-directions.css" />
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
            background: linear-gradient(135deg,
                    var(--primary-color) 0%,
                    var(--primary-dark) 100%);
            color: var(--white);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
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
            top: 75px;
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 0 12px 12px 0;
            background: linear-gradient(135deg,
                    var(--primary-color) 0%,
                    var(--primary-dark) 100%);
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

        .search-box:focus {
            outline: none;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .search-icon {
            position: absolute;
            right: 25px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            pointer-events: none;
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

        .route-search-panel {
            padding: 0 15px;
            /* ลด padding ด้านข้าง */
            width: 100%;
            /* ให้ความกว้างเต็ม container */
            box-sizing: border-box;
            /* รวม padding ในการคำนวณความกว้าง */
        }

        .route-search-container {
            margin: 10px 0;
            padding: 12px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
            width: 100%;
        }

        .input-group {
            display: flex;
            gap: 5px;
            width: 100%;
        }

        /* สร้าง wrapper สำหรับ input */
        .input-wrapper {
            width: calc(100% - 41px);
            /* 36px ของปุ่ม + 5px ของ gap */
        }

        .location-input {
            width: 100%;
            height: 36px;
            /* กำหนดความสูงให้เท่ากับปุ่ม */
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
            touch-action: manipulation;
        }

        /* สำหรับช่อง input ปลายทาง */
        #destination-input {
            width: calc(100% - 41px);
            /* ให้มีความกว้างเท่ากับช่องต้นทาง */
        }

        .location-button {
            width: 36px;
            /* ลดขนาดปุ่ม */
            height: 36px;
            padding: 6px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            /* ป้องกันการหด */
        }

        #route-search-panel {
            display: none;
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

        @media screen and (max-width: 360px) {
            .route-search-panel {
                padding: 0 10px;
            }

            .route-search-container {
                padding: 10px;
            }

            .location-button {
                width: 32px;
                height: 32px;
            }

            .input-group {
                gap: 4px;
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
        <div class="sidebar-header" style="
          display: flex;
          align-items: center;
          gap: 15px;
          padding: 20px;
          background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        ">
            <img src="https://aprlabtop.com/Honey_test/logo.png" alt="Logo" style="
            height: 80px;
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.2));
            border-radius: 80%;
          " />
            <h2 style="
            font-size: 28px;
            font-weight: 600;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
          ">
                WILDSAFE
            </h2>
        </div>

        <div class="sidebar-content">
            <!-- ส่วนของเมนูค้นหาเส้นทาง -->
            <div class="menu-item" id="route-search-btn" style="background: #3498db; border-color: #3498db">
                <i class="fas fa-route" style="background: rgba(255, 255, 255, 0.2); color: white"></i>
                <span style="color: white">ค้นหาเส้นทาง</span>
            </div>

            <!-- ปุ่มรูปแบบแผนที่ -->
            <div class="menu-item" style="background: #67cb78; border-color: #67cb78">
                <i class="fas fa-layer-group" style="background: rgba(255, 255, 255, 0.2); color: white"></i>
                <span style="color: white">รูปแบบแผนที่</span>
            </div>

            <!-- ตัวเลือกรูปแบบแผนที่ -->
            <div class="map-type-container" style="padding-left: 40px; display: none">
                <div class="map-type-item" data-type="roadmap">แผนที่ถนน</div>
                <div class="map-type-item" data-type="satellite">ดาวเทียม</div>
                <div class="map-type-item" data-type="hybrid">ผสม</div>
                <div class="map-type-item" data-type="terrain">ภูมิประเทศ</div>
            </div>

            <!-- ปุ่มค้นหาตำแหน่งปัจจุบัน -->
            <div class="menu-item" id="locate-btn" style="background: #e7caa0; border-color: #e7caa0">
                <i class="fas fa-location-crosshairs" style="background: rgba(255, 255, 255, 0.2); color: white"></i>
                <span style="color: white">ค้นหาตำแหน่งปัจจุบัน</span>
            </div>

            <!-- ส่วนค้นหาเส้นทาง -->
            <div id="route-search-panel" class="route-search-panel">
                <div class="route-search-container">
                    <!-- ช่องกรอกต้นทาง -->
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 5px; color: #666">ต้นทาง:</label>
                        <div class="input-group">
                            <div class="input-wrapper">
                                <input type="text" id="origin-input" class="location-input"
                                    placeholder="เลือกจุดเริ่มต้น" />
                            </div>
                            <button id="use-current-location" class="location-button">
                                <i class="fas fa-location-crosshairs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ช่องกรอกปลายทาง -->
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 5px; color: #666">ปลายทาง:</label>
                        <div class="input-group">
                            <input type="text" id="destination-input" class="location-input"
                                placeholder="เลือกจุดหมาย" />
                        </div>
                    </div>
                    <!-- ปุ่มค้นหาเส้นทาง -->
                    <button id="find-route-btn" style="
                width: 100%;
                padding: 10px;
                margin-top: 15px; /* เพิ่มระยะห่างด้านบน */
                background: #3498db;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-family: 'Kanit', sans-serif;
              ">
                        <i class="fas fa-search"></i>
                        ค้นหาเส้นทาง
                    </button>

                    <!-- ปุ่มล้างการนำทาง -->
                    <button id="clear-route-btn" style="
                width: 100%;
                padding: 10px;
                margin-top: 10px;
                background: #e74c3c;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-family: 'Kanit', sans-serif;
                display: none;
              ">
                        <i class="fas fa-times"></i>
                        ล้างการนำทาง
                    </button>
                </div>
            </div>

            <!-- ปุ่มติดต่อเจ้าหน้าที่ -->
            <div class="menu-item" onclick="callEmergency()" style="background: #ff4444; border-color: #ff4444">
                <i class="fas fa-phone-alt" style="background: rgba(255, 255, 255, 0.2); color: white"></i>
                <span style="color: white">ติดต่อเจ้าหน้าที่</span>
            </div>

            <!-- ปุ่มแผงควบคุมผู้ดูแล -->
            <!--<div
          class="menu-item"
          onclick="window.location.href='https://aprlabtop.com/Honey_test/admin_login.php'"
          style="background: #2196f3; border-color: #2196f3"
        >
          <i
            class="fas fa-user-shield"
            style="background: rgba(255, 255, 255, 0.2); color: white"
          ></i>
          <span style="color: white">แผงควบคุมผู้ดูแล</span>
        </div>-->
        </div>

        <!-- ปุ่มซ่อน/แสดง Sidebar -->
        <button class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="search-container">
        <input id="search-box" class="search-box" type="text" placeholder="ค้นหาสถานที่..." />
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
        let currentRoute = null;
        let userMarker = null;
        let watchId = null;

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
                panel: document.getElementById("directionsPanel"),
            });

            // Add click listener for location details
            map.addListener("click", showLocationPopup);

            // Add camera markers
            const cameraLocations = [
                { lat: 14.22414, lng: 101.40563 },
                { lat: 14.22777, lng: 101.40369 },
                { lat: 14.23162, lng: 101.40108 },
                { lat: 14.23476, lng: 101.39829 },
            ];

            cameraLocations.forEach((location) => {
                new google.maps.Marker({
                    position: location,
                    map: map,
                    icon: {
                        url: "https://cdn-icons-png.flaticon.com/128/2642/2642715.png",
                        scaledSize: new google.maps.Size(40, 40),
                    },
                    title: "Camera Location",
                });
            });

            // Setup map type controls
            const mapTypeContainer = document.querySelector(".map-type-container");
            const mapTypeButton = document.querySelector(".menu-item:nth-child(2)");
            const routeSearchPanel = document.getElementById("route-search-panel");
            const routeSearchBtn = document.getElementById("route-search-btn");

            mapTypeButton.addEventListener("click", () => {
                mapTypeContainer.style.display =
                    mapTypeContainer.style.display === "none" ? "block" : "none";
                routeSearchPanel.style.display = "none";
            });

            routeSearchBtn.addEventListener("click", () => {
                routeSearchPanel.style.display =
                    routeSearchPanel.style.display === "none" ? "block" : "none";
                mapTypeContainer.style.display = "none";
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

            // Setup search box
            searchBox = new google.maps.places.SearchBox(
                document.getElementById("search-box")
            );

            map.addListener("bounds_changed", () => {
                searchBox.setBounds(map.getBounds());
            });

            // Updated search box functionality
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();
                if (places.length === 0) return;

                // Clear existing markers
                searchMarkers.forEach((marker) => marker.setMap(null));
                searchMarkers = [];

                const bounds = new google.maps.LatLngBounds();
                const place = places[0]; // Use first place found

                // Check if it's Khao Yai
                const khaoYaiKeywords = [
                    "เขาใหญ่",
                    "อุทยานแห่งชาติเขาใหญ่",
                    "khao yai",
                    "khao yai national park",
                ];

                const isKhaoYai = khaoYaiKeywords.some(
                    (keyword) =>
                        place.name.toLowerCase().includes(keyword.toLowerCase()) ||
                        (place.formatted_address &&
                            place.formatted_address
                                .toLowerCase()
                                .includes(keyword.toLowerCase()))
                );

                let markerPosition;
                let placeName;
                let placeAddress;

                if (isKhaoYai) {
                    // Use Khao Yai entrance coordinates
                    markerPosition = new google.maps.LatLng(14.439367, 101.372433);
                    placeName = "ด่านอุทยานแห่งชาติเขาใหญ่";
                    placeAddress =
                        "อุทยานแห่งชาติเขาใหญ่ ตำบล หมูสี อำเภอปากช่อง จังหวัดนครราชสีมา 30130";
                } else {
                    markerPosition = place.geometry.location;
                    placeName = place.name;
                    placeAddress = place.formatted_address;
                }

                if (markerPosition) {
                    const marker = new google.maps.Marker({
                        map: map,
                        title: placeName,
                        position: markerPosition,
                        animation: google.maps.Animation.DROP,
                        icon: {
                            url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
                            scaledSize: new google.maps.Size(40, 40),
                        },
                    });

                    searchMarkers.push(marker);

                    // Create popup content with proper lat/lng access
                    const content = `
      <div class="popup-header">
        <h3 style="margin: 0; color: #333; font-size: 18px;">${placeName}</h3>
        <button class="close-popup" onclick="closePopup()">&times;</button>
      </div>
      <div style="font-size: 14px;">
        ${placeAddress
                            ? `<p style="margin: 10px 0;"><strong>ที่อยู่:</strong> ${placeAddress}</p>`
                            : ""
                        }
        ${!isKhaoYai && place.rating
                            ? `<p style="margin: 10px 0;"><strong>คะแนน:</strong> ${place.rating} ดาว</p>`
                            : ""
                        }
        ${!isKhaoYai && place.user_ratings_total
                            ? `<p style="margin: 10px 0;"><strong>รีวิว:</strong> ${place.user_ratings_total} รีวิว</p>`
                            : ""
                        }
        <p style="margin: 10px 0;">
          <strong>พิกัด:</strong> ${markerPosition.lat()}, ${markerPosition.lng()}
        </p>
        <button onclick="startNavigation(${markerPosition.lat()}, ${markerPosition.lng()})" 
          style="
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

                    // Show popup
                    document.querySelector(".popup-content").innerHTML = content;
                    document.getElementById("locationPopup").classList.add("show");

                    // Adjust map
                    if (isKhaoYai) {
                        map.setCenter(markerPosition);
                        map.setZoom(15);
                    } else {
                        if (place.geometry.viewport) {
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(markerPosition);
                        }
                        map.fitBounds(bounds);
                    }
                }
            });

            // Setup route search functionality
            setupRouteSearch();

            fetchData();
            setInterval(fetchData, 5000);
        }

        async function setupRouteSearch() {
            const clearRouteBtn = document.getElementById("clear-route-btn");
            const findRouteBtn = document.getElementById("find-route-btn");
            const originInput = document.getElementById("origin-input");
            const destinationInput = document.getElementById("destination-input");

            // เพิ่มโค้ดส่วนนี้เพื่อตั้งค่าตำแหน่งเริ่มต้น
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const currentLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        // ใช้ Geocoder เพื่อแปลงพิกัดเป็นที่อยู่
                        const geocoder = new google.maps.Geocoder();
                        geocoder.geocode({ location: currentLocation }, (results, status) => {
                            if (status === "OK" && results[0]) {
                                originInput.value = results[0].formatted_address;
                            } else {
                                originInput.value = `${currentLocation.lat}, ${currentLocation.lng}`;
                            }
                        });
                    },
                    (error) => {
                        console.error("Error getting location:", error);
                        handleLocationError(error);
                    }
                );
            }

            // โค้ดส่วน Autocomplete และอื่นๆ ที่มีอยู่แล้ว...
            const originAutocomplete = new google.maps.places.Autocomplete(originInput, {
                fields: ["formatted_address", "geometry", "name"],
                strictBounds: false
            });

            const destinationAutocomplete = new google.maps.places.Autocomplete(destinationInput, {
                fields: ["formatted_address", "geometry", "name"],
                strictBounds: false
            });

            // อัพเดท bounds เมื่อแผนที่เปลี่ยนแปลง
            map.addListener("bounds_changed", () => {
                originAutocomplete.setBounds(map.getBounds());
                destinationAutocomplete.setBounds(map.getBounds());
            });

            findRouteBtn.addEventListener("click", async () => {
                if (!originInput.value || !destinationInput.value) {
                    alert("กรุณาระบุต้นทางและปลายทาง");
                    return;
                }

                try {
                    await calculateAndDisplayRoute(
                        originInput.value,
                        destinationInput.value
                    );
                    // แสดงปุ่มล้างการนำทาง
                    clearRouteBtn.style.display = "flex";
                    // พับ sidebar
                    sidebar.classList.add("collapsed");
                    mapElement.classList.add("expanded");
                    searchContainer.classList.add("expanded");
                } catch (error) {
                    alert(error.message);
                }
            });

            clearRouteBtn.addEventListener("click", () => {
                // ล้างค่าใน input
                originInput.value = "";
                destinationInput.value = "";

                // ซ่อนปุ่มล้างการนำทาง
                clearRouteBtn.style.display = "none";

                // ล้างเส้นทางจากแผนที่
                if (directionsRenderer) {
                    directionsRenderer.setMap(null);
                    directionsRenderer = null;
                }

                // ล้าง marker ตำแหน่งผู้ใช้
                if (userMarker) {
                    userMarker.setMap(null);
                    userMarker = null;
                }

                // ยกเลิกการติดตามตำแหน่ง
                if (watchId) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
            });

            // เพิ่ม event listener สำหรับปุ่มตำแหน่งปัจจุบัน
            document
                .getElementById("use-current-location")
                .addEventListener("click", () => {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const currentLocation = {
                                    lat: position.coords.latitude,
                                    lng: position.coords.longitude,
                                };

                                const geocoder = new google.maps.Geocoder();
                                geocoder.geocode(
                                    { location: currentLocation },
                                    (results, status) => {
                                        if (status === "OK" && results[0]) {
                                            originInput.value = results[0].formatted_address;
                                        } else {
                                            originInput.value = `${currentLocation.lat}, ${currentLocation.lng}`;
                                        }
                                    }
                                );
                            },
                            (error) => {
                                handleLocationError(error);
                            }
                        );
                    } else {
                        alert("เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง");
                    }
                });
        }

        async function calculateAndDisplayRoute(origin, destination) {
            try {
                // Create custom markers for origin and destination
                const carIcon = {
                    url: "https://cdn-icons-png.flaticon.com/128/2555/2555013.png",
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 16)
                };

                const destinationIcon = {
                    url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 32)
                };

                let originCoords = origin;
                let destCoords = destination;

                // Check if destination is Khao Yai National Park
                const khaoYaiKeywords = [
                    "เขาใหญ่",
                    "อุทยานแห่งชาติเขาใหญ่",
                    "khao yai",
                    "khao yai national park",
                ];

                const isKhaoYaiDestination = khaoYaiKeywords.some((keyword) =>
                    destination.toString().toLowerCase().includes(keyword.toLowerCase())
                );

                if (isKhaoYaiDestination) {
                    // Coordinates for Khao Yai National Park Main Entrance
                    destCoords = {
                        lat: 14.439367,
                        lng: 101.372433,
                    };
                } else if (typeof destination === "string") {
                    const geocoder = new google.maps.Geocoder();
                    try {
                        const response = await new Promise((resolve, reject) => {
                            geocoder.geocode(
                                {
                                    address: destination,
                                    region: "th",
                                    componentRestrictions: { country: "TH" },
                                },
                                (results, status) => {
                                    if (status === "OK" && results[0]) {
                                        resolve(results[0].geometry.location);
                                    } else {
                                        reject(new Error("ไม่สามารถค้นหาตำแหน่งปลายทาง"));
                                    }
                                }
                            );
                        });
                        destCoords = response;
                    } catch (error) {
                        console.error("Error geocoding destination:", error);
                        throw new Error(
                            "ไม่สามารถค้นหาตำแหน่งปลายทาง กรุณาระบุสถานที่ให้ชัดเจนขึ้น"
                        );
                    }
                }

                if (typeof origin === "string") {
                    const geocoder = new google.maps.Geocoder();
                    try {
                        const response = await new Promise((resolve, reject) => {
                            geocoder.geocode(
                                {
                                    address: origin,
                                    region: "th",
                                    componentRestrictions: { country: "TH" },
                                },
                                (results, status) => {
                                    if (status === "OK" && results[0]) {
                                        resolve(results[0].geometry.location);
                                    } else {
                                        reject(new Error("ไม่สามารถค้นหาตำแหน่งต้นทาง"));
                                    }
                                }
                            );
                        });
                        originCoords = response;
                    } catch (error) {
                        console.error("Error geocoding origin:", error);
                        throw new Error(
                            "ไม่สามารถค้นหาตำแหน่งต้นทาง กรุณาระบุสถานที่ให้ชัดเจนขึ้น"
                        );
                    }
                }

                const request = {
                    origin: originCoords,
                    destination: destCoords,
                    travelMode: google.maps.TravelMode.DRIVING,
                    optimizeWaypoints: true,
                    provideRouteAlternatives: true,
                    avoidHighways: false,
                    avoidTolls: false,
                    region: "th",
                };

                const result = await new Promise((resolve, reject) => {
                    directionsService.route(request, (result, status) => {
                        if (status === "OK") {
                            resolve(result);
                        } else {
                            reject(new Error("ไม่สามารถค้นหาเส้นทางได้: " + status));
                        }
                    });
                });

                // Create new renderer and display route
                if (directionsRenderer) {
                    directionsRenderer.setMap(null);
                }

                directionsRenderer = new google.maps.DirectionsRenderer({
                    map: map,
                    directions: result,
                    suppressMarkers: true, // Hide default markers
                    preserveViewport: false,
                    polylineOptions: {
                        strokeColor: "#2196F3",
                        strokeWeight: 5,
                        strokeOpacity: 0.8,
                    },
                });

                // Add custom markers
                new google.maps.Marker({
                    position: result.routes[0].legs[0].start_location,
                    map: map,
                    icon: carIcon,
                    title: "จุดเริ่มต้น"
                });

                new google.maps.Marker({
                    position: result.routes[0].legs[0].end_location,
                    map: map,
                    icon: destinationIcon,
                    title: "จุดหมาย"
                });

                // Show navigation info with modified destination name if it's Khao Yai
                if (isKhaoYaiDestination) {
                    const modifiedResult = { ...result };
                    modifiedResult.routes[0].legs[0].end_address =
                        "ด่านอุทยานแห่งชาติเขาใหญ่";
                    displayNavigationInfo(modifiedResult);
                } else {
                    displayNavigationInfo(result);
                }

                // Adjust viewport to show entire route
                const bounds = new google.maps.LatLngBounds();
                result.routes[0].legs.forEach((leg) => {
                    bounds.extend(leg.start_location);
                    bounds.extend(leg.end_location);
                });
                map.fitBounds(bounds);
            } catch (error) {
                console.error("Route calculation error:", error);
                alert(error.message);
            }
        }

        async function geocodeAddress(address) {
            const geocoder = new google.maps.Geocoder();

            try {
                const result = await new Promise((resolve, reject) => {
                    geocoder.geocode({ address: address }, (results, status) => {
                        if (status === "OK" && results[0]) {
                            resolve(results[0].geometry.location);
                        } else {
                            reject(new Error("ไม่สามารถค้นหาตำแหน่งที่ระบุได้"));
                        }
                    });
                });
                return result;
            } catch (error) {
                console.error("Geocoding error:", error);
                return null;
            }
        }

        function displayNavigationInfo(result) {
            const route = result.routes[0];
            const leg = route.legs[0];

            const content = `
          <div style="padding: 15px; position: relative;">
            <button class="close-popup" onclick="closePopup()" style="
              position: absolute;
              right: 10px;
              top: 10px;
              background: none;
              border: none;
              font-size: 20px;
              cursor: pointer;
              color: #666;
              padding: 5px;
            ">&times;</button>
            <h3 style="margin: 0 0 15px 0; padding-right: 30px;">ข้อมูลการนำทาง</h3>
            <div style="margin-bottom: 10px;">
              <strong>ระยะทางรวม:</strong> ${leg.distance.text}
            </div>
            <div style="margin-bottom: 10px;">
              <strong>เวลาโดยประมาณ:</strong> ${leg.duration.text}
            </div>
            <div style="margin-bottom: 10px;">
              <strong>จุดเริ่มต้น:</strong> ${leg.start_address}
            </div>
            <div style="margin-bottom: 10px;">
              <strong>จุดหมาย:</strong> ${leg.end_address}
            </div>
          </div>
        `;

            document.querySelector(".popup-content").innerHTML = content;
            document.getElementById("locationPopup").classList.add("show");
            addStopNavigationButton();
        }

        function addStopNavigationButton() {
            const stopButton = document.createElement("button");
            stopButton.innerHTML = '<i class="fas fa-times"></i> ยกเลิกการนำทาง';
            stopButton.style.cssText = `
          width: 100%;
          padding: 12px;
          background: #ff4444;
          color: white;
          border: none;
          border-radius: 8px;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          margin-top: 15px;
          font-family: 'Kanit', sans-serif;
        `;
            stopButton.onclick = stopNavigation;

            document.querySelector(".popup-content").appendChild(stopButton);
        }

        function stopNavigation() {
            if (directionsRenderer) {
                directionsRenderer.setMap(null);
                directionsRenderer = null;
            }

            if (userMarker) {
                userMarker.setMap(null);
                userMarker = null;
            }

            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }

            closePopup();
        }

        function handleLocationError(error) {
            let errorMessage;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = "ผู้ใช้ปฏิเสธการเข้าถึงตำแหน่ง";
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = "ไม่สามารถระบุตำแหน่งได้";
                    break;
                case error.TIMEOUT:
                    errorMessage = "หมดเวลาในการรับตำแหน่ง";
                    break;
                default:
                    errorMessage = "เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ";
            }
            alert(errorMessage);
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
        ${details.locality
                        ? `<p style="margin: 10px 0;">
                <strong>ตำบล/แขวง:</strong> ${details.locality}
              </p>`
                        : ""
                    }
        ${details.sublocality
                        ? `<p style="margin: 10px 0;">
                <strong>อำเภอ/เขต:</strong> ${details.sublocality}
              </p>`
                        : ""
                    }
        ${details.province
                        ? `<p style="margin: 10px 0;">
                <strong>จังหวัด:</strong> ${details.province}
              </p>`
                        : ""
                    }
        ${details.postalCode
                        ? `<p style="margin: 10px 0;">
                <strong>รหัสไปรษณีย์:</strong> ${details.postalCode}
              </p>`
                        : ""
                    }
        <p style="margin: 10px 0;">
          <strong>พิกัด:</strong> ${details.lat}, ${details.lng}
        </p>
        <button onclick="startNavigation(${details.lat}, ${details.lng
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
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const origin = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        const destination = {
                            lat: destLat,
                            lng: destLng,
                        };

                        if (directionsRenderer) {
                            directionsRenderer.setMap(null);
                        }

                        // กำหนดให้ suppressMarkers: true เพื่อซ่อนมาร์กเกอร์ A/B เริ่มต้น
                        directionsRenderer = new google.maps.DirectionsRenderer({
                            map: map,
                            suppressMarkers: true
                        });

                        // กำหนดไอคอนรถสำหรับจุดเริ่มต้น
                        const carIcon = {
                            url: "https://cdn-icons-png.flaticon.com/128/2555/2555013.png",
                            scaledSize: new google.maps.Size(32, 32),
                            anchor: new google.maps.Point(16, 16),
                        };

                        // กำหนดไอคอนสำหรับจุดหมาย
                        const destinationIcon = {
                            url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
                            scaledSize: new google.maps.Size(32, 32),
                            anchor: new google.maps.Point(16, 32),
                        };

                        const request = {
                            origin: origin,
                            destination: destination,
                            travelMode: google.maps.TravelMode.DRIVING,
                        };

                        directionsService.route(request, (result, status) => {
                            if (status === "OK") {
                                directionsRenderer.setDirections(result);

                                // เพิ่มมาร์กเกอร์จุดเริ่มต้น (ไอคอนรถ)
                                new google.maps.Marker({
                                    position: result.routes[0].legs[0].start_location,
                                    map: map,
                                    icon: carIcon,
                                    title: "จุดเริ่มต้น",
                                });

                                // เพิ่มมาร์กเกอร์จุดหมาย
                                new google.maps.Marker({
                                    position: result.routes[0].legs[0].end_location,
                                    map: map,
                                    icon: destinationIcon,
                                    title: "จุดหมาย",
                                });

                                displayNavigationInfo(result);
                            } else {
                                alert("ไม่สามารถค้นหาเส้นทางได้: " + status);
                            }
                        });

                        if (userMarker) {
                            userMarker.setMap(null);
                        }
                        userMarker = new google.maps.Marker({
                            position: origin,
                            map: map,
                            icon: {
                                url: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png",
                                scaledSize: new google.maps.Size(32, 32),
                            },
                            title: "ตำแหน่งของคุณ",
                        });
                    },
                    (error) => {
                        handleLocationError(error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0,
                    }
                );
            } else {
                alert("เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง");
            }
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
            // เพิ่มบรรทัดนี้
            document.getElementById("route-search-panel").style.display = "none";
        });

        function callEmergency() {
            const phoneNumber = "0970533906";
            window.location.href = `tel:${phoneNumber}`;
        }
    </script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAWO3NgFt1_1fWEN70KwMmgTuFxVmQ76aw&libraries=places&callback=initMap"
        async defer></script>
</body>

</html>