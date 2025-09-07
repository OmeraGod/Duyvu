<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bản đồ tính khoảng cách</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .controls {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .info-panel {
            padding: 20px;
            background: white;
        }
        .location-info, .distance-info {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .location-info h3, .distance-info h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.2em;
        }
        .coordinates {
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #666;
            margin: 5px 0;
        }
        .distance {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
            text-align: center;
        }
        #map {
            height: 500px;
            margin: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }
        .status.loading {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .instruction {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 10px 0;
        }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header h1 { font-size: 2em; }
            .button-group { flex-direction: column; }
            #map { height: 400px; margin: 10px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🗺️ Bản đồ tính khoảng cách</h1>
        <p>Nhấn vào bản đồ để tính khoảng cách từ vị trí hiện tại</p>
    </div>

    <div class="controls">
        <div class="button-group">
            <button class="btn" id="getCurrentLocation">📍 Lấy vị trí hiện tại</button>
            <button class="btn" id="clearMarkers">🗑️ Xóa tất cả điểm</button>
        </div>
    </div>

    <div class="info-panel">
        <div id="status" class="status" style="display: none;"></div>

        <div class="location-info">
            <h3>📍 Vị trí hiện tại</h3>
            <div id="currentLocation">Chưa lấy được vị trí</div>
            <div id="currentCoords" class="coordinates"></div>
        </div>

        <div class="distance-info" style="display: none;" id="distancePanel">
            <h3>📏 Khoảng cách</h3>
            <div class="instruction">Nhấn vào bản đồ để tính khoảng cách</div>
            <div id="distanceResult" class="distance"></div>
        </div>
    </div>

    <div id="map"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    let map;
    let currentLocationMarker;
    let currentPosition = null;
    let clickedMarkers = [];

    // Khởi tạo bản đồ
    function initMap() {
        map = L.map('map').setView([10.7769, 106.7009], 13); // Mặc định ở TP.HCM

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Xử lý sự kiện click trên bản đồ
        map.on('click', function(e) {
            if (currentPosition) {
                addClickedMarker(e.latlng);
                calculateDistance(e.latlng);
            } else {
                showStatus('Vui lòng lấy vị trí hiện tại trước!', 'error');
            }
        });
    }

    // Hiển thị trạng thái
    function showStatus(message, type) {
        const statusEl = document.getElementById('status');
        statusEl.textContent = message;
        statusEl.className = `status ${type}`;
        statusEl.style.display = 'block';

        if (type !== 'loading') {
            setTimeout(() => {
                statusEl.style.display = 'none';
            }, 3000);
        }
    }

    // Lấy vị trí hiện tại
    function getCurrentLocation() {
        showStatus('Đang lấy vị trí hiện tại...', 'loading');

        if (!navigator.geolocation) {
            showStatus('Trình duyệt không hỗ trợ geolocation!', 'error');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            async function(position) {
                currentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                // Xóa marker cũ nếu có
                if (currentLocationMarker) {
                    map.removeLayer(currentLocationMarker);
                }

                // Thêm marker vị trí hiện tại
                currentLocationMarker = L.marker([currentPosition.lat, currentPosition.lng], {
                    icon: L.divIcon({
                        className: 'current-location-marker',
                        html: '📍',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    })
                }).addTo(map);

                // Di chuyển bản đồ đến vị trí hiện tại
                map.setView([currentPosition.lat, currentPosition.lng], 15);

                // Lấy địa chỉ từ tọa độ
                const address = await getAddressFromCoords(currentPosition.lat, currentPosition.lng);

                document.getElementById('currentLocation').innerHTML = `<strong>${address}</strong>`;
                document.getElementById('currentCoords').textContent =
                    `Tọa độ: ${currentPosition.lat.toFixed(6)}, ${currentPosition.lng.toFixed(6)}`;

                document.getElementById('distancePanel').style.display = 'block';
                showStatus('Đã lấy vị trí hiện tại thành công!', 'success');
            },
            function(error) {
                let errorMessage = 'Không thể lấy vị trí: ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Người dùng từ chối chia sẻ vị trí';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Thông tin vị trí không có sẵn';
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'Hết thời gian chờ';
                        break;
                    default:
                        errorMessage += 'Lỗi không xác định';
                }
                showStatus(errorMessage, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    // Lấy địa chỉ từ tọa độ
    async function getAddressFromCoords(lat, lng) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=vi`);
            const data = await response.json();
            return data.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        } catch (error) {
            return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
    }

    // Thêm marker cho điểm được click
    function addClickedMarker(latlng) {
        const marker = L.marker([latlng.lat, latlng.lng], {
            icon: L.divIcon({
                className: 'clicked-marker',
                html: '🎯',
                iconSize: [25, 25],
                iconAnchor: [12, 12]
            })
        }).addTo(map);

        clickedMarkers.push(marker);
    }

    // Tính khoảng cách giữa hai điểm
    function calculateDistance(clickedLatLng) {
        if (!currentPosition) return;

        const R = 6371; // Bán kính Trái Đất (km)
        const dLat = (clickedLatLng.lat - currentPosition.lat) * Math.PI / 180;
        const dLng = (clickedLatLng.lng - currentPosition.lng) * Math.PI / 180;
        const a =
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(currentPosition.lat * Math.PI / 180) * Math.cos(clickedLatLng.lat * Math.PI / 180) *
            Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c;

        // Hiển thị kết quả
        const distanceText = distance < 1
            ? `${Math.round(distance * 1000)} mét`
            : `${distance.toFixed(2)} km`;

        document.getElementById('distanceResult').innerHTML = `
                <div>Khoảng cách: <span style="color: #e74c3c;">${distanceText}</span></div>
                <div style="font-size: 0.8em; color: #666; margin-top: 5px;">
                    Đến điểm: ${clickedLatLng.lat.toFixed(6)}, ${clickedLatLng.lng.toFixed(6)}
                </div>
            `;

        // Vẽ đường nối
        L.polyline([
            [currentPosition.lat, currentPosition.lng],
            [clickedLatLng.lat, clickedLatLng.lng]
        ], {
            color: '#e74c3c',
            weight: 3,
            opacity: 0.7,
            dashArray: '10, 10'
        }).addTo(map);
    }

    // Xóa tất cả markers và đường nối
    function clearMarkers() {
        clickedMarkers.forEach(marker => map.removeLayer(marker));
        clickedMarkers = [];

        map.eachLayer(function(layer) {
            if (layer instanceof L.Polyline) {
                map.removeLayer(layer);
            }
        });

        document.getElementById('distanceResult').innerHTML = '';
        document.querySelector('.instruction').style.display = 'block';
    }

    // Event listeners
    document.getElementById('getCurrentLocation').addEventListener('click', getCurrentLocation);
    document.getElementById('clearMarkers').addEventListener('click', clearMarkers);

    // Khởi tạo bản đồ khi trang load
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
    });
</script>
</body>
</html>