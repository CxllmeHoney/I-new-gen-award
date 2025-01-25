<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-time Elephant Markers</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Real-time Elephant Markers</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            :root {
                --primary-color: #4CAF50;
                --primary-dark: #45a049;
                --primary-light: rgba(76, 175, 80, 0.1);
                --white: #ffffff;
                --shadow: rgba(0, 0, 0, 0.1);
                --transition: 0.3s;
            }

            html,
            body {
                height: 100%;
                font-family: 'Kanit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

            /* Sidebar Styles */
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
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
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

            /* Menu Items */
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
                content: '';
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

            /* Map Type Container */
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
                content: '';
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

            /* Toggle Button */
            .toggle-btn {
                position: absolute;
                right: -45px;
                top: 50%;
                width: 45px;
                height: 45px;
                border: none;
                border-radius: 0 12px 12px 0;
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
                color: var(--white);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all var(--transition);
                box-shadow: 4px 0 15px var(--shadow);
            }

            .toggle-btn:hover {
                width: 50px;
                background: linear-gradient(135deg, var(--primary-dark) 0%, #3d8b40 100%);
            }

            .toggle-btn i {
                font-size: 1.2rem;
                transition: transform var(--transition);
            }

            .sidebar.collapsed .toggle-btn i {
                transform: rotate(180deg);
            }

            /* Search Container */
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

            /* Animations */
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

            /* Scrollbar Styling */
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

            /* Responsive Design */
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
            }

            /* Dark Mode Support */
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
        <div class="sidebar-header">
            <h2>ระบบติดตามช้างป่า</h2>
        </div>
        <div class="sidebar-content">
            <div class="menu-item" id="locate-btn">
                <i class="fas fa-location-crosshairs"></i>
                <span>ค้นหาตำแหน่งปัจจุบัน</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-layer-group"></i>
                <span>รูปแบบแผนที่</span>
            </div>
            <div class="map-type-container" style="padding-left: 40px; display: none;">
                <div class="map-type-item" data-type="roadmap">แผนที่ถนน</div>
                <div class="map-type-item" data-type="satellite">ดาวเทียม</div>
                <div class="map-type-item" data-type="hybrid">ผสม</div>
                <div class="map-type-item" data-type="terrain">ภูมิประเทศ</div>
            </div>
        </div>
        <button class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="search-container">
        <input id="search-box" class="search-box" type="text" placeholder="ค้นหาสถานที่...">
        <i class="fas fa-search search-icon"></i>
    </div>
    <div id="map"></div>

    <script>
        let map;
        const elephantMarkers = [];
        let lastDetectionID = 0;
        let searchBox;

        const sidebar = document.querySelector('.sidebar');
        const mapElement = document.getElementById('map');
        const searchContainer = document.querySelector('.search-container');
        const toggleBtn = document.querySelector('.toggle-btn');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mapElement.classList.toggle('expanded');
            searchContainer.classList.toggle('expanded');
        });

        async function initMap() {
            const mapOptions = {
                center: { lat: 14.22512, lng: 101.40544 },
                zoom: 14,
                mapTypeId: google.maps.MapTypeId.HYBRID,  // เพิ่มบรรทัดนี้
                mapTypeControl: false,
                zoomControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_CENTER
                },
                streetViewControl: true,
                streetViewControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_CENTER
                },
                fullscreenControl: true,
                fullscreenControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_TOP
                }
            };

            map = new google.maps.Map(document.getElementById("map"), mapOptions);

            new google.maps.Marker({
                position: { lat: 14.22512, lng: 101.40544 },
                map: map,
                icon: {
                    url: "https://cdn-icons-png.flaticon.com/128/2642/2642715.png",
                    scaledSize: new google.maps.Size(40, 40),
                },
                title: "Camera Location",
            });

            // Add map type selection functionality
            const mapTypeContainer = document.querySelector('.map-type-container');
            const mapTypeButton = document.querySelector('.menu-item:nth-child(2)');

            mapTypeButton.addEventListener('click', () => {
                mapTypeContainer.style.display =
                    mapTypeContainer.style.display === 'none' ? 'block' : 'none';
            });

            const mapTypeItems = document.querySelectorAll('.map-type-item');
            mapTypeItems.forEach(item => {
                item.addEventListener('click', () => {
                    const mapType = item.dataset.type;
                    map.setMapTypeId(mapType);

                    mapTypeItems.forEach(i => i.classList.remove('active'));
                    item.classList.add('active');
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

                const bounds = new google.maps.LatLngBounds();
                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) return;

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

        function clearMarkers() {
            elephantMarkers.forEach(marker => marker.setMap(null));
            elephantMarkers.length = 0;
        }

        function fetchData() {
            const apiUrl = `https://aprlabtop.com/elephant_api/get_detections.php?last_id=${lastDetectionID}`;

            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.json();
                })
                .then(json => {
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
                            let latArray = typeof latestItem.elephant_lat === "string"
                                ? JSON.parse(latestItem.elephant_lat)
                                : latestItem.elephant_lat;
                            let lngArray = typeof latestItem.elephant_long === "string"
                                ? JSON.parse(latestItem.elephant_long)
                                : latestItem.elephant_long;

                            if (Array.isArray(latArray) && Array.isArray(lngArray) &&
                                latArray.length > 0 && lngArray.length > 0) {

                                const count = Math.min(latArray.length, lngArray.length);
                                for (let i = 0; i < count; i++) {
                                    const lat = parseFloat(latArray[i]);
                                    const lng = parseFloat(lngArray[i]);

                                    if (!isNaN(lat) && !isNaN(lng)) {
                                        const elephantMarker = new google.maps.Marker({
                                            position: { lat, lng },
                                            map: map,
                                            icon: {
                                                url: "https://aprlabtop.com/Honey_test/icons/elephant-icon.png",
                                                scaledSize: new google.maps.Size(40, 40),
                                            },
                                            title: `Elephant from detection #${latestId}`,
                                            animation: google.maps.Animation.DROP
                                        });
                                        elephantMarkers.push(elephantMarker);
                                    }
                                }
                            }
                        } catch (err) {
                            console.error("Failed to parse coordinates:", err);
                        }
                        lastDetectionID = latestId;
                    }
                })
                .catch(err => console.error("Error fetching data:", err));
        }
        document.getElementById('locate-btn').addEventListener('click', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        map.setCenter(pos);
                        map.setZoom(15);

                        // Optional: Add a marker at current location
                        new google.maps.Marker({
                            position: pos,
                            map: map,
                            icon: {
                                url: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                            },
                            title: "ตำแหน่งปัจจุบัน"
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
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.sidebar').classList.add('collapsed');
            document.getElementById('map').classList.add('expanded');
            document.querySelector('.search-container').classList.add('expanded');
        });
    </script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAWO3NgFt1_1fWEN70KwMmgTuFxVmQ76aw&libraries=places&callback=initMap"
        async defer></script>
</body>

</html>