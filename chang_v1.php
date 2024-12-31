  <!DOCTYPE html>
  <html lang="th">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>AI ตรวจจับช้างป่า</title>
      <!-- Leaflet CSS -->
      <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
      <!-- Leaflet Geocoder CSS -->
      <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css"
      />
      <!-- Leaflet Routing Machine CSS -->
      <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css"
      />
      <!-- Google Fonts -->
      <link
        href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
      />

      <!-- Leaflet JS -->
      <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
      <!-- Leaflet Geocoder JS -->
      <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
      <!-- Leaflet Routing Machine JS -->
      <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
      <style>
        :root {
          --primary-color: #3b82f6;
          --primary-dark: #2563eb;
          --success-color: #10b981;
          --bg-light: #f3f4f6;
          --bg-dark: #1f2937;
          --text-light: #374151;
          --text-dark: #f9fafb;
        }

        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
          font-family: "Prompt", sans-serif;
        }

        html {
          font-size: 16px;
        }

        @media screen and (max-width: 768px) {
          html {
            font-size: 14px;
          }
        }

        @media screen and (max-width: 480px) {
          html {
            font-size: 12px;
          }
        }

        body {
          background-color: var(--bg-light);
          padding: clamp(10px, 2vw, 20px);
          transition: all 0.3s ease;
          min-height: 100vh;
          width: 100%;
        }

        body.dark-mode {
          background-color: var(--bg-dark);
          color: var(--text-dark);
        }

        .container {
          max-width: min(1200px, 95%);
          margin: 0 auto;
          background: white;
          border-radius: clamp(10px, 2vw, 20px);
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          padding: clamp(10px, 3vw, 20px);
          transition: all 0.3s ease;
        }

        .dark-mode .container {
          background: #111827;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: clamp(10px, 2vw, 20px);
          padding: 0 clamp(5px, 1vw, 10px);
          flex-wrap: wrap;
          gap: 10px;
        }

        h1 {
          color: var(--text-light);
          font-size: clamp(1.5rem, 4vw, 2rem);
          font-weight: 600;
        }

        .dark-mode h1 {
          color: var(--text-dark);
        }

        .controls {
          display: flex;
          gap: clamp(8px, 1.5vw, 15px);
          margin-bottom: clamp(10px, 2vw, 20px);
          padding: clamp(5px, 1vw, 10px);
          background: var(--bg-light);
          border-radius: 15px;
          flex-wrap: wrap;
          width: 100%;
        }

        .dark-mode .controls {
          background: #374151;
        }

        .modern-button {
          padding: clamp(8px, 1.5vw, 12px) clamp(16px, 2vw, 24px);
          background: var(--primary-color);
          color: white;
          border: none;
          border-radius: clamp(8px, 1.5vw, 12px);
          cursor: pointer;
          font-size: clamp(0.85rem, 2vw, 0.95rem);
          font-weight: 500;
          transition: all 0.3s ease;
          display: flex;
          align-items: center;
          gap: 8px;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
          white-space: nowrap;
        }

        .modern-button:hover {
          background: var(--primary-dark);
          transform: translateY(-2px);
        }

        .theme-toggle {
          padding: 8px 16px;
          border-radius: 12px;
          background: var(--bg-light);
          border: 2px solid var(--primary-color);
          color: var(--text-light);
          cursor: pointer;
          transition: all 0.3s ease;
        }

        .dark-mode .theme-toggle {
          background: #374151;
          color: var(--text-dark);
          border-color: var(--primary-dark);
        }

        #mapid {
          height: clamp(300px, 60vh, 600px);
          width: 100%;
          border-radius: clamp(8px, 1.5vw, 15px);
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          margin-bottom: clamp(10px, 2vw, 20px);
          z-index: 1;
        }

        .dark-mode #mapid {
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .info-panel {
          background: rgba(255, 255, 255, 0.9);
          padding: 15px;
          border-radius: 12px;
          margin-top: 20px;
          backdrop-filter: blur(10px);
          border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .dark-mode .info-panel {
          background: rgba(17, 24, 39, 0.9);
          border-color: rgba(255, 255, 255, 0.1);
        }

        .loading {
          display: none;
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: rgba(255, 255, 255, 0.95);
          padding: 20px 40px;
          border-radius: 15px;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          z-index: 1000;
          backdrop-filter: blur(5px);
          width: min(90%, 300px);
          text-align: center;
        }

        .dark-mode .loading {
          background: rgba(17, 24, 39, 0.95);
          color: var(--text-dark);
        }

        .loading::after {
          content: "";
          display: block;
          width: 30px;
          height: 30px;
          margin: 10px auto;
          border: 3px solid var(--primary-color);
          border-radius: 50%;
          border-top-color: transparent;
          animation: spin 1s linear infinite;
        }

        @keyframes spin {
          0% {
            transform: rotate(0deg);
          }

          100% {
            transform: rotate(360deg);
          }
        }

        .stats {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
          gap: clamp(8px, 1.5vw, 15px);
          margin-top: clamp(10px, 2vw, 20px);
        }

        .stat-card {
          background: white;
          padding: clamp(10px, 1.5vw, 15px);
          border-radius: clamp(8px, 1.5vw, 12px);
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
          transition: all 0.3s ease;
        }

        .dark-mode .stat-card {
          background: #374151;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .stat-card:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .leaflet-control-geocoder {
          border-radius: 8px !important;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
          background: white !important;
          border: none !important;
          overflow: hidden !important;
        }

        .leaflet-control-geocoder-form {
          padding: 0 !important;
          margin: 0 !important;
        }

        .dark-mode .leaflet-control-geocoder,
        .dark-mode .leaflet-control-geocoder-form input {
          background: #374151 !important;
          color: var(--text-dark) !important;
        }

        .leaflet-popup-content-wrapper {
          border-radius: 12px;
          padding: 5px;
        }

        .dark-mode .leaflet-popup-content-wrapper {
          background: #374151;
          color: var(--text-dark);
        }

        .popup-content {
          padding: 10px;
        }

        .popup-content h3 {
          margin-bottom: 10px;
          color: var(--primary-color);
        }

        .dark-mode .popup-content h3 {
          color: var(--primary-dark);
        }

        .leaflet-routing-container.leaflet-bar.leaflet-control {
          display: none !important;
        }

        /* Mobile-specific adjustments */
        @media screen and (max-width: 480px) {
          .header {
            flex-direction: column;
            text-align: center;
          }

          .controls {
            justify-content: center;
          }

          .modern-button {
            width: 100%;
            justify-content: center;
          }

          .stats {
            grid-template-columns: 1fr;
          }
        }

        /* Tablet-specific adjustments */
        @media screen and (min-width: 481px) and (max-width: 768px) {
          .controls {
            justify-content: center;
          }

          .stats {
            grid-template-columns: repeat(2, 1fr);
          }
        }

        .notification {
          position: fixed;
          left: 50%;
          top: 20px;
          transform: translateX(-50%);
          padding: 15px 20px;
          border-radius: 12px;
          color: white;
          font-weight: 500;
          z-index: 1000;
          opacity: 0;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          display: flex;
          align-items: center;
          gap: 10px;
          min-width: 280px;
          max-width: 400px;
          animation: slideDown 0.3s ease forwards;
        }

        .notification.info {
          background-color: var(--primary-color);
        }

        .notification.success {
          background-color: var(--success-color);
        }

        .notification.warning {
          background-color: #f59e0b;
        }

        .notification.error {
          background-color: #ef4444;
        }

        #clearRouteBtn {
          background-color: #ef4444;
          /* สีแดง */
          color: #ffffff;
          /* ตัวอักษรสีขาว */
          border: none;
          /* ไม่มีเส้นขอบ */
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
          /* เงาเล็กน้อย */
          transition: all 0.3s ease;
        }

        #clearRouteBtn:hover {
          background-color: #dc2626;
          /* สีแดงเข้มเมื่อโฮเวอร์ */
          transform: translateY(-2px);
          /* ยกปุ่มขึ้นเล็กน้อย */
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
          /* เงาเพิ่มขึ้น */
        }

        #stopTrackingBtn {
          background-color: #f59e0b;
          /* สีส้ม */
          color: #ffffff;
          /* ตัวอักษรสีขาว */
          border: none;
          /* ไม่มีเส้นขอบ */
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
          /* เงาเล็กน้อย */
          transition: all 0.3s ease;
        }

        #stopTrackingBtn:hover {
          background-color: #d97706;
          /* สีส้มเข้มเมื่อโฮเวอร์ */
          transform: translateY(-2px);
          /* ยกปุ่มขึ้นเล็กน้อย */
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
          /* เงาเพิ่มขึ้น */
        }

        @keyframes slideDown {
          0% {
            opacity: 0;
            transform: translate(-50%, -100%);
          }

          100% {
            opacity: 1;
            transform: translate(-50%, 0);
          }
        }

        @keyframes slideUp {
          0% {
            opacity: 1;
            transform: translate(-50%, 0);
          }

          100% {
            opacity: 0;
            transform: translate(-50%, -100%);
          }
        }

        .notification.hide {
          animation: slideUp 0.3s ease forwards;
        }

        /* สำหรับมือถือ */
        @media screen and (max-width: 480px) {
          .notification {
            width: 90%;
            min-width: auto;
            top: 10px;
          }
        }
      </style>
    </head>

    <body>
      <div class="container">
        <div class="header">
          <h1>AI ตรวจจับช้างป่า</h1>
          <button onclick="toggleTheme()" class="theme-toggle" id="themeToggle">
            <span id="themeIcon">🌙</span>
          </button>
        </div>

        <div class="controls">
          <button class="modern-button" onclick="getLocation()">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              fill="currentColor"
              viewBox="0 0 16 16"
            >
              <path
                d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"
              />
            </svg>
            ค้นหาตำแหน่งของคุณ
          </button>
          <button class="modern-button" onclick="showNearbyPlaces()">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              fill="currentColor"
              viewBox="0 0 16 16"
            >
              <path
                d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM2.04 4.326c.325 1.329 2.532 2.54 3.717 3.19.48.263.793.434.743.484-.08.08-.162.158-.242.234-.416.396-.787.749-.758 1.266.035.634.618.824 1.214 1.017.577.188 1.168.38 1.286.983.082.417-.075.988-.22 1.52-.215.782-.406 1.48.22 1.48 1.5-.5 3.798-3.186 4-5 .138-1.243-2-2-3.5-2.5-.478-.16-.755.081-.99.284-.172.15-.322.279-.51.216-.445-.148-2.5-2-1.5-2.5.78-.39.952-.171 1.227.182.078.099.163.208.273.318.609.304.662-.132.723-.633.039-.322.081-.671.277-.867.434-.434 1.265-.791 2.028-1.12.712-.306 1.365-.587 1.579-.88A7 7 0 1 1 2.04 4.327z"
              />
            </svg>
            ค้นหาช้าง
          </button>
          <button
            class="modern-button"
            onclick="window.location.href='https://aprlabtop.com/Honey_test/admin_dashboard.php'"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              fill="currentColor"
              viewBox="0 0 16 16"
            >
              <path
                d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"
              />
              <path
                d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"
              />
            </svg>
            แผงควบคุมผู้ดูแล
          </button>
          <button class="modern-button" onclick="navigateToCamera()">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              fill="currentColor"
              viewBox="0 0 16 16"
            >
              <path
                d="M15 12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.12-.879l.83-.828A1 1 0 0 1 6.827 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0 12.828 5H14a1 1 0 0 1 1 1v6zM2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4H2z"
              />
              <path
                d="M8 11a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5zm0 1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"
              />
            </svg>
            ไปยังกล้อง CCTV
          </button>
          <button
            class="modern-button"
            onclick="clearRoute()"
            id="clearRouteBtn"
            style="display: none"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              fill="currentColor"
              viewBox="0 0 16 16"
            >
              <path
                d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"
              />
            </svg>
            ล้างการนำทาง
          </button>
        </div>

        <div id="mapid"></div>
        <div class="loading">กำลังโหลด...</div>

        <div class="stats">
          <div class="stat-card">
            <h3>ระยะทางรวมเฉลี่ย</h3>
            <p id="totalDistance">- กม.</p>
          </div>
          <div class="stat-card">
            <h3>เวลาเดินทางเฉลี่ย</h3>
            <p id="travelTime">- นาที</p>
          </div>
          <div class="stat-card">
            <h3>พบช้างจำนวน</h3>
            <p id="nearbyCount">- ตัว</p>
          </div>
          <div class="stat-card" id="elephantStatusCard">
            <h3>สถานะการตรวจจับช้าง</h3>
            <p id="elephantStatus">-</p>
          </div>
        </div>
      </div>

      <script>
        // ตั้งค่าตัวแปรพื้นฐาน
        let isDarkMode = false;
        const darkTileLayer =
          "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png";
        const lightTileLayer =
          "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
        let currentTileLayer;
        let mymap;
        let currentLocationMarker = null;
        let lastMarker = null;
        let currentRoute = null;
        let watchId = null;
        let popupUpdateInterval = null;
        let isFirstPosition = true;
        let lastDetectionTime = 0;

        // ไอคอนสำหรับตำแหน่งปัจจุบัน
        const currentLocationIcon = L.icon({
          iconUrl: "https://cdn-icons-png.flaticon.com/512/1828/1828884.png",
          iconSize: [36, 36],
          iconAnchor: [18, 18],
          popupAnchor: [0, -18],
        });

        // ไอคอนสำหรับช้าง
        const elephantIcon = L.icon({
          iconUrl: "https://cdn-icons-png.flaticon.com/128/1864/1864469.png",
          iconSize: [32, 32],
          iconAnchor: [16, 32],
          popupAnchor: [0, -32],
        });

        // ฟังก์ชันสำหรับเริ่มต้นแผนที่
        function initializeMap() {
          mymap = L.map("mapid").setView([14.439606, 101.372359], 13);
          currentTileLayer = L.tileLayer(lightTileLayer, {
            maxZoom: 19,
            attribution: "© OpenStreetMap",
          }).addTo(mymap);

          // เพิ่มกล้อง CCTV และวงกลมรัศมีลงบนแผนที่
          var cameraIcon = L.icon({
            iconUrl: "https://cdn-icons-png.flaticon.com/128/45/45010.png",
            iconSize: [15, 15],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36],
          });

          // กำหนดตำแหน่งกล้อง CCTV
          const camera2Position = [14.22512, 101.40544];

          // สร้างวงกลมแสดงรัศมีของกล้อง
          const cameraRadiusCircle = L.circle(camera2Position, {
            radius: 1000, // รัศมี 1 กิโลเมตร
            color: "#FF5C5C",
            fillColor: "#FF5C5C",
            fillOpacity: 0.2,
            weight: 2,
            dashArray: "5, 10", // เส้นประ
          }).addTo(mymap);

          // สร้างวงกลมชั้นในแสดงรัศมีที่ใกล้กว่า
          const innerCameraCircle = L.circle(camera2Position, {
            radius: 500, // รัศมี 500 เมตร
            color: "#FF0000",
            fillColor: "#FF0000",
            fillOpacity: 0.1,
            weight: 2,
          }).addTo(mymap);

          // เพิ่มมาร์กเกอร์กล้อง
          var camera2Marker = L.marker(camera2Position, {
            icon: cameraIcon,
          }).addTo(mymap);

          camera2Marker.bindPopup(`
            <div class="popup-content">
              <h3>กล้อง CCTV #2</h3>
              <p>ละติจูด: ${camera2Position[0]}</p>
              <p>ลองจิจูด: ${camera2Position[1]}</p>
              <p>รัศมีการตรวจจับ: 1 กิโลเมตร</p>
              <p>พื้นที่เฝ้าระวัง: 500 เมตร</p>
              <p>สถานะ: ออนไลน์</p>
              <button class="modern-button" onclick="viewCameraFeed(2)">ดูภาพจากกล้อง</button>
            </div>
          `);

          // เพิ่ม tooltip สำหรับวงกลม
          cameraRadiusCircle.bindTooltip("รัศมีการตรวจจับ 1 กม.", {
            permanent: false,
            direction: "center",
          });

          innerCameraCircle.bindTooltip("พื้นที่เฝ้าระวัง 500 ม.", {
            permanent: false,
            direction: "center",
          });

          // เพิ่ม Geocoder สำหรับค้นหาสถานที่
          const geocoder = L.Control.Geocoder.nominatim({
            geocodingQueryParams: {
              countrycodes: "th",
              "accept-language": "th",
            },
          });

          const searchControl = new L.Control.Geocoder({
            geocoder: geocoder,
            position: "topleft",
            placeholder: "ค้นหาสถานที่...",
            defaultMarkGeocode: false,
          })
            .on("markgeocode", function (e) {
              handleLocationSelect(e.geocode.center);
            })
            .addTo(mymap);

          // เพิ่ม event listener สำหรับการคลิกบนแผนที่
          mymap.on("click", function (e) {
            handleLocationSelect(e.latlng);
          });
        }

        // ฟังก์ชันสำหรับดึงข้อมูลการตรวจจับช้างจาก API
        async function fetchElephantData() {
          try {
            showLoading();
            const response = await fetch(
              "https://aprlabtop.com/elephant_api/get_detections.php"
            );
            const data = await response.json();

            // ตรวจสอบสถานะของการดึงข้อมูล
            if (data.status !== "success") {
              throw new Error(data.message || "ไม่สามารถดึงข้อมูลได้");
            }

            // ตรวจสอบว่า data.data เป็น array และมีข้อมูล
            if (!Array.isArray(data.data) || data.data.length === 0) {
              throw new Error("ไม่มีข้อมูลการตรวจจับช้าง");
            }

            // ดึงข้อมูลการตรวจจับล่าสุด
            const latestDetection = data.data[0];
            handleNewDetection(latestDetection);
            updateMap(latestDetection);

            // อัปเดตจำนวนจุดที่พบช้าง
            const validMarkers = data.data.filter(
              (elephant) => elephant.elephant === true
            ).length;
            document.getElementById(
              "nearbyCount"
            ).textContent = `${validMarkers} จุด`;

            // *** คอมเมนต์การแจ้งเตือนที่นี่ ***
            // showNotification(
            //   `พบช้างป่าในพื้นที่ทั้งหมด ${validMarkers} จุด`,
            //   "success"
            // );

            // อัปเดตสถานะการตรวจจับช้าง
            const elephantStatus = latestDetection.elephant
              ? "เจอช้าง"
              : "ไม่เจอช้าง";
            const elephantStatusValue = latestDetection.elephant ? 1 : 0;
            document.getElementById("elephantStatus").textContent =
              elephantStatus;
            document
              .getElementById("elephantStatus")
              .setAttribute("data-value", elephantStatusValue);
          } catch (error) {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูล:", error);
            showNotification(
              "ไม่สามารถดึงข้อมูลช้างป่าได้ กรุณาลองใหม่อีกครั้ง",
              "error"
            );
          } finally {
            hideLoading();
          }
        }

        // ฟังก์ชันสำหรับจัดการการตรวจจับใหม่
        function handleNewDetection(detection) {
          if (detection.elephant) {
            console.log("พบช้างป่า");
            showNotification("ตรวจพบช้างป่าในพื้นที่!", "warning");
          }
        }

        // ฟังก์ชันสำหรับอัปเดตแผนที่ด้วยการตรวจจับใหม่
        function updateMap(detection) {
          // ลบมาร์กเกอร์ช้างเก่าถ้ามี
          if (window.elephantMarkers) {
            window.elephantMarkers.forEach((marker) => mymap.removeLayer(marker));
          }

          window.elephantMarkers = [];

          // สร้างมาร์กเกอร์ช้างจากข้อมูลที่ได้รับ
          const lat = parseFloat(detection.lat_ele);
          const lng = parseFloat(detection.long_ele);

          // สร้างมาร์กเกอร์ช้างตัวแรก
          if (!isNaN(lat) && !isNaN(lng)) {
            createElephantMarker(lat, lng, detection);
          }

          // สร้างมาร์กเกอร์ช้างตัวที่สอง (สมมติตำแหน่งใกล้เคียง)
          const lat2 = lat + 0.0002; // เพิ่มละติจูดเล็กน้อย (ประมาณ 200 เมตร)
          const lng2 = lng + 0.0002; // เพิ่มลองจิจูดเล็กน้อย
          createElephantMarker(lat2, lng2, {
            ...detection,
            id: detection.id + "_2",
            timestamp: new Date().toISOString(),
          });
        }

        function navigateToCamera() {
          const cameraPosition = [14.22512, 101.40544]; // พิกัดกล้อง CCTV
          mymap.setView(cameraPosition, 15);
          showNotification("กำลังนำทางไปยังตำแหน่งกล้อง CCTV", "info");
        }
        // ฟังก์ชันสำหรับสร้างมาร์กเกอร์ช้าง
        function createElephantMarker(lat, lng, data) {
          const marker = L.marker([lat, lng], { icon: elephantIcon }).addTo(
            mymap
          );
          const timestamp = new Date(data.timestamp).toLocaleString("th-TH", {
            year: "numeric",
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
          });

          const popupContent = `
          <div class="popup-content">
              <h3>ช้างป่าที่ตรวจพบ</h3>
              <p>รหัสการตรวจจับ: ${data.id || "ไม่ระบุ"}</p>
              <p>วันและเวลาที่พบ: ${timestamp}</p>
              <p>พิกัด: ${lat}, ${lng}</p>
              <button onclick="createRoute([${lat}, ${lng}])" class="modern-button">นำทางไปยังจุดที่พบ</button>
          </div>
      `;

          marker.bindPopup(popupContent);
          window.elephantMarkers.push(marker);
        }

        // ฟังก์ชันสำหรับตรวจสอบข้อมูลใหม่จาก API
        async function checkNewData() {
          try {
            const response = await fetch(
              "https://aprlabtop.com/elephant_api/get_detections.php"
            );
            if (!response.ok) {
              throw new Error(
                "Network response was not ok " + response.statusText
              );
            }
            const data = await response.json();

            if (
              data.status !== "success" ||
              !Array.isArray(data.data) ||
              data.data.length === 0
            ) {
              return;
            }

            const latestDetection = data.data[0];
            if (
              latestDetection.timestamp &&
              new Date(latestDetection.timestamp).getTime() > lastDetectionTime
            ) {
              lastDetectionTime = new Date(latestDetection.timestamp).getTime();
              handleNewDetection(latestDetection);
              updateMap(latestDetection);

              const validMarkers = data.data.filter(
                (elephant) => elephant.elephant === true
              ).length;
              document.getElementById(
                "nearbyCount"
              ).textContent = `${validMarkers} จุด`;

              const elephantStatus = latestDetection.elephant
                ? "เจอช้าง"
                : "ไม่เจอช้าง";
              document.getElementById("elephantStatus").textContent =
                elephantStatus;
            }
          } catch (error) {
            console.error("Error fetching data:", error);
          }
        }

        // ฟังก์ชันสำหรับจัดการการตรวจจับใหม่
        function handleNewDetection(detection) {
          if (detection.elephant) {
            console.log("พบช้างป่า");
            showNotification("ตรวจพบช้างป่าในพื้นที่!", "warning");
          }
        }

        // ฟังก์ชันสำหรับค้นหาตำแหน่งผู้ใช้
        function getLocation() {
          showLoading();
          if (navigator.geolocation) {
            if (watchId) {
              navigator.geolocation.clearWatch(watchId);
            }

            isFirstPosition = true;

            watchId = navigator.geolocation.watchPosition(
              showPosition,
              showError,
              {
                enableHighAccuracy: true,
                timeout: 3000,
                maximumAge: 0,
              }
            );
            addStopTrackingButton();
          } else {
            hideLoading();
            showNotification("เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง", "error");
          }
        }

        // ฟังก์ชันสำหรับแสดงตำแหน่งผู้ใช้บนแผนที่
        function showPosition(position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          if (currentLocationMarker) {
            mymap.removeLayer(currentLocationMarker);
          }

          if (popupUpdateInterval) {
            clearInterval(popupUpdateInterval);
          }

          currentLocationMarker = L.marker([lat, lng], {
            icon: currentLocationIcon,
          }).addTo(mymap);

          function updatePopupTime() {
            if (currentLocationMarker && currentLocationMarker.getPopup()) {
              currentLocationMarker.setPopupContent(`
                <div class="popup-content">
                  <h3>ตำแหน่งของคุณ</h3>
                  <p>ละติจูด: ${lat.toFixed(5)}</p>
                  <p>ลองจิจูด: ${lng.toFixed(5)}</p>
                  <p>อัพเดทล่าสุด: ${new Date().toLocaleTimeString("th-TH")}</p>
                </div>
              `);
            }
          }

          currentLocationMarker.bindPopup(`
          <div class="popup-content">
              <h3>ตำแหน่งของคุณ</h3>
              <p>ละติจูด: ${lat.toFixed(5)}</p>
              <p>ลองจิจูด: ${lng.toFixed(5)}</p>
              <p>อัพเดทล่าสุด: ${new Date().toLocaleTimeString("th-TH")}</p>
          </div>
      `);

          if (isFirstPosition) {
            currentLocationMarker.openPopup();
            isFirstPosition = false;
            mymap.setView([lat, lng], 15);
          }

          popupUpdateInterval = setInterval(updatePopupTime, 1000);
          hideLoading();
          updateStats([lat, lng]);
          // ลบ findNearbyPlaces([lat, lng]); ออกเพื่อไม่ให้เรียกตรวจสอบช้างทุกครั้งที่อัพเดตตำแหน่ง
        }

        // ฟังก์ชันสำหรับแสดงข้อผิดพลาดในการดึงตำแหน่ง
        function showError(error) {
          hideLoading();
          let message = "";
          switch (error.code) {
            case error.PERMISSION_DENIED:
              message = "ผู้ใช้ปฏิเสธการขอตำแหน่ง";
              break;
            case error.POSITION_UNAVAILABLE:
              message = "ไม่สามารถรับข้อมูลตำแหน่งได้";
              break;
            case error.TIMEOUT:
              message = "หมดเวลารอการตอบกลับ";
              break;
            case error.UNKNOWN_ERROR:
              message = "เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ";
              break;
          }
          showNotification(message, "error");
        }

        // ฟังก์ชันสำหรับสร้างเส้นทาง
        function createRoute(end) {
          if (!currentLocationMarker) {
            showNotification("กรุณาระบุตำแหน่งปัจจุบันก่อน", "warning");
            return;
          }

          const start = currentLocationMarker.getLatLng();

          if (currentRoute) {
            mymap.removeControl(currentRoute);
          }

          currentRoute = L.Routing.control({
            waypoints: [L.latLng(start.lat, start.lng), L.latLng(end[0], end[1])],
            router: L.Routing.osrmv1({
              serviceUrl: "https://router.project-osrm.org/route/v1",
              profile: "driving",
            }),
            lineOptions: {
              styles: [
                {
                  color: isDarkMode ? "#60A5FA" : "#3B82F6",
                  opacity: 0.8,
                  weight: 6,
                },
              ],
            },
            showAlternatives: true,
            altLineOptions: {
              styles: [{ color: "#A5B4FC", opacity: 0.6, weight: 4 }],
            },
            fitSelectedRoutes: true,
            routeWhileDragging: true,
          }).addTo(mymap);

          currentRoute.on("routesfound", function (e) {
            const routes = e.routes;
            const summary = routes[0].summary;
            updateRouteStats(summary);
          });

          document.getElementById("clearRouteBtn").style.display = "inline-flex";
        }

        // ฟังก์ชันสำหรับล้างเส้นทาง
        function clearRoute() {
          if (currentRoute) {
            mymap.removeControl(currentRoute);
            currentRoute = null;
            document.getElementById("clearRouteBtn").style.display = "none";
            showNotification("ล้างเส้นทางการนำทางแล้ว", "info");
          }
        }

        // ฟังก์ชันอัพเดทสถิติ
        function updateStats(coords) {
          if (currentLocationMarker) {
            const currentLatLng = currentLocationMarker.getLatLng();
            const distance = mymap.distance(
              currentLatLng,
              L.latLng(coords[0], coords[1])
            );
            document.getElementById("totalDistance").textContent =
              (distance / 1000).toFixed(2) + " กม.";
            const timeInMinutes = Math.round((distance / 675 / 50) * 60);
            document.getElementById("travelTime").textContent =
              timeInMinutes + " นาที";
          }
        }

        // ฟังก์ชันอัพเดทสถิติเส้นทาง
        function updateRouteStats(summary) {
          document.getElementById("totalDistance").textContent =
            (summary.totalDistance / 1000).toFixed(2) + " กม.";
          document.getElementById("travelTime").textContent =
            Math.round(summary.totalTime / 60) + " นาที";
        }

        // ฟังก์ชันค้นหาสถานที่ใกล้เคียง
        function findNearbyPlaces(coords) {
          fetchElephantData();
        }

        // ฟังก์ชันแสดงสถานที่ใกล้เคียง
        function showNearbyPlaces() {
          if (!currentLocationMarker) {
            showNotification("กรุณาระบุตำแหน่งปัจจุบันก่อน", "warning");
            return;
          }
          showNotification("กำลังค้นหาช้างป่าในบริเวณใกล้เคียง...", "info");
          findNearbyPlaces([
            currentLocationMarker.getLatLng().lat,
            currentLocationMarker.getLatLng().lng,
          ]);
        }

        // ฟังก์ชันสำหรับจัดการเมื่อเลือกตำแหน่ง
        async function handleLocationSelect(latlng) {
          try {
            showLoading();

            // ใช้ Nominatim สำหรับ reverse geocoding
            const response = await fetch(
              `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&accept-language=th`
            );

            if (!response.ok) {
              throw new Error("ไม่สามารถดึงข้อมูลสถานที่ได้");
            }

            const data = await response.json();
            const locationName = data.display_name || "ไม่พบชื่อสถานที่";

            // ลบ marker เก่า (ถ้ามี)
            if (lastMarker) {
              mymap.removeLayer(lastMarker);
            }

            // สร้าง marker ใหม่
            lastMarker = L.marker([latlng.lat, latlng.lng]).addTo(mymap);

            // สร้าง popup content
            const popupContent = `
        <div class="popup-content">
          <h3>ตำแหน่งที่เลือก</h3>
          <p><strong>สถานที่:</strong> ${locationName}</p>
          <p><strong>พิกัด:</strong> ${latlng.lat.toFixed(
            5
          )}, ${latlng.lng.toFixed(5)}</p>
          <button onclick="createRoute([${latlng.lat}, ${
              latlng.lng
            }])" class="modern-button">นำทางไปยังจุดนี้</button>
        </div>
      `;

            // แสดง popup
            lastMarker.bindPopup(popupContent).openPopup();

            // อัพเดทสถิติ
            updateStats([latlng.lat, latlng.lng]);

            // แสดงการแจ้งเตือน
            showNotification(`เลือกตำแหน่ง: ${locationName}`, "info");
          } catch (error) {
            console.error("Error:", error);
            showNotification("ไม่สามารถดึงข้อมูลสถานที่ได้", "error");
          } finally {
            hideLoading();
          }
        }

        // ฟังก์ชันหยุดการติดตาม
        function stopTracking() {
          if (watchId) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;

            if (popupUpdateInterval) {
              clearInterval(popupUpdateInterval);
              popupUpdateInterval = null;
            }

            showNotification("หยุดการติดตามตำแหน่งแล้ว", "info");
            removeStopTrackingButton();
          }
        }

        // ฟังก์ชันเพิ่มปุ่มหยุดการติดตาม
        function addStopTrackingButton() {
          removeStopTrackingButton();

          const stopButton = document.createElement("button");
          stopButton.className = "modern-button";
          stopButton.id = "stopTrackingBtn";
          stopButton.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
              <path d="M5 6.25a1.25 1.25 0 1 1 2.5 0v3.5a1.25 1.25 0 1 1-2.5 0v-3.5zm3.5 0a1.25 1.25 0 1 1 2.5 0v3.5a1.25 1.25 0 1 1-2.5 0v-3.5z"/>
            </svg>
            หยุดการติดตาม
          `;
          stopButton.onclick = stopTracking;

          document.querySelector(".controls").appendChild(stopButton);
        }

        // ฟังก์ชันลบปุ่มหยุดการติดตาม
        function removeStopTrackingButton() {
          const existingButton = document.getElementById("stopTrackingBtn");
          if (existingButton) {
            existingButton.remove();
          }
        }

        // ฟังก์ชันสลับธีม
        function toggleTheme() {
          isDarkMode = !isDarkMode;
          document.body.classList.toggle("dark-mode");
          document.getElementById("themeIcon").textContent = isDarkMode
            ? "☀️"
            : "🌙";

          // ลบส่วนการเปลี่ยน tile layer ออก
          // if (currentTileLayer) {
          //   mymap.removeLayer(currentTileLayer);
          // }
          // currentTileLayer = L.tileLayer(isDarkMode ? darkTileLayer : lightTileLayer, {
          //   maxZoom: 19,
          //   attribution: "© OpenStreetMap",
          // }).addTo(mymap);
        }

        // ฟังก์ชันแสดง loading
        function showLoading() {
          document.querySelector(".loading").style.display = "block";
        }

        // ฟังก์ชันซ่อน loading
        function hideLoading() {
          document.querySelector(".loading").style.display = "none";
        }

        // ฟังก์ชันแสดงการแจ้งเตือน
        function showNotification(message, type = "info") {
          const notification = document.createElement("div");
          notification.className = `notification ${type}`;
          notification.textContent = message;
          document.body.appendChild(notification);

          setTimeout(() => {
            notification.classList.add("hide");
            setTimeout(() => {
              notification.remove();
            }, 300);
          }, 3000);
        }

        // เริ่มการทำงานเมื่อโหลดหน้าเว็บ
        document.addEventListener("DOMContentLoaded", () => {
          initializeMap();
          fetchElephantData(); // ดึงข้อมูลครั้งแรก
          // เรียก checkNewData ทุก ๆ 1 นาที
          setInterval(checkNewData, 20000);
        });

        // ตั้งเวลาอัพเดทข้อมูลอัตโนมัติทุก 5 นาที
        setInterval(fetchElephantData, 60000);
      </script>
    </body>
  </html>
